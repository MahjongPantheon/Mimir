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

require __DIR__ . '/../vendor/heilage-nsk/idiorm/src/idiorm.php';
require __DIR__ . '/../src/interfaces/IDb.php';

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
     * @return \Idiorm\ORM
     */
    public function table($tableName)
    {
        return ORM::forTable($tableName);
    }

    public function lastInsertId()
    {
        switch (0) {
            case strpos($this->_connString, 'mysql'):
                ORM::rawExecute('SELECT LAST_INSERT_ID()');
                return ORM::getLastStatement()->fetchColumn();
                break;
            case strpos($this->_connString, 'pgsql'):
                ORM::rawExecute('SELECT LASTVAL()');
                return ORM::getLastStatement()->fetchColumn();
                break;
            case strpos($this->_connString, 'sqlite'):
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
}
