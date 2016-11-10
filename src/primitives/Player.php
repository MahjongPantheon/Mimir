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
 * Class PlayerPrimitive
 *
 * Low-level model with basic CRUD operations and relations
 * @package Riichi
 */
class PlayerPrimitive extends Primitive
{
    protected static $_table = 'user';

    protected static $_fieldsMapping = [
        'id' => '_id',
        'display_name' => '_displayName',
        'ident' => '_ident',
        'alias' => '_alias',
        'tenhou_id' => '_tenhouId'
    ];

    protected function _getFieldsTransforms()
    {
        return [
            '_id' => $this->_integerTransform(true),
        ];
    }

    /**
     * Local id
     * @var int
     */
    protected $_id;
    /**
     * oauth ident info, for example
     * @var string
     */
    protected $_ident;
    /**
     * alias for text-mode game logs
     * @var string
     */
    protected $_alias;
    /**
     * How to display in state
     * @var string
     */
    protected $_displayName;
    /**
     * Tenhou username (actually not id!)
     * @var string
     */
    protected $_tenhouId;

    /**
     * Find users by local ids (primary key)
     *
     * @param IDb $db
     * @param int[] $ids
     * @throws \Exception
     * @return PlayerPrimitive[]
     */
    public static function findById(IDb $db, $ids)
    {
        return self::_findBy($db, 'id', $ids);
    }

    /**
     * Find users by [oauth] idents (indexed search)
     *
     * @param IDb $db
     * @param int[] $idents
     * @throws \Exception
     * @return PlayerPrimitive[]
     */
    public static function findByIdent(IDb $db, $idents)
    {
        return self::_findBy($db, 'ident', $idents);
    }

    /**
     * Find users by alias (indexed search)
     *
     * @param IDb $db
     * @param int[] $idents
     * @throws \Exception
     * @return PlayerPrimitive[]
     */
    public static function findByAlias(IDb $db, $idents)
    {
        return self::_findBy($db, 'alias', $idents);
    }

    /**
     * Find users by tenhou ids (indexed search)
     *
     * @param IDb $db
     * @param int[] $ids
     * @throws \Exception
     * @return PlayerPrimitive[]
     */
    public static function findByTenhouId(IDb $db, $ids)
    {
        return self::_findBy($db, 'tenhou_id', $ids);
    }

    protected function _create()
    {
        $user = $this->_db->table(self::$_table)->create();
        $success = $this->_save($user);
        if ($success) {
            $this->_id = $this->_db->lastInsertId();
        }

        return $success;
    }

    /**
     * @param string $displayName
     * @return $this
     */
    public function setDisplayName($displayName)
    {
        $this->_displayName = $displayName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->_displayName;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $ident
     * @return $this
     */
    public function setIdent($ident)
    {
        $this->_ident = $ident;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdent()
    {
        return $this->_ident;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->_alias = $alias;
        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->_alias;
    }

    /**
     * @param string $tenhouId
     * @return $this
     */
    public function setTenhouId($tenhouId)
    {
        $this->_tenhouId = $tenhouId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTenhouId()
    {
        return $this->_tenhouId;
    }
}
