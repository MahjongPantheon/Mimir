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

require_once __DIR__ . '/../../src/primitives/Session.php';
require_once __DIR__ . '/../../src/primitives/Event.php';
require_once __DIR__ . '/../../src/primitives/Player.php';
require_once __DIR__ . '/../util/Db.php';

class SessionPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    protected $_event;
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
    }

    public function testNewSession()
    {
        $newSession = new SessionPrimitive($this->_db);
        $newSession
            ->setState('inprogress')
            ->setOrigLink('test')
            ->setReplayHash('hash')
            ->setEvent($this->_event)
            ->setPlayers($this->_players);

        $this->assertEquals('test', $newSession->getOrigLink());
        $this->assertEquals('hash', $newSession->getReplayHash());
        $this->assertEquals('inprogress', $newSession->getState());
        $this->assertTrue($this->_event === $newSession->getEvent());
        $this->assertTrue($this->_players[1] === $newSession->getPlayers()[1]);

        $success = $newSession->save();
        $this->assertTrue($success, "Saved session");
        $this->assertGreaterThan(0, $newSession->getId());
    }

    public function testFindSessionById()
    {
        $newSession = new SessionPrimitive($this->_db);
        $newSession
            ->setState('inprogress')
            ->setOrigLink('test')
            ->setReplayHash('hash')
            ->setEvent($this->_event)
            ->save();

        $sessionCopy = SessionPrimitive::findById($this->_db, [$newSession->getId()]);
        $this->assertEquals(1, count($sessionCopy));
        $this->assertEquals('hash', $sessionCopy[0]->getReplayHash());
        $this->assertTrue($newSession !== $sessionCopy[0]); // different objects!
    }

    public function testFindSessionByState()
    {
        $newSession = new SessionPrimitive($this->_db);
        $newSession
            ->setState('inprogress')
            ->setOrigLink('test')
            ->setReplayHash('hash')
            ->setEvent($this->_event)
            ->save();

        $sessionCopy = SessionPrimitive::findByState($this->_db, [$newSession->getState()]);
        $this->assertEquals(1, count($sessionCopy));
        $this->assertEquals('hash', $sessionCopy[0]->getReplayHash());
        $this->assertTrue($newSession !== $sessionCopy[0]); // different objects!
    }

    public function testFindSessionByReplay()
    {
        $newSession = new SessionPrimitive($this->_db);
        $newSession
            ->setState('inprogress')
            ->setOrigLink('test')
            ->setReplayHash('hash')
            ->setEvent($this->_event)
            ->save();

        $sessionCopy = SessionPrimitive::findByReplayHash($this->_db, [$newSession->getReplayHash()]);
        $this->assertEquals(1, count($sessionCopy));
        $this->assertEquals('inprogress', $sessionCopy[0]->getState());
        $this->assertTrue($newSession !== $sessionCopy[0]); // different objects!
    }

    public function testFindSessionByRepHash()
    {
        $newSession = new SessionPrimitive($this->_db);
        $newSession
            ->setState('inprogress')
            ->setOrigLink('test')
            ->setReplayHash('hash')
            ->setEvent($this->_event)
            ->save();

        $this->assertNotEmpty($newSession->getRepresentationalHash());
        $sessionCopy = SessionPrimitive::findByRepresentationalHash($this->_db, [$newSession->getRepresentationalHash()]);
        $this->assertEquals(1, count($sessionCopy));
        $this->assertEquals('hash', $sessionCopy[0]->getReplayHash());
        $this->assertTrue($newSession !== $sessionCopy[0]); // different objects!
    }

    public function testUpdateSession()
    {
        $newSession = new SessionPrimitive($this->_db);
        $newSession
            ->setState('inprogress')
            ->setOrigLink('test')
            ->setReplayHash('hash')
            ->setEvent($this->_event)
            ->save();

        $sessionCopy = SessionPrimitive::findById($this->_db, [$newSession->getId()]);
        $sessionCopy[0]->setReplayHash('someanotherhash')->save();
        $this->assertEquals($newSession->getRepresentationalHash(), $sessionCopy[0]->getRepresentationalHash());

        $anotherSessionCopy = SessionPrimitive::findById($this->_db, [$newSession->getId()]);
        $this->assertEquals('someanotherhash', $anotherSessionCopy[0]->getReplayHash());
        $this->assertEquals($newSession->getRepresentationalHash(), $anotherSessionCopy[0]->getRepresentationalHash());
    }

    public function testRelationGetters()
    {
        // TODO
        // 1) save to db
        // 2) get copy
        // 3) use getters of copy to get copies of resources
    }
}
