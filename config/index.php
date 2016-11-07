<?php
/*  Riichi mahjong API game server
 *  Copyright (C) 2016  o.klimenko aka ctizen
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Local server deployment settings
$locals = [];
if (file_exists(__DIR__ . '/local/index.php')) {
    $locals = require __DIR__ . '/local/index.php';
} else {
    trigger_error(
        'Notice: using default config & DB settings. '
        . 'It\'s fine on developer machine, but wrong on prod server. '
        . 'You might want to create config/db.local.php file with production settings.'
    );
}

return array_merge([
    'db'        => require __DIR__ . '/db.php',
    'routes'    => require __DIR__ . '/routes.php'
], $locals);