<?php
/*  Riichi mahjong API game server
 *  Copyright (C) 2016  player1 and others
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

require_once __DIR__ . '/../../../src/helpers/onlineLog/Parser.php';

/**
 * Replay parser integration test suite
 *
 * Class OnlinelogParserTest
 * @package Riichi
 */
class OnlinelogParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Db
     */
    protected $_db;
    /**
     * @var PlayerPrimitive[]
     */
    protected $_players;
    /**
     * @var EventPrimitive
     */
    protected $_event;
    /**
     * @var SessionPrimitive
     */
    protected $_session;

    public function setUp()
    {
        $this->_db = Db::getCleanInstance();

        $this->_event = (new EventPrimitive($this->_db))
            ->setTitle('title')
            ->setDescription('desc')
            ->setType('online')
            ->setLobbyId('0')
            ->setRuleset(Ruleset::instance('tenhounet'));
        $this->_event->save();

        $this->_players = array_map(function ($i) {
            $p = (new PlayerPrimitive($this->_db))
                ->setDisplayName('player' . $i)
                ->setAlias('player' . $i)
                ->setIdent('oauth' . $i)
                ->setTenhouId('player' . $i);
            $p->save();
            $this->_event->registerPlayer($p)->save();
            return $p;
        }, [1, 2, 3, 4]);

        $this->_session = (new SessionPrimitive($this->_db))
            ->setEvent($this->_event)
            ->setPlayers($this->_players)
            ->setStatus('inprogress')
            ->setReplayHash('')
            ->setOrigLink('');
        $this->_session->save();
    }

    public function testParseUsualGame()
    {
        $content = file_get_contents(__DIR__ . '/testdata/usual.xml');
        list($success, $results/*, $debug*/) = (new OnlineParser($this->_db))
            ->parseToSession($this->_session, $content);

        $this->assertTrue($success);
        $this->assertEquals(
            $results,
            $this->_session->getCurrentState()->getScores()
        );
    }

    public function testParseYakumanDoubleRon()
    {
        $content = file_get_contents(__DIR__ . '/testdata/doubleron.xml');
        list($success, $results/*, $debug*/) = (new OnlineParser($this->_db))
            ->parseToSession($this->_session, $content);

        $this->assertTrue($success);
        $this->assertEquals(
            $results,
            $this->_session->getCurrentState()->getScores()
        );
    }

    public function testParseTripleYakuman()
    {
        $content = file_get_contents(__DIR__ . '/testdata/tripleyakuman.xml');
        list($success, $results/*, $debug*/) = (new OnlineParser($this->_db))
            ->parseToSession($this->_session, $content);

        $this->assertTrue($success);
        $this->assertEquals(
            $results,
            $this->_session->getCurrentState()->getScores()
        );
    }
}
