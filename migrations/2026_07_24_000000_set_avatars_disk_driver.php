<?php

/*
 * This file is part of gradba/flarum-s3-avatars.
 *
 * Points the avatars disk at this extension's driver. A migration (rather than
 * an Enabled listener) because an extension's own listeners are not registered
 * in the process that enables it.
 *
 * The setting is intentionally left in place when the extension is disabled:
 * Flarum resolves an unregistered driver name back to `local`, so avatars fall
 * back automatically, and re-enabling picks straight back up.
 */

use Flarum\Database\Migration;

return Migration::addSettings([
    'disk_driver.flarum-avatars' => 'gradba-s3-avatars',
]);
