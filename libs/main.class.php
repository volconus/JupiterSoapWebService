<?php
/**
 * Created by PhpStorm.
 * User: volkan
 * Date: 26.11.2015
 * Time: 12:19
 */
function getDateTime() {
    return date('d.m.Y H:i:s');
}

class main
{
    public $dir, $db;
    public function terminate($reason) {
        die('TERMINATING... Reason: '.$reason);
    }

    public function registerMethod($server, $thisethod, $inputs = array(), $return = array()) {

        $res = $server->register(
            // method name:
            $thisethod,
            // parameter list:
            $inputs,
            // return value(s):
            $return,
            // namespace:
            $namespace,
            // soapaction: (use default)
            null,
            // style: rpc or document
            'rpc',
            // use: encoded or literal
            'encoded',
            // description: documentation for the method
            'A simple Hello World web method');
        if ($res) return true;
    }

    public function returnArrayMethod($server, $thisethod, $output) {
        $server->wsdl->addComplexType(
            $thisethod,
            'complextType',
            'struct',
            'sequence',
            '',
            $output
        );
    }

    public function registerComplexMethod($server, $thisethod, $input, $output) {
        $this->returnArrayMethod($server,$thisethod, $output);
        $this->registerMethod($server, 'j.'.$thisethod, $input, array('return'=>"tns:$thisethod"));
    }

    public function encode($string, $rand)
    {
        $encode = base64_encode($string);
        $exp = str_split($encode);
        $encoded = null;
        foreach ($exp as $char) {
            $charDouble = (ord($char) + $rand);
            $encoded .= chr($charDouble);
        }
        return $encoded;
    }

    public function decode($string, $rand) {
        $exp = str_split($string);
        $decoded = null;
        foreach ($exp as $char) {
            $charDouble = (ord($char) - $rand);
            $decoded .= chr($charDouble);
        }
        return base64_decode($decoded);
    }
    
    public function decodePackage($p) {
        for ($i = 0; $i <= 5; $i++) {
            $encode = $this->decode($this->decode($p, $i), $i);
            if (substr($encode, 0, 2) == 'OK') {
                return explode('|', $encode);
            }
        }
        return array('False');
    }

    public function newSipPassword() {
        return substr(md5(rand(1,99999999)), 0, 20);
    }

    public function loginFailReason($agent, $station, $stationUserInfo, $serverInfo) {
        if ($agent->name == '')
            return "Username password incorrect.";
        else if ($agent->domain_name == '')
            return "Domain incorrect.";
        else if ($station->peer_id == '')
            return "Station not found. $localIp";
        else if ($stationUserInfo->fromuser == '')
            return "Station Peer not found.";
        else if ($serverInfo->ip == '')
            return "Server not found.";
    }

    public function makeSessionUniqueId() {
        return md5(rand(1,999999999));
    }

    public function makeAgentSession($domainId, $stationId, $agentId, $userName, $localIp, $publicIp) {
        $sessionExist = $this->selectOne('"pbx".agent_session', array('session_id'), "agent_id = '$agentId' and session_end IS NULL");
        if (!$sessionExist->session_id) {
            $uniqueId = $this->makeSessionUniqueId();
            $ins = $this->insert('"pbx".agent_session', array(
                'domain_id' => $domainId,
                'agent_id' => $agentId,
                'station_id' => $stationId,
                'session_start' => 'NOW()',
                'session_id' => $uniqueId,
                'session_last_move' => 'NOW()',
                'local_ip' => $localIp,
                'public_ip' => $publicIp,
                'session_state' => 'pending',
            ));
            if ($ins == true)
                return $uniqueId;
        }
        else {
            $upd = $this->update('"pbx".agent_session', array('session_last_move' => 'NOW()'), "session_id = '{$sessionExist->session_id}'");
            if ($upd == true)
                return $sessionExist->session_id;
        }
    }

    public function checkSession($sessionId) {
        $session = $this->selectOne('pbx.agent_session sess, pbx.station s', array('sess.*', 's.extension as extension'), "s.id = sess.station_id and sess.session_id = '$sessionId' and sess.session_end IS NULL");
        if ($session->id != '')
            return $session;
        else
            return false;
    }

}