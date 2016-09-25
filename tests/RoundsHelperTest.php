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

require_once __DIR__.'/../src/helpers/Rounds.php';
require_once __DIR__ . '/util/Db.php';

class RoundsHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    protected $_log;
    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
        $this->_log = $this->getMock('Monolog\\Logger', null, ['RiichiApi']);
    }

    public function testCheckOneOf()
    {
        $checkOneOf = new \ReflectionMethod('\Riichi\RoundsHelper', '_checkOneOf');
        $checkOneOf->setAccessible(true);

        $data = ['test' => 'okval'];
        $possibleVals = ['okval3', 'okval', 'okval2'];
        $checkOneOf->invokeArgs(null, [$data, 'test', $possibleVals]);
        $this->assertTrue(true); // no exception == ok
    }

    /**
     * @expectedException \Riichi\MalformedPayloadException
     */
    public function testCheckOneOfFail()
    {
        $checkOneOf = new \ReflectionMethod('\Riichi\RoundsHelper', '_checkOneOf');
        $checkOneOf->setAccessible(true);

        $data = ['test' => 'notokval'];
        $possibleVals = ['okval3', 'okval', 'okval2'];
        $checkOneOf->invokeArgs(null, [$data, 'test', $possibleVals]);
    }

    public function testCheckZeroOrMoreOf()
    {
        $checkOneOf = new \ReflectionMethod('\Riichi\RoundsHelper', '_csvCheckZeroOrMoreOf');
        $checkOneOf->setAccessible(true);

        $data = ['test' => 'a,b'];

        $checkOneOf->invokeArgs(null, [
            $data,
            'test',
            'a,b,c,d'
        ]);
        $this->assertTrue(true); // no exception == ok

        $checkOneOf->invokeArgs(null, [
            $data,
            'test',
            'a,b'
        ]);
        $this->assertTrue(true); // no exception == ok

        $checkOneOf->invokeArgs(null, [
            $data,
            'test',
            'c,b,a,d'
        ]);
        $this->assertTrue(true); // no exception == ok

        $checkOneOf->invokeArgs(null, [
            ['test' => ''], // empty ok
            'test',
            'c,b,a,d'
        ]);
        $this->assertTrue(true); // no exception == ok
    }

    public function testCheckZeroOrMoreOfFail()
    {
        $checkOneOf = new \ReflectionMethod('\Riichi\RoundsHelper', '_csvCheckZeroOrMoreOf');
        $checkOneOf->setAccessible(true);

        $data = ['test' => 'a,b'];
        $expected = 0;
        $catched = 0;

        try {
            $expected ++;
            $checkOneOf->invokeArgs(null, [
                $data,
                'test',
                'c,d'
            ]);
        } catch (\Exception $e) {
            $catched ++;
        }

        try {
            $expected ++;
            $checkOneOf->invokeArgs(null, [
                $data,
                'test',
                'a,c,d'
            ]);
        } catch (\Exception $e) {
            $catched ++;
        }

        try {
            $expected ++;
            $checkOneOf->invokeArgs(null, [
                $data,
                'test',
                '' // nothing matches
            ]);
        } catch (\Exception $e) {
            $catched ++;
        }

        $this->assertEquals($expected, $catched, "Catched exceptions count matches expectations");
    }
}
