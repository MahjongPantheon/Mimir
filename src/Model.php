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

use Monolog\Logger;

abstract class Model
{
    protected static $_table;

    /**
     * @var Db
     */
    protected $_db;

    public function __construct(IDb $db)
    {
        $this->_db = $db;
        if (empty(static::$_table)) {
            throw new \Exception('Table name must be set!');
        }
    }

    abstract public function save();

    abstract protected function _restore($data);

    protected static function _recreateInstance(IDb $db, $data)
    {
        /** @var Model $instance */
        $instance = new static($db);
        return $instance->_restore($data);
    }

    /**
     * Find items by indexed search
     *
     * @param IDb $db
     * @param string $key
     * @param array $identifiers
     * @throws \Exception
     * @return Player[]
     */
    protected static function _findBy(IDb $db, $key, $identifiers)
    {
        if (!is_array($identifiers)) {
            throw new \Exception("Identifiers should be an array in search by $key");
        }

        $result = $db->table(static::$_table)->whereIn($key, $identifiers)->findArray();
        if (empty($result)) {
            return [];
        }

        return array_map(function($data) use ($db) {
            return self::_recreateInstance($db, $data);
        }, $result);
    }
}
