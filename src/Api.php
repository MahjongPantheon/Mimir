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

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Db.php';

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use JsonRPC\Exception\ResponseException;
use JsonRPC\Exception\ServerErrorException;

class Api
{
    protected $_db;
    protected $_syslog;

    public function __construct()
    {
        $this->_config = new Config(__DIR__ . '/../config/index.php');
        $this->_db = new Db($this->_config);
        $this->_syslog = new Logger('RiichiApi');
        $this->_syslog->pushHandler(new ErrorLogHandler());
    }

    /**
     * Magic facade method for all api method implementations
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \JsonRPC\Exception\ServerErrorException
     * @throws \JsonRPC\Exception\ResponseException
     */
    public function __call($name, $arguments)
    {
        $this->_syslog->info('Method called: ' . $name);

        $impl = $this->_config->getRouteImplementation($name);
        if (!$impl) {
            throw new ResponseException('No method found to process this request.');
        }

        list($class, $method) = $impl;
        if (file_exists(__DIR__ . "/controllers/$class.php")) {
            require_once __DIR__ . "/controllers/$class.php";
        }

        $class = __NAMESPACE__ . '\\' . $class;

        if (!is_callable([$class, $method])) {
            throw new ServerErrorException('Requested method is not implemented.');
        }

        $instance = new $class($this->_db, $this->_syslog);
        return $instance->$method($arguments);
    }
}
