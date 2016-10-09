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

/**
 * Class SessionState
 *
 * Low-level model helper
 * @package Riichi
 */
class SessionState
{
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
     * @return string
     */
    public function toJson()
    {

    }

    /**
     * @param $json
     * @return SessionState
     */
    public static function fromJson($json)
    {

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
