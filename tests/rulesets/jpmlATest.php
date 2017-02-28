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
require_once __DIR__ . '/../../config/rulesets/jpmlA.php';

class JPMLARulesetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RulesetJpmlA
     */
    protected $_ruleset;

    public function setUp()
    {
        $this->_ruleset = new RulesetJpmlA();
    }

    public function testUma()
    {
        $this->assertEquals(30000, $this->_ruleset->startPoints()); // If this changes, all below should be changed too

        $this->assertEquals(
            [1 => 120, -10, -30, -80],
            $this->_ruleset->uma([28000, 29000, 37000, 27000])
        ); // 1 leader

        $this->assertEquals(
            [1 => 80, 30, 10, -120],
            $this->_ruleset->uma([31000, 33000, 24000, 32000])
        ); // 1 loser

        $this->assertEquals(
            [1 => 80, 40, -40, -80],
            $this->_ruleset->uma([29000, 29000, 24000, 29000])
        ); // all losers

        $this->assertEquals(
            [1 => 80, 40, -40, -80],
            $this->_ruleset->uma([32000, 29000, 24000, 37000])
        ); // 2x2

        $this->assertEquals(
            [1 => 80, 40, -40, -80],
            $this->_ruleset->uma([30000, 30000, 30000, 30000])
        ); // all initial score
    }
}