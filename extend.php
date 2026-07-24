<?php

/*
 * This file is part of gradba/flarum-s3-avatars.
 *
 * Stores user avatars in an S3-compatible bucket (built for Cloudflare R2)
 * instead of local disk, so Flarum can run as a stateless container.
 *
 * Scope is deliberately narrow: only the `flarum-avatars` disk is relocated.
 * Compiled assets stay local because they are build artifacts that belong in
 * the image, versioned with the deploy.
 *
 * For the full copyright and license information, see the LICENSE file.
 */

use Flarum\Extend;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Gradba\S3Avatars\Console\MigrateAvatarsCommand;
use Gradba\S3Avatars\Driver\S3AvatarDriver;
use Gradba\S3Avatars\Listener\ToggleDiskDriver;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    // A *named* driver — never overrides the built-in `local` driver.
    (new Extend\Filesystem())
        ->driver(ToggleDiskDriver::DRIVER, S3AvatarDriver::class),

    (new Extend\Event())
        ->listen(Enabled::class, [ToggleDiskDriver::class, 'whenEnabled'])
        ->listen(Disabled::class, [ToggleDiskDriver::class, 'whenDisabled']),

    (new Extend\Console())
        ->command(MigrateAvatarsCommand::class),

    (new Extend\Settings())
        ->default('gradba-s3-avatars.inheritFromFofUpload', true)
        ->default('gradba-s3-avatars.cacheControl', 31536000),
];
