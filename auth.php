<?php
/**
 * DokuWiki Plugin authhiorgserver (Auth Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  HiOrg Server GmbH <support@hiorg-server.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class auth_plugin_authhiorgserver extends DokuWiki_Auth_Plugin {
    
    private $ssourl = "";
    private $data = array();
    private $triedsilent = false;
    private $usersepchar = "@";

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(); // for compatibility

        $this->cando['addUser']     = false; // can Users be created?
        $this->cando['delUser']     = false; // can Users be deleted?
        $this->cando['modLogin']    = false; // can login names be changed?
        $this->cando['modPass']     = false; // can passwords be changed?
        $this->cando['modName']     = false; // can real names be changed?
        $this->cando['modMail']     = false; // can emails be changed?
        $this->cando['modGroups']   = false; // can groups be changed?
        $this->cando['getUsers']    = false; // can a (filtered) list of users be retrieved?
        $this->cando['getUserCount']= false; // can the number of users be retrieved?
        $this->cando['getGroups']   = false; // can a list of available groups be retrieved?
        $this->cando['logout']      = true; // can the user logout again? (eg. not possible with HTTP auth)
        $this->cando['external']    = true; // does the module do external auth checking?
        
        // $this->loadConfig(); // deprecated seit 2012

        $this->ssourl = $this->getConf('ssourl');
        $ov = $this->getConf('ov');
        if(!empty($ov)) {
            $this->ssourl = $this->addUrlParams($this->ssourl,array("ov"=>$ov));
        }

        $this->data = array();
        
        $this->triedsilent = (isset($_SESSION[DOKU_COOKIE]['auth']['hiorg']['triedsilent'])
                              && ($_SESSION[DOKU_COOKIE]['auth']['hiorg']['triedsilent'] == true));
        
        $this->success = true;
    }


    /**
     * Log off the current user 
     */
    public function logOff() {
        $url = $this->addUrlParams($this->ssourl,array("logout"=>1,"token"=>$this->data["token"],"weiter"=> $this->myUrl()));

        $this->data = array();
        $_SESSION[DOKU_COOKIE]['auth']['hiorg'] = array("triedsilent"=>true);

        send_redirect($url);
    }

    /**
     * Do all authentication 
     *
     * @param   string  $user    Username
     * @param   string  $pass    Cleartext Password
     * @param   bool    $sticky  Cookie should not expire
     * @return  bool             true on successful auth
     */
    public function trustExternal($user, $pass, $sticky = false) {
        
        if ($this->loadUserInfoFromSession()) {
            $this->setGlobalConfig();
            return true;
        }
        
        global $ACT;
        if($ACT == "login") {
            $this->processSSO();
        
            $this->setGlobalConfig();
            $this->saveUserInfoToSession();
            
        } elseif(!$this->triedsilent) {
            $_SESSION[DOKU_COOKIE]['auth']['hiorg']['triedsilent'] = $this->triedsilent = true;
            $this->SSOsilent();
        }
        
        return true;
    }
    
    function myUrl($urlParameters = '') {
        // global $ID;  // ist zu diesem Zeitpunkt noch nicht initialisiert
        $ID = getID();
        return wl($ID, $urlParameters, true, '&');
    }
    
    function processSSO() {
        
        // 1. Schritt: noch kein gueltiges Token vom HiOrg-Server erhalten
        if(empty($_GET["token"])) { 
            $ziel = $this->addUrlParams($this->ssourl,array("weiter"=> $this->myUrl(array("do"=>"login")), // do=login, damit wir für den 2. Schritt wieder hier landen
                                                            "getuserinfo"=>"name,vorname,username,email,user_id"));
            send_redirect($ziel);
        } 
        
        // 2. Schritt: Token vom HiOrg-Server erhalten: jetzt Login ueberpruefen und Nutzerdaten abfragen
        $token = $_GET["token"];

        $url = $this->addUrlParams($this->ssourl,array("token"=>$token));
        $daten = $this->getUrl($url);
        
        if(mb_substr( $daten ,0,2) != "OK") nice_die("Login beim HiOrg-Server fehlgeschlagen!");
        $daten = unserialize(base64_decode(mb_substr( $daten , 3)));

        // wenn per Konfig auf eine Organisation festgelegt, Cross-Logins abfangen:
        $ov = $this->getConf('ov');
        if( !empty($ov) && ($daten["ov"] != $ov) ) nice_die("Falsches Organisationskuerzel: ".$daten["ov"]. ", erwartet: ".$ov);

        // $daten = array("name"=>"Hansi", "vorname"=>"Tester", "username"=>"admin", "email"=>"test@test.de", "user_id"=>"abcde12345", "ov"=>"xxx");
        
        $this->data = array("uid"  => $daten["user_id"],
                            "user" => $this->buildUser($daten["username"],$daten["ov"]),
                            "name" => $daten["vorname"]." ".$daten["name"],
                            "mail" => $daten["email"],
                            "token"=> $token);
        $this->data["grps"] = $this->getGroups($this->data["user"]);
        
        return true;
    }
    
    function SSOsilent() {
        $ziel = $this->addUrlParams($this->ssourl, array("weiter"      => $this->myUrl(array("do"=>"login")), // do=login, damit wir für den 2. Schritt wieder hier landen
                                                         "getuserinfo" => "name,vorname,username,email,user_id",
                                                         "silent"      => $this->myUrl()));
        send_redirect($ziel);
    }
    
    function getGroups($user) {
        if(empty($user)) {
            return "";
        }
        
        $ov = trim($this->getConf("ov"));
        
        global $conf;
        $return = array($this->cleanGroup($conf["defaultgroup"]));
        
        $groups = array("group1"=>$this->getConf("group1_name"),
                        "group2"=>$this->getConf("group2_name"),
                        "admin" =>"admin");
        
        foreach($groups as $name => $group) {
            $users = $this->getConf($name."_users");
            if(!empty($group) && !empty($users)) {
                if(!empty($ov)) { // ov automatisch ergänzen, wenn bekannt und nicht genannt
                    $userary = explode(",",$users);
                    $users = "";
                    foreach($userary as $u) {
                        if(strpos($u,$this->usersepchar)===false) {
                            $u = $this->buildUser($u, $ov);
                        }
                        $users .= "," . $u;
                    }
                }
                if(strpos($users,$user)!==false) {
                    $return[] = $this->cleanGroup($group);
                }
            }
        }

        return $return;
    }
    
    function buildUser($user, $ov="") {
        if(empty($ov)) {
            $ov = trim($this->getConf("ov"));
        }
        return $this->cleanUser($user) . $this->usersepchar . $this->cleanUser($ov);
    }
    
    function loadUserInfoFromSession() {
        if(isset($_SESSION[DOKU_COOKIE]['auth']['hiorg'])) {
            $data = $_SESSION[DOKU_COOKIE]['auth']['hiorg'];
            if(empty($data) || !is_array($data) || empty($data["token"])) {
                return false;
            } else {
                $this->data = $data;
                return true;
            }
        }
        return false;
    }
    
    function saveUserInfoToSession() {
        if(!empty($this->data["token"])) {
            $_SESSION[DOKU_COOKIE]['auth']['hiorg'] = $this->data;
            return true;
        }
        return false;
    }
    
    function setGlobalConfig() {
        global $USERINFO;
        $USERINFO['name'] = $this->data['name'];
        $USERINFO['mail'] = $this->data['mail'];
        $USERINFO['grps'] = $this->data['grps'];
        $_SERVER['REMOTE_USER'] = $this->data['user'];
        $_SESSION[DOKU_COOKIE]['auth']['user'] = $this->data['user'];
        $_SESSION[DOKU_COOKIE]['auth']['pass'] = "";
        $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
        return true;
    }
    
    /**
     * Helper: builds URL by adding parameters
     *
     * @param   string $url URL
     * @param   array $params additional parameters
     * @return  string
     */
    function addUrlParams($url, $params) {
        if(!is_array($params) || empty($params)) return $url;

        $parary = array();
        $p = strpos($url,"?");
        if($p!==false) {
            foreach(explode("&",substr($url,$p+1)) as $par) {
                $q = strpos($par,"=");
                $parary[substr($par,0,$q)] = substr($par,$q+1);
            }
            $url = substr($url,0,$p);
        }
        
        foreach($params as $par => $val) {
            $parary[rawurlencode($par)] = rawurlencode($val);
        }
        
        $ret = $url;
        $sep = "?";
        foreach($parary as $par => $val) {
            $ret .= $sep . $par . "=" . $val;
            $sep = "&";
        }
        return $ret;
    }
    
    /**
     * Helper: fetches external URL via GET
     *
     * @param   string $url URL
     * @return  string
     */
    function getUrl($url) {
        $http = new DokuHTTPClient();
        $daten = $http->get($url);
        
        // Workarounds, o.g. Klasse macht manchmal Probleme:
        if(empty($daten)) {
            if(function_exists("curl_init")) {
                $ch = curl_init($url);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                $daten = curl_exec($ch);
                curl_close($ch);
            
            } else {
                if (!ini_get("allow_url_fopen") && version_compare(phpversion(), "4.3.4", "<=")) {
                    ini_set("allow_url_fopen", "1");
                }
                if ($fp = @fopen($url, "r")) {
                    $daten = "";
                    while (!feof($fp)) $daten.= fread($fp, 1024);
                    fclose($fp);
                }
            }
        }
        
        return $daten;
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @param   string $user the user name
     * @return  array containing user data or false
     */
    /*public function getUserData($user) {
        // FIXME implement
        return false;
    }*/


    /**
     * Bulk retrieval of user data [implement only where required/possible]
     *
     * Set getUsers capability when implemented
     *
     * @param   int   $start     index of first user to be returned
     * @param   int   $limit     max number of users to be returned
     * @param   array $filter    array of field/pattern pairs, null for no filter
     * @return  array list of userinfo (refer getUserData for internal userinfo details)
     */
    //public function retrieveUsers($start = 0, $limit = -1, $filter = null) {
        // FIXME implement
    //    return array();
    //}

    /**
     * Return a count of the number of user which meet $filter criteria
     * [should be implemented whenever retrieveUsers is implemented]
     *
     * Set getUserCount capability when implemented
     *
     * @param  array $filter array of field/pattern pairs, empty array for no filter
     * @return int
     */
    //public function getUserCount($filter = array()) {
        // FIXME implement
    //    return 0;
    //}

    /**
     * Retrieve groups [implement only where required/possible]
     *
     * Set getGroups capability when implemented
     *
     * @param   int $start
     * @param   int $limit
     * @return  array
     */
    //public function retrieveGroups($start = 0, $limit = 0) {
        // FIXME implement
    //    return array();
    //}

    /**
     * Return case sensitivity of the backend
     *
     * When your backend is caseinsensitive (eg. you can login with USER and
     * user) then you need to overwrite this method and return false
     *
     * @return bool
     */
    public function isCaseSensitive() {
        return false;
    }

    /**
     * Sanitize a given username
     *
     * This function is applied to any user name that is given to
     * the backend and should also be applied to any user name within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce username restrictions.
     *
     * @param string $user username
     * @return string the cleaned username
     */

    public function cleanUser($user) {
        global $conf;
        return cleanID(str_replace(':', $conf['sepchar'], $user));
    }
    /**
     * Sanitize a given groupname
     *
     * This function is applied to any groupname that is given to
     * the backend and should also be applied to any groupname within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce groupname restrictions.
     *
     * Groupnames are to be passed without a leading '@' here.
     *
     * @param  string $group groupname
     * @return string the cleaned groupname
     */
    public function cleanGroup($group) {
        global $conf;
        return cleanID(str_replace(':', $conf['sepchar'], $group));
    }

    /**
     * Check Session Cache validity [implement only where required/possible]
     *
     * DokuWiki caches user info in the user's session for the timespan defined
     * in $conf['auth_security_timeout'].
     *
     * This makes sure slow authentication backends do not slow down DokuWiki.
     * This also means that changes to the user database will not be reflected
     * on currently logged in users.
     *
     * To accommodate for this, the user manager plugin will touch a reference
     * file whenever a change is submitted. This function compares the filetime
     * of this reference file with the time stored in the session.
     *
     * This reference file mechanism does not reflect changes done directly in
     * the backend's database through other means than the user manager plugin.
     *
     * Fast backends might want to return always false, to force rechecks on
     * each page load. Others might want to use their own checking here. If
     * unsure, do not override.
     *
     * @param  string $user - The username
     * @return bool
     */
    //public function useSessionCache($user) {
      // FIXME implement
    //}
}

// vim:ts=4:sw=4:et: