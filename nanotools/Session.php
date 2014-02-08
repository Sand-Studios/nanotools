<?php

class Session {

    public static function init() {
        return session_start();
    }

    public static function get($key, $default = null) {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return $default;
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function delete($key) {
        unset($_SESSION[$key]);
    }

    public static function commit() {
        session_write_close();
    }

    public static function destroy() {
        session_unset();
        return session_destroy();
    }

}
