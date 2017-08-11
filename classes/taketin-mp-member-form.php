<?php

class TaketinMpMemberForm {

    protected $fields;
    protected $op;
    protected $errors;
    protected $sanitized;

    public function __construct($fields) {
        $this->fields = $fields;
        $this->sanitized = array();
        foreach ($fields as $key => $value)
            $this->$key();
    }

    protected function id() {
        
    }
    
    protected function email() {
        global $wpdb;
        $email = filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW);
        if (empty($email)) {
            $this->errors['email'] = 'メールアドレスを入力してください。';
            return;
        }
        if (!is_email($email)) {
            $this->errors['email'] = '正しいメールアドレスを入力してください。';
            return;
        }
        $saned = sanitize_email($email);
        $query = "SELECT count(id) FROM {$wpdb->prefix}tmp_members WHERE email= %s";
        $member_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); 
        if (!empty($member_id)) {
            $query .= ' AND id !=%d';
            $result = $wpdb->get_var($wpdb->prepare($query, strip_tags($saned), $member_id));
        }
        else{            
            $result = $wpdb->get_var($wpdb->prepare($query, strip_tags($saned)));
        }
        
        if ($result > 0) {
            if ($saned != $this->fields['email']) {
                $this->errors['email'] = '既に同じメールアドレスが登録されています。';
                return;
            }
        }
        $this->sanitized['email'] = $saned;
    }
    
    protected function name_sei() {
        $name_sei = filter_input(INPUT_POST, 'name_sei', FILTER_SANITIZE_STRING);
        if (empty($name_sei)) {
            $this->errors['name_sei'] = '名前 姓を入力してください。';
            return;
        }
        $this->sanitized['name_sei'] = sanitize_text_field($name_sei);
    }
    
    protected function name_mei() {
        $name_mei = filter_input(INPUT_POST, 'name_mei', FILTER_SANITIZE_STRING);
        if (empty($name_mei)) {
            $this->errors['name_mei'] = '名前 名を入力してください。';
            return;
        }
        $this->sanitized['name_mei'] = sanitize_text_field($name_mei);
    }

    protected function unique_code() {
        $unique_code = filter_input(INPUT_POST, 'unique_code');
        if (empty($unique_code)) {
            $this->errors['unique_code'] = 'ユニークコードを入力してください。';
            return;
        }
        $this->sanitized['unique_code'] = sanitize_text_field($unique_code);
    }

    protected function membership_level() {
        $membership_level = filter_input(INPUT_POST, 'membership_level');
        if (empty($membership_level)) {
            $this->errors['membership_level'] = '会員レベルを選択してください。';
            return;
        }
        $this->sanitized['membership_level'] = sanitize_text_field($membership_level);
    }

    protected function memberships_id() {
        $memberships_id = filter_input(INPUT_POST, 'memberships_id');
        $this->sanitized['memberships_id'] = sanitize_text_field($memberships_id);
    }

    protected function progress() {
        $progress = filter_input(INPUT_POST, 'progress');
        if ($progress == '') $progress = 0;
        $this->sanitized['progress'] = sanitize_text_field($progress);
    }

    protected function tickets() {
        $tickets = filter_input( INPUT_POST, "tickets", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if ($tickets == "") {
            $this->errors['tickets'] = 'チケット';
            return;
        }
        
        //DBから利用中のチケットIDを取得し、そのチケット情報をAPIから取得する
		list($error, $api_tickets) = TaketinMpUtils::get_api_tickets();
        
        $filter_tickets = array();
		if (empty($error)) {
		    $ticket_data = array();
		    foreach ($api_tickets as $ticket) {
		        if (in_array($ticket['ticket_id'], $tickets)) {
		            $filter_tickets[] = $ticket['ticket_id'];
		        }
		    }
		}
		
        $this->sanitized['tickets'] = $filter_tickets;
    }

    public function is_valid() {
        return count($this->errors) < 1;
    }

    public function get_sanitized() {
        return $this->sanitized;
    }

    public function get_errors() {
        return $this->errors;
    }
    
    public function get_sanitized_member_form_data() {
        return $this->sanitized;
    }
}
