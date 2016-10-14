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

use \Idiorm\ORM;

require_once __DIR__ . '/../Primitive.php';

/**
 * Class PlayerPrimitive
 *
 * Low-level model with basic CRUD operations and relations
 * @package Riichi
 */
class PlayerHistoryPrimitive extends Primitive
{
    protected static $_table = 'player_history';

    protected static $_fieldsMapping = [
        'id'         => '_id',
        'user_id'    => '_playerId',
        'session_id' => '_sessionId',
        'event_id'   => '_eventId',
        'rating'     => '_rating'
    ];

    protected function _getFieldsTransforms()
    {
        return [
            '_id' => $this->_nullableIntegerTransform(),
            '_rating' => $this->_floatTransform()
        ];
    }

    /**
     * Local id
     * @var int
     */
    protected $_id;
    /**
     * @var int
     */
    protected $_playerId;
    /**
     * @var PlayerPrimitive
     */
    protected $_player;
    /**
     * @var int
     */
    protected $_sessionId;
    /**
     * @var SessionPrimitive
     */
    protected $_session;
    /**
     * @var int
     */
    protected $_eventId;
    /**
     * @var EventPrimitive
     */
    protected $_event;
    /**
     * @var float
     */
    protected $_rating;

    /**
     * @param IDb $db
     * @param $playerId
     * @param $eventId
     * @return PlayerHistoryPrimitive[]
     */
    public static function findAllByEvent(IDb $db, $playerId, $eventId)
    {
        // todo: optional pagination and sorting

        return self::_findBySeveral($db, [
            'user_id'  => [$playerId],
            'event_id' => [$eventId]
        ]);
    }

    /**
     * @param IDb $db
     * @param $playerId
     * @param $eventId
     * @return PlayerHistoryPrimitive
     */
    public static function findLastByEvent(IDb $db, $playerId, $eventId)
    {
        return self::_findBySeveral($db, [
            'user_id'  => [$playerId],
            'event_id' => [$eventId]
        ], true);
    }

    /**
     * @param IDb $db
     * @param $playerId
     * @param $sessionId
     * @return PlayerHistoryPrimitive
     */
    public static function findBySession(IDb $db, $playerId, $sessionId)
    {
        return self::_findBySeveral($db, [
            'user_id'    => [$playerId],
            'session_id' => [$sessionId]
        ], true); // should be only one or none, getting last is ok
    }

    protected function _create()
    {
        $histItem = $this->_db->table(self::$_table)->create();
        $success = $this->_save($histItem);
        if ($success) {
            $this->_id = $this->_db->lastInsertId();
        }

        return $success;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param \Riichi\EventPrimitive $event
     * @return PlayerHistoryPrimitive
     */
    public function setEvent(EventPrimitive $event)
    {
        $this->_event = $event;
        $this->_eventId = $event->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return \Riichi\EventPrimitive
     */
    public function getEvent()
    {
        if (empty($this->_event)) {
            $this->_event = $this->getSession()->getEvent();
            $this->_eventId = $this->_event->getId();
        }

        return $this->_event;
    }

    /**
     * @return int
     */
    public function getEventId()
    {
        return $this->_eventId;
    }

    /**
     * @param \Riichi\PlayerPrimitive $player
     * @return PlayerHistoryPrimitive
     */
    public function setPlayer(PlayerPrimitive $player)
    {
        $this->_player = $player;
        $this->_playerId = $player->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return \Riichi\PlayerPrimitive
     */
    public function getPlayer()
    {
        if (empty($this->_player)) {
            $foundUsers = PlayerPrimitive::findById($this->_db, [$this->_playerId]);
            if (empty($foundUsers)) {
                throw new EntityNotFoundException("Entity PlayerPrimitive with id#" . $this->_playerId . ' not found in DB');
            }
            $this->_player = $foundUsers[0];
        }

        return $this->_player;
    }

    /**
     * @return int
     */
    public function getPlayerId()
    {
        return $this->_playerId;
    }

    /**
     * @param float $rating
     * @return PlayerHistoryPrimitive
     */
    public function setRating($rating)
    {
        $this->_rating = $rating;
        return $this;
    }

    /**
     * @return float
     */
    public function getRating()
    {
        return $this->_rating;
    }

    /**
     * @param \Riichi\SessionPrimitive $session
     * @return PlayerHistoryPrimitive
     */
    public function setSession(SessionPrimitive $session)
    {
        $this->_session = $session;
        $this->_sessionId = $session->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return \Riichi\SessionPrimitive
     */
    public function getSession()
    {
        if (empty($this->_session)) {
            $foundSessions = SessionPrimitive::findById($this->_db, [$this->_sessionId]);
            if (empty($foundSessions)) {
                throw new EntityNotFoundException("Entity SessionPrimitive with id#" . $this->_sessionId . ' not found in DB');
            }
            $this->_session = $foundSessions[0];
        }

        return $this->_session;
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->_sessionId;
    }
}
