<?php

/*
 * This file is part of gradba/flarum-s3-avatars.
 *
 * For the full copyright and license information, see the LICENSE file.
 */

namespace Gradba\S3Avatars\Listener;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Points the avatars disk at this driver on enable, and releases it on disable.
 *
 * Only the `flarum-avatars` disk is touched; every other disk keeps whatever it
 * was using. Flarum resolves an unknown driver name back to `local`, so even a
 * stale setting cannot break avatars.
 */
class ToggleDiskDriver
{
    public const DISK = 'flarum-avatars';
    public const DRIVER = 'gradba-s3-avatars';

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function whenEnabled(Enabled $event): void
    {
        if ($event->extension->getId() !== 'gradba-s3-avatars') {
            return;
        }

        $this->settings->set('disk_driver.'.self::DISK, self::DRIVER);
    }

    public function whenDisabled(Disabled $event): void
    {
        if ($event->extension->getId() !== 'gradba-s3-avatars') {
            return;
        }

        // Hand the disk back to local storage explicitly.
        $this->settings->set('disk_driver.'.self::DISK, 'local');
    }
}
