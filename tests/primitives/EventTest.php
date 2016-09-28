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

require_once __DIR__ . '/../../src/primitives/Event.php';
require_once __DIR__ . '/../util/Db.php';

class EventPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
    }

    public function testNewEvent()
    {
        $newEvent = new EventPrimitive($this->_db);
        $newEvent
            ->setTitle('event1')
            ->setDescription('eventdesc1')
            ->setType('online')
            ->setRuleset('');

        $this->assertEquals('event1', $newEvent->getTitle());
        $this->assertEquals('eventdesc1', $newEvent->getDescription());
        $this->assertEquals('online', $newEvent->getType());

        $success = $newEvent->save();
        $this->assertTrue($success, "Saved event");
        $this->assertGreaterThan(0, $newEvent->getId());
    }

    public function testFindEventById()
    {
        $newEvent = new EventPrimitive($this->_db);
        $newEvent
            ->setTitle('event1')
            ->setDescription('eventdesc1')
            ->setType('online')
            ->setRuleset('')
            ->save();

        $eventCopy = EventPrimitive::findById($this->_db, [$newEvent->getId()]);
        $this->assertEquals(1, count($eventCopy));
        $this->assertEquals('event1', $eventCopy[0]->getTitle());
        $this->assertTrue($newEvent !== $eventCopy); // different objects!
    }

    public function testFindEventByLobby()
    {
        $newEvent = new EventPrimitive($this->_db);
        $newEvent
            ->setTitle('event1')
            ->setDescription('eventdesc1')
            ->setType('online')
            ->setRuleset('')
            ->setLobbyId('some_lobby')
            ->save();

        $eventCopy = EventPrimitive::findByLobby($this->_db, [$newEvent->getLobbyId()]);
        $this->assertEquals(1, count($eventCopy));
        $this->assertEquals('event1', $eventCopy[0]->getTitle());
        $this->assertTrue($newEvent !== $eventCopy); // different objects!
    }

    public function testUpdateEvent()
    {
        $newEvent = new EventPrimitive($this->_db);
        $newEvent
            ->setTitle('event1')
            ->setDescription('eventdesc1')
            ->setType('online')
            ->setRuleset('')
            ->save();

        $eventCopy = EventPrimitive::findById($this->_db, [$newEvent->getId()]);
        $eventCopy[0]->setDescription('someanotherdesc')->save();

        $anotherEventCopy = EventPrimitive::findById($this->_db, [$newEvent->getId()]);
        $this->assertEquals('someanotherdesc', $anotherEventCopy[0]->getDescription());
    }
}
