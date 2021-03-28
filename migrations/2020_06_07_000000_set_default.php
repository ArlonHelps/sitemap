<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 *
 */

use Flarum\Database\Migration;

return Migration::addSettings([
    'fof-sitemap.mode'      => 'run',
    'fof-sitemap.frequency' => 'daily',
]);
