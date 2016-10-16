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

/*
    Input example:

    [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}]
    ron [player] from [player] [5-12]han
    ron [player] from [player] [1-4]han \d{2,3}fu
    ron [player] from [player] yakuman
    tsumo [player] [5-12]han
    tsumo [player] [1-4]han \d{2,3}fu
    tsumo [player] yakuman
    draw tempai nobody
    draw tempai [player]
    draw tempai [player] [player]
    draw tempai [player] [player] [player]
    draw tempai all
    chombo [player]
*/

require_once __DIR__ . '/../helpers/Tokenizer.php';

class ParseException extends \Exception {}

class TextmodeSessionModel
{
    /**
     * @var $db
     */
    protected $_db;

    /**
     * @var array
     */
    protected $_resultScoresOrig;

    public function __construct(Db $db)
    {
        $this->_db = $db;
    }

    public function addGame($eventId, $gameLog)
    {
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $gameLog = trim($gameLog);
        if (empty($gameLog)) {
            throw new MalformedPayloadException('Game log is empty');
        }

        $tokenizer = new Tokenizer();
        $session = (new SessionPrimitive($this->_db))
            ->setEvent($event)
            ->setStatus('inprogress');

        /** @var Token[] $statement */
        foreach ($tokenizer->tokenize($gameLog) as $statement) {
            if ($statement[0]->type() == Tokenizer::USER_ALIAS) {
                $this->_fillSession($session, $statement)->save(); // initial save required, rounds use session id
                continue;
            }

            if ($statement[0]->type() == Tokenizer::OUTCOME) {
                $round = $this->_fillRound($session, $statement);
                $round->save();
                $session->updateCurrentState($round);
                continue;
            }

            $string = array_reduce($statement, function ($acc, $el) {
                return $acc . ' ' . $el;
            }, '');
            throw new ParseException("Couldn't parse game log: " . $string, 202);
        }

        $session->save();
        $session->finish();

        $calculatedScore = $session->getCurrentState()->getScores();
        if (array_diff($calculatedScore, $this->_resultScoresOrig) !== []
            || array_diff($this->_resultScoresOrig, $calculatedScore) !== []) {
            throw new ParseException("Calculated scores do not match with given ones: " . PHP_EOL
                . print_r($this->_resultScoresOrig, 1) . PHP_EOL
                . print_r($calculatedScore, 1), 225);
        }
    }

    /**
     * @param SessionPrimitive $session
     * @param Token[] $statement
     * @return SessionPrimitive
     * @throws ParseException
     */
    protected function _fillSession(SessionPrimitive $session, $statement)
    {
        $playersList = [];

        // Line with users and scores
        while (!empty($statement)) {
            /** @var $player Token */
            $player = array_shift($statement);
            /** @var $delimiter Token */
            $delimiter = array_shift($statement);
            /** @var $score Token */
            $score = array_shift($statement);

            if ($player->type() != Tokenizer::USER_ALIAS || $delimiter->type() != Tokenizer::SCORE_DELIMITER || $score->type() != Tokenizer::SCORE) {
                throw new ParseException("Wrong score line format: {$player} {$delimiter} {$score}", 106);
            }

            /** @var PlayerPrimitive $playerItem */
            $playerItem = PlayerPrimitive::findByAlias($this->_db, [$player->token()]);
            if (empty($playerItem)) {
                throw new ParseException("No player named '{$player->token()}' exists in our DB", 101);
            }
            $playersList []= $playerItem;

            $this->_resultScoresOrig[$playerItem->getId()] = $score; // For checking after calculcations
        }

        return $session->setPlayers($playersList);
    }

    /**
     * @param SessionPrimitive $session
     * @param Token[] $statement
     * @return RoundPrimitive
     * @throws ParseException
     */
    protected function _fillRound(SessionPrimitive $session, $statement)
    {
        // Line with round item
        $methodName = '_parseOutcome' . ucfirst($statement[0]->token());
        if (!is_callable([$this, $methodName])) {
            throw new ParseException("Не удалось разобрать исход ({$statement[0]->token()}: {$methodName})", 106);
        }

        return RoundPrimitive::createFromData(
            $this->_db,
            $session,
            $this->$methodName($statement, $session)
        );
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return string comma-separated riichi-players ids
     * @throws ParseException
     */
    protected function _getRiichi($tokens, SessionPrimitive $session)
    {
        $participants = $this->_getParticipantsMap($session);

        $riichi = [];
        $started = false;
        foreach ($tokens as $v) {
            if ($v->type() == Tokenizer::RIICHI_DELIMITER) {
                $started = true;
                continue;
            }

            if ($started) {
                if ($v->type() == Tokenizer::USER_ALIAS) {
                    if (empty($participants[$v->token()])) {
                        throw new ParseException("Failed to parse riichi statement. Player {$v->token()} not found. Typo?", 107);
                    }
                    $riichi []= $participants[$v->token()]->getId();
                } else {
                    return implode(',', $riichi);
                }
            }
        }

        if ($started && empty($riichi)) {
            throw new ParseException('Failed to prase riichi statement.', 108);
        }
        return implode(',', $riichi);
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return string comma-separated tempai players ids
     * @throws ParseException
     */
    protected function _getTempai($tokens, SessionPrimitive $session)
    {
        $participants = $this->_getParticipantsMap($session);

        $tempai = [];
        $started = false;
        foreach ($tokens as $v) {
            if ($v->type() == Tokenizer::TEMPAI) {
                $started = true;
                continue;
            }

            if (!$started) {
                continue;
            }

            switch ($v->type()) {
                case Tokenizer::USER_ALIAS:
                    if (empty($participants[$v->token()])) {
                        throw new ParseException("Failed to parse tempai statement. Player {$v->token()} not found. Typo?", 117);
                    }
                    $tempai [] = $participants[$v->token()]->getId();
                    break;
                case Tokenizer::ALL:
                    if (!empty($tempai)) {
                        throw new ParseException("Failed to parse riichi statement. Unexpected keyword 'all'. Typo?", 119);
                    }
                    return implode(',', array_map(function (PlayerPrimitive $p) {
                        return $p->getId();
                    }, $participants));
                case Tokenizer::NOBODY:
                    if (!empty($tempai)) {
                        throw new ParseException("Failed to parse riichi statement. Unexpected keyword 'nobody'. Typo?", 120);
                    }
                    return '';
                default:
                    return implode(',', $tempai);
            }
        }

        if (empty($tempai)) {
            throw new ParseException('Не удалось распознать темпай: не распознаны игроки.', 118);
        }
        return implode(',', $tempai);
    }

    /**
     * @param SessionPrimitive $session
     * @return PlayerPrimitive[] [alias => PlayerPrimitive]
     */
    protected function _getParticipantsMap(SessionPrimitive $session)
    {
        // TODO: runtime cache
        return array_combine(
            array_map(
                function(PlayerPrimitive $player) {
                    return $player->getAlias();
                },
                $session->getPlayers()
            ),
            $session->getPlayers()
        );
    }

    /**
     * @param $tokens Token[]
     * @param $type
     * @return Token
     */
    protected function _findByType($tokens, $type) {
        foreach ($tokens as $v) {
            if ($v->type() == $type) {
                return $v;
            }
        }

        return new Token(null, Tokenizer::UNKNOWN_TOKEN, [], null);
    }

    /**
     * @param $tokens Token[]
     * @return array
     * @throws ParseException
     * @throws TokenizerException
     */
    protected function _parseYaku($tokens)
    {
        if (!$this->_findByType($tokens, Tokenizer::YAKU_START)->token()) {
            return [
                'yaku' => [],
                'dora' => '0'
            ]; // no yaku info
        }

        $yakuStarted = false;
        $yaku = [];
        $doraCount = 0;
        foreach ($tokens as $t) {
            if ($t->type() == Tokenizer::YAKU_START) {
                $yakuStarted = true;
                continue;
            }

            if ($t->type() == Tokenizer::YAKU_END) {
                $yakuStarted = false;
                break;
            }

            if ($yakuStarted && $t->type() == Tokenizer::YAKU) {
                $yaku []= $t;
            }

            if ($yakuStarted && $t->type() == Tokenizer::DORA_DELIMITER) {
                $doraCount = '1'; // means dora 1 if there is only delimiter
            }

            if ($doraCount == '1' && $yakuStarted && $t->type() == Tokenizer::DORA_COUNT) {
                $doraCount = $t->token();
            }
        }

        if ($yakuStarted) {
            throw new ParseException('Yaku list ending paren was not found', 210);
        }

        return [
            'yaku' => array_map(function(Token $yaku) {
                    if ($yaku->type() != Tokenizer::YAKU) {
                        throw new TokenizerException('Requested token #' . $yaku->token() . ' is not yaku', 211);
                    }

                    $id = Tokenizer::identifyYakuByName($yaku->token());
                    if (!$id) {
                        throw new TokenizerException('No id found for requested yaku #' . $yaku->token() .
                        ', this should not happen!', 212);
                    }

                    return $id;
                }, $yaku),
            'dora' => $doraCount ? $doraCount : '0'
        ];
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return array
     * @throws ParseException
     */
    protected function _parseOutcomeRon($tokens, SessionPrimitive $session)
    {
        // check if double/triple ron occured
        $multiRon = !!$this->_findByType($tokens, Tokenizer::ALSO)->token();
        if ($multiRon) {
            if ($session->getEvent()->getRuleset()->withAtamahane()) {
                throw new ParseException("Detected multi-ron, but current rules use atamahane.");
            }
            return $this->_parseOutcomeMultiRon($tokens, $session);
        } else {
            return $this->_parseOutcomeSingleRon($tokens, $session);
        }
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return array
     * @throws ParseException
     */
    protected function _parseOutcomeSingleRon($tokens, SessionPrimitive $session)
    {
        $participants = $this->_getParticipantsMap($session);

        /** @var $winner Token
         * @var $from Token
         * @var $loser Token */
        list(/*ron*/, $winner, $from, $loser) = $tokens;
        if (empty($participants[$winner->token()])) {
            throw new ParseException("Player {$winner} is not found. Typo?", 104);
        }
        if ($from->type() != Tokenizer::FROM) {
            throw new ParseException("No 'from' keyword found in ron statement", 103);
        }
        if (empty($participants[$loser->token()])) {
            throw new ParseException("Player {$loser} is not found. Typo?", 105);
        }

        $yakuParsed = $this->_parseYaku($tokens);
        return [
            'outcome'   => 'ron',
            'winner_id' => $participants[$winner->token()]->getId(),
            'loser_id'  => $participants[$loser->token()]->getId(),
            'han'       => $this->_findByType($tokens, Tokenizer::HAN_COUNT)->clean(),
            'fu'        => $this->_findByType($tokens, Tokenizer::FU_COUNT)->clean(),
            'multi_ron' => false,
            'dora'      => $yakuParsed['dora'],
            'uradora'   => 0,
            'kandora'   => 0,
            'kanuradora' => 0,
            'yaku'      => implode(',', $yakuParsed['yaku']),
            'riichi'    => $this->_getRiichi($tokens, $session),
        ];
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return array
     * @throws ParseException
     */
    protected function _parseOutcomeTsumo($tokens, SessionPrimitive $session)
    {
        $participants = $this->_getParticipantsMap($session);

        /** @var $winner Token */
        list(/*tsumo*/, $winner) = $tokens;
        if (empty($participants[$winner->token()])) {
            throw new ParseException("Player {$winner} is not found. Typo?", 104);
        }

        $yakuParsed = $this->_parseYaku($tokens);
        return [
            'outcome'   => 'tsumo',
            'winner_id' => $participants[$winner->token()]->getId(),
            'han'       => $this->_findByType($tokens, Tokenizer::HAN_COUNT)->clean(),
            'fu'        => $this->_findByType($tokens, Tokenizer::FU_COUNT)->clean(),
            'multi_ron' => false,
            'dora'      => $yakuParsed['dora'],
            'uradora'   => 0,
            'kandora'   => 0,
            'kanuradora' => 0,
            'yaku'      => implode(',', $yakuParsed['yaku']),
            'riichi' => $this->_getRiichi($tokens, $session),
        ];
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return array
     */
    protected function _parseOutcomeDraw($tokens, SessionPrimitive $session)
    {
        return [
            'outcome'   => 'draw',
            'tempai'    => $this->_getTempai($tokens, $session),
            'riichi'    => $this->_getRiichi($tokens, $session),
        ];
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return array
     */
    protected function _parseOutcomeAbort($tokens, SessionPrimitive $session)
    {
        return [
            'outcome'   => 'abort',
            'riichi'    => $this->_getRiichi($tokens, $session),
        ];
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @return array
     * @throws ParseException
     */
    protected function _parseOutcomeChombo($tokens, SessionPrimitive $session)
    {
        $participants = $this->_getParticipantsMap($session);

        /** @var $loser Token */
        list(/*chombo*/, $loser) = $tokens;
        if (empty($participants[$loser->token()])) {
            throw new ParseException("Player {$loser} is not found. Typo?", 104);
        }

        return [
            'outcome'   => 'chombo',
            'loser_id'  => $participants[$loser->token()]->getId(),
        ];
    }














    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @param $tokens Token[]
     * @return array
     * @throws ParseException
     */
    protected function _splitMultiRon($tokens)
    {
        /** @var $loser Token
         *  @var $from Token */
        list(/*ron*/, /*winner*/, $from, $loser) = $tokens;
        if ($from->type() != Tokenizer::FROM) {
            throw new ParseException("Не указан игрок, с которого взят рон", 103);
        }

        $chunks = [[]];
        $idx = 0;
        foreach ($tokens as $k => $t) {
            if (
                $t->type() == Tokenizer::OUTCOME ||
                $t->type() == Tokenizer::FROM
            ) continue; // unify statements, cut unused keywords

            if (
                $k > 0 &&
                $tokens[$k-1]->type() == Tokenizer::FROM &&
                $t->type() == Tokenizer::USER_ALIAS &&
                $t->token() == $loser->token()
            ) continue; // saved separately

            if ($t->type() == Tokenizer::ALSO) {
                $idx ++;
                $chunks []= [];
                continue;
            }
            $chunks[$idx] []= $t;
        }

        return [$chunks, $loser];
    }

    /**
     * TODO: move this to SessionState
     *
     * @param $rons Token[][]
     * @param $loser Token
     * @param $session SessionPrimitive
     * @return array
     * @throws ParseException
     */
    protected function _assignRiichiBets($rons, $loser, SessionPrimitive $session) {
        $riichiOnTable = $this->_riichi; // save this one as it's erased with this->_getRiichi
        $bets = [];
        $winners = [];

        /** @var $ron Token[] */
        foreach ($rons as $ron) {
            $winners[$ron[0]->token()] = [];
            $bets = array_merge($bets, $this->_getRiichi($ron, $session));
            foreach ($bets as $k => $player) {
                if (isset($winners[$player])) {
                    $winners[$player] []= $ron[0]->token(); // winner always gets back his bet
                    unset($bets[$k]);
                }
            }
        }

        // Find player who gets non-winning riichi bets
        $playersRing = array_merge(array_keys($participants), array_keys($participants)); // double the array to form a ring
        $closestWinner = null;
        for ($i = 0; $i < count($playersRing); $i++) {
            if ($loser->token() == $playersRing[$i]) {
                for ($j = $i + 1; $j < count($playersRing); $j++) {
                    if (isset($winners[$playersRing[$j]])) {
                        $closestWinner = $playersRing[$j];
                        break 2;
                    }
                }
            }
        }

        if (!$closestWinner) {
            throw new ParseException('Не найден ближайший победитель для риичи-ставок: такого не должно было произойти!', 119);
        }

        $winners[$closestWinner] = array_merge($winners[$closestWinner], $bets);

        // assign riichi counts, add riichi on table for first winner
        foreach ($winners as $name => $bets) {
            if ($name == $closestWinner) {
                $winners[$name] = [
                    'riichi_totalCount' => $riichiOnTable + count($winners[$name]),
                    'riichi' => $winners[$name]
                ];
            } else {
                $winners[$name] = [
                    'riichi_totalCount' => count($winners[$name]),
                    'riichi' => $winners[$name]
                ];
            }
        }

        return $winners;
    }

    /**
     * @param $tokens Token[]
     * @param $session SessionPrimitive
     * @throws ParseException
     */
    protected function _parseOutcomeMultiRon($tokens, SessionPrimitive $session)
    {
        /** @var $loser Token */
        list($rons, $loser) = $this->_splitMultiRon($tokens);
        if (empty($participants[$loser->token()])) {
            throw new ParseException("Игрок {$loser} не указан в заголовке лога. Опечатка?", 105);
        }

        $riichiGoesTo = $this->_assignRiichiBets($rons, $loser, $participants);

        foreach ($rons as $ron) {
            /** @var $winner Token */
            $winner = $ron[0];
            if (empty($participants[$winner->token()])) {
                throw new ParseException("Игрок {$winner} не указан в заголовке лога. Опечатка?", 104);
            }

            $yakuParsed = $this->_parseYaku($ron);
            $resultData = [
                'outcome' => 'ron',
                'multiRon' => count($rons),
                'round' => $this->_currentRound,
                'winner' => $winner->token(),
                'loser' => $loser->token(),
                'honba' => $this->_honba,
                'han' => $this->_findByType($ron, Tokenizer::HAN_COUNT)->clean(),
                'fu' => $this->_findByType($ron, Tokenizer::FU_COUNT)->clean(),
                'yakuman' => !!$this->_findByType($ron, Tokenizer::YAKUMAN)->token(),
                'yakuList' => $yakuParsed['yaku'],
                'doraCount' => $yakuParsed['dora'],
                'dealer' => $this->_checkDealer($winner)
            ];
            $resultData = array_merge($resultData, $riichiGoesTo[$winner->token()]);

            if (!empty($resultData['yakuman'])) {
                $resultData['han'] = 13; // TODO: remove
                $this->_counts['yakuman']++;
            } else {
                $this->_counts['ron']++;
            }
        }

        if (count($rons) == 2) $this->_counts['doubleRon']++;
        if (count($rons) == 3) $this->_counts['tripleRon']++;
    }



    //<editor-fold desc="For testing only!!!">
    public function _iGetRiichi($tokens, $participants)
    {
        return $this->_getRiichi($tokens, $participants);
    }

    public function _iGetTempai($tokens, $participants)
    {
        return $this->_getTempai($tokens, $participants);
    }

    public function _iSplitMultiRon($tokens)
    {
        return $this->_splitMultiRon($tokens);
    }

    public function _iAssignRiichiBets($tokens, $participants)
    {
        list($rons, $loser) = $this->_splitMultiRon($tokens);
        return $this->_assignRiichiBets($rons, $loser, $participants);
    }
    //</editor-fold>
}
