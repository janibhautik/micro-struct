<?php

/**
 * Session Class.
 *
 * @author Bhavik Patel
 */
class Session {

    /**
     * Initialization.
     */
    public function __construct() {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
            } else {
                if (session_id() === '') {
                    session_start();
                }
            }
        }
    }

    /**
     * Sets $value to $name session key.
     * Overrides if already exists.
     * 
     * @param   string  $name
     * @param   mixed   $value
     */
    public static function setValue($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     * Returns session value of given key.
     * Returns FALSE on session key not found.
     * 
     * @param   string  $name
     * @return  mixed
     */
    public static function getValue($name) {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : FALSE;
    }

    /**
     * Removes value from the session.
     * 
     * @param string $name
     */
    public static function removeValue($name) {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }

    public static function redirect($name) {
        if (!isset($_SESSION[$name])) {
            $config = eval(substr(file_get_contents(SYSC . 'config.php'), 5));
            if (!isset($config["route_defauls"]["login"])) {
                throw new Exception("Please specify route_defauls-login in config file.");
            }
            redirect(getBaseURL($config["route_defauls"]["login"]) . "?redirect=" . urlencode(System::getCurrentURL()));
        }
    }

    /**
     * Returns all set session data.
     * 
     * @return array
     */
    public static function getAll() {
        return $_SESSION;
    }

    /**
     * Destroy session
     */
    public static function destroy() {
        session_destroy();
    }

}
