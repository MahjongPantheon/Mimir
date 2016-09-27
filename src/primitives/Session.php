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

require_once __DIR__ . '/../exceptions/EntityNotFound.php';
require_once __DIR__ . '/../exceptions/InvalidParameters.php';
require_once __DIR__ . '/../Primitive.php';

class SessionPrimitive extends Primitive
{
    protected static $_table = 'session';

    /**
     * Local id
     * @var int
     */
    protected $_id;

    /**
     * @var int
     */
    protected $_eventId;
    /**
     *
     * @var EventPrimitive
     */
    protected $_event;

    /**
     * Client-known hash to identify game
     * @var string
     */
    protected $_representationalHash;

    /**
     * tenhou game hash, for deduplication
     * @var string
     */
    protected $_replayHash;

    /**
     * original tenhou game link, for access to replay
     * @var string
     */
    protected $_origLink;

    /**
     * Timestamp
     * @var string
     */
    protected $_playDate;

    /**
     * comma-separated ordered list of player ids, east to north.
     * @var string
     */
    protected $_playersIds = [];

    /**
     * Ordered list of player entities
     * @var PlayerPrimitive[]
     */
    protected $_players;

    /**
     * planned / inprogress / finished
     * @var string
     */
    protected $_state;

    public function __construct(Db $db)
    {
        parent::__construct($db);
        $this->_playDate = date('Y-m-d H:i:s'); // may be actualized on restore
    }

    /**
     * Find sessions by local ids (primary key)
     *
     * @param IDb $db
     * @param int[] $ids
     * @throws \Exception
     * @return SessionPrimitive[]
     */
    public static function findById(IDb $db, $ids)
    {
        return self::_findBy($db, 'id', $ids);
    }

    /**
     * Find sessions by replay hash list (indexed search)
     *
     * @param IDb $db
     * @param string[] $replayIds
     * @throws \Exception
     * @return SessionPrimitive[]
     */
    public static function findByReplayHash(IDb $db, $replayIds)
    {
        return self::_findBy($db, 'replay_hash', $replayIds);
    }

    /**
     * Find sessions by client-aware hash list (indexed search)
     *
     * @param IDb $db
     * @param string[] $hashList
     * @throws \Exception
     * @return SessionPrimitive[]
     */
    public static function findByRepresentationalHash(IDb $db, $hashList)
    {
        return self::_findBy($db, 'representational_hash', $hashList);
    }

    /**
     * Find sessions by state (indexed search)
     *
     * @param IDb $db
     * @param string[] $stateList
     * @throws \Exception
     * @return SessionPrimitive[]
     */
    public static function findByState(IDb $db, $stateList)
    {
        // TODO: Finished games are likely to be too much. Make pagination here.
        return self::_findBy($db, 'state', $stateList);
    }

    /**
     * Save session instance to db
     * @return bool success
     */
    public function save()
    {
        $this->_representationalHash = sha1(implode(',', $this->_playersIds) . $this->_playDate);
        $session = $this->_db->table(self::$_table)->findOne($this->_id);
        return ($session ? $this->_save($session) : $this->_create());
    }

    protected function _create()
    {
        $session = $this->_db->table(self::$_table)->create();
        $success = $this->_save($session);
        if ($success) {
            $this->_id = $this->_db->lastInsertId();
        }

        return $success;
    }

    protected function _save(ORM $session)
    {
        return $session->set([
            'event_id'              => $this->_eventId,
            'representational_hash' => $this->_representationalHash,
            'replay_hash'           => $this->_replayHash,
            'orig_link'             => $this->_origLink,
            'play_date'             => $this->_playDate,
            'players'               => implode(',', $this->_playersIds),
            'state'                 => $this->_state
        ])->save();
    }

    protected function _restore($data)
    {
        $this->_id = $data['id'];
        $this->_eventId = $data['event_id'];
        $this->_representationalHash = $data['representational_hash'];
        $this->_replayHash = $data['replay_hash'];
        $this->_origLink = $data['orig_link'];
        $this->_playDate = $data['play_date'];
        $this->_playersIds = explode(',', $data['players']);
        $this->_state = $data['state'];
        return $this;
    }

    /**
     * @param \Riichi\EventPrimitive $event
     * @return $this
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
        if (!$this->_event) {
            $foundEvents = EventPrimitive::findById($this->_db, [$this->_eventId]);
            if (empty($foundEvents)) {
                throw new EntityNotFoundException("Entity EventPrimitive with id#" . $this->_eventId . ' not found in DB');
            }
            $this->_event = $foundEvents[0];
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
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $origLink
     * @return $this
     */
    public function setOrigLink($origLink)
    {
        $this->_origLink = $origLink;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrigLink()
    {
        return $this->_origLink;
    }

    /**
     * @return string
     */
    public function getPlayDate()
    {
        return $this->_playDate;
    }

    /**
     * @throws InvalidParametersException
     * @param \Riichi\PlayerPrimitive[] $players
     * @return $this
     */
    public function setPlayers($players)
    {
        $this->_players = [];
        $ids = [];
        foreach ($players as $player) {
            if (!($player instanceof PlayerPrimitive)) {
                throw new InvalidParametersException('All array elements must be PlayerPrimitive entities');
            }

            $this->_players []= $player;
            $ids []= $player->getId();
        }

        $this->_playersIds = $ids;
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return \Riichi\PlayerPrimitive[]
     */
    public function getPlayers()
    {
        if (empty($this->_players)) {
            $idArray = explode(',', $this->_playersIds);
            $this->_players = PlayerPrimitive::findById(
                $this->_db,
                $idArray
            );
            if (empty($this->_players) || count($this->_players) !== count($idArray)) {
                $this->_players = null;
                throw new EntityNotFoundException("Not all players were found in DB (among id#" . $this->_playersIds);
            }
        }
        return $this->_players;
    }

    /**
     * @return string
     */
    public function getPlayersIds()
    {
        return $this->_playersIds;
    }

    /**
     * @param string $replayHash
     * @return $this
     */
    public function setReplayHash($replayHash)
    {
        $this->_replayHash = $replayHash;
        return $this;
    }

    /**
     * @return string
     */
    public function getReplayHash()
    {
        return $this->_replayHash;
    }

    /**
     * Client-known hash to find games
     *
     * Warning! This will be empty for all new Sessions until they are saved!
     * @return string
     */
    public function getRepresentationalHash()
    {
        return $this->_representationalHash;
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->_state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->_state;
    }
}
