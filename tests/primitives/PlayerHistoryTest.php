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

require_once __DIR__ . '/../../src/Ruleset.php';
require_once __DIR__ . '/../../src/primitives/Round.php';
require_once __DIR__ . '/../../src/primitives/Session.php';
require_once __DIR__ . '/../../src/primitives/Event.php';
require_once __DIR__ . '/../../src/primitives/Player.php';
require_once __DIR__ . '/../util/Db.php';

class PlayerHistoryPrimitiveTest extends \PHPUnit_Framework_TestCase
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

        $this->_session = (new SessionPrimitive($this->_db))
            ->setEvent($this->_event)
            ->setPlayers($this->_players)
            ->setStatus('inprogress')
            ->setReplayHash('')
            ->setOrigLink('');
        $this->_session->save();
    }

    public function testNewHistoryItem()
    {
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($this->_players[1])
//            ->setLoser($this->_players[2])
//            ->setYaku('');
//
//        $this->assertEquals($this->_session->getId(), $newRound->getSessionId());
//        $this->assertTrue($this->_session === $newRound->getSession());
//        $this->assertTrue($this->_players[1] === $newRound->getWinner());
//        $this->assertTrue($this->_players[2] === $newRound->getLoser());
//        $this->assertEquals(1, count($newRound->getRiichiUsers()));
//        $this->assertTrue($this->_players[1] === $newRound->getRiichiUsers()[0]);
//
//        $success = $newRound->save();
//        $this->assertTrue($success, "Saved round");
//        $this->assertGreaterThan(0, $newRound->getId());
    }

    public function testFindLastItemByPlayerAndEvent()
    {
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($this->_players[2])
//            ->setLoser($this->_players[3])
//            ->setRoundIndex(1);
//        $newRound->save();
//
//        $roundsCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()]);
//        $this->assertEquals(1, count($roundsCopy));
//        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
//        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testFindAllItemsByPlayerAndEvent()
    {
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($this->_players[2])
//            ->setLoser($this->_players[3])
//            ->setRoundIndex(1);
//        $newRound->save();
//
//        $roundsCopy = RoundPrimitive::findBySessionIds($this->_db, [$this->_session->getId()]);
//        $this->assertEquals(1, count($roundsCopy));
//        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
//        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testFindItemByPlayerAndSession()
    {
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($this->_players[2])
//            ->setLoser($this->_players[3])
//            ->setRoundIndex(1);
//        $newRound->save();
//
//        $roundsCopy = RoundPrimitive::findByEventIds($this->_db, [$this->_event->getId()]);
//        $this->assertEquals(1, count($roundsCopy));
//        $this->assertEquals('ron', $roundsCopy[0]->getOutcome());
//        $this->assertTrue($newRound !== $roundsCopy[0]); // different objects!
    }

    public function testUpdateHistoryItem()
    {
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($this->_players[2])
//            ->setLoser($this->_players[3])
//            ->setRoundIndex(1);
//        $newRound->save();
//
//        $roundCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()]);
//        $roundCopy[0]->setOutcome('tsumo')->save();
//
//        $anotherRoundCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()]);
//        $this->assertEquals('tsumo', $anotherRoundCopy[0]->getOutcome());
    }

    public function testRelationSession()
    {
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($this->_players[2])
//            ->setLoser($this->_players[3])
//            ->setRoundIndex(1);
//        $newRound->save();
//
//        $roundCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()])[0];
//        $this->assertEquals($this->_session->getId(), $roundCopy->getSessionId()); // before fetch
//        $this->assertNotEmpty($roundCopy->getSession());
//        $this->assertEquals($this->_session->getId(), $roundCopy->getSession()->getId());
//        $this->assertTrue($this->_session !== $roundCopy->getSession()); // different objects!
    }

    public function testRelationEvent()
    {
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($this->_players[2])
//            ->setLoser($this->_players[3])
//            ->setRoundIndex(1);
//        $newRound->save();
//
//        $roundCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()])[0];
//        $this->assertEquals($this->_event->getId(), $roundCopy->getEventId()); // before fetch
//        $this->assertNotEmpty($roundCopy->getEvent());
//        $this->assertEquals($this->_event->getId(), $roundCopy->getEvent()->getId());
//        $this->assertTrue($this->_event !== $roundCopy->getEvent()); // different objects!
    }

    public function testRelationPlayer()
    {
//        $newUser = new PlayerPrimitive($this->_db);
//        $newUser
//            ->setDisplayName('user1')
//            ->setIdent('someident')
//            ->setTenhouId('someid');
//        $newUser->save();
//
//        $this->_players[1] = $newUser;
//        $this->_session->setPlayers($this->_players)->save();
//
//        $newRound = new RoundPrimitive($this->_db);
//        $newRound
//            ->setSession($this->_session)
//            ->setOutcome('ron')
//            ->setWinner($newUser)
//            ->setLoser($this->_players[3])
//            ->setRoundIndex(1);
//        $newRound->save();
//
//        $roundCopy = RoundPrimitive::findById($this->_db, [$newRound->getId()])[0];
//        $this->assertEquals($newUser->getId(), $roundCopy->getWinnerId()); // before fetch
//
//        $this->assertNotEmpty($roundCopy->getWinner());
//        $this->assertEquals($newUser->getId(), $roundCopy->getWinner()->getId());
//        $this->assertTrue($newUser !== $roundCopy->getWinner()); // different objects!
    }
}
