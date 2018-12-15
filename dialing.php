<?php
    /**
     * Created by PhpStorm.
     * User: volkan
     * Date: 23.12.2015
     * Time: 15:22
     */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
    include "libs/main.class.php";
    include "libs/database.class.php";
    include "libs/validate.class.php";
    $ws = new main();
    $db = new database();

    preg_match("/^SIP\/(.*)-.(.*)$/", $_POST['from'], $fromExt);
    if (is_numeric($fromExt[1])) {
        $domain = $db->selectOne('pbx.station s, main.domain d', array('d.prefix'), "d.id = s.domain_id and s.extension = '$fromExt[1]'");
        $callerId = $db->selectOne('pbx.caller_id', array('number', 'name'), "extension = '$fromExt[1]'");
        echo "Y|{$domain->prefix}|{$callerId->number}|{$callerId->name}";
    } else {
        echo "N";
    }
    ?>