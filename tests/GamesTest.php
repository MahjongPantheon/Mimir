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
        $this->_db = Db::getInstance();
        $this->_log = $this->getMock('Monolog\\Logger', null, ['RiichiApi']);

        $this->_db->table('user')->create([
            'id' => 1,
            'ident' => '',
            'display_name' => 'user1',
            'tenhou_id' => null
        ])->save();
        $this->_db->table('user')->create([
            'id' => 2,
            'ident' => '',
            'display_name' => 'user2',
            'tenhou_id' => null
        ])->save();
        $this->_db->table('user')->create([
            'id' => 3,
            'ident' => '',
            'display_name' => 'user3',
            'tenhou_id' => null
        ])->save();
        $this->_db->table('user')->create([
            'id' => 4,
            'ident' => '',
            'display_name' => 'user4',
            'tenhou_id' => null
        ])->save();
    }

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
}
