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
namespace Riichi;
require_once __DIR__ . '/../../src/Ruleset.php';
require_once __DIR__ . '/../../src/helpers/YakuMap.php';

class RulesetEma extends Ruleset
{
    public static $_title = 'ema';
    protected static $_ruleset = [
        'tenboDivider'          => 1,
        'ratingDivider'         => 1,
        'startRating'           => 0,
        'oka'                   => 0,
        'startPoints'           => 30000,
        'riichiGoesToWinner'    => true,
        'extraChomboPayments'   => false,
        'chomboPenalty'         => 20000,
        'withAtamahane'         => false,
        'withAbortives'         => false,
        'withKuitan'            => true,
        'withKazoe'             => false,
        'withButtobi'           => false,
        'withMultiYakumans'     => false,
        'withNagashiMangan'     => false,
        'withKiriageMangan'     => false,
        'tonpuusen'             => false,
        'autoRegisterUsers'     => false,
        'gameExpirationTime'    => false,
        'withLeadingDealerGameOver' => false
    ];

    public function allowedYaku()
    {
        return YakuMap::listExcept([
            Y_RENHOU,
            Y_OPENRIICHI
        ]);
    }

    /**
     * EMA uses equalized uma in case of equal scores
     * @param array $scores
     * @return array
     */
    public function uma($scores = [])
    {
        // hint: stricter conditions should go first

        rsort($scores);
        $uniqScores = array_unique($scores);
        if (count($uniqScores) === 4) {
            return [1 => 15000, 5000, -5000, -15000];
        }

        if (count($uniqScores) === 1) {
            return [1 => 0, 0, 0, 0];
        }

        if ($scores[0] == $scores[1] && $scores[1] == $scores[2]) {
            return [1 => 5000, 5000, 5000, -15000];
        }

        if ($scores[1] == $scores[2] && $scores[2] == $scores[3]) {
            return [1 => 15000, -5000, -5000, -5000];
        }

        if ($scores[0] == $scores[1] && $scores[2] == $scores[3]) {
            return [1 => 10000, 10000, -10000, -10000];
        }

        if ($scores[0] == $scores[1]) {
            return [1 => 10000, 10000, -5000, -15000];
        }

        if ($scores[1] == $scores[2]) {
            return [1 => 15000, 0, 0, -15000];
        }

        if ($scores[2] == $scores[3]) {
            return [1 => 15000, 5000, -10000, -10000];
        }
    }
}