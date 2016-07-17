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

class Config
{
    protected $_data;

    public function __construct($baseFile)
    {
        $this->_data = require $baseFile;
    }

    public function getValue($path)
    {
        $parts = explode('.', $path);
        $current = $this->_data;
        while ($part = array_shift($parts)) {
            $current = $current[$part];
        }

        return $current;
    }

    public function getDbConnectionString()
    {
        $value = $this->getValue('db.connectionString');
        if (empty($value)) {
            throw new \RuntimeException('DB connection string not found in configuration!');
        }

        return $value;
    }

    public function getDbConnectionCredentials()
    {
        return $this->getValue('db.credentials');
    }

    /**
     * Simple routing: get api method implementation info from config
     *
     * @param $methodName
     * @return mixed
     */
    public function getRouteImplementation($methodName)
    {
        return $this->getValue('routes.' . $methodName);
    }
}
