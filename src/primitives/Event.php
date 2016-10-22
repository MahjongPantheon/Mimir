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

use \Idiorm\ORM;

require_once __DIR__ . '/../Primitive.php';

/**
 * Class EventPrimitive
 *
 * Low-level model with basic CRUD operations and relations
 * @package Riichi
 */
class EventPrimitive extends Primitive
{
    protected static $_table = 'event';

    protected static $_fieldsMapping = [
        'id'                => '_id',
        'title'             => '_title',
        'description'       => '_description',
        'start_time'        => '_startTime',
        'end_time'          => '_endTime',
        'owner_formation'   => '_ownerFormationId',
        'owner_user'        => '_ownerUserId',
        'type'              => '_type',
        'lobby_id'          => '_lobbyId',
        'ruleset'           => '_ruleset',
    ];

    protected function _getFieldsTransforms()
    {
        return [
            '_ownerFormationId'   => $this->_integerTransform(),
            '_ownerUserId'        => $this->_integerTransform(),
            '_id'                 => $this->_nullableIntegerTransform(),
            '_ruleset'            => [
                'serialize' => function (Ruleset $rules) {
                    return $rules->title();
                },
                'deserialize' => function ($rulesId) {
                    return Ruleset::instance($rulesId);
                }
            ]
        ];
    }

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
     * @var FormationPrimitive|null
     */
    protected $_ownerFormation = null;
    /**
     * Owner organisation id
     * @var int
     */
    protected $_ownerFormationId;
    /**
     * Owner player
     * @var PlayerPrimitive|null
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
     * @var Ruleset
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
     * @return EventPrimitive[]
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
     * @return EventPrimitive[]
     */
    public static function findByLobby(IDb $db, $lobbyList)
    {
        // TODO: All games in lobby are likely to be too much. Make pagination here.
        return self::_findBy($db, 'lobby_id', $lobbyList);
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

    /**
     * @param string $description
     * @return EventPrimitive
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
     * @return EventPrimitive
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
     * @return Ruleset
     */
    public function getRuleset()
    {
        return $this->_ruleset;
    }

    /**
     * @param Ruleset $rules
     * @return EventPrimitive
     */
    public function setRuleset(Ruleset $rules)
    {
        $this->_ruleset = $rules;
        return $this;
    }

    /**
     * @param string $lobbyId
     * @return EventPrimitive
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
     * @param null|\Riichi\FormationPrimitive $ownerFormation
     * @return EventPrimitive
     */
    public function setOwnerFormation(FormationPrimitive $ownerFormation)
    {
        $this->_ownerFormation = $ownerFormation;
        $this->_ownerFormationId = $ownerFormation->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return null|\Riichi\FormationPrimitive
     */
    public function getOwnerFormation()
    {
        if (!$this->_ownerFormation) {
            $foundFormations = FormationPrimitive::findById($this->_db, [$this->_ownerFormationId]);
            if (empty($foundFormations)) {
                throw new EntityNotFoundException("Entity FormationPrimitive with id#" . $this->_ownerFormationId . ' not found in DB');
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
     * @param null|\Riichi\PlayerPrimitive $ownerUser
     * @return EventPrimitive
     */
    public function setOwnerUser(PlayerPrimitive $ownerUser)
    {
        $this->_ownerUser = $ownerUser;
        $this->_ownerUserId = $ownerUser->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return null|\Riichi\PlayerPrimitive
     */
    public function getOwnerUser()
    {
        if (!$this->_ownerUser) {
            $foundUsers = PlayerPrimitive::findById($this->_db, [$this->_ownerUserId]);
            if (empty($foundUsers)) {
                throw new EntityNotFoundException("Entity PlayerPrimitive with id#" . $this->_ownerUserId . ' not found in DB');
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
     * @param string $startTime
     * @return EventPrimitive
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
     * @return EventPrimitive
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
     * @return EventPrimitive
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
