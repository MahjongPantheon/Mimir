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

require_once __DIR__.'/../src/controllers/Sortition.php';
require_once __DIR__ . '/util/Db.php';

class ApiTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    protected $_log;
    public function setUp()
    {
        $this->_db = Db::getCleanInstance();
        $this->_log = $this->getMock('Monolog\\Logger', null, ['RiichiApi']);
    }

    public function testDummy()
    {
        $controller = new SortitionController($this->_db, $this->_log);
        $result = $controller->generate();
        $this->assertEquals('test data!', $result);
    }
}
