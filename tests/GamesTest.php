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

require_once __DIR__ . '/../src/controllers/Games.php';
require_once __DIR__ . '/util/Db.php';

/**
 * Class GamesTest: integration test suite
 * @package Riichi
 */
class GamesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Db
     */
    protected $_db;
    protected $_log;
    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
        $this->_log = $this->getMock('Monolog\\Logger', null, ['RiichiApi']);

        $this->_db->table('user')->create([
            'id'            => 1,
            'ident'         => '',
            'display_name'  => 'user1',
            'tenhou_id'     => null
        ])->save();
        $this->_db->table('user')->create([
            'id'            => 2,
            'ident'         => '',
            'display_name'  => 'user2',
            'tenhou_id'     => null
        ])->save();
        $this->_db->table('user')->create([
            'id'            => 3,
            'ident'         => '',
            'display_name'  => 'user3',
            'tenhou_id'     => null
        ])->save();
        $this->_db->table('user')->create([
            'id'            => 4,
            'ident'         => '',
            'display_name'  => 'user4',
            'tenhou_id'     => null
        ])->save();
    }

    // Positive tests

    public function testNewGame()
    {
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);

        $this->assertNotEmpty($hash, "Hash received");
        $game = $this->_db->table('session')->where('representational_hash', $hash)->findOne();
        $this->assertNotEmpty($game, "Game created");
        $this->assertEquals('inprogress', $game->get('state'), "Game status fine");
        $this->assertEquals('1,2,3,4', $game->get('players'), "Players fine");
        $this->assertNotEmpty($game->get('play_date'), "Timestamp filled");
    }

    public function testEndGame()
    {
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);
        $success = $controller->end($hash);

        $this->assertTrue($success, "Game finished");
        $game = $this->_db->table('session')->where('representational_hash', $hash)->findOne();
        $this->assertEquals('finished', $game->get('state'), "Game status fine");
    }

    public function testAddRoundRon()
    {
        $roundData = [
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
        ];
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);
        $success = $controller->addRound($hash, $roundData);

        $this->assertTrue($success, "Round added (ron)");
        $game = $this->_db->table('session')->where('representational_hash', $hash)->findOne();
        $roundsCount = $this->_db->table('round')->where('session_id', $game->get('id'))->count();

        $this->assertEquals(1, $roundsCount, "Exactly one round added");
    }

    public function testAddRoundTsumo()
    {
        $roundData = [
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
        ];
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);
        $success = $controller->addRound($hash, $roundData);

        $this->assertTrue($success, "Round added (tsumo)");
        $game = $this->_db->table('session')->where('representational_hash', $hash)->findOne();
        $roundsCount = $this->_db->table('round')->where('session_id', $game->get('id'))->count();

        $this->assertEquals(1, $roundsCount, "Exactly one round added");
    }

    public function testAddRoundDraw()
    {
        $roundData = [
            'outcome'   => 'draw',
            'round'     => 1,
            'riichi'    => '',
            'tempai'    => ''
        ];
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);
        $success = $controller->addRound($hash, $roundData);

        $this->assertTrue($success, "Round added (draw)");
        $game = $this->_db->table('session')->where('representational_hash', $hash)->findOne();
        $roundsCount = $this->_db->table('round')->where('session_id', $game->get('id'))->count();

        $this->assertEquals(1, $roundsCount, "Exactly one round added");
    }

    public function testAddRoundAbortiveDraw()
    {
        $roundData = [
            'outcome'   => 'abort',
            'round'     => 1,
            'riichi'    => ''
        ];
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);
        $success = $controller->addRound($hash, $roundData);

        $this->assertTrue($success, "Round added (abortive)");
        $game = $this->_db->table('session')->where('representational_hash', $hash)->findOne();
        $roundsCount = $this->_db->table('round')->where('session_id', $game->get('id'))->count();

        $this->assertEquals(1, $roundsCount, "Exactly one round added");
    }

    public function testAddRoundChombo()
    {
        $roundData = [
            'outcome'   => 'chombo',
            'round'     => 1,
            'loser_id'  => 2,
        ];
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);
        $success = $controller->addRound($hash, $roundData);

        $this->assertTrue($success, "Round added (chombo)");
        $game = $this->_db->table('session')->where('representational_hash', $hash)->findOne();
        $roundsCount = $this->_db->table('round')->where('session_id', $game->get('id'))->count();

        $this->assertEquals(1, $roundsCount, "Exactly one round added");
    }

    // Negative tests

    /**
     * @expectedException \Riichi\InvalidUserException
     */
    public function testNewGameBadUser()
    {
        $controller = new Games($this->_db, $this->_log);
        $controller->start([2, 3, 4, 5]); // id 5 does not exist
    }

    /**
     * @expectedException \Riichi\InvalidUserException
     */
    public function testNewGameWrongUserCount()
    {
        $controller = new Games($this->_db, $this->_log);
        $controller->start([2, 3, 4]);
    }

    /**
     * @expectedException \Riichi\DatabaseException
     */
    public function testEndGameWrongHash()
    {
        $controller = new Games($this->_db, $this->_log);
        $controller->end('some_inexisting_hash');
    }

    public function testEndGameButGameAlreadyFinished()
    {
        $controller = new Games($this->_db, $this->_log);
        $hash = $controller->start([1, 2, 3, 4]);
        $controller->end($hash); // Really finish

        $caught = false;
        try {
            $controller->end($hash); // Try to finish again
        } catch (BadActionException $e) {
            // We do try/catch here to avoid catching same exception from
            // upper clauses, as it might give some false positives in that case.
            $caught = true;
        }

        $this->assertTrue($caught, "Finished game throws exception");
    }
}
