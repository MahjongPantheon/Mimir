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
            ->setType('offline')
            ->setTitle('test')
            ->setDescription('test')
            ->setGameDuration(1); // for timers check
        $evt->save();

        $this->_client = new Client('http://localhost:1349');
        $this->_client->getHttpClient()->withHeaders(['X-Auth-Token: 198vdsh904hfbnkjv98whb2iusvd98b29bsdv98svbr9wghj']);
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

    public function testDryRunMultiron()
    {
        // registration and enrollment boilerplate...
        $this->_client->execute('addPlayer', ['p1', 'p1', 'player1', null]);
        $this->_client->execute('addPlayer', ['p2', 'p2', 'player2', null]);
        $this->_client->execute('addPlayer', ['p3', 'p3', 'player3', null]);
        $this->_client->execute('addPlayer', ['p4', 'p4', 'player4', null]);

        $pin1 = $this->_client->execute('enrollPlayer', [1, 1]);
        $pin2 = $this->_client->execute('enrollPlayer', [2, 1]);
        $pin3 = $this->_client->execute('enrollPlayer', [3, 1]);
        $pin4 = $this->_client->execute('enrollPlayer', [4, 1]);

        $this->_client->execute('registerPlayer', [$pin1]);
        $this->_client->execute('registerPlayer', [$pin2]);
        $this->_client->execute('registerPlayer', [$pin3]);
        $this->_client->execute('registerPlayer', [$pin4]);

        $hashcode = $this->_client->execute('startGame', [1, [1, 2, 3, 4]]);

        $data = [
            "round_index" => 1,
            "honba" => 0,
            "outcome" => "multiron",
            "loser_id" => 1,
            "multi_ron" => 2,
            "wins" => [
                [
                    "riichi" => "",
                    "winner_id" => 2,
                    "han" => 2,
                    "fu" => 30,
                    "dora" => 0,
                    "uradora" => 0,
                    "kandora" => 0,
                    "kanuradora" => 0,
                    "yaku" => "23,8"
                ], [
                    "riichi" => "",
                    "winner_id" => 3,
                    "han" => 3,
                    "fu" => 30,
                    "dora" => 0,
                    "uradora" => 0,
                    "kandora" => 0,
                    "kanuradora" => 0,
                    "yaku" => "15"
                ]
            ]
        ];

        $expectedOutput = [
            'dealer' => 2,
            'round' => 2,
            'riichi' => 0,
            'honba' => 0,
            'scores' => [
                1 => 24100,
                2 => 32000,
                3 => 33900,
                4 => 30000
            ],
            'payments' => [
                'direct' => [
                    '2<-1' => 2000,
                    '3<-1' => 3900
                ],
                'riichi' => [
                    '2<-' => 0,
                    '3<-' => 0
                ],
                'honba' => [
                    '2<-1' => 0,
                    '3<-1' => 0
                ]
            ]
        ];

        $dryRunData = $this->_client->execute('addRound', [$hashcode, $data, true]);
        $this->assertEquals($expectedOutput, $dryRunData);
    }

    public function testGetLastRoundInfo()
    {
        /*
         * TODO:
         * 1. create all stuff
         * 2. make dry run of round
         * 3. add round (ron)
         * 4. Query lastRound
         * 5. Check that dry run results match last round results
         * 6. Same for tsumo, draw, abort, chombo, multiron
         */
    }
}
