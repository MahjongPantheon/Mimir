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

require __DIR__ . '/../vendor/heilage-nsk/idiorm/src/idiorm.php';

/**
 * Class Db
 * @package Riichi
 *
 * Simple wrapper around IdiORM to encapsulate it's configuration
 */
class Db
{
    /**
     * @var int instances counter
     */
    static protected $_ctr = 0;

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

        $connectionString = $cfg->getDbConnectionString();
        $credentials = $cfg->getDbConnectionCredentials();

        ORM::configure($connectionString);
        if (!empty($credentials)) {
            ORM::configure($credentials); // should pass username and password
        }

        if (strpos($connectionString, 'mysql') === 0) { // force encoding for mysql
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
}
