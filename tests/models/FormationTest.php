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

require_once __DIR__ . '/../../src/models/Formation.php';
require_once __DIR__ . '/../../src/models/Player.php';
require_once __DIR__ . '/../util/Db.php';

class FormationModelTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    protected $_owner;

    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
        $this->_owner = (new Player($this->_db))
            ->setDisplayName('player')
            ->setIdent('oauth')
            ->setTenhouId('tenhou');
        $this->_owner->save();
    }

    public function testNewFormation()
    {
        $newFormation = new Formation($this->_db);
        $newFormation
            ->setTitle('f1')
            ->setDescription('fdesc1')
            ->setCity('city')
            ->setContactInfo('someinfo')
            ->setPrimaryOwner($this->_owner);

        $this->assertEquals('f1', $newFormation->getTitle());
        $this->assertEquals('fdesc1', $newFormation->getDescription());
        $this->assertEquals('city', $newFormation->getCity());
        $this->assertEquals('someinfo', $newFormation->getContactInfo());
        $this->assertTrue($this->_owner === $newFormation->getPrimaryOwner());

        $success = $newFormation->save();
        $this->assertTrue($success, "Saved formation");
        $this->assertGreaterThan(0, $newFormation->getId());
    }

    public function testFindFormationById()
    {
        $newFormation = new Formation($this->_db);
        $newFormation
            ->setTitle('f1')
            ->setDescription('fdesc1')
            ->setCity('city')
            ->setContactInfo('someinfo')
            ->setPrimaryOwner($this->_owner)
            ->save();

        $formationCopy = Formation::findById($this->_db, [$newFormation->getId()]);
        $this->assertEquals(1, count($formationCopy));
        $this->assertEquals('f1', $formationCopy[0]->getTitle());
        $this->assertTrue($newFormation !== $formationCopy); // different objects!
    }

    public function testUpdateFormation()
    {
        $newFormation = new Formation($this->_db);
        $newFormation
            ->setTitle('f1')
            ->setDescription('fdesc1')
            ->setCity('city')
            ->setContactInfo('someinfo')
            ->setPrimaryOwner($this->_owner)
            ->save();

        $formationCopy = Formation::findById($this->_db, [$newFormation->getId()]);
        $formationCopy[0]->setDescription('someanotherdesc')->save();

        $anotherFormationCopy = Formation::findById($this->_db, [$newFormation->getId()]);
        $this->assertEquals('someanotherdesc', $anotherFormationCopy[0]->getDescription());
    }
}
