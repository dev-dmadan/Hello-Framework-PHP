<?php
Defined('BASE_PATH') or die(ACCESS_DENIED);

use \Firebase\JWT\JWT;

class Auth {

    private $useJWT;

    public function __construct() {
        $this->useJWT = USE_JWT;
    }

    /**
     * Method alreadyLogin
     * Check the user already logged in the system or not
     * Check authorization by token or session
     * If the verify is success, the user can access process / page
     * If the verify is fail, the user will be redirect to login
     */
    public function alreadyLogin() {
        if(!$this->isLogin()) {
            if(!$this->useJWT) {
                session_unset();
				session_destroy();
            }

            header("Location: ". SITE_URL. 'login');
            die();
        }
    }

    /**
     * 
     */
    public function isLogin() {
        $isLogin = $this->useJWT ? $this->verifyJWT() : $this->verifySession();
        
        return $isLogin;
    }

    /**
     * Method isAuthorised
     * Check authorization user by token JWT
     * @return {boolean}
     */
    public function isAuthorised() {
        return $this->verifyJWT(true);
    }

    /**
     * Method buildJWT
     * Generate and build token
     */
    public function buildJWT($data) {
        $payload = array(
            "iss" => SITE_URL,
            "aud" => SITE_URL,
            "iat" => time(),
            "nbf" => time(),
            "exp" => time() + 7200,
            "data" => $data
        );
        $jwt = JWT::encode($payload, KEY_AUTH);

        return $jwt;
    }

    /**
     * Method verifyJWT
     * Check token JWT is verify or not
     * @param {boolean} isReturnMessage
     * @return {boolean | object} verify return boolean if the param is false, return object is the param is true
     *      verify.success {boolean}
     *      verify.message {message}
     */
    private function verifyJWT($isReturnMessage = false) {
        $verify = $isReturnMessage ? (Object)array('success' => false, 'message' => '') : false;
        
        $http_authorization = (isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) ? $_SERVER['HTTP_AUTHORIZATION'] : false;
        $query_string = (isset($_GET[QUERY_STRING_AUTH]) && !empty($_GET[QUERY_STRING_AUTH])) ? $_GET[QUERY_STRING_AUTH] : false;
        $cookies = (isset($_COOKIE[QUERY_STRING_AUTH]) && !empty($_COOKIE[QUERY_STRING_AUTH])) ? $_COOKIE[QUERY_STRING_AUTH] : false;
        
        $authHeader = $http_authorization ? $http_authorization : ($query_string ? $query_string : ($cookies ? $cookies : false));
        
        try {
            if(!$authHeader) {
                if($isReturnMessage) {
                    throw new Exception('Auth Header is undefined');
                }

                return false;
            }

            if($query_string || $cookies) {
                $JWT = $authHeader;
            }
            else {
                $tempJWT = explode("Bearer ", $authHeader);
                $JWT = isset($tempJWT[1]) ? $tempJWT[1] : false;
            }

            if(!$JWT) {
                if($isReturnMessage) {
                    throw new Exception('JWT is undefined');
                }

                return false;
            }

            $decoded = JWT::decode($JWT, KEY_AUTH, array('HS256'));
            if($isReturnMessage) {
                $verify->success = true;
            }
        } 
        catch (Exception $e) {
            if($isReturnMessage) {
                $verify->message = $e->getMessage();
            }
            else {
                return false;
            }
        }

        return $verify ;
    }

    /**
     * Method verifySession
     * Check session login is verify or not
     * @return {boolean} verify
     */
    private function verifySession() {
        $login = isset($_SESSION['sess_login']) && !empty($_SESSION['sess_login']) ? $_SESSION['sess_login'] : false;
        $timeout = isset($_SESSION['sess_timeout']) && !empty($_SESSION['sess_timeout']) ? strtotime($_SESSION['sess_timeout']) : false;

        if(!$login) { 
            return false; 
        }
		
		if($login && $login === true && (time() > $timeout)) {
            $_SESSION['sess_login'] = false;
            
			return false;
		}

		return true;
    }
}