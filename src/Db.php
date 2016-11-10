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

require_once __DIR__ . '/../vendor/heilage-nsk/idiorm/src/idiorm.php';
require_once __DIR__ . '/../src/interfaces/IDb.php';
require_once __DIR__ . '/../src/Config.php';

/**
 * Class Db
 * @package Riichi
 *
 * Simple wrapper around IdiORM to encapsulate it's configuration
 */
class Db implements IDb
{
    /**
     * @var int instances counter
     */
    static protected $_ctr = 0;

    protected $_connString;

    public function __construct(Config $cfg)
    {
        self::$_ctr++;
        if (self::$_ctr > 1) {
            trigger_error(
                "Using more than single instance of DB is generally not recommended, " . PHP_EOL .
                "as it uses IdiORM inside, which has static configuration! Current \n" . PHP_EOL .
                "DB settings were applied to all instances - this may be not what you want!",
                E_USER_WARNING
            );
        }

        $this->_connString = $cfg->getDbConnectionString();
        $credentials = $cfg->getDbConnectionCredentials();

        ORM::configure($this->_connString);
        if (!empty($credentials)) {
            ORM::configure($credentials); // should pass username and password
        }

        if (strpos($this->_connString, 'mysql') === 0) { // force encoding for mysql
            ORM::configure('driver_options', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
        }
    }

    /**
     * General entry point for all queries
     *
     * @param $tableName
     * @throws \Exception
     * @return \Idiorm\ORM
     */
    public function table($tableName)
    {
        return ORM::forTable($tableName);
    }

    public function debug()
    {
        return [
            'LAST_QUERY' => ORM::getLastStatement()->queryString,
            'ERROR_INFO' => ORM::getLastStatement()->errorInfo()
        ];
    }

    public function lastInsertId()
    {
        switch (true) {
            case strpos($this->_connString, 'mysql') === 0:
                ORM::rawExecute('SELECT LAST_INSERT_ID()');
                return ORM::getLastStatement()->fetchColumn();
                break;
            case strpos($this->_connString, 'pgsql') === 0:
                ORM::rawExecute('SELECT LASTVAL()');
                return ORM::getLastStatement()->fetchColumn();
                break;
            case strpos($this->_connString, 'sqlite') === 0:
                ORM::rawExecute('SELECT last_insert_rowid()');
                return ORM::getLastStatement()->fetchColumn();
                break;
            default:
                throw new \Exception(
                    'Sorry, your DBMS is not supported. You may want to try implementing ' .
                    'last_insert_id function for your DB in place where this exception thrown from. ' .
                    'Check it out, this might be enough to make it work!'
                );
        }
    }

    /**
     * Basic upsert.
     * All fields are casted to integer for safer operations.
     * This functionality is used for many-to-many relations,
     * so integer should be enough.
     *
     * This should not be used for external data processing.
     * May have some vulnerabilities on field names escaping.
     *
     * Warning:
     * Don't touch this crap until you totally know what are you doing :)
     *
     * @param $table
     * @param $data [ [ field => value, field2 => value2 ], [ ... ] ] - nested arrays should be monomorphic
     * @throws \Exception
     * @return boolean
     */
    public function upsertQuery($table, $data)
    {
        $data = array_map(function ($dataset) {
            foreach ($dataset as $k => $v) {
                $dataset[$k] = intval($v); // Maybe use PDO::quote here in future
            }
            return $dataset;
        }, $data);

        switch (true) {
            case strpos($this->_connString, 'mysql') === 0:
                $fields = array_map(function ($field) {
                    return '`' . $field . '`';
                }, array_keys(reset($data)));

                $values = '(' . implode('), (', array_map(function ($dataset) {
                    return implode(', ', array_values($dataset));
                }, $data)) . ')';

                $assignments = implode(', ', array_map(function ($field) {
                    return $field . '=VALUES(' . $field . ')';
                }, $fields));

                $fields = implode(', ', $fields);

                return ORM::rawExecute("
                    INSERT INTO {$table} ({$fields}) VALUES {$values}
                    ON DUPLICATE KEY UPDATE {$assignments}
                ");
                break;
            case strpos($this->_connString, 'pgsql') === 0:
                $fields = array_map(function ($field) {
                    return '"' . $field . '"';
                }, array_keys(reset($data)));

                $values = '(' . implode('), (', array_map(function ($dataset) {
                    return implode(', ', array_values($dataset));
                }, $data)) . ')';

                $assignments = implode(', ', array_map(function ($field) {
                    return $field . '= excluded.' . $field;
                }, $fields));

                $fields = implode(', ', $fields);

                // Postgresql >= 9.5
                return ORM::rawExecute("
                    INSERT INTO {$table} ({$fields}) VALUES {$values}
                    ON CONFLICT ({$table}_uniq) DO UPDATE SET {$assignments}
                ");
                break;
            case strpos($this->_connString, 'sqlite') === 0:
                // sqlite does not support multi-row upsert :( loop manually here

                $fields = implode(', ', array_map(function ($field) {
                    return '"' . $field . '"';
                }, array_keys(reset($data))));

                $values = array_map(function ($dataset) {
                    return implode(', ', array_values($dataset));
                }, $data);

                return array_reduce($values, function ($acc, $dataset) use ($table, $fields) {
                    return $acc && ORM::rawExecute("
                        REPLACE INTO {$table} ({$fields}) VALUES ({$dataset});
                    ");
                }, true);
                break;
            default:
                throw new \Exception(
                    'Sorry, your DBMS is not supported. You may want to try implementing ' .
                    'last_insert_id function for your DB in place where this exception thrown from. ' .
                    'Check it out, this might be enough to make it work!'
                );
        }
    }

    // For testing purposes
    static protected $__testingInstance = null;
    public static function __getCleanTestingInstance()
    {
        $db = __DIR__ . '/../tests/data/db.sqlite';

        if (!is_dir(dirname($db))) {
            mkdir(dirname($db));
        }
        shell_exec('cd ' . __DIR__ . '/../ && SQLITE_FILE=' . $db . ' make init_sqlite_nointeractive');

        if (self::$__testingInstance === null) {
            $cfg = new Config(__DIR__ . '/../tests/util/config.php');
            self::$_ctr = 0;
            self::$__testingInstance = new self($cfg);
        }

        return self::$__testingInstance;
    }
}
