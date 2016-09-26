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

/**
 * Class Formation
 * Represents any organisation, club, association of players, etc
 * @package Riichi
 */
class Formation extends Model
{
    protected static $_table = 'formation';

    // TODO! many-to-many relation here!

    /**
     * Local id
     * @var int
     */
    protected $_id;
    /**
     * Formation title
     * @var string
     */
    protected $_title;
    /**
     * Formation location
     * @var string
     */
    protected $_city;
    /**
     * Formation description
     * @var string
     */
    protected $_description;
    /**
     * Logo image local URL
     * @var string
     */
    protected $_logo;
    /**
     * Contact info
     * @var string
     */
    protected $_contactInfo;
    /**
     * Owner player
     * @var Player|null
     */
    protected $_primaryOwner = null;
    /**
     * Owner player id
     * @var int
     */
    protected $_primaryOwnerId;

    /**
     * Find formations by local ids (primary key)
     *
     * @param IDb $db
     * @param int[] $ids
     * @throws \Exception
     * @return Formation[]
     */
    public static function findById(IDb $db, $ids)
    {
        return self::_findBy($db, 'id', $ids);
    }

    /**
     * Save formation instance to db
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
            'city'              => $this->_city,
            'logo'              => $this->_logo,
            'contact_info'      => $this->_contactInfo,
            'primary_owner'     => $this->_primaryOwnerId
        ])->save();
    }

    protected function _restore($data)
    {
        $this->_id = $data['id'];
        $this->_title = $data['title'];
        $this->_description = $data['description'];
        $this->_city = $data['city'];
        $this->_logo = $data['logo'];
        $this->_contactInfo = $data['contact_info'];
        $this->_primaryOwnerId = $data['primary_owner'];
        return $this;
    }

    /**
     * @param string $description
     * @return Formation
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
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param null|\Riichi\Player $owner
     * @return Formation
     */
    public function setPrimaryOwner($owner)
    {
        $this->_primaryOwner = $owner;
        $this->_primaryOwnerId = $owner->getId();
        return $this;
    }

    /**
     * @throws EntityNotFoundException
     * @return null|\Riichi\Player
     */
    public function getPrimaryOwner()
    {
        if (!$this->_primaryOwner) {
            $foundUsers = Player::findById($this->_db, [$this->_primaryOwnerId]);
            if (empty($foundUsers)) {
                throw new EntityNotFoundException("Entity Player with id#" . $this->_primaryOwnerId . ' not found in DB');
            }
            $this->_primaryOwner = $foundUsers[0];
        }
        return $this->_primaryOwner;
    }

    /**
     * @return int
     */
    public function getOwnerUserId()
    {
        return $this->_primaryOwnerId;
    }

    /**
     * @param string $title
     * @return Formation
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
     * @param string $logo
     * @return Formation
     */
    public function setLogo($logo)
    {
        $this->_logo = $logo;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->_logo;
    }

    /**
     * @return int
     */
    public function getPrimaryOwnerId()
    {
        return $this->_primaryOwnerId;
    }

    /**
     * @param string $contactInfo
     * @return Formation
     */
    public function setContactInfo($contactInfo)
    {
        $this->_contactInfo = $contactInfo;
        return $this;
    }

    /**
     * @return string
     */
    public function getContactInfo()
    {
        return $this->_contactInfo;
    }

    /**
     * @param string $city
     * @return Formation
     */
    public function setCity($city)
    {
        $this->_city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->_city;
    }
}
