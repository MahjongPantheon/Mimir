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

abstract class Ruleset
{
    protected static $_instance;

    /**
     * @return self
     */
    public static function instance()
    {
        if (!static::$_instance) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    protected static $_title;
    protected static $_ruleset;
    abstract public function calcRating($currentRating, $place, $points);

    public function title()
    {
        return static::$_title;
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

    public function uma()
    {
        return static::$_ruleset['uma'];
    }

    public function oka()
    {
        return static::$_ruleset['oka'];
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

    public function withOpenRiichi()
    {
        return static::$_ruleset['withOpenRiichi'];
    }

    public function withNagashiMangan()
    {
        return static::$_ruleset['withNagashiMangan'];
    }
}
