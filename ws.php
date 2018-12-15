<?php
    /**
     * Created by PhpStorm.
     * User: volkan
     * Date: 26.11.2015
     * Time: 21:15
     */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
    include "libs/main.class.php";
    include "libs/database.class.php";
    include "libs/validate.class.php";
    include "libs/method.class.php";
    include "nusoap/lib/nusoap.php";
    $ws = new main();
    $db = new database();
    $j = new j(); // bu class a farklı bir isim veremedim. çünkü method isimleri $class$method şeklinde ilerliyor. Örn: jAgentLogin gibi.

    $server = new soap_server();
    $server->configureWSDL("JupiterWS");
    $server->wsdl->schemaTargetNamespace = "http://jupiterws.dna.com";

    $ws->registerMethod($server, 'getDateTime', array(), array('return' => 'xsd:string'));

    $ws->registerComplexMethod($server, 'AgentLogin',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'agentId' => array('name' => 'agentId', 'type' => 'xsd:integer'),
            'domainName' => array('name' => 'domainName', 'type' => 'xsd:integer'),
            'sessionId' => array('name' => 'sessionId', 'type' => 'xsd:string'),
            'sipUserName' => array('name' => 'sipUserName', 'type' => 'xsd:string'),
            'sipAuth' => array('name' => 'sipAuth', 'type' => 'xsd:string'),
            'sipPassword' => array('name' => 'sipPassword', 'type' => 'xsd:string'),
            'sipServerIp' => array('name' => 'sipServerIp', 'type' => 'xsd:string'),
            'sipServerHostname' => array('name' => 'sipServerHostname', 'type' => 'xsd:string'),
            'sipServerPort' => array('name' => 'sipServerPort', 'type' => 'xsd:string'),
            'states' => array('name' => 'states', 'type' => 'xsd:string'),
            'skills' => array('name' => 'skills', 'type' => 'xsd:string'),
            'defMicrophone' => array('name' => 'defMicrophone', 'type' => 'xsd:string'),
            'defSpeaker' => array('name' => 'defSpeaker', 'type' => 'xsd:string'),
            'defAudioCodec' => array('name' => 'defAudioCodec', 'type' => 'xsd:string'),
            'defVideoCodec' => array('name' => 'defVideoCodec', 'type' => 'xsd:string')
        )
    );

    $ws->registerComplexMethod($server, 'AgentGetStates',
        array('p' => 'xsd:string'),
        array(
            'states' => array('name' => 'states', 'type' => 'xsd:string')
        )
    );

    $ws->registerComplexMethod($server, 'AgentChangeState',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'reason' => array('name' => 'reason', 'type' => 'xsd:string')
        )
    );

    $ws->registerComplexMethod($server, 'AgentGetAnnounce',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'reason' => array('name' => 'reason', 'type' => 'xsd:string'),
            'records' => array('name' => 'records', 'type' => 'xsd:string')
        )
    );


    $ws->registerComplexMethod($server, 'AgentGetAnnounceFiles',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'reason' => array('name' => 'reason', 'type' => 'xsd:string'),
            'recordFile1' => array('name' => 'recordFile1', 'type' => 'xsd:string'),
            'recordContent1' => array('name' => 'recordContent1', 'type' => 'xsd:base64Binary'),
            'recordFile2' => array('name' => 'recordFile2', 'type' => 'xsd:string'),
            'recordContent2' => array('name' => 'recordContent2', 'type' => 'xsd:base64Binary'),
            'recordFile3' => array('name' => 'recordFile3', 'type' => 'xsd:string'),
            'recordContent3' => array('name' => 'recordContent3', 'type' => 'xsd:base64Binary'),
            'recordFile4' => array('name' => 'recordFile4', 'type' => 'xsd:string'),
            'recordContent4' => array('name' => 'recordContent4', 'type' => 'xsd:base64Binary'),
            'recordFile5' => array('name' => 'recordFile5', 'type' => 'xsd:string'),
            'recordContent5' => array('name' => 'recordContent5', 'type' => 'xsd:base64Binary'),
            'recordFile6' => array('name' => 'recordFile6', 'type' => 'xsd:string'),
            'recordContent6' => array('name' => 'recordContent6', 'type' => 'xsd:base64Binary'),
            'recordFile7' => array('name' => 'recordFile7', 'type' => 'xsd:string'),
            'recordContent7' => array('name' => 'recordContent7', 'type' => 'xsd:base64Binary'),
            'recordFile8' => array('name' => 'recordFile8', 'type' => 'xsd:string'),
            'recordContent8' => array('name' => 'recordContent8', 'type' => 'xsd:base64Binary'),
            'recordFile9' => array('name' => 'recordFile9', 'type' => 'xsd:string'),
            'recordContent9' => array('name' => 'recordContent9', 'type' => 'xsd:base64Binary'),
            'recordFile10' => array('name' => 'recordFile10', 'type' => 'xsd:string'),
            'recordContent10' => array('name' => 'recordContent10', 'type' => 'xsd:base64Binary')
        )
    );


    $ws->registerComplexMethod($server, 'AgentRemoveAnnounce',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'reason' => array('name' => 'reason', 'type' => 'xsd:string')
        )
    );


    $ws->registerComplexMethod($server, 'AgentAddAnnounce',
        array(
            "p" => "xsd:string",
            "fileContent" => "xsd:base64Binary"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'reason' => array('name' => 'reason', 'type' => 'xsd:string')
        )
    );

    $ws->registerComplexMethod($server, 'AgentSetAnnounceState',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'reason' => array('name' => 'reason', 'type' => 'xsd:string')
        )
    );

    $ws->registerComplexMethod($server, 'AgentSetSoundOption',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'reason' => array('name' => 'reason', 'type' => 'xsd:string')
        )
    );

    $ws->registerComplexMethod($server, 'GetPublicIp',
        array(
            "p" => "xsd:string"
        ),
        array(
            'result' => array('name' => 'result', 'type' => 'xsd:string'),
            'publicIp' => array('name' => 'publicIp', 'type' => 'xsd:string')
        )
    );


    $POST_DATA = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
    $server->service($POST_DATA);
    exit();
?>