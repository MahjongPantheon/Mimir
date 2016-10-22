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

abstract class Ruleset
{
    private static $_instances = [];

    /**
     * @param $title
     * @return Ruleset
     */
    public static function instance($title)
    {
        if (empty(self::$_instances[$title])) {
            require_once __DIR__ . '/../config/rulesets/' . $title . '.php';
            /** @var Ruleset $className */
            $className = 'Riichi\Ruleset' . ucfirst($title);
            self::$_instances[$title] = new $className();
        }

        return static::$_instances[$title];
    }

    protected static $_title;
    protected static $_ruleset;

    public function title()
    {
        return static::$_title;
    }

    public function allowedYaku()
    {
        return static::$_ruleset['allowedYaku'];
    }

    public function tenboDivider()
    {
        return static::$_ruleset['tenboDivider'];
    }

    public function ratingDivider()
    {
        return static::$_ruleset['ratingDivider'];
    }

    public function tonpuusen()
    {
        return static::$_ruleset['tonpuusen'];
    }

    public function startRating()
    {
        return static::$_ruleset['startRating'];
    }

    public function uma($scores = [])
    {
        return static::$_ruleset['uma'];
    }

    public function oka($place)
    {
        if ($place === 1) {
            return static::$_ruleset['oka'];
        } else {
            return -(static::$_ruleset['oka'] / 4);
        }
    }

    public function startPoints()
    {
        return static::$_ruleset['startPoints'];
    }

    public function riichiGoesToWinner()
    {
        return static::$_ruleset['riichiGoesToWinner'];
    }

    public function extraChomboPayments()
    {
        return static::$_ruleset['extraChomboPayments'];
    }

    public function chomboPenalty()
    {
        return static::$_ruleset['chomboPenalty'];
    }

    public function withAtamahane()
    {
        return static::$_ruleset['withAtamahane'];
    }

    public function withAbortives()
    {
        return static::$_ruleset['withAbortives'];
    }

    public function withKuitan()
    {
        return static::$_ruleset['withKuitan'];
    }

    public function withKazoe()
    {
        return static::$_ruleset['withKazoe'];
    }

    public function withButtobi()
    {
        return static::$_ruleset['withButtobi'];
    }

    public function withLeadingDealerGameOver()
    {
        return static::$_ruleset['withLeadingDealerGameOver'];
    }

    public function withMultiYakumans()
    {
        return static::$_ruleset['withMultiYakumans'];
    }

    public function withNagashiMangan()
    {
        return static::$_ruleset['withNagashiMangan'];
    }

    public function withKiriageMangan()
    {
        return static::$_ruleset['withKiriageMangan'];
    }
}
