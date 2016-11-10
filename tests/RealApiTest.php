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
use JsonRPC\Client;

/**
 * Class RealApiTest: integration test suite
 * @package Riichi
 */
class RealApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $_client;

    public function setUp()
    {
        $this->_client = new Client('http://127.0.0.1:1349');
    }

    public function testSomeApiMethod()
    {
        sleep(100);
        $response = $this->_client->execute('getGameConfig', [100500]);
        var_dump($response);
    }
}
