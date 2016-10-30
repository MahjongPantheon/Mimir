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
require_once __DIR__ . '/../../src/models/InteractiveSession.php';
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
    /**
     * @var PlayerPrimitive[]
     */
    protected $_players = [];
    /**
     * @var EventPrimitive
     */
    protected $_event;
    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
        $this->_event = (new EventPrimitive($this->_db))
            ->setTitle('title')
            ->setDescription('desc')
            ->setType('online')
            ->setRuleset(Ruleset::instance('jpmlA'));
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
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                return $p->getId();
            }, $this->_players)
        );

        $sessionPrimitive = SessionPrimitive::findByRepresentationalHash($this->_db, [$hash]);
        $this->assertEquals(1, count($sessionPrimitive));
        $this->assertEquals($this->_event->getId(), $sessionPrimitive[0]->getEventId());
        $this->assertEquals('inprogress', $sessionPrimitive[0]->getStatus());
    }

    public function testEndGame()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                return $p->getId();
            }, $this->_players)
        );

        $session->addRound($hash, [
            'outcome' => 'draw',
            'tempai' => '',
            'riichi' => ''
        ]);

        $session->endGame($hash);

        $sessionPrimitive = SessionPrimitive::findByRepresentationalHash($this->_db, [$hash]);
        $this->assertEquals(1, count($sessionPrimitive));
        $this->assertEquals($this->_event->getId(), $sessionPrimitive[0]->getEventId());
        $this->assertEquals('finished', $sessionPrimitive[0]->getStatus());
    }

    public function testAddRoundRon()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                return $p->getId();
            }, $this->_players)
        );

        $roundData = [
            'outcome'   => 'ron',
            'riichi'    => '',
            'winner_id' => $this->_players[1]->getId(),
            'loser_id'  => $this->_players[2]->getId(),
            'han'       => 2,
            'fu'        => 30,
            'multi_ron' => null,
            'dora'      => 0,
            'uradora'   => 0,
            'kandora'   => 0,
            'kanuradora' => 1,
            'yaku'      => '2'
        ];

        $this->assertTrue($session->addRound($hash, $roundData));
    }

    public function testAddRoundTsumo()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                    return $p->getId();
            }, $this->_players)
        );

        $roundData = [
            'outcome'   => 'tsumo',
            'riichi'    => '',
            'winner_id' => 2,
            'han'       => 2,
            'fu'        => 30,
            'multi_ron' => null,
            'dora'      => 0,
            'uradora'   => 0,
            'kandora'   => 0,
            'kanuradora' => 1,
            'yaku'      => '3'
        ];

        $this->assertTrue($session->addRound($hash, $roundData));
    }

    public function testAddRoundDraw()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                    return $p->getId();
            }, $this->_players)
        );

        $roundData = [
            'outcome'   => 'draw',
            'riichi'    => '',
            'tempai'    => ''
        ];

        $this->assertTrue($session->addRound($hash, $roundData));
    }

    public function testAddRoundAbortiveDraw()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                    return $p->getId();
            }, $this->_players)
        );

        $roundData = [
            'outcome'   => 'abort',
            'riichi'    => ''
        ];

        $this->assertTrue($session->addRound($hash, $roundData));
    }

    public function testAddRoundChombo()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                    return $p->getId();
            }, $this->_players)
        );

        $roundData = [
            'outcome'   => 'chombo',
            'loser_id'  => 2,
        ];

        $this->assertTrue($session->addRound($hash, $roundData));
    }

    // Negative tests

    /**
     * @expectedException \Riichi\InvalidUserException
     */
    public function testNewGameBadUser()
    {
        $playerIds = array_map(function (PlayerPrimitive $p) {
            return $p->getId();
        }, $this->_players);
        $playerIds[1] = 100400; // non-existing id

        $session = new InteractiveSessionModel($this->_db);
        $session->startGame($this->_event->getId(), $playerIds);
    }

    /**
     * @expectedException \Riichi\InvalidUserException
     */
    public function testNewGameWrongUserCount()
    {
        $playerIds = array_map(function (PlayerPrimitive $p) {
            return $p->getId();
        }, $this->_players);
        array_pop($playerIds); // 3 players instead of 4

        $session = new InteractiveSessionModel($this->_db);
        $session->startGame($this->_event->getId(), $playerIds);
    }

    /**
         * @expectedException \Riichi\InvalidParametersException
     */
    public function testEndGameWrongHash()
    {
        $session = new InteractiveSessionModel($this->_db);
        $session->endGame('inexisting_hash');
    }

    public function testEndGameButGameAlreadyFinished()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                return $p->getId();
            }, $this->_players)
        );

        $session->endGame($hash); // Finish ok

        $caught = false;
        try {
            $session->endGame($hash); // Try to finish again
        } catch (BadActionException $e) {
            // We do try/catch here to avoid catching same exception from
            // upper clauses, as it might give some false positives in that case.
            $caught = true;
        }

        $this->assertTrue($caught, "Finished game throws exception");
    }

    public function testAutoEndGameWhenHanchanFinishes()
    {
        $session = new InteractiveSessionModel($this->_db);
        $hash = $session->startGame(
            $this->_event->getId(),
            array_map(function (PlayerPrimitive $p) {
                    return $p->getId();
            }, $this->_players)
        );

        $roundData = [
            'outcome'   => 'draw',
            'riichi'    => '',
            'tempai'    => ''
        ];

        $this->assertTrue($session->addRound($hash, $roundData)); // 1e
        $this->assertTrue($session->addRound($hash, $roundData)); // 2e
        $this->assertTrue($session->addRound($hash, $roundData)); // 3e
        $this->assertTrue($session->addRound($hash, $roundData)); // 4e
        $this->assertTrue($session->addRound($hash, $roundData)); // 1s
        $this->assertTrue($session->addRound($hash, $roundData)); // 2s
        $this->assertTrue($session->addRound($hash, $roundData)); // 3s
        $this->assertTrue($session->addRound($hash, $roundData)); // 4s, should auto-finish here

        $caught = false;
        try {
            $session->endGame($hash); // Try to finish again
        } catch (BadActionException $e) {
            // We do try/catch here to avoid catching same exception from
            // upper clauses, as it might give some false positives in that case.
            $caught = true;
        }

        $this->assertTrue($caught, "Game should be already finished");

        // Check that results exist in db
        $results = SessionResultsPrimitive::findByEventId($this->_db, [$this->_event->getId()]);
        $this->assertEquals(4, count($results));
        // See jpmlA ruleset to find out why these numbers are ok
        $this->assertEquals(8, $results[0]->getRatingDelta());
        $this->assertEquals(4, $results[1]->getRatingDelta());
        $this->assertEquals(-4, $results[2]->getRatingDelta());
        $this->assertEquals(-8, $results[3]->getRatingDelta());

        // Check that user history items exist in db
        /** @var PlayerHistoryPrimitive[] $items */
        $items = array_map(function (PlayerPrimitive $player) {
            return PlayerHistoryPrimitive::findLastByEvent(
                $this->_db,
                $this->_event->getId(),
                $player->getId()
            );
        }, $this->_players);

        $this->assertEquals(1508, $items[0]->getRating());
        $this->assertEquals(1504, $items[1]->getRating());
        $this->assertEquals(1496, $items[2]->getRating());
        $this->assertEquals(1492, $items[3]->getRating());
    }
}
