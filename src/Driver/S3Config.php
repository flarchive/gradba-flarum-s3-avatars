<?php

/*
 * This file is part of gradba/flarum-s3-avatars.
 *
 * For the full copyright and license information, see the LICENSE file.
 */

namespace Gradba\S3Avatars\Driver;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Resolves the S3/R2 connection settings, in precedence order:
 *
 *   1. Environment variables (GRADBA_AVATARS_S3_*) — for containerised deploys,
 *      where credentials come from a Kubernetes Secret rather than the database.
 *   2. This extension's own admin settings.
 *   3. FoF Upload's settings, if "inherit" is on — most installs already have a
 *      working bucket configured there and gain nothing from a second copy.
 */
class S3Config
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /** @return array<string, mixed> Empty when incomplete — callers fall back to local disk. */
    public function get(): array
    {
        $c = $this->fromEnv() ?: $this->fromSettings();

        // Bucket, credentials and a public URL are all required: without the URL
        // we would emit unreachable avatar links, which is worse than staying local.
        foreach (['key', 'secret', 'region', 'bucket', 'url'] as $required) {
            if (empty($c[$required])) {
                return [];
            }
        }

        return $c;
    }

    protected function fromEnv(): ?array
    {
        $key = getenv('GRADBA_AVATARS_S3_KEY');
        $secret = getenv('GRADBA_AVATARS_S3_SECRET');
        $bucket = getenv('GRADBA_AVATARS_S3_BUCKET');

        if (!$key || !$secret || !$bucket) {
            return null;
        }

        return $this->build(
            $key,
            $secret,
            getenv('GRADBA_AVATARS_S3_REGION') ?: 'auto',
            $bucket,
            getenv('GRADBA_AVATARS_S3_URL') ?: '',
            getenv('GRADBA_AVATARS_S3_ENDPOINT') ?: null,
            filter_var(getenv('GRADBA_AVATARS_S3_PATH_STYLE'), FILTER_VALIDATE_BOOLEAN),
            getenv('GRADBA_AVATARS_S3_CACHE_CONTROL') ?: null
        );
    }

    protected function fromSettings(): array
    {
        $p = $this->settings->get('gradba-s3-avatars.inheritFromFofUpload', true)
            ? 'fof-upload'
            : 'gradba-s3-avatars';

        $url = $this->settings->get("$p.awsS3CustomUrl") ?: $this->settings->get("$p.cdnUrl");

        return $this->build(
            (string) $this->settings->get("$p.awsS3Key"),
            (string) $this->settings->get("$p.awsS3Secret"),
            (string) ($this->settings->get("$p.awsS3Region") ?: 'auto'),
            (string) $this->settings->get("$p.awsS3Bucket"),
            (string) $url,
            $this->settings->get("$p.awsS3Endpoint") ?: null,
            (bool) $this->settings->get("$p.awsS3UsePathStyleEndpoint"),
            $this->settings->get('gradba-s3-avatars.cacheControl') ?: null
        );
    }

    protected function build(
        string $key,
        string $secret,
        string $region,
        string $bucket,
        string $url,
        ?string $endpoint,
        bool $pathStyle,
        ?string $cacheControl
    ): array {
        $config = [
            'driver' => 's3',
            'key'    => $key,
            'secret' => $secret,
            'region' => $region,
            'bucket' => $bucket,
            'url'    => rtrim($url, '/'),
            'options' => [],
        ];

        if ($endpoint) {
            $config['endpoint'] = $endpoint;
        }

        // Cloudflare R2 and most S3-compatible services need path-style addressing.
        if ($pathStyle) {
            $config['use_path_style_endpoint'] = true;
        }

        // No ACL is ever sent: R2 rejects ACLs, and bucket policy governs access.
        if ($cacheControl) {
            $config['options']['CacheControl'] = 'max-age='.(int) $cacheControl;
        }

        return $config;
    }
}
