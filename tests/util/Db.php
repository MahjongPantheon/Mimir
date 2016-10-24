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

require __DIR__ . '/../../vendor/heilage-nsk/idiorm/src/idiorm.php';
require __DIR__ . '/../../src/interfaces/IDb.php';

/**
 * Class Db
 * @package Riichi
 *
 * Simple wrapper around IdiORM to encapsulate it's configuration
 * Test suite mock package, uses only sqlite w/ hardcoded path
 *
 * Class interface should be perfectly compatible with real Db class
 */
class Db implements IDb
{
    static protected $instance = null;

    public static function getCleanInstance()
    {
        $db = __DIR__ . '/../data/db.sqlite';
        if (!is_dir(dirname($db))) {
            mkdir(dirname($db));
        }

        shell_exec('cd ' . __DIR__ . '/../../ && SQLITE_FILE=' . $db . ' make init_sqlite_nointeractive');

        ORM::configure([
            'connection_string' => 'sqlite:' . $db,
            'credentials' => [
                'username' => '',
                'password' => ''
            ]
        ]);

        if (self::$instance === null) {
            self::$instance = new Db();
        }

        return self::$instance;
    }

    /**
     * General entry point for all queries
     *
     * @param $tableName
     * @return \Idiorm\ORM
     */
    public function table($tableName)
    {
        return ORM::forTable($tableName);
    }

    /**
     * @return int|string
     */
    public function lastInsertId()
    {
        ORM::rawExecute('SELECT last_insert_rowid()');
        return ORM::getLastStatement()->fetchColumn();
    }

    /**
     * @param $table
     * @param $data
     * @return string
     */
    public function upsertQuery($table, $data)
    {
        foreach ($data as $k => $v) {
            $data[$k] = intval($v); // Maybe use PDO::quote here in future
        }

        $values = implode(', ', array_values($data));
        $fields = implode(', ', array_map(function($field) {
            return '"' . $field . '"';
        }, array_keys($data)));

        return ORM::rawExecute("
            REPLACE INTO {$table} ({$fields}) VALUES ({$values});
        ");
    }
}
