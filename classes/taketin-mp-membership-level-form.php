<?php

class TaketinMpLevelForm {

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

    protected function name() {
        $name = filter_input(INPUT_POST, 'name');
        if (empty($name)) {
            $this->errors['email'] = '会員レベル名称を入力してください。';
            return;
        }
        $this->sanitized['name'] = sanitize_text_field($name);
    }

    protected function alias() {
        $alias = filter_input(INPUT_POST, 'alias');
        $this->sanitized['alias'] = sanitize_text_field($alias);
    }

    protected function levelclass() {
        $levelclass = filter_input(INPUT_POST, 'levelclass');
        if (!is_numeric($levelclass)) {
            $this->errors['email'] = '階級は数値で入力してください。';
            return;
        }
        $this->sanitized['levelclass'] = sanitize_text_field($levelclass);
    }

    protected function role() {
        $role = filter_input(INPUT_POST, 'role');
        $this->sanitized['role'] = sanitize_text_field($role);
    }

    protected function classes() {
        $classes = filter_input(INPUT_POST, 'classes');
        $this->sanitized['classes'] = sanitize_text_field($classes);
    }

    protected function permissions() {
        $this->sanitized['permissions'] = 63;
    }
    protected function tickets() {
        $tickets = filter_input( INPUT_POST, "tickets", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if ($tickets == "") {
            $this->errors['tickets'] = 'チケットを最低１つは選択してください。';
            return;
        }
        $this->sanitized['tickets'] = $tickets;
    }

    protected function subscription_period() {
        $subscript_duration_type = filter_input(INPUT_POST, 'subscription_duration_type');

        if ($subscript_duration_type == TaketinMpMembershipLevel::NO_EXPIRY) {
            $this->sanitized['subscription_period'] = "";
            return;
        }

        $subscription_period = filter_input(INPUT_POST, 'subscription_period_'. $subscript_duration_type);
        if (($subscript_duration_type == TaketinMpMembershipLevel::FIXED_DATE)){
            $dateinfo = date_parse($subscription_period);
            if ($dateinfo['warning_count']|| $dateinfo['error_count']){
                $this->errors['subscription_period'] = _("Date format is not valid.");
                return;
            }
            $this->sanitized['subscription_period'] = sanitize_text_field($subscription_period);
            return;
        }
        
        if (!is_numeric($subscription_period)) {
            $this->errors['subscription_period'] = _("Access duration must be > 0.");
            return;
        }
        $this->sanitized['subscription_period'] = sanitize_text_field($subscription_period);
    }

    protected function subscription_duration_type(){
        $subscription_duration_type = filter_input(INPUT_POST, 'subscription_duration_type');
        $this->sanitized['subscription_duration_type'] = $subscription_duration_type;
        return;
    }
    protected function subscription_unit(){
        
    }
    protected function loginredirect_page() {
        
    }

    protected function category_list() {
        
    }

    protected function page_list() {
        
    }

    protected function post_list() {
        
    }

    protected function comment_list() {
        
    }

    protected function attachment_list() {
        
    }

    protected function custom_post_list() {
        
    }

    protected function disable_bookmark_list() {
        
    }

    protected function options() {
        
    }

    protected function campaign_name() {
        
    }

    protected function protect_older_posts() {
        $checked = filter_input(INPUT_POST, 'protect_older_posts');
        $this->sanitized['protect_older_posts'] = empty($checked) ? 0 : 1;
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

}
