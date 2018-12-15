<?php
class validate extends main {
    public $l;
    public function __construct() {
        global $GLOBALS;
        $this->l =& $GLOBALS["lang"];
    }

    public $error = array();

    public function controllerSyntax($value) {
        if (ereg("[A-Za-z]\/[A-Za-z]", $value))
            return true;
        else
            return false;
    }

    public function isLikeType($string) {
        if ($string != 'share' and $string != 'comment' and $string != 'video' and $string != 'image')
            return false;
        else
            return true;
    }

    public function isMail($field, $value){
        if (!eregi ("^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}$", $value))
            return $this->error[] = $field.$this->l['errMailAddress'];
    }

    public function notNull($elements = array()){
        foreach ($elements as $field => $value) {
            if ($value == '')
                $this->error[] = $field.$this->l['errNotNull'];
        }
        return $this->error;
    }

    public function isNumeric($elements = array()){
        foreach ($elements as $field => $value) {
            if (!is_numeric($value))
                $this->error[] = $field.$this->l['errIsNotNumeric'];
        }
        return $this->error;
    }

    public function isAZ09($elements = array()) { // a-z 0-9 _  . ve space
        foreach ($elements as $field => $value) {
            if (!preg_match("^[ _A-Za-z0-9öüÖÜİışŞçÇğĞ\.]+$", $value))
                $this->error[] = $field.$this->l['errIsNotAZ09'];
        }
        return $this->error;
    }

    public function getErrors() {
        static $totalError;
        if (isset($this->error[0])) {
            foreach ($this->error as $error)
                $totalError .= $error.'<br>';
        }
        return $totalError;
    }

    public function ifFailShowErrors() {
        if (isset($this->error[0])) {
            return true;
        } else
            return false;
    }

    public function isStatus($elements = array()) {
        foreach ($elements as $field => $value) {
            if ($value != 'A' and $value != 'P')
                $this->error[] = $field.$this->l['errStatusValue'];
        }
        return $this->error;
    }

    public function isYesNo($elements = array()) {
        foreach ($elements as $field => $value) {
            if ($value != 'Y' and $value != 'N')
                $this->error[] = $field.$this->l['errYesNoValue'];
        }
        return $this->error;
    }

    public function forwardGuest() {
        if(@$_SESSION['id'] == "" and !ereg('_login\/[A-Za-z]', $_GET['q'])){
            Header("Location: ".parent::$projectUri."_login/index");
            die();
        }
    }

    public function checkSessionForward($sessionParameter, $forwardUri = parent::projectUri){
        if(@$_SESSION[$sessionParameter] == ""){
            $this->refresh($forwardUri);
            die();
        }
        else{
            return true;
        }
    }

    public function userName($field, $value){
        foreach ($elements as $field => $value) {
            if (!preg_match("^[' A-Za-z0-9\.\-]+$", $value))
                $this->error[] = "$field Username Invalid";
        }
    }

    public function isDomain($field, $value)
    {
        if (!preg_match("/^[A-Z0-9._%-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]$/i", $value))
            $this->error[] = "$field Struct Invalid.";
    }

    public function macAddress($elements = array())
    {
        foreach ($elements as $field => $value) {
            if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $value))
                $this->error[] = "$field Mac Invalid";
        }
    }
    public function ipAddress($elements = array())
    {
        foreach ($elements as $field => $value) {
            if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $value))
                $this->error[] = "$field IP Invalid";
        }
    }

}

?>