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

    public function __construct(Db $db)
    {
        $this->_db = $db;
    }

    /**
     * TODO: this should receive final scores from game log
     * TODO move to _tokenSMTH
     *
     * @param $content
     * @return array|bool
     */
    protected function _parseOutcome($content) {
        $regex = "#owari=\"([^\"]*)\"#";
        $matches = [];
        if (preg_match($regex, $content, $matches)) {
            $parts = explode(',', $matches[1]);
            return [
                $parts[0] . '00',
                $parts[2] . '00',
                $parts[4] . '00',
                $parts[6] . '00'
            ];
        }

        return false;
    }

    public function parseToSession(SessionPrimitive $session, $content) {
        $reader = new XMLReader();
        $reader->xml($content);

        while ($reader->read()) {
            if ($reader->nodeType != XMLReader::ELEMENT) continue;

            if (is_callable([$this, '_token' . $reader->localName])) {
                $method = '_token' . $reader->localName;
                $this->$method($reader, $session);
            }
        }
    }

    // new round, this looks redundant
//    protected function _tokenINIT(XMLReader $reader, SessionPrimitive $session)
//    {
//        $newDealer = $reader->getAttribute('oya');
//        if ($currentDealer != $newDealer) {
//            $currentRound ++;
//            $currentDealer = $newDealer;
//        }
//    }

    /**
     * This actually should be called first, before any round.
     * If game format is not changed, this won't break.
     *
     * @param XMLReader $reader
     * @param SessionPrimitive $session
     * @throws ParseException
     */
    protected function _tokenUN(XMLReader $reader, SessionPrimitive $session)
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

    protected function _tokenAGARI(XMLReader $reader, SessionPrimitive $session)
    {
        $winner = $reader->getAttribute('who');
        $loser = $reader->getAttribute('fromWho');
        $outcomeType = ($winner == $loser ? 'tsumo' : 'ron');

        list($fu, $points) = explode(',', $reader->getAttribute('ten'));
        $yakuList = $reader->getAttribute('yaku');
        $yakumanList = $reader->getAttribute('yakuman');

        $yakuData = YakuMap::fromTenhou($yakuList, $yakumanList);

        $this->_roundData []= [
            'outcome'   => $outcomeType,
            'winner_id' => $this->_players[$winner]->getId(),
            'loser_id'  => $outcomeType === 'ron' ? $this->_players[$loser]->getId() : null,
            'han'       => $yakuData['han'],
            'fu'        => $fu,
            'multi_ron' => false, // TODO!!!!!!!!!!!!!!!!!
            'dora'      => $yakuData['dora'],
            'uradora'   => 0,
            'kandora'   => 0,
            'kanuradora' => 0,
            'yaku'      => implode(',', $yakuData['yaku']),
            'riichi'    => $this->_getRiichi($session), // TODO: where to get them?
        ];
    }

    protected function _tokenRYUUKYOKU(XMLReader $reader, SessionPrimitive $session)
    {
        if ($reader->getAttribute('type')) { // abortive draw
            $this->_roundData []= [
                'outcome'   => 'abort',
                'riichi'    => $this->_getRiichi($session), // TODO: where to get them?
            ];
        } else {
            $scores = array_filter(explode(',', $reader->getAttribute('sc')));
            $tempai = [];

            // scores is in form of: player1,score,player2,score,player3,score,player4,score
            for ($i = 0; $i < count($scores); $i++) {
                if (intval($scores[$i * 2 + 1]) < 0 ) continue;
                $tempai []= $this->_players[$scores[$i * 2]]->getId();
            }

            // TODO: как отличить all от nobody? Нужен какой-то маркер. Не хочется ориентироваться на следующий раунд.

            $this->_roundData []= [
                'outcome'   => 'draw',
                'tempai'    => $tempai,
                'riichi'    => $this->_getRiichi($session), // TODO: where to get them?
            ];
        }
    }

    protected function _tokenGO(XMLReader $reader, SessionPrimitive $session)
    {
        $lobby = $reader->getAttribute('lobby');
        if ($session->getEvent()->getLobbyId() != $lobby) {
            throw new MalformedPayloadException('Provided replay does not belong to current event (wrong lobby)');
        }
    }


    // TODO: nagashi mangan (need to implement it in lower layers too)
}