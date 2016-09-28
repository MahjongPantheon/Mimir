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

require_once __DIR__ . '/../../src/primitives/Formation.php';
require_once __DIR__ . '/../../src/primitives/Player.php';
require_once __DIR__ . '/../util/Db.php';

class FormationPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    protected $_owner;

    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
        $this->_owner = (new PlayerPrimitive($this->_db))
            ->setDisplayName('player')
            ->setIdent('oauth')
            ->setTenhouId('tenhou');
        $this->_owner->save();
    }

    public function testNewFormation()
    {
        $newFormation = new FormationPrimitive($this->_db);
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
        $newFormation = new FormationPrimitive($this->_db);
        $newFormation
            ->setTitle('f1')
            ->setDescription('fdesc1')
            ->setCity('city')
            ->setContactInfo('someinfo')
            ->setPrimaryOwner($this->_owner)
            ->save();

        $formationCopy = FormationPrimitive::findById($this->_db, [$newFormation->getId()]);
        $this->assertEquals(1, count($formationCopy));
        $this->assertEquals('f1', $formationCopy[0]->getTitle());
        $this->assertTrue($newFormation !== $formationCopy[0]); // different objects!
    }

    public function testUpdateFormation()
    {
        $newFormation = new FormationPrimitive($this->_db);
        $newFormation
            ->setTitle('f1')
            ->setDescription('fdesc1')
            ->setCity('city')
            ->setContactInfo('someinfo')
            ->setPrimaryOwner($this->_owner)
            ->save();

        $formationCopy = FormationPrimitive::findById($this->_db, [$newFormation->getId()]);
        $formationCopy[0]->setDescription('someanotherdesc')->save();

        $anotherFormationCopy = FormationPrimitive::findById($this->_db, [$newFormation->getId()]);
        $this->assertEquals('someanotherdesc', $anotherFormationCopy[0]->getDescription());
    }

    public function testRelationGetters()
    {
        // TODO
        // 1) save to db
        // 2) get copy
        // 3) use getters of copy to get copies of resources
    }
}
