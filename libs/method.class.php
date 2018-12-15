<?php
/**
 * Created by PhpStorm.
 * User: volkan
 * Date: 27.11.2015
 * Time: 17:18
 */
class j extends database
{
    public function GetPublicIp($p) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();

        return array(
            'result' => 'OK',
            'publicIp' => $_SERVER['REMOTE_ADDR']
        );
    }
    public function AgentChangeState($p) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();

        $p = $this->decodePackage($p);

        $sessionId = $p[1];
        $currentStateName = $p[2];
        $newStateName = $p[3];

        $session = $this->checkSession($sessionId);
        if ($session->agent_id == '')
            return array(
                'result' => 'ERR',
                'reason' => 'Session expired.'
            );

        $v->notNull(array($currentStateName, $newStateName));
        $v->ifFailShowErrors();

        $currentState = $this->selectOne('pbx.agent_state_list', array('id', 'name'), "name = '$currentStateName' and status = 'A'");
        $newState = $this->selectOne('pbx.agent_state_list', array('id', 'name', 'state_type', 'accept_queue_call'), "name = '$newStateName' and status = 'A'");

        if ($currentStateName == $newStateName)
            return array(
                'result' => 'ERR',
                'reason' => 'You are already in this state.'
            );

        if ($newState->state_type == 'custom' or $newState->state_type == 'mixed') {
            // access controlssss
            $this->pdo->beginTransaction();

            $newState->accept_queue_call == 'F' ? $paused = 1 : $paused = 0;

            $query = $this->query("SELECT pbx.\"addstatesession(int4, int4)\"({$session->extension}, {$newState->id})");

            $upd1 = $this->update('hub.queue_members', array(
                'paused' => $paused
            ), "membername = '{$session->extension}'");

                if ($query == true and $upd1 == true) {
                    $this->pdo->commit();
                    return array('result' => 'OK');
                }
                else {
                    $this->pdo->rollBack();
                    return array(
                        'result' => 'ERR',
                        'reason' => 'DB Write Error.');
                }
        } else if ($newState->state_type == 'system') {
            return array(
                'result' => 'ERR',
                'reason' => 'Doesnt access.');
        }
            else {
            return array(
                'result' => 'ERR',
                'reason' => 'State not found.');
        }


    }

    public function AgentGetStates() {
        $states = $this->select('pbx.agent_state_list s LEFT JOIN pbx.agent_session sess ON sess.domain_id = s.domain_id ', array('s.id', 's.name'), "(s.domain_id IS NULL or s.domain_id = sess.domain_id) and status = 'A' and (state_type = 'custom' or state_type = 'mixed') ORDER By name asc");
        foreach ($states as $state) $statesString .= $state->name.'|'.$state->id.';';
        return array(
            'states' => $statesString,
        );

    }

    public function AgentGetAnnounce($p) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();

        $p = $this->decodePackage($p);

        $sessionId = $p[1];
        $session = $this->checkSession($sessionId);

        if ($session->agent_id == '')
            return array(
                'result' => 'ERR',
                'reason' => 'Session expired.'
            );

        $records = $this->select('pbx.agent_announce a, pbx.skill s', array('a.*', 's.name as skill_name'), "s.id = a.skill_id and agent_id = '{$session->agent_id}' ORDER By record_date desc");
        foreach ($records as $record)
            $recordsString .= $record->id.'|'.$record->filename.'|'.$record->name.'|'.$record->skill_name.'|'.$record->record_time.'|'.$record->status.';';

        return array(
            'result' => 'OK',
            'reason' => '',
            'records' => $recordsString
        );
    }

    public function AgentGetAnnounceFiles($p) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();

        $p = $this->decodePackage($p);

        $sessionId = $p[1];
        $session = $this->checkSession($sessionId);

        if ($session->agent_id == '')
            return array(
                'result' => 'ERR',
                'reason' => 'Session expired.'
            );

        $records = $this->select('pbx.agent_announce', array('filename'), "agent_id = '{$session->agent_id}'");
        $i = 0;
        foreach ($records as $record) {
            $i++;
            $recordContent = file_get_contents("/projects/jupiterws/uploads/{$record->filename}", FILE_USE_INCLUDE_PATH);
            $recordContent = base64_encode($recordContent);
            $data["recordFile$i"] = $record->filename;
            $data["recordContent$i"] = $recordContent;
        }


        $data['result'] = 'OK';
        $data['reason'] = '';
        return $data;


    }

    public function AgentSetAnnounceState($p)
    {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();
        $p = $this->decodePackage($p);

        $sessionId = $p[1];
        $allId = $p[2];
        $newState = $p[3];

        $session = $this->checkSession($sessionId);

        $v->notNull(array($newState, $allId));
        $v->ifFailShowErrors();

        if ($session->agent_id == '')
            return array(
                'result' => 'ERR',
                'reason' => 'Session expired.'
            );

        $ids = explode(',', $allId);
        foreach($ids as $id) {
            if (is_numeric($id))
                $sqlWhere .= "id = '$id' or ";
        }
        $sqlWhere = substr($sqlWhere, 0, -4);

        $announce = $this->selectOne('pbx.agent_announce', array('id'), "$sqlWhere");

        if (!$announce->id)
            return array(
                'result' => 'ERR',
                'reason' => 'Announce not found.'
            );

        if ($announce->id) {
            $result = $this->update('pbx.agent_announce', array(
                'status' => $newState
            ), $sqlWhere);
            if ($result == true)
                return array(
                    'result' => 'OK',
                    'reason' => ''
                );
            else
                return array(
                    'result' => 'ERR',
                    'reason' => 'DB Error.'
                );
        }

    }

    public function AgentAddAnnounce($p, $fileContent) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();
        $p = $this->decodePackage($p);

        $sessionId = $p[1];
        $fileName = $p[2];
        $name = $p[3];
        $skillName = $p[4];
        $recordTime = $p[5];
        $agentId = $p[6];

        $session = $this->checkSession($sessionId);

        $v->notNull(array($fileName, $name, $skillName));
        $v->isNumeric(array($recordTime, $agentId));
        $v->ifFailShowErrors();

        if ($session->agent_id == '')
            return array(
                'result' => 'ERR',
                'reason' => 'Session expired.'
            );

        $skill = $this->selectOne('pbx.skill', array('id'), "name = '$skillName'");
        $announce = $this->selectOne('pbx.agent_announce', array('id'), "name = '$name' and skill_id = '{$skill->id}' and agent_id = '{$session->agent_id}'");

        if (!$skill->id)
            return array(
                'result' => 'ERR',
                'reason' => 'Skill not found.'
            );

        $fp = fopen("/projects/jupiterws/uploads/$fileName", "w");
        fwrite($fp, $fileContent);
        fclose($fp);

        if ($announce->id) {
            $result = $this->update('pbx.agent_announce', array(
                'filename' => $fileName,
                'name' => $name,
                'skill_id' => $skill->id,
                'record_date' => 'NOW()',
                'record_time' => $recordTime,
                'status' => 'A',
                'agent_id' => $session->agent_id
            ), "id = '{$announce->id}'");
        } else {
            $result = $this->insert('pbx.agent_announce', array(
                'filename' => $fileName,
                'name' => $name,
                'skill_id' => $skill->id,
                'record_date' => 'NOW()',
                'record_time' => $recordTime,
                'status' => 'A',
                'agent_id' => $session->agent_id
            ));
        }

        if ($result == true)
            return array(
                'result' => 'OK',
                'reason' => ''
            );
        else
            return array(
                'result' => 'ERR',
                'reason' => 'DB Error.'
            );
    }

    public function AgentRemoveAnnounce($p) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();

        $p = $this->decodePackage($p);

        $sessionId = $p[1];
        $allId = $p[2];
        $session = $this->checkSession($sessionId);

        if ($session->agent_id == '')
            return array(
                'result' => 'ERR',
                'reason' => 'Session expired.'
            );

        $v->notNull(array($allId));
        $v->ifFailShowErrors();

        $ids = explode(',', $allId);
        foreach($ids as $id) {
            if (is_numeric($id))
                $sqlWhere .= "id = '$id' or ";
        }
        $sqlWhere = substr($sqlWhere, 0, -4);

        $record = $this->selectOne('pbx.agent_announce', array('filename'), "agent_id = '{$session->agent_id}' and ($sqlWhere)");
        if ($record->filename) {
            $del = $this->delete('pbx.agent_announce', "agent_id = '{$session->agent_id}' and ($sqlWhere)");
            if ($del == true) {
                return array(
                    'result' => 'OK',
                    'reason' => ''
                );
            } else {
                return array(
                    'result' => 'ERR',
                    'reason' => 'DB Error.'
                );
            }
        } else {
            return array(
                'result' => 'ERR',
                'reason' => 'Record not found.'
            );
        }
    }

    public function AgentLogin($p) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();

        $p = $this->decodePackage($p);

        $userNameWithDomain = $p[1];
        $password = $p[2];
        $macAddress = $p[3];
        $localIp = $p[4];
        $publicIp = $p[5];


        $v->isDomain('Username', $userNameWithDomain);
        $v->macAddress(array('Local Mac' => $macAddress));
        $v->ipAddress(array('Local IP' => $localIp, 'Public IP' => $publicIp));
        $error = $v->ifFailShowErrors();
        if ($error == true)
            return array('result' => $v->error[0]);

        $userNameBomb = explode('@', $userNameWithDomain);
        $userName = $userNameBomb[0];
        $domainName = $userNameBomb[1];

        $v->notNull(array($userName, $domainName));
        $v->userName('Username', $userName);
        $v->ifFailShowErrors();
        if ($error == true)
            return array('result' => $v->error[0]);

        $agent = $this->selectOne('pbx.agent a, main.domain d, main.domain_index di', array('a.id', 'a.name', 'd.name as domain_name', 'd.id as domain_id'),
            "a.id = di.member_id and di.domain_id = d.id and d.name = '$domainName' and di.default_domain = 'T' and a.username = '$userName' and a.password = '$password'");
        $station = $this->selectOne('pbx.station', array('id', 'peer_id', 'peer_scheme'), "local_ip = '$localIp' and public_ip = '$publicIp'");
        if ($station->peer_id != "" and $station->peer_scheme != "" and $agent->name != "") {
            //$updNewPassword = $this->update("{$station->peer_scheme}.sippeers", array('secret' => $this->newSipPassword()), "id = '{$station->peer_id}'");
            $stationUserInfo = $this->selectOne("{$station->peer_scheme}.sippeers", array('defaultuser', 'secret'), "id = '{$station->peer_id}'");
            $serverInfo = $this->selectOne('pbx.server', array('ip', 'hostname', 'port'), "name = '{$station->peer_scheme}'");
        }

        if ($agent->name != "" and $agent->domain_name != "" and $stationUserInfo->defaultuser != "" and $serverInfo->ip != "") {
            $uniqueId = $this->makeAgentSession($agent->domain_id, $station->id, $agent->id, $userName, $localIp, $publicIp);

            $states = $this->select('pbx.agent_state_list s LEFT JOIN pbx.agent_session sess ON sess.domain_id = s.domain_id ', array('s.id', 's.name'), "(s.domain_id IS NULL or s.domain_id = sess.domain_id) and status = 'A' and (state_type = 'custom' or state_type = 'mixed') ORDER By name asc");
            foreach ($states as $state) $statesString .= $state->name.'|'.$state->id.';';

            $skills = $this->select('pbx.skill s, pbx.skill_index si', array('s.id', 's.name'), "si.skill_id = s.id  ORDER By s.name asc");
            foreach ($skills as $skill) $skillsString .= $skill->name.'|'.$skill->id.';';

            $option = $this->selectOne('pbx.agent_option', array('*'), "agent_id = '{$agent->id}'");
            !$option->microphone ? $option->microphone = ' ' : '';
            !$option->speaker ? $option->speaker = ' ' : '';
            !$option->audio_codec ? $option->audio_codec = ' ' : '';
            !$option->video_codec ? $option->video_codec = ' ' : '';

            $response = array(
                'result' => 'OK',
                'agentId' => $agent->id,
                'domainName' => $agent->domain_name,
                'sessionId' => $uniqueId,
                'sipUserName' => $stationUserInfo->defaultuser,
                'sipAuth' => $stationUserInfo->defaultuser,
                'sipPassword' => $stationUserInfo->secret,
                'sipServerIp' => $serverInfo->ip,
                'sipServerHostname' => $serverInfo->hostname,
                'sipServerPort' => $serverInfo->port,
                'states' => $statesString,
                'skills' => $skillsString,
                'defMicrophone' => $option->microphone,
                'defSpeaker' => $option->speaker,
                'defAudioCodec' => $option->audio_codec,
                'defVideoCodec' => $option->video_codec
            );

            ## loading agent states
            $agentStates = $this->select('pbx.agent_state_list', array('id', 'name'), "status = 'A'");
            $i = 0;
            foreach ($agentStates as $state) {
                $i++;
                $response['state'.$i] = "{$state->name}|{$state->id}";
            }
            ## loading agent states

            return $response;

        } else {
            return array(
                'result' => $this->loginFailReason($agent, $station, $stationUserInfo, $serverInfo)
            );
        }
    }

    public function AgentSetSoundOption($p) {
        $v = new validate();
        $v->notNull(array($p));
        $v->ifFailShowErrors();
        $p = $this->decodePackage($p);

        $sessionId = $p[1];
        $microphone = $p[2];
        $speaker = $p[3];
        $audio_codec = $p[4];
        $video_codec = $p[5];

        $session = $this->checkSession($sessionId);

        $v->notNull(array($microphone, $speaker, $audio_codec, $video_codec));
        $v->isAZ09(array($microphone, $speaker, $audio_codec, $video_codec));
        $v->ifFailShowErrors();

        if ($session->agent_id == '')
            return array(
                'result' => 'ERR',
                'reason' => 'Session expired.'
            );

        $option = $this->selectOne('pbx.agent_option', array('id'), "agent_id = '{$session->agent_id}'");

        if ($option->id)
            $success = $this->update('pbx.agent_option', array(
                'microphone' => $microphone,
                'speaker' => $speaker,
                'audio_codec' => $audio_codec,
                'video_codec' => $video_codec
                ), "id = '{$option->id}'");
        else
            $success = $this->insert('pbx.agent_option', array(
                'agent_id' => $session->agent_id,
                'microphone' => $microphone,
                'speaker' => $speaker,
                'audio_codec' => $audio_codec,
                'video_codec' => $video_codec
            ));

        if ($success == true)
            return array(
                'result' => 'OK',
                'reason' => ''
            );
        else
            return array(
                'result' => 'ERR',
                'reason' => 'DB Error'
            );


    }

}