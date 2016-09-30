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

require_once __DIR__ . '/Date.php';

use Monolog\Logger;
use Idiorm\ORM;

abstract class Primitive
{
    /**
     * @var string
     */
    protected static $_table;

    /**
     * @var array
     */
    protected static $_fieldsMapping = [];

    /**
     * Default csv array serializer
     *
     * @param array $obj
     * @return string
     */
    protected function _serializeCsv($obj)
    {
        return $obj ? implode(',', $obj) : '';
    }

    /**
     * Default csv array deserializer
     *
     * @param string $str
     * @return array
     */
    protected function _deserializeCsv($str)
    {
        return $str ? explode(',', $str) : [];
    }

    /**
     * Default csv array transformer
     * @return array
     */
    protected function _csvTransform()
    {
        return [
            'serialize' => [$this, '_serializeCsv'],
            'deserialize' => [$this, '_deserializeCsv']
        ];
    }

    /**
     * Default integer cast transform
     * @return array
     */
    protected function _integerTransform()
    {
        return [
            'serialize' => function ($obj) {
                return (int)$obj;
            }
        ];
    }

    /**
     * Default integer cast transform
     * Return null if empty
     * @return array
     */
    protected function _nullableIntegerTransform()
    {
        return [
            'serialize' => function ($obj) {
                return $obj ? (int)$obj : null;
            }
        ];
    }

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

        if (empty(static::$_fieldsMapping)) {
            throw new \Exception('ORM field mapping must be set!');
        }
    }

    /**
     * Save instance to db
     * @return bool success
     */
    public function save()
    {
        $id = $this->getId();
        if (empty($id)) {
            return $this->_create();
        }

        $instance = $this->_db->table(static::$_table)->findOne($id);
        return ($instance ? $this->_save($instance) : $this->_create());
    }

    /**
     * Update instance in db
     * @param ORM $instance
     * @return bool
     */
    protected function _save(ORM $instance)
    {
        $fieldsTransform = $this->_getFieldsTransforms();

        foreach (static::$_fieldsMapping as $dst => $src) {
            $instance->set(
                $dst,
                empty($fieldsTransform[$src]['serialize'])
                    ? $this->$src
                    : call_user_func($fieldsTransform[$src]['serialize'], $this->$src)
            );
        }

        return $instance->save();
    }

    /**
     * Create instance to db
     * @return mixed
     */
    abstract protected function _create();

    abstract public function getId();

    /**
     * @overrideMe
     * @return array
     */
    protected function _getFieldsTransforms()
    {
        return [];
    }

    /**
     * @param $data
     * @return $this
     */
    protected function _restore($data)
    {
        $fieldsTransform = $this->_getFieldsTransforms();

        foreach (static::$_fieldsMapping as $src => $dst) {
            $this->$dst = empty($fieldsTransform[$dst]['deserialize'])
                ? empty($data[$src]) ? '' : $data[$src]
                : call_user_func(
                    $fieldsTransform[$dst]['deserialize'],
                    empty($data[$src]) ? '' : $data[$src]
                );
        }

        return $this;
    }

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
     * @return PlayerPrimitive[]
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

        return array_map(function ($data) use ($db) {
            return self::_recreateInstance($db, $data);
        }, $result);
    }
}
