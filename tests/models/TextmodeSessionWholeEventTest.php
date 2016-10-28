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

require_once __DIR__ . '/../../src/Ruleset.php';
require_once __DIR__ . '/../../src/models/TextmodeSession.php';
require_once __DIR__ . '/../../src/models/PlayerStat.php';
require_once __DIR__ . '/../../src/primitives/Player.php';
require_once __DIR__ . '/../../src/primitives/Event.php';
require_once __DIR__ . '/../util/Db.php';

use \JsonSchema\Validator;

/**
 * Class SessionTest: integration test suite
 * @package Riichi
 */
class TextmodeSessionWholeEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Db
     */
    protected $_db;
    /**
     * @var EventPrimitive
     */
    protected $_event;

    public function testMakeTournament()
    {
        $playerNames = array_filter(preg_split('#\s#is', file_get_contents(__DIR__ . '/testdata/players.txt')));
        $games = explode("\n\n\n", file_get_contents(__DIR__ . '/testdata/games.txt'));

        $this->_db = Db::getCleanInstance();
        $this->_event = (new EventPrimitive($this->_db))
            ->setTitle('title')
            ->setDescription('desc')
            ->setType('offline')
            ->setRuleset(Ruleset::instance('ema'));
        $this->_event->save();

        $players = array_map(function ($id) {
            $p = (new PlayerPrimitive($this->_db))
                ->setDisplayName($id)
                ->setAlias($id)
                ->setIdent($id)
                ->setTenhouId($id);
            $p->save();
            return $p;
        }, $playerNames);

        $model = new TextmodeSessionModel($this->_db);

        foreach ($games as $log) {
            $model->addGame($this->_event->getId(), $log);
        }
        // no exceptions - ok!

        // Make some stats and check them
        $statModel = new PlayerStatModel($this->_db);
        $stats = $statModel->getStats($this->_event->getId(), '10');
        $this->assertEquals(8 + 1, count($stats['rating_history'])); // initial + 8 games
        $this->assertEquals(8, count($stats['score_history']));
        $this->assertGreaterThan(12, count($stats['players_info']));
        $this->assertEquals(8, array_sum($stats['places_summary']));

        // check schema
        $validator = new Validator();
        $schema = json_decode(file_get_contents(__DIR__ . '/../../src/validators/playerStatSchema.json'));
        $validator->check(json_decode(json_encode($stats)), $schema);
        $this->assertEquals(true, $validator->isValid(),
            implode("", array_map(function($error) {
                return sprintf("[%s] %s\n", $error['property'], $error['message']);
            }, $validator->getErrors()))
        );
        $this->assertEquals([], $validator->getErrors());
    }
}
