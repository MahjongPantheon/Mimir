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

use \Idiorm\ORM;

require_once __DIR__ . '/../exceptions/MalformedPayload.php';

class RoundsHelper
{
    /**
     * Check if round data is valid
     *
     * @param Db $db
     * @param \Idiorm\ORM $game
     * @throws MalformedPayloadException
     * @param $roundData
     */
    public static function checkRound(Db $db, ORM $game, $roundData)
    {
        self::_checkOneOf($roundData, 'outcome', ['ron', 'tsumo', 'draw', 'abort'. 'chombo']);
        self::_checkOneOf($roundData, 'round', [1, 2, 3, 4, 5, 6, 7, 8]); // TODO: wests? Also, should depend on rules
        switch ($roundData['outcome']) {
            case 'ron':
                self::_checkRon($db, $game, $roundData);
                break;
            case 'tsumo':
                self::_checkTsumo($db, $game, $roundData);
                break;
            case 'draw':
                self::_checkDraw($game, $roundData);
                break;
            case 'abort':
                self::_csvCheckZeroOrMoreOf($roundData, 'riichi', $game->get('players'));
                break;
            case 'chombo':
                self::_checkChombo($game, $roundData);
                break;
        }
    }

    protected static function _checkRon(Db $db, ORM $game, $roundData)
    {
        self::_csvCheckZeroOrMoreOf($roundData, 'riichi', $game->get('players'));
        self::_checkOneOf($roundData, 'winner_id', explode(',', $game->get('players')));
        self::_checkOneOf($roundData, 'loser_id', explode(',', $game->get('players')));
        // -1 to -5 stand for one to five yakumans
        self::_checkOneOf($roundData, 'han', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, -1, -2, -3, -4, -5]);
        // 0 for 5+ han
        self::_checkOneOf($roundData, 'fu', [20, 25, 30, 40, 50, 60, 70, 80, 90, 100, 110, 0]);
        self::_checkOneOf($roundData, 'multi_ron', [null, 2, 3]);
        self::_checkYaku($roundData['yaku'], $db);

        self::_checkOneOf($roundData, 'dora', [0, 1, 2, 3, 4]);
        self::_checkOneOf($roundData, 'uradora', [0, 1, 2, 3, 4]); // TODO: not sure if we really need these guys
        self::_checkOneOf($roundData, 'kandora', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]);
        self::_checkOneOf($roundData, 'kanuradora', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]);
    }

    protected static function _checkTsumo(Db $db, ORM $game, $roundData)
    {
        self::_csvCheckZeroOrMoreOf($roundData, 'riichi', $game->get('players'));
        self::_checkOneOf($roundData, 'winner_id', explode(',', $game->get('players')));
        // -1 to -5 stand for one to five yakumans
        self::_checkOneOf($roundData, 'han', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, -1, -2, -3, -4, -5]);
        // 0 for 5+ han
        self::_checkOneOf($roundData, 'fu', [20, 25, 30, 40, 50, 60, 70, 80, 90, 100, 110, 0]);
        self::_checkYaku($roundData['yaku'], $db);

        self::_checkOneOf($roundData, 'dora', [0, 1, 2, 3, 4]);
        self::_checkOneOf($roundData, 'uradora', [0, 1, 2, 3, 4]); // TODO: not sure if we really need these guys
        self::_checkOneOf($roundData, 'kandora', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]);
        self::_checkOneOf($roundData, 'kanuradora', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]);
    }

    protected static function _checkDraw(ORM $game, $roundData)
    {
        self::_csvCheckZeroOrMoreOf($roundData, 'riichi', $game->get('players'));
        self::_csvCheckZeroOrMoreOf($roundData, 'tempai', $game->get('players'));
    }

    protected static function _checkChombo(ORM $game, $roundData)
    {
        self::_checkOneOf($roundData, 'loser_id', explode(',', $game->get('players')));
    }

    // === Generic checkers ===

    protected static function _checkOneOf($data, $key, $values)
    {
        if (!in_array($data[$key], $values)) {
            throw new MalformedPayloadException('Field ' . $key . ' should be one of [' . implode(', ', $values) . ']');
        }
    }

    protected static function _csvCheckZeroOrMoreOf($data, $key, $csvValues)
    {
        $redundantVals = array_diff(
            explode(',', $csvValues),
            explode(',', $data[$key])
        );

        if (count($redundantVals) > 0) {
            throw new MalformedPayloadException('Field ' . $key . ' should contain zero or more of [' . $data[$key]
                . '], but also contains [' . implode(',', $redundantVals) . ']');
        }
    }

    protected static function _checkYaku($yakuList, Db $db)
    {
        // TODO
    }
}
