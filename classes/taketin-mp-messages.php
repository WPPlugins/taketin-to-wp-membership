<?php
/**
 * メッセージ関連
 */
class TaketinMpMessages {

    private $messages;
    private $session_key;

    public function __construct() {
        $this->messages = get_option('taketin-mp-messages');
        $this->sesion_key = isset($_COOKIE['taketin-mp-session']) ? $_COOKIE['taketin-mp-session'] : "0";
    }

    public function get($key) {
        $combined_key = $this->session_key . '_' . $key;
        if (isset($this->messages[$combined_key])) {
            $m = $this->messages[$combined_key];
            unset($this->messages[$combined_key]);
            update_option('taketin-mp-messages', $this->messages);
            return $m;
        }
        return '';
    }

    public function set($key, $value) {
        $combined_key = $this->session_key . '_' . $key;
        $this->messages[$combined_key] = $value;
        update_option('taketin-mp-messages', $this->messages);
    }

}
