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

require_once __DIR__ . '/../../src/Ruleset.php';
require_once __DIR__ . '/../../src/primitives/SessionResults.php';
require_once __DIR__ . '/../../src/helpers/SessionState.php';
require_once __DIR__ . '/../../src/primitives/Event.php';
require_once __DIR__ . '/../../src/primitives/Session.php';
require_once __DIR__ . '/../../src/primitives/Player.php';
require_once __DIR__ . '/../util/Db.php';

class SessionResultsPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    protected $_db;
    /**
     * @var EventPrimitive
     */
    protected $_event;
    /**
     * @var PlayerPrimitive[]
     */
    protected $_players;
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
            ->setRuleset(Ruleset::instance('jpmlA'));
        $this->_event->save();

        $this->_players = array_map(function ($i) {
            $p = (new PlayerPrimitive($this->_db))
                ->setDisplayName('player' . $i)
                ->setIdent('oauth' . $i)
                ->setTenhouId('tenhou' . $i);
            $p->save();
            return $p;
        }, [1, 2, 3, 4]);

        $this->_session = (new SessionPrimitive($this->_db))
            ->setEvent($this->_event)
            ->setPlayers($this->_players)
            ->setStatus('inprogress');
        $this->_session->save();
    }

    public function testSinglePlayerResults()
    {
        $result = (new SessionResultsPrimitive($this->_db))
            ->setPlayer($this->_players[1])
            ->setSession($this->_session)
            ->calc(
                $this->_session->getEvent()->getRuleset(),
                $this->_session->getCurrentState(),
                $this->_session->getPlayersIds()
            );
        $result->save();

        $this->assertEquals(30000, $result->getScore());
    }

}
