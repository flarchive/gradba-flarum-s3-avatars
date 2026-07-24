<?php

/*
 * This file is part of gradba/flarum-s3-avatars.
 *
 * For the full copyright and license information, see the LICENSE file.
 */

namespace Gradba\S3Avatars\Console;

use Flarum\Foundation\Paths;
use Gradba\S3Avatars\Driver\S3AvatarDriver;
use Gradba\S3Avatars\Driver\S3Config;
use Illuminate\Contracts\Filesystem\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copies avatars that already exist on local disk into the bucket.
 *
 * Copy-only by default: local files are left in place so the disk can be pointed
 * back at `local` at any time. Pass --delete-local once you've verified.
 */
class MigrateAvatarsCommand extends Command
{
    protected static $defaultName = 'gradba:avatars:migrate';

    public function __construct(
        protected Paths $paths,
        protected Factory $filesystem,
        protected S3Config $config
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Copy locally stored avatars into the configured S3/R2 bucket')
            ->addOption('delete-local', null, InputOption::VALUE_NONE, 'Remove each local file after a verified copy')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would happen, change nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dry = (bool) $input->getOption('dry-run');
        $delete = (bool) $input->getOption('delete-local');

        if (empty($this->config->get())) {
            $output->writeln('<error>S3 configuration is incomplete — nothing to migrate to.</error>');

            return Command::FAILURE;
        }

        $localDir = $this->paths->public.'/assets/avatars';
        if (!is_dir($localDir)) {
            $output->writeln("<comment>No local avatar directory at $localDir</comment>");

            return Command::SUCCESS;
        }

        $disk = $this->filesystem->disk('flarum-avatars');
        $files = array_values(array_filter(scandir($localDir), fn ($f) => is_file($localDir.'/'.$f)));

        $output->writeln(sprintf('%s %d local avatar(s)', $dry ? 'Would copy' : 'Copying', count($files)));

        $copied = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $name) {
            $path = $localDir.'/'.$name;
            $bytes = @file_get_contents($path);

            if ($bytes === false) {
                $output->writeln("  <error>unreadable: $name</error>");
                $failed++;
                continue;
            }

            if ($disk->exists($name) && $disk->size($name) === strlen($bytes)) {
                $output->writeln("  already present: $name");
                $skipped++;
                continue;
            }

            if ($dry) {
                $output->writeln("  would copy: $name (".strlen($bytes)." bytes)");
                $copied++;
                continue;
            }

            $disk->put($name, $bytes);

            // Verify before considering the local copy expendable.
            if (!$disk->exists($name) || $disk->size($name) !== strlen($bytes)) {
                $output->writeln("  <error>verification failed: $name</error>");
                $failed++;
                continue;
            }

            $output->writeln("  copied: $name");
            $copied++;

            if ($delete) {
                @unlink($path);
            }
        }

        $output->writeln(sprintf(
            "\n%s: %d copied, %d already present, %d failed%s",
            $dry ? 'Dry run' : 'Done',
            $copied,
            $skipped,
            $failed,
            $delete && !$dry ? ' (local copies removed)' : ''
        ));

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
