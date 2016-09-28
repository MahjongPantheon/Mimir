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

require_once __DIR__ . '/../../src/primitives/Round.php';
require_once __DIR__ . '/../../src/primitives/Session.php';
require_once __DIR__ . '/../../src/primitives/Event.php';
require_once __DIR__ . '/../../src/primitives/Player.php';
require_once __DIR__ . '/../util/Db.php';

class RoundPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    /**
     * @var SessionPrimitive
     */
    protected $_session;
    /**
     * @var EventPrimitive
     */
    protected $_event;
    /**
     * @var PlayerPrimitive[]
     */
    protected $_players;

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

        $this->_session = (new SessionPrimitive($this->_db))
            ->setEvent($this->_event)
            ->setPlayers($this->_players)
            ->setState('inprogress')
            ->setReplayHash('')
            ->setOrigLink('');
        $this->_session->save();
    }

    public function testNewRound()
    {
        $newRound = new RoundPrimitive($this->_db);
        $newRound
            ->setSession($this->_session)
            ->setOutcome('ron')
            ->setWinner($this->_players[1])
            ->setLoser($this->_players[2])
            ->setHan(1)
            ->setFu(30)
            ->setDora(0)
            ->setKandora(0)
            ->setUradora(0)
            ->setKanuradora(0)
            ->setRiichiUsers([$this->_players[1]])
            ->setTempaiUsers([])
            ->setRoundIndex(1)
            ->setMultiRon(0)
            ->setYaku('');

        $this->assertEquals($this->_session->getId(), $newRound->getSessionId());
        $this->assertTrue($this->_session === $newRound->getSession());
        $this->assertTrue($this->_players[1] === $newRound->getWinner());
        $this->assertTrue($this->_players[2] === $newRound->getLoser());
        $this->assertEquals(1, count($newRound->getRiichiUsers()));
        $this->assertTrue($this->_players[1] === $newRound->getRiichiUsers()[0]);
        $this->assertEquals(0, count($newRound->getTempaiUsers()));
        $this->assertEquals('ron', $newRound->getOutcome());
        $this->assertEquals(1, $newRound->getRoundIndex());
        $this->assertEquals(1, $newRound->getHan());
        $this->assertEquals(30, $newRound->getFu());
        $this->assertEquals(0, $newRound->getDora());
        $this->assertEquals(0, $newRound->getKandora());
        $this->assertEquals(0, $newRound->getUradora());
        $this->assertEquals(0, $newRound->getKanuradora());
        $this->assertEquals(0, $newRound->getMultiRon());
        $this->assertEquals('', $newRound->getYaku());
        $this->assertTrue($this->_event === $newRound->getEvent());
        $this->assertEquals($this->_event->getId(), $newRound->getEventId());

        $success = $newRound->save();
        $this->assertTrue($success, "Saved round");
        $this->assertGreaterThan(0, $newRound->getId());
    }

    public function testFindRoundById()
    {
        $newRound = new RoundPrimitive($this->_db);
        $newRound
            ->setSession($this->_session)
            ->setOutcome('ron')
            ->setRoundIndex(1);
        $newRound->save();

        $roundsCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()]);
        $this->assertEquals(1, count($roundsCopy));
        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testFindRoundBySession()
    {
        $newRound = new RoundPrimitive($this->_db);
        $newRound
            ->setSession($this->_session)
            ->setOutcome('ron')
            ->setRoundIndex(1);
        $newRound->save();

        $roundsCopy = RoundPrimitive::findBySessionIds($this->_db, [$this->_session->getId()]);
        $this->assertEquals(1, count($roundsCopy));
        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testFindRoundByEvent()
    {
        $newRound = new RoundPrimitive($this->_db);
        $newRound
            ->setSession($this->_session)
            ->setOutcome('ron')
            ->setRoundIndex(1);
        $newRound->save();

        $roundsCopy = RoundPrimitive::findByEventIds($this->_db, [$this->_event->getId()]);
        $this->assertEquals(1, count($roundsCopy));
        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testFindRoundByWinner()
    {
        $newRound = new RoundPrimitive($this->_db);
        $newRound
            ->setSession($this->_session)
            ->setWinner($this->_players[0])
            ->setOutcome('ron')
            ->setRoundIndex(1);
        $newRound->save();

        $roundsCopy = RoundPrimitive::findByWinnerIds($this->_db, [$this->_players[0]->getId()]);
        $this->assertEquals(1, count($roundsCopy));
        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testFindRoundByLoser()
    {
        $newRound = new RoundPrimitive($this->_db);
        $newRound
            ->setSession($this->_session)
            ->setLoser($this->_players[0])
            ->setOutcome('ron')
            ->setRoundIndex(1);
        $newRound->save();

        $roundsCopy = RoundPrimitive::findByLoserIds($this->_db, [$this->_players[0]->getId()]);
        $this->assertEquals(1, count($roundsCopy));
        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testUpdateRound()
    {
        $newRound = new RoundPrimitive($this->_db);
        $newRound
            ->setSession($this->_session)
            ->setOutcome('ron')
            ->setRoundIndex(1);
        $newRound->save();

        $roundCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()]);
        $roundCopy[0]->setOutcome('tsumo')->save();

        $anotherRoundCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()]);
        $this->assertEquals('tsumo', $anotherRoundCopy[0]->getOutcome());
    }

    public function testRelationGetters()
    {
        // TODO
        // 1) save to db
        // 2) get copy
        // 3) use getters of copy to get copies of resources
    }
}
