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

require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Event.php';
require_once __DIR__ . '/Round.php';
require_once __DIR__ . '/../Primitive.php';
require_once __DIR__ . '/../validators/Round.php';

/**
 * Class MultiRoundPrimitive
 * Special case of round: multi-ron, contains multiple RoundPrimitives
 *
 * Low-level model with basic CRUD operations and relations
 * @package Riichi
 */
class MultiRoundPrimitive extends RoundPrimitive
{
    protected static $_table = '';

    protected static $_fieldsMapping = [];

    protected function _getFieldsTransforms()
    {
        return [];
    }

    public function __construct(IDb $db)
    {
        $this->_db = $db;
    }

    /**
     * @var RoundPrimitive[]
     */
    protected $_rounds = [];

    protected function _create()
    {
        $success = true;
        foreach ($this->_rounds as $round) {
            $success = $success && $round->_create();
        }
        return $success;
    }

    /**
     * @param Db $db
     * @param SessionPrimitive $session
     * @param $roundData
     * @return RoundPrimitive|MultiRoundPrimitive
     */
    public static function createFromData(Db $db, SessionPrimitive $session, $roundData)
    {
        if ($roundData['outcome'] !== 'multiron') {
            return RoundPrimitive::createFromData($db, $session, $roundData);
        }

        RoundsHelper::checkRound($session, $roundData);
        $roundData['session_id'] = $session->getId();
        $roundData['event_id'] = $session->getEventId();
        $roundData['id'] = null;

        $item = new self($db);
        $item->_rounds = array_map(function ($round) use (&$roundData, $db) {
            return (new RoundPrimitive($db))->_restore(array_merge($round, [
                'outcome'    => $roundData['outcome'],
                'multi_ron'  => $roundData['multi_ron'],
                'loser_id'   => $roundData['loser_id'],
                'session_id' => $roundData['session_id'],
                'event_id'   => $roundData['event_id'],
                'id'         => null
            ]));
        }, $roundData['wins']);
        return $item;
    }

    public function save()
    {
        $success = true;
        foreach ($this->_rounds as $round) {
            $success = $success && $round->save();
        }

        return $success;
    }

    /**
     * @return RoundPrimitive[]
     */
    public function rounds()
    {
        return $this->_rounds;
    }

    /**
     * @deprecated
     * For unit testing only
     */
    public function _setRounds($rounds)
    {
        $this->_rounds = $rounds;
        return $this;
    }

    public function setDora($dora)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getDora()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function _setEvent()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getEvent()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getEventId()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setFu($fu)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getFu()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setHan($han)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getHan()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getId()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setKandora($kandora)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getKandora()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setKanuradora($kanuradora)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getKanuradora()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setLoser(PlayerPrimitive $loser)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getLoserId()
    {
        return $this->_rounds[0]->getLoserId();
    }

    public function getLoser()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setMultiRon($multiRon)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getMultiRon()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setOutcome($outcome)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getOutcome()
    {
        return 'multiron';
    }

    public function getRiichiIds()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setRiichiUsers($riichiUsers)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getRiichiUsers()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setRoundIndex($roundIndex)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getRoundIndex()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setSession(SessionPrimitive $session)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getSession()
    {
        return $this->_rounds[0]->getSession();
    }

    public function getSessionId()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getTempaiIds()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setTempaiUsers($tempaiUsers)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getTempaiUsers()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setUradora($uradora)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getUradora()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setWinner(PlayerPrimitive $winner)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getWinner()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getWinnerId()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function setYaku($yaku)
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }

    public function getYaku()
    {
        throw new InvalidParametersException('MultiRound should not be treated as round');
    }
}