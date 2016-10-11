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

require_once __DIR__ . '/PointsCalc.php';

/**
 * Class SessionState
 *
 * Low-level model helper
 * @package Riichi
 */
class SessionState
{
    /**
     * @var Ruleset
     */
    protected $_rules;
    /**
     * @var int[] { player_id => score }
     */
    protected $_scores = [];
    /**
     * @var int[] { player_id => penalty_score }
     */
    protected $_penalties = [];
    /**
     * @var int
     */
    protected $_round = 1; // 1e-4s
    /**
     * @var int
     */
    protected $_honba = 0;
    /**
     * Count of riichi bets on table from previous rounds
     * @var int
     */
    protected $_riichiBets = 0;

    public function __construct(Ruleset $rules, $playersIds)
    {
        $this->_rules = $rules;
        $this->_scores = array_combine(
            $playersIds,
            array_fill(0, 4, $rules->startPoints())
        );
    }

    /**
     * @throws InvalidParametersException
     * @return string
     */
    public function toJson()
    {
        $arr = [];
        foreach ($this as $key => $value) {
            if ($key === '_rules') {
                continue;
            }
            if (!is_scalar($value) && !is_array($value)) {
                throw new InvalidParametersException('No objects/functions allowed in session state');
            }
            $arr[$key] = $value;
        }
        return json_encode($arr);
    }

    /**
     * @param Ruleset $rules
     * @param $playersIds
     * @param $json
     * @return SessionState
     * @throws InvalidParametersException
     */
    public static function fromJson(Ruleset $rules, $playersIds, $json)
    {
        if (empty($ret)) {
            $ret = [];
        } else {
            $ret = json_decode($json, true);
            if (json_last_error() !== 0) {
                throw new InvalidParametersException(json_last_error_msg());
            }
        }
        $instance = new self($rules, $playersIds);
        foreach ($ret as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    /**
     * @return bool
     */
    protected function _buttobi()
    {
        $scores = $this->getScores();
        return $scores[0] < 0 || $scores[1] < 0 || $scores[2] < 0 || $scores[3] < 0;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->getRound() > 8
            || ($this->_rules->withButtobi() && $this->_buttobi());
    }

    /**
     * @return SessionState
     */
    protected function _addHonba()
    {
        $this->_honba++;
        return $this;
    }

    /**
     * @return SessionState
     */
    protected function _resetHonba()
    {
        $this->_honba = 0;
        return $this;
    }

    /**
     * @return int
     */
    public function getHonba()
    {
        return $this->_honba;
    }

    /**
     * @param int $riichiBets
     * @return SessionState
     */
    protected function _addRiichiBets($riichiBets)
    {
        $this->_riichiBets += $riichiBets;
        return $this;
    }

    /**
     * @return SessionState
     */
    protected function _resetRiichiBets()
    {
        $this->_riichiBets = 0;
        return $this;
    }

    /**
     * @return int
     */
    public function getRiichiBets()
    {
        return $this->_riichiBets;
    }

    /**
     * @return SessionState
     */
    protected function _nextRound()
    {
        $this->_round++;
        return $this;
    }

    /**
     * @return int
     */
    public function getRound()
    {
        return $this->_round;
    }

    /**
     * @return \int[]
     */
    public function getScores()
    {
        return $this->_scores;
    }

    /**
     * @return \int[]
     */
    public function getPenalties()
    {
        return $this->_penalties;
    }

    /**
     * Return id of current dealer
     * @return int|string
     */
    public function getCurrentDealer()
    {
        $players = array_keys($this->_scores);
        return $players[($this->_round % 4) - 1];
    }

    /**
     * Register new round in current session
     * @param RoundPrimitive $round
     * @throws InvalidParametersException
     * @return bool
     */
    public function update(RoundPrimitive $round)
    {
        switch ($round->getOutcome()) {
            case 'ron':
                $this->_updateAfterRon($round);
                break;
            case 'tsumo':
                $this->_updateAfterTsumo($round);
                break;
            case 'draw':
                $this->_updateAfterDraw($round);
                break;
            case 'abort':
                $this->_updateAfterAbort($round);
                break;
            case 'chombo':
                $this->_updateAfterChombo($round);
                break;
            default:
                ;
        }

        return true; // todo: can be here any errors?
    }

    /**
     * @param RoundPrimitive $round
     */
    protected function _updateAfterRon(RoundPrimitive $round)
    {
        $isDealer = $this->getCurrentDealer() == $round->getWinnerId();

        $this->_scores = PointsCalc::ron(
            $this->_rules,
            $isDealer,
            $this->getScores(),
            $round->getWinnerId(),
            $round->getLoserId(),
            $round->getHan(),
            $round->getFu(),
            $round->getRiichiIds(),
            $this->getHonba(),
            $this->getRiichiBets()
        );

        if ($isDealer) {
            $this->_addHonba();
        } else {
            $this->_resetHonba()
                ->_nextRound();
        }

        $this->_resetRiichiBets();
    }

    /**
     * @param RoundPrimitive $round
     */
    protected function _updateAfterTsumo(RoundPrimitive $round)
    {
        $this->_scores = PointsCalc::tsumo(
            $this->_rules,
            $this->getCurrentDealer(),
            $this->getScores(),
            $round->getWinnerId(),
            $round->getHan(),
            $round->getFu(),
            $round->getRiichiIds(),
            $this->getHonba(),
            $this->getRiichiBets()
        );

        if ($this->getCurrentDealer() == $round->getWinnerId()) {
            $this->_addHonba();
        } else {
            $this->_resetHonba()
                ->_nextRound();
        }

        $this->_resetRiichiBets();
    }

    /**
     * @param RoundPrimitive $round
     */
    protected function _updateAfterDraw(RoundPrimitive $round)
    {
        $this->_scores = PointsCalc::draw(
            $this->getScores(),
            $round->getTempaiIds(),
            $round->getRiichiIds()
        );

        $this->_addHonba()
            ->_addRiichiBets(count($round->getRiichiIds()));

        if (!in_array($this->getCurrentDealer(), $round->getTempaiIds())) {
            $this->_nextRound();
        }
    }

    /**
     * @param RoundPrimitive $round
     * @throws InvalidParametersException
     */
    protected function _updateAfterAbort(RoundPrimitive $round)
    {
        if (!$this->_rules->withAbortives()) {
            throw new InvalidParametersException('Current game rules do not allow abortive draws');
        }

        $this->_scores = PointsCalc::abort(
            $this->getScores(),
            $round->getRiichiIds()
        );

        $this->_addHonba()
            ->_addRiichiBets(count($round->getRiichiIds()));
    }

    /**
     * @param RoundPrimitive $round
     */
    protected function _updateAfterChombo(RoundPrimitive $round)
    {
        $this->_scores = PointsCalc::chombo(
            $this->_rules,
            $this->getCurrentDealer(),
            $round->getLoserId(),
            $this->getScores()
        );

        if (empty($this->_penalties[$round->getLoserId()])) {
            $this->_penalties[$round->getLoserId()] = 0;
        }
        $this->_penalties[$round->getLoserId()] -= $this->_rules->chomboPenalty();
    }
}
