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
if (file_exists(__DIR__ . '/db.local.php')) {
    return require __DIR__ . '/db.local.php';
}

trigger_error('Notice: using default DB settings. '
    . 'It\'s fine on developer machine, but wrong on prod server. '
    . 'You might want to create config/db.local.php file with production settings.');

return [
    'connection_string' => 'sqlite:' . __DIR__ . '/../data/db.sqlite',
    'credentials' => [
        'username' => '',
        'password' => ''
    ]
];