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

require_once __DIR__ . '/../src/Db.php';
require_once __DIR__ . '/../src/primitives/Event.php';
require_once __DIR__ . '/../src/Ruleset.php';
use JsonRPC\Client;

/**
 * Class RealApiTest: integration test suite
 * @package Riichi
 */
class RealApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $_client;

    public function setUp()
    {
        // Init db! Or bunch of PDOExceptions will appeal
        $db = Db::__getCleanTestingInstance();
        $evt = (new EventPrimitive($db))
            ->setRuleset(Ruleset::instance('ema'))
            ->setType('offlime')
            ->setTitle('test')
            ->setDescription('test')
            ->setGameDuration(1); // for timers check
        $evt->save();

        $this->_client = new Client('http://localhost:1349');
    }

    public function testGameConfig()
    {
        $response = $this->_client->execute('getGameConfig', [1]);
        $this->assertEquals(false, $response['withAbortives']);
        $this->assertEquals(30000, $response['startPoints']);
    }

    public function testTimer()
    {
        $response = $this->_client->execute('getTimerState', [1]);
        $this->assertEquals([
            'started' => false,
            'finished' => false,
            'time_remaining' => null
        ], $response);

        $this->assertTrue($this->_client->execute('startTimer', [1]));
        $response = $this->_client->execute('getTimerState', [1]);
        $this->assertTrue($response['started']);
        $this->assertFalse($response['finished']);
        $this->assertTrue($response['time_remaining'] == 60
            || $response['time_remaining'] == 59);

        // TODO: timer is now in integer minutes, investigate how to check it
        // sleep(6); // wait unit timer expires
        // $response = $this->_client->execute('getTimerState', [1]);
        // $this->assertEquals([
        //    'started' => false,
        //    'finished' => true,
        //    'time_remaining' => null
        // ], $response);
    }
}
