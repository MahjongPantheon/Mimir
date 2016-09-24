<?php
/*  Riichi mahjong API game server
 *  Copyright (C) 2016  heilage and others
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
namespace Riichi;

class UsersHelper {
    /**
     * Check if ids are valid user ids
     *
     * @param Db $db
     * @param $playersIdList
     * @return string InvalidityReason
     */
    public static function valid(Db $db, $playersIdList) {
        if (count($playersIdList) !== 4) {
            return "Invalid players count";
        }

        $countInDb = $db->table('user')->whereIn('id', $playersIdList)->count();
        if ($countInDb !== 4) {
            return "Some of players are missing in DB, check ids";
        }

        return null;
    }
}