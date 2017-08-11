<?php
class TaketinMpMember {
    
    private static $_instance = null;

    private function __construct() {
        //NOP
    }

    public static function get_instance() {
        self::$_instance = empty(self::$_instance) ? new TaketinMpMember() : self::$_instance;
        return self::$_instance;
    }
    
    // 会員登録
    function create_member() {
        //Check nonce
        if (
            !isset($_POST['_wpnonce_create_tmp_user_admin_end']) 
            || !wp_verify_nonce($_POST['_wpnonce_create_tmp_user_admin_end'], 'create_tmp_user_admin_end' )
        ){
            //Nonce check failed.
            wp_die('Nonce認証エラー 不正なアクセスです。');
        }
        global $wpdb;
        $default_level_fields = array(
            'unique_code' => '',
            'email' => '',
            'name_sei' => '',
            'name_mei' => '',
            'memberships_id' => '',
            'tickets' => ''
        );
        $tmmessage = new TaketinMpMessages();
        $form = new TaketinMpMemberForm($default_level_fields);
        if ($form->is_valid()) {
            $member_info = $form->get_sanitized();
            //tmp_membersテーブル登録
            $wpdb->insert(
                $wpdb->prefix . "tmp_members",
                $insert_data = array(
                    'name' => $member_info['name_sei'] . " " . $member_info['name_mei'],    // 名前
                    'email' => $member_info['email'],
                    'unique_code' => $member_info['unique_code'],
                    'ticket_list_serialized' => serialize($member_info['tickets']),
                    'memberships_id' => $member_info['memberships_id'],
                    'progress' => 0,
                    'memberships_check_date' => date("Y-m-d H:i:s"),
                    'last_login' => "0000-00-00 0:0:0",
                    'created' => date("Y-m-d H:i:s")
                ),
                $format = array(
                    '%s',   //name
                    '%s',   //email
                    '%s',   //unique_code
                    '%s',   //ticket_list_serialized
                    '%d',   //memberships_id
                    '%d',   //progress
                    '%s',   //memberships_check_date
                    '%s',   //last_login
                    '%s',   //created
                )
            );
            $message = array('succeeded' => true, 'message' => '<p>会員を登録しました。</p>');
            $tmmessage->set('status', $message);
            wp_redirect('admin.php?page=taketin_mp_membership');
            exit(0);
        }
        $message = array('succeeded' => false, 'message' => '登録に失敗しました。エラーメッセージをご確認ください。', 'extra' => $form->get_errors());
        $tmmessage->set('status', $message);
    }
    
    // 会員更新
    function edit_member($id) {
        //Check nonce
        if (
            !isset($_POST['_wpnonce_edit_tmp_user_admin_end']) 
            || !wp_verify_nonce($_POST['_wpnonce_edit_tmp_user_admin_end'], 'edit_tmp_user_admin_end' )){
            //Nonce check failed.
            wp_die('Nonce認証エラー 不正なアクセスです。');
        }
        
        global $wpdb;
        $query = $wpdb->prepare("SELECT email, unique_code, progress FROM " . $wpdb->prefix . "tmp_members WHERE id = %d", $id);
        $member = $wpdb->get_row($query, ARRAY_A);
        $member += array(
            'name_sei' => '',
            'name_mei' => '',
            'memberships_id' => '',
            'tickets' => ''
        );
        
        $tmmessage = new TaketinMpMessages();
        $form = new TaketinMpMemberForm($member);
        if ($form->is_valid()) {
            $member = $form->get_sanitized_member_form_data();
            $member['name'] = $member['name_sei'] . " " . $member['name_mei'];    // 名前
            unset($member['name_sei']);
            unset($member['name_mei']);
            $member['ticket_list_serialized'] = serialize($member['tickets']);
            unset($member['tickets']);
            $member['memberships_check_date'] = date("Y-m-d H:i:s");
            
            $wpdb->update($wpdb->prefix . "tmp_members", $member, array('id' => $id));
            
            $message = array('succeeded' => true, 'message' => '<p>対象の会員を更新しました。</p>');
            $tmmessage->set('status', $message);
            wp_redirect('admin.php?page=taketin_mp_membership');
            exit(0);
        }
        $message = array('succeeded' => false, 'message' => '更新に失敗しました。エラーメッセージをご確認ください。', 'extra' => $form->get_errors());
        $tmmessage->set('status', $message);
    }
    

    // 会員登録: ユーザーからの登録
    function create_member_authenticate($data) {
        global $wpdb;
        //-----------------------------------
        //名前チェック
        //-----------------------------------
        if (empty($data['name_sei']) || empty($data['name_mei'])) {
            //名前エラー
            $error_msg = "名前が設定されていません。[DB]";
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $error_msg, 0);
            return array("result" => false, "error_msg" => $error_msg);
        }
        $data = array_merge(array('name' => sanitize_text_field($data['name_sei'] . " " . $data['name_mei'])), $data);
        unset($data['name_sei']);
        unset($data['name_mei']);
        //-----------------------------------
        //メールアドレスチェック
        //-----------------------------------
        if (!is_email($data['email'])) {
            $error_msg = "メールアドレスが設定されていません。[DB]";
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $error_msg, 0);
            return array("result" => false, "error_msg" => $error_msg);
        }
        $sql = "SELECT COUNT(id) FROM " . $wpdb->prefix . "tmp_members WHERE email = %s ";
        $query = $wpdb->prepare($sql, $data['email']);
        $result = $wpdb->get_var($query);
        if ($result > 0) {
            $error_msg = "メールアドレスが既に登録されています。[DB]";
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $error_msg, 0);
            return array("result" => false, "error_msg" => $error_msg);
        }
        $data['unique_code'] = sanitize_text_field($data['unique_code']);
        $data['memberships_id'] = sanitize_text_field($data['memberships_id']);
        
        //-----------------------------------
        //新規登録処理
        //-----------------------------------
        $res = $wpdb->insert(
                $wpdb->prefix . "tmp_members",
                $data,
                $format = 
                    array(
                        '%s',   //name
                        '%s',   //email
                        '%s',   //unique_code
                        '%s',   //ticket_list_serialized
                        '%d',   //memberships_id
                        '%d',   //progress
                        '%s',   //memberships_check_date
                        '%s',   //last_login
                        '%s',   //created
                    )
        );
        if ($res != 1) {
            //エラー
            $error_msg = "新規ユーザー登録に失敗しました。[DB]";
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $error_msg, 0);
            return array("result" => false, "error_msg" => $error_msg);
        }
        
        return array("result" => true, "member_id" => $wpdb->insert_id);
    }
}