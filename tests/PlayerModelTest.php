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

require_once __DIR__.'/../src/models/Player.php';
require_once __DIR__ . '/util/Db.php';

class PlayerModelTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
    }

    public function testNewPlayer()
    {
        $newUser = new Player($this->_db);
        $newUser
            ->setDisplayName('user1')
            ->setIdent('someident')
            ->setTenhouId('someid');

        $this->assertEquals('user1', $newUser->getDisplayName());
        $this->assertEquals('someident', $newUser->getIdent());
        $this->assertEquals('someid', $newUser->getTenhouId());

        $success = $newUser->save();
        $this->assertTrue($success, "Saved player");

        $this->assertGreaterThan(0, $newUser->getId());
    }

    public function testFindPlayerById()
    {
        $newUser = new Player($this->_db);
        $newUser
            ->setDisplayName('user1')
            ->setIdent('someident')
            ->setTenhouId('someid')
            ->save();

        $userCopy = Player::findById($this->_db, [$newUser->getId()]);
        $this->assertEquals(1, count($userCopy));
        $this->assertEquals('someident', $userCopy[0]->getIdent());
        $this->assertTrue($newUser !== $userCopy); // different objects!
    }

    public function testFindPlayerByIdent()
    {
        $newUser = new Player($this->_db);
        $newUser
            ->setDisplayName('user1')
            ->setIdent('someident')
            ->setTenhouId('someid')
            ->save();

        $userCopy = Player::findByIdent($this->_db, [$newUser->getIdent()]);
        $this->assertEquals(1, count($userCopy));
        $this->assertEquals('someid', $userCopy[0]->getTenhouId());
        $this->assertTrue($newUser !== $userCopy); // different objects!
    }

    public function testFindPlayerByTenhouId()
    {
        $newUser = new Player($this->_db);
        $newUser
            ->setDisplayName('user1')
            ->setIdent('someident')
            ->setTenhouId('someid')
            ->save();

        $userCopy = Player::findByTenhouId($this->_db, [$newUser->getTenhouId()]);
        $this->assertEquals(1, count($userCopy));
        $this->assertEquals('user1', $userCopy[0]->getDisplayName());
        $this->assertTrue($newUser !== $userCopy); // different objects!
    }

    public function testUpdatePlayer()
    {
        $newUser = new Player($this->_db);
        $newUser
            ->setDisplayName('user1')
            ->setIdent('someident')
            ->setTenhouId('someid')
            ->save();

        $userCopy = Player::findByTenhouId($this->_db, [$newUser->getTenhouId()]);
        $userCopy[0]->setIdent('someanotherident')->save();

        $anotherUserCopy = Player::findById($this->_db, [$newUser->getId()]);
        $this->assertEquals('someanotherident', $anotherUserCopy[0]->getIdent());
    }
}
