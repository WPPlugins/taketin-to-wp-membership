<?php

class TmpAuth {

    public $protected;
    public $permitted;
    private $isLoggedIn;
    private $lastStatusMsg;
    private static $_this;
    private $_error_msg;

    private function __construct() {
        $this->isLoggedIn = false;
        $this->protected = TmpProtection::get_instance();
    }

    private function init() {
        $valid = $this->_validate();
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: " . ($valid ? "valid" : "invalid"), 0);
    }

    public static function get_instance() {
        if (empty(self::$_this)) {
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: get_instance start.", 0);
            self::$_this = new TmpAuth();
            self::$_this->init();
        }
        return self::$_this;
    }
    
    /*ログインセッション確認*/
    private function _validate() {
        
        if (!isset($_COOKIE[TMP_MEM_COOKIE_KEY]) || empty($_COOKIE[TMP_MEM_COOKIE_KEY])) {
            //未ログイン
            $this->isLoggedIn = false;
        } else {
            //ログインCookie有り
            $this->isLoggedIn = true;
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO:". $_COOKIE[TMP_MEM_COOKIE_KEY]['unique_code'], 0);
        }
        return $this->isLoggedIn;
    }
    
    /**
     * 認証メソッド ログイン処理
     * @param string $mail
     * @param string $pass
     */
    private function authenticate($mail = null, $pass = null) {
        //Check nonce
        if ( !isset($_POST['_wpnonce_login_tmp_end']) || !wp_verify_nonce($_POST['_wpnonce_login_tmp_end'], 'login_tmp_end' ) ){
            //Nonce check failed.
            wp_die('Nonce認証エラー 不正なアクセスです。');
        }
        
        $tmp_password = empty($pass) ? filter_input(INPUT_POST, 'tmp_password') : $pass;
        $tmp_mail = empty($mail) ? filter_input(INPUT_POST, 'tmp_mail') : $mail;
        $auto_login = filter_input(INPUT_POST, 'auto_login', FILTER_SANITIZE_STRING);

        if (!empty($tmp_mail) && !empty($tmp_password)) {
        
            //API認証
            $unique_code = $this->_api_login($tmp_mail, $tmp_password);
            if (!$unique_code) {
                //認証失敗
                //NGならログイン画面へリダイレクト
                $this->isLoggedIn = false;
                return false;
            }
            
            //DBから会員レコードを探す
            $tmp_member_data = $this->_get_member($tmp_mail, $unique_code);
            if (!$tmp_member_data) {
                //会員レコードがなければ作成
                $tmp_member_data = $this->create_tmp_member($tmp_mail, $tmp_password, $unique_code);
            } else {
                //会員レコードがあればAPIから所持チケットを取得し会員レベルに変動がないかチェックし対象カラムの情報更新
                if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 既存ユーザー", 0);
                $tmp_member_data = $this->update_tmp_member($tmp_member_data);
            }
            if (!$tmp_member_data) return false;
            //ログイン状態
            $this->isLoggedIn = true;
            //ログインセッションを保存
            TaketinMpUtils::set_cookie_tmp_member($tmp_member_data, $auto_login);
            
            return true;
        }
        return false;   // initから呼ばれた場合
    }
    
    /**
     * 会員情報を新規作成
     **/
    private function create_tmp_member($mail, $password, $unique_code) {
        
        //CMSからユーザー情報を取得する
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: API:ユーザー情報取得", 0);
        $cms_user_data = $this->_api_get_user_data($mail, $unique_code);
        if (!$cms_user_data) return false; //エラーのため終了
        
        //CMSからユーザーの所持するチケット情報を取得する
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: API: 所持チケット取得", 0);
        $ticket_ids = $this->_api_get_user_ticket_ids($unique_code);
        if (!$ticket_ids) return false; //エラーのため終了
        
        //DBからチケットIDに該当する会員レベルを取得する
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: DB:会員レベル判定", 0);
        $memberships_level_id = $this->_get_membership_level_id($ticket_ids);
        if (!$memberships_level_id) return false; //エラーのため終了
        
        //登録情報を作成
        $insert_date = date("Y-m-d H:i:s");
        $insert_data = array(
            'name_sei' => $cms_user_data["name_sei"],
            'name_mei' => $cms_user_data["name_mei"],
            'email' => $mail,
            'unique_code' => $unique_code,
            'ticket_list_serialized' => serialize($ticket_ids),
            'memberships_id' => $memberships_level_id,
            'progress' => 0,
            'memberships_check_date' => $insert_date,
            'last_login' => $insert_date,
            'created' => $insert_date
        );
        
        //登録処理
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: DB:会員データ新規登録", 0);
        $result = TaketinMpMember::get_instance()->create_member_authenticate($insert_data);
        if ($result['result'] != true) {
            $this->_error_msg = isset($result['error_msg']) ? : "";
            return false;
        }
        
        //登録したデータを取得する
        $member_id = $result['member_id'];
        $tmp_member_data = $this->_get_member_by_id($member_id);
        return $tmp_member_data;
    }
    
    /**
     * 会員レベルに変更がないかチェックし、会員情報を更新
     **/
    private function update_tmp_member($tmp_member, $is_last_login_update = true) {
        $update_member = array();
        
        //CMSからユーザーの所持するチケット情報を取得する
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: API: 所持チケット取得 -> 差分チェック", 0);
        $ticket_ids = $this->_api_get_user_ticket_ids($tmp_member['unique_code']);
        //エラーのため終了
        if (!$ticket_ids) return false;
        
        if (serialize($ticket_ids) != $tmp_member['ticket_list_serialized']) {
            //APIの値とDBの値との比較で所持チケットが異なる
            
            //DBからチケットIDに該当する会員レベルを取得する
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: DB:会員レベル取得 -> 差分チェック", 0);
            $memberships_level_id = $this->_get_membership_level_id($ticket_ids);
            if (!$memberships_level_id) {
                return false; //エラーのため終了
            }
            if ($memberships_level_id != $tmp_member['memberships_id']) {
                //APIの値から判定した会員レベルとDBの会員レベルが異なる
                if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 会員Lv更新: 所持チケット変更、会員レベル変更", 0);
        
                //所持チケット、会員レベルの両方を更新
                $update_member['ticket_list_serialized'] = serialize($ticket_ids);
                $update_member['memberships_id'] = $memberships_level_id;
            } else {
                //会員レベルは変わらない
                if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 会員Lv更新: 所持チケット変更、会員レベル変更なし", 0);
                
                //所持チケットのみ更新
                $update_member['ticket_list_serialized'] = serialize($ticket_ids);
            }
        } else {
            //APIの値とDBの値との比較で所持チケットが一致
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 会員Lv更新: 所持チケット変更なし", 0);
        }
        
        global $wpdb;
        $upd_date = date("Y-m-d H:i:s");
        $update_member["memberships_check_date"] = $upd_date;
        if ($is_last_login_update) $update_member["last_login"] = $upd_date;
        
        $tmp_member_id = $tmp_member["id"];
        $wpdb->update($wpdb->prefix . "tmp_members", $update_member, array('id' => $tmp_member_id));
        $wpdb->flush();
        
        unset($tmp_member["last_login"]);
        $tmp_member += $update_member;
        
        return $tmp_member;
    }


    /**
     * ログイン認証API
     **/
    private function _api_login($mail, $password) {
        $json_result = TaketinMpUtils::get_api_user_login($mail, $password);
    
        if ($json_result) {
            $result = json_decode($json_result, true);
    
            if (isset($result["result"]) && $result["result"] == true) {
                //CMS側認証成功
                if (isset($result["hash"]) && !empty($result["hash"])) {
                    $unique_code = $result["hash"];
                    return $unique_code;
                }
            }
        }
        //認証失敗
        return false;
    }
    
    /**
     * APIを使いユーザー情報を取得する
     **/
    private function _api_get_user_data($mail, $unique_code) {
        $result = array();
        //CMSからユーザー情報を取得する
        $cms_user_object = TaketinMpUtils::get_api_user($unique_code);
        $cms_user_data = json_decode($cms_user_object, true);
        if (isset($cms_user_data['hUser']) && !empty($cms_user_data['hUser'])) {
            $result['name_sei'] = isset($cms_user_data['hUser']['User']['name_sei']) ? $cms_user_data['hUser']['User']['name_sei'] : "";
            $result['name_mei'] = isset($cms_user_data['hUser']['User']['name_mei']) ? $cms_user_data['hUser']['User']['name_mei'] : "";
            $result['email'] = isset($cms_user_data['hUser']['User']['mail']) ? $cms_user_data['hUser']['User']['mail'] : "";
            
            if ($result['email'] != $mail) {
                //エラー
                $this->_error_msg = "入力されたメールアドレスが一致しませんでした。[API]";
                error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $this->_error_msg, 0);
                return false;
            }
        } else {
            //エラー
            $this->_error_msg = "ユーザー情報の取得に失敗しました。[API]";
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $this->_error_msg, 0);
            return false;
        }
        return $result;
    }
    
    /**
     * APIを使いユーザーの所持するチケット情報を取得する
     **/
    private function _api_get_user_ticket_ids($unique_code) {
        $result = array();
        //CMSからユーザーの所持するチケット情報を取得する
        $cms_tickets_object = TaketinMpUtils::get_api_user_ticket($unique_code); 
        $cms_tickets_data = json_decode($cms_tickets_object, true);
        if (isset($cms_tickets_data['hTickets']) && !empty($cms_tickets_data['hTickets'])) {
            if (isset($cms_tickets_data['hTickets'])) {
                foreach ($cms_tickets_data['hTickets'] as $val) {
                    $result[] = $val['ticket_id'];
                }
            }
        } else {
            //エラー
            $this->_error_msg = "ユーザーの所持するチケット情報の取得に失敗しました。[API]";
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $this->_error_msg, 0);
            return false;
        }
        return $result;
    }
    
    /**
     * DBからチケットIDに該当する会員レベルを取得する
     **/
    private function _get_membership_level_id($ticket_ids) {
        $result = "";
        //チケットIDに該当する会員レベルを取得する
        $membership_level = TaketinMpUtils::get_membership_level($ticket_ids);
        
        if (isset($membership_level['id']) && !empty($membership_level['id'])) {
            $result = $membership_level['id'];
        } else {
            $this->_error_msg = "ログインできる権限がありません。";
            error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] ERR:" . $this->_error_msg, 0);
            return false;
        }
        return $result;
    }
    
    /**
     * メールアドレス、ユニークコードから会員情報をDBから取得
     **/
    private function _get_member($mail, $unique_code) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . $wpdb->prefix . "tmp_members WHERE email = %s AND unique_code = %s";
        $query = $wpdb->prepare($sql, $mail, $unique_code);
        $tmp_member = $wpdb->get_row($query, ARRAY_A);
        
        if (isset($tmp_member['id'])) {
            // TMP会員情報を返す
            return $tmp_member;
        }
        //該当データなし
        return false;
    }
    
    /**
     * 会員IDから会員情報をDBから取得
     **/
    private function _get_member_by_id($id) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . $wpdb->prefix . "tmp_members WHERE id = %d";
        $query = $wpdb->prepare($sql, $id);
        $tmp_member = $wpdb->get_row($query, ARRAY_A);
        
        if (isset($tmp_member['id'])) {
            // TMP会員情報を返す
            return $tmp_member;
        }
        //該当データなし
        return false;
    }
    
    /**
     * 閲覧制限判定
     */
     public function is_allow() {
        $is_allow = false;
         
        //未ログインは許可しない
        if (!$this->isLoggedIn) return false;
        //会員レベルIDを取得する
        $user_memberships_level_id = TaketinMpUtils::get_cookie_data("memberships_id");
        
        if (is_single()) {
        //記事ページ
        
            //カテゴリIDを取得
            $get_the_category = get_the_category();
            if (isset($get_the_category[0])) {
                $cat_now = $get_the_category[0];
                $cat_id = $cat_now->cat_ID; 
                
                //会員レベルIDとカテゴリIDを元に閲覧することができるカテゴリかを判定
                $is_allow = TaketinMpMembershipLevel::get_instance()->is_member_allow_category($user_memberships_level_id, $cat_id);
            }
        } else if (is_page())  {
        //固定ページ
            $is_allow = true;
        } else if (is_archive())  {
        //アーカイブ
        
            //カテゴリIDを取得
            $get_the_category = get_the_category();
            if (isset($get_the_category[0])) {
                $cat_now = $get_the_category[0];
                $cat_id = $cat_now->cat_ID; 
                
                //会員レベルIDとカテゴリIDを元に閲覧することができるカテゴリかを判定
                $is_allow = TaketinMpMembershipLevel::get_instance()->is_member_allow_category($user_memberships_level_id, $cat_id);
            }
        }
        return $is_allow;
    }
    
    public function get_allow_category_id() {
        $result = "";
        //未ログインは許可しない
        if (!$this->isLoggedIn) return false;
        //会員レベルIDを取得する
        $user_memberships_level_id = TaketinMpUtils::get_cookie_data("memberships_id");
        //会員レベルIDを元に閲覧することができるカテゴリIDを取得する
        $cat_ids = TaketinMpMembershipLevel::get_instance()->get_member_allow_category_ids($user_memberships_level_id);
        if (!is_null($cat_ids) && !empty($cat_ids)) {
            //取得できたらカテゴリIDの表示を加工する
            $ar = array_values($cat_ids);
            if ( count($ar) > 1) {
                //カンマ区切りに変換
                foreach ($ar as $v) {
                    if (isset($v[0])) {
                        $result .= $v[0] . ",";
                    }
                }
                $result = rtrim( $result,"," );
            } else {
                if (isset($ar[0][0])) {
                    $result = $ar[0][0];
                }
            }
        }
        return $result;
    }

    //ログイン中かどうか
    public function is_logged_in() {
        return $this->isLoggedIn;
    }
    
    /**
     * ログイン
     **/
    public function login() {
        if ($this->isLoggedIn) {
            return;
        }
        $res = $this->authenticate();
        return $res;
    }
    
    /**
     * ログアウト
     **/
    public function logout() {
        if (!$this->isLoggedIn) {
            return;
        }
        TaketinMpUtils::clear_cookie();
        $this->isLoggedIn = false;
    }

    /**
     * [ログイン中の処理]
     * Cookieの会員IDから会員情報が存在するかチェック
     */
    public function login_in_is_exist_member() {
        //未ログインは存在していないものとして扱う
        if (!$this->isLoggedIn) return false;
        //会員IDを取得する
        $member_id = TaketinMpUtils::get_cookie_data("member_id");
        //取得失敗は存在していないものとして扱う
        if (!$member_id) return false;
        //会員IDから会員情報を取得
        $tmp_member = $this->_get_member_by_id($member_id);
        if (!$tmp_member) {
            //会員情報が存在しない
            return false;
        } else {
            //会員情報が存在する
            return true;
        }
    }
    
    /**
     * [ログイン中の処理]
     * Cookieの会員レベル更新日時をみて24時間経過しているか判定
     * @return boolean
     */
    public function login_in_is_past_memberships_check_date() {
        //未ログインは過ぎたものとして扱う
        if (!$this->isLoggedIn) return true;
        //会員レベル更新日時を取得する
        $memberships_check_date = TaketinMpUtils::get_cookie_data("memberships_check_date"); 
        //取得失敗は過ぎたものとして扱う
        if (!$memberships_check_date) return true;
        if( time() < $memberships_check_date +  TMP_MEM_CHECK_MEMBERLEVEL_UPDATE ){
            return true;
        }
        return false;
    }
    
    /**
     * [ログイン中の処理]
     * 会員レベル再判定と会員情報更新
     */
    public  function login_in_update_tmp_menber() {
        //未ログインは終了
        if (!$this->isLoggedIn) return false;
        //会員IDを取得する
        $member_id = TaketinMpUtils::get_cookie_data("member_id");
        //取得失敗は終了
        if (!$member_id) return false;
        //会員IDから会員情報を取得
        $tmp_member = $this->_get_member_by_id($member_id);
        //会員レベル判定と情報の更新処理
        $refresh_tmp_member = $this->update_tmp_member($tmp_member, false);
        if (!$refresh_tmp_member) return false;
        //Cookieを更新
        TaketinMpUtils::login_in_update_cookie_data($refresh_tmp_member);
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: Cookie値更新完了", 0);
        return true;
    }
    
    /**
     * エラーメッセージを返す
     **/
    public function get_err_message() {
        return $this->_error_msg;
    }
}
?>