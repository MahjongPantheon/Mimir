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

use Idiorm\ORM;

require_once __DIR__ . '/../../src/models/Session.php';
require_once __DIR__ . '/../../src/primitives/Player.php';
require_once __DIR__ . '/../../src/primitives/Event.php';
require_once __DIR__ . '/../util/Db.php';

/**
 * Class SessionTest: integration test suite
 * @package Riichi
 */
class SessionModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Db
     */
    protected $_db;
    protected $_players = [];
    protected $_event;
    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
        $this->_event = (new EventPrimitive($this->_db))
            ->setTitle('title')
            ->setDescription('desc')
            ->setType('online')
            ->setRuleset('');
        $this->_event->save();

        $this->_players = array_map(function ($i) {
            $p = (new PlayerPrimitive($this->_db))
                ->setDisplayName('player' . $i)
                ->setIdent('oauth' . $i)
                ->setTenhouId('tenhou' . $i);
            $p->save();
            return $p;
        }, [1, 2, 3, 4]);
    }

    // Positive tests

    public function testNewGame()
    {
        //->start([1, 2, 3, 4]);
    }

    public function testEndGame()
    {
        //->end('some_existing_hash');
    }

    public function testAddRoundRon()
    {
        /*$roundData = [
            'outcome'   => 'ron',
            'round'     => 1,
            'riichi'    => '',
            'winner_id' => 2,
            'loser_id'  => 3,
            'han'       => 2,
            'fu'        => 30,
            'multi_ron' => null,
            'dora'      => 0,
            'uradora'   => 0,
            'kandora'   => 0,
            'kanuradora' => 1,
            'yaku'      => ''
        ];*/
    }

    public function testAddRoundTsumo()
    {
        /*$roundData = [
            'outcome'   => 'tsumo',
            'round'     => 1,
            'riichi'    => '',
            'winner_id' => 2,
            'han'       => 2,
            'fu'        => 30,
            'multi_ron' => null,
            'dora'      => 0,
            'uradora'   => 0,
            'kandora'   => 0,
            'kanuradora' => 1,
            'yaku'      => ''
        ];*/
    }

    public function testAddRoundDraw()
    {
        /*$roundData = [
            'outcome'   => 'draw',
            'round'     => 1,
            'riichi'    => '',
            'tempai'    => ''
        ];*/
    }

    public function testAddRoundAbortiveDraw()
    {
        /*$roundData = [
            'outcome'   => 'abort',
            'round'     => 1,
            'riichi'    => ''
        ];*/
    }

    public function testAddRoundChombo()
    {
        /*$roundData = [
            'outcome'   => 'chombo',
            'round'     => 1,
            'loser_id'  => 2,
        ];*/
    }

    // Negative tests
//
//    /**
//     * @expectedException \Riichi\InvalidUserException
//     */
//    public function testNewGameBadUser()
//    {
//        //->start([2, 3, 4, 5]); // id 5 does not exist
//    }
//
//    /**
//     * @expectedException \Riichi\InvalidUserException
//     */
//    public function testNewGameWrongUserCount()
//    {
//        //->start([2, 3, 4]);
//    }
//
//    /**
//     * @expectedException \Riichi\DatabaseException
//     */
//    public function testEndGameWrongHash()
//    {
//        //->end('some_inexisting_hash');
//    }
//
//    public function testEndGameButGameAlreadyFinished()
//    {
//        //->start([1, 2, 3, 4]);
//        //->end($hash); // Really finish
//
//        $caught = false;
//        try {
//            //->end($hash); // Try to finish again
//        } catch (BadActionException $e) {
//            // We do try/catch here to avoid catching same exception from
//            // upper clauses, as it might give some false positives in that case.
//            $caught = true;
//        }
//
//        $this->assertTrue($caught, "Finished game throws exception");
//    }
}
