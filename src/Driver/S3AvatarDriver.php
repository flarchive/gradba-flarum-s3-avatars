<?php

/*
 * This file is part of gradba/flarum-s3-avatars.
 *
 * For the full copyright and license information, see the LICENSE file.
 */

namespace Gradba\S3Avatars\Driver;

use Flarum\Filesystem\DriverInterface;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

/**
 * Backs a single Flarum disk with an S3-compatible bucket.
 *
 * Registered as a *named* driver, so it only applies to disks explicitly pointed
 * at it via `disk_driver.<disk>`. It never replaces the built-in `local` driver,
 * which is what keeps compiled assets (forum.js/forum.css) on local disk where
 * they belong — they are build artifacts, not user data.
 */
class S3AvatarDriver implements DriverInterface
{
    protected FilesystemManager $manager;

    public function __construct(
        protected Paths $paths,
        protected S3Config $config,
        protected LoggerInterface $log,
        Container $container
    ) {
        $this->manager = new FilesystemManager($container);
    }

    public function build(
        string $diskName,
        SettingsRepositoryInterface $settings,
        Config $config,
        array $localConfig
    ): Cloud {
        $s3 = $this->config->get();

        if (empty($s3)) {
            // Incomplete configuration must degrade to the local disk rather than
            // break avatars site-wide.
            $this->log->warning("[gradba-s3-avatars] incomplete S3 config; disk '$diskName' stays local");

            // @phpstan-ignore-next-line — createLocalDriver returns a Cloud in practice
            return $this->manager->createLocalDriver($localConfig);
        }

        // Mirror the local layout inside the bucket: the local root
        // "<public>/assets/avatars" becomes the key prefix "assets/avatars".
        $root = trim(str_replace($this->paths->public, '', (string) Arr::get($localConfig, 'root')), '/');

        return $this->manager->createS3Driver(array_merge($s3, ['root' => $root]));
    }
}
