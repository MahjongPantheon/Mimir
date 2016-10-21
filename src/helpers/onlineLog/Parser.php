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

require_once __DIR__ . '/../../exceptions/Parser.php';

class OnlineParser
{
    protected $_roundData = [];
    /**
     * @var PlayerPrimitive[]
     */
    protected $_players = [];
    protected $_db;
    protected $_riichi = [];

    protected $_lastTokenIsAgari = false;

    public function __construct(Db $db)
    {
        $this->_db = $db;
    }

    /**
     * Much simpler to get final scores by regex :)
     *
     * @param $content
     * @return array (player id => score)
     */
    protected function _parseOutcome($content)
    {
        $regex = "#owari=\"([^\"]*)\"#";
        $matches = [];
        if (preg_match($regex, $content, $matches)) {
            $parts = explode(',', $matches[1]);
            return array_combine(
                array_map(function (PlayerPrimitive $p) {
                    return $p->getId();
                }, $this->_players),
                [
                    $parts[0] . '00',
                    $parts[2] . '00',
                    $parts[4] . '00',
                    $parts[6] . '00'
                ]
            );
        }

        return [];
    }

    /**
     * @param SessionPrimitive $session
     * @param $content string game log xml string
     * @return array parsed score
     */
    public function parseToSession(SessionPrimitive $session, $content)
    {
        $reader = new \XMLReader();
        $reader->xml($content);

        while ($reader->read()) {
            if ($reader->nodeType != \XMLReader::ELEMENT) {
                continue;
            }

            if (is_callable([$this, '_token' . $reader->localName])) {
                $method = '_token' . $reader->localName;
                $this->$method($reader, $session);
            }
        }

        return $this->_parseOutcome($content);
    }

    protected function _getRiichi()
    {
        $riichis = $this->_riichi;
        $this->_riichi = [];
        return implode(',', $riichis);
    }

    /**
     * This actually should be called first, before any round.
     * If game format is not changed, this won't break.
     *
     * @param \XMLReader $reader
     * @param SessionPrimitive $session
     * @throws ParseException
     */
    protected function _tokenUN(\XMLReader $reader, SessionPrimitive $session)
    {
        if (count($this->_players) == 0) {
            $this->_players = [
                base64_encode(rawurldecode($reader->getAttribute('n0'))) => 1,
                base64_encode(rawurldecode($reader->getAttribute('n1'))) => 1,
                base64_encode(rawurldecode($reader->getAttribute('n2'))) => 1,
                base64_encode(rawurldecode($reader->getAttribute('n3'))) => 1
            ];

            if (!empty($this->_players['NoName'])) {
                throw new ParseException('No unnamed players are allowed in replays');
            }

            $players = PlayerPrimitive::findByAlias($this->_db, array_keys($this->_players));
            if (count($players) !== count($this->_players)) {
                throw new ParseException('Some of players are not found in DB');
            }

            $session->setPlayers($players);
            $this->_players = array_combine(array_keys($this->_players), $players); // players order should persist
        }
    }

    protected function _tokenAGARI(\XMLReader $reader)
    {
        $winner = $reader->getAttribute('who');
        $loser = $reader->getAttribute('fromWho');
        $outcomeType = ($winner == $loser ? 'tsumo' : 'ron');

        list($fu) = explode(',', $reader->getAttribute('ten'));
        $yakuList = $reader->getAttribute('yaku');
        $yakumanList = $reader->getAttribute('yakuman');

        $yakuData = YakuMap::fromTenhou($yakuList, $yakumanList);

        if (!$this->_lastTokenIsAgari) { // single ron, or first ron in sequence
            $this->_roundData [] = [
                'outcome' => $outcomeType,
                'winner_id' => $this->_players[$winner]->getId(),
                'loser_id' => $outcomeType === 'ron' ? $this->_players[$loser]->getId() : null,
                'han' => $yakuData['han'],
                'fu' => $fu,
                'multi_ron' => false,
                'dora' => $yakuData['dora'],
                'uradora' => 0,
                'kandora' => 0,
                'kanuradora' => 0,
                'yaku' => implode(',', $yakuData['yaku']),
                'riichi' => $this->_getRiichi(),
            ];
        } else {
            // double or triple ron, previous round record should be modified
            $roundRecord = array_pop($this->_roundData);

            if ($roundRecord['outcome'] === 'ron') {
                $roundRecord = [
                    'outcome' => 'multiron',
                    'multi_ron' => 1,
                    'loser_id' => $this->_players[$loser]->getId(),
                    'wins' => [[
                        'winner_id' => $roundRecord['winner_id'],
                        'han' => $roundRecord['han'],
                        'fu' => $roundRecord['fu'],
                        'dora' => $roundRecord['dora'],
                        'uradora' => $roundRecord['uradora'],
                        'kandora' => $roundRecord['kandora'],
                        'kanuradora' => $roundRecord['kanuradora'],
                        'yaku' => $roundRecord['yaku'],
                        'riichi' => $roundRecord['riichi'],
                    ]]
                ];
            }

            $roundRecord['multi_ron'] ++;
            $roundRecord['wins'] []= [
                'winner_id' => $this->_players[$winner]->getId(),
                'han' => $yakuData['han'],
                'fu' => $fu,
                'dora' => $yakuData['dora'],
                'uradora' => 0,
                'kandora' => 0,
                'kanuradora' => 0,
                'yaku' => implode(',', $yakuData['yaku']),
                'riichi' => $this->_getRiichi(),
            ];

            $this->_roundData []= $roundRecord;
        }

        $this->_lastTokenIsAgari = true;
    }

    // round start, reset all needed things
    protected function _tokenINIT()
    {
        $this->_lastTokenIsAgari = false; // resets double/triple ron sequence
    }

    protected function _tokenRYUUKYOKU(\XMLReader $reader)
    {
        $rkType = $reader->getAttribute('type');

        if ($rkType && $rkType == 'nm') {
            // TODO: nagashi mangan (need to implement it in lower layers too)
            return;
        }

        if ($rkType) { // abortive draw
            $this->_roundData []= [
                'outcome'   => 'abort',
                'riichi'    => $this->_getRiichi(),
            ];

            return;
        }

        // form array in form of [int 'player id' => bool 'tempai?']
        $tempai = array_filter(
            array_combine(
                array_map(
                    function(PlayerPrimitive $el) {
                        return $el->getId();
                    },
                    $this->_players
                ), [
                    !!$reader->getAttribute('hai0'),
                    !!$reader->getAttribute('hai1'),
                    !!$reader->getAttribute('hai2'),
                    !!$reader->getAttribute('hai3'),
                ]
            )
        );

        $this->_roundData []= [
            'outcome' => 'draw',
            'tempai'  => implode(',', array_keys($tempai)),
            'riichi'  => $this->_getRiichi(),
        ];
    }

    protected function _tokenREACH(\XMLReader $reader)
    {
        $player = $reader->getAttribute('who');
        $this->_riichi []= $this->_players[$player]->getId();
    }

    protected function _tokenGO(\XMLReader $reader, SessionPrimitive $session)
    {
        $lobby = $reader->getAttribute('lobby');
        if ($session->getEvent()->getLobbyId() != $lobby) {
            throw new MalformedPayloadException('Provided replay does not belong to current event (wrong lobby)');
        }
    }
}
