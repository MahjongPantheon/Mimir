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

require_once __DIR__ . '/../Model.php';

class Event extends Model
{
    protected static $_table = 'event';

    /**
     * Local id
     * @var int
     */
    protected $_id;
    /**
     * Event title
     * @var string
     */
    protected $_title;
    /**
     * Event description
     * @var string
     */
    protected $_description;
    /**
     * Start date and time
     * @var string
     */
    protected $_startTime;
    /**
     * End date and time
     * @var string
     */
    protected $_endTime;
    /**
     * Owner organisation
     * @var Formation|null
     */
    protected $_ownerFormation = null;
    /**
     * Owner organisation id
     * @var int
     */
    protected $_ownerFormationId;
    /**
     * Owner player
     * @var Player|null
     */
    protected $_ownerUser = null;
    /**
     * Owner player id
     * @var int
     */
    protected $_ownerUserId;
    /**
     * online/offline
     * tournament/local rating
     * @var string
     */
    protected $_type;
    /**
     * Tenhou lobby id (for online events)
     * @var string
     */
    protected $_lobbyId;
    /**
     * Rules to apply to the event
     * @var mixed
     */
    protected $_ruleset;

    public function __construct(Db $db)
    {
        parent::__construct($db);
        $this->_startTime = date('Y-m-d H:i:s'); // may be actualized on restore
    }

    /**
     * Find events by local ids (primary key)
     *
     * @param IDb $db
     * @param int[] $ids
     * @throws \Exception
     * @return Event[]
     */
    public static function findById(IDb $db, $ids)
    {
        return self::_findBy($db, 'id', $ids);
    }

    /**
     * Find events by lobby (indexed search)
     *
     * @param IDb $db
     * @param string[] $lobbyList
     * @throws \Exception
     * @return Event[]
     */
    public static function findByState(IDb $db, $lobbyList)
    {
        // TODO: Finished games are likely to be too much. Make pagination here.
        return self::_findBy($db, 'lobby_id', $lobbyList);
    }

    /**
     * Save event instance to db
     * @return bool success
     */
    public function save()
    {
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
            'title'             => $this->_title,
            'description'       => $this->_description,
            'start_time'        => $this->_startTime,
            'end_time'          => $this->_endTime,
            'owner_formation'   => $this->_ownerFormationId,
            'owner_user'        => $this->_ownerUserId,
            'type'              => $this->_type,
            'lobby_id'          => $this->_lobbyId,
            'ruleset'           => $this->_ruleset
        ])->save();
    }

    protected function _restore($data)
    {
        $this->_id = $data['id'];
        $this->_title = $data['title'];
        $this->_description = $data['description'];
        $this->_startTime = $data['start_time'];
        $this->_endTime = $data['end_time'];
        $this->_ownerFormationId = $data['owner_formation'];
        $this->_ownerUserId = $data['owner_user'];
        $this->_type = $data['type'];
        $this->_lobbyId = $data['lobby_id'];
        $this->_ruleset = $data['ruleset'];
        return $this;
    }

    /**
     * @param string $description
     * @return Event
     */
    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param string $endTime
     * @return Event
     */
    public function setEndTime($endTime)
    {
        $this->_endTime = $endTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getEndTime()
    {
        return $this->_endTime;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $lobbyId
     * @return Event
     */
    public function setLobbyId($lobbyId)
    {
        $this->_lobbyId = $lobbyId;
        return $this;
    }

    /**
     * @return string
     */
    public function getLobbyId()
    {
        return $this->_lobbyId;
    }

    /**
     * @param null|\Riichi\Formation $ownerFormation
     * @return Event
     */
    public function setOwnerFormation(Formation $ownerFormation)
    {
        $this->_ownerFormation = $ownerFormation;
        $this->_ownerFormationId = $ownerFormation->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return null|\Riichi\Formation
     */
    public function getOwnerFormation()
    {
        if (!$this->_ownerFormation) {
            $foundFormations = Formation::findById($this->_db, [$this->_ownerFormationId]);
            if (empty($foundFormations)) {
                throw new EntityNotFoundException("Entity Formation with id#" . $this->_ownerFormationId . ' not found in DB');
            }
            $this->_ownerFormation = $foundFormations[0];
        }
        return $this->_ownerFormation;
    }

    /**
     * @return int
     */
    public function getOwnerFormationId()
    {
        return $this->_ownerFormationId;
    }

    /**
     * @param null|\Riichi\Player $ownerUser
     * @return Event
     */
    public function setOwnerUser($ownerUser)
    {
        $this->_ownerUser = $ownerUser;
        $this->_ownerUserId = $ownerUser->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return null|\Riichi\Player
     */
    public function getOwnerUser()
    {
        if (!$this->_ownerUser) {
            $foundUsers = Player::findById($this->_db, [$this->_ownerUserId]);
            if (empty($foundUsers)) {
                throw new EntityNotFoundException("Entity Player with id#" . $this->_ownerUserId . ' not found in DB');
            }
            $this->_ownerUser = $foundUsers[0];
        }
        return $this->_ownerUser;
    }

    /**
     * @return int
     */
    public function getOwnerUserId()
    {
        return $this->_ownerUserId;
    }

    /**
     * @param mixed $ruleset
     * @return Event
     */
    public function setRuleset($ruleset)
    {
        $this->_ruleset = $ruleset;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRuleset()
    {
        return $this->_ruleset;
    }

    /**
     * @param string $startTime
     * @return Event
     */
    public function setStartTime($startTime)
    {
        $this->_startTime = $startTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getStartTime()
    {
        return $this->_startTime;
    }

    /**
     * @param string $title
     * @return Event
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param string $type
     * @return Event
     */
    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
}
