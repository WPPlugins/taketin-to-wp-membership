<?php

class TaketinMpTransfer {
    
    public static $admin_messages = array();
    
    private static $_this;

    private function __contruct() {
        $this->message = get_option('tmp-messages');
    }

    public static function get_instance() {
        self::$_this = empty(self::$_this) ? new TaketinMpTransfer() : self::$_this;
        return self::$_this;
    }

    public function get($key) {
        $messages = new TaketinMpMessages();
        return $messages->get($key);
    }

    public function set($key, $value) {
        $messages = new TaketinMpMessages();
        $messages->set($key, $value);
    }
}
