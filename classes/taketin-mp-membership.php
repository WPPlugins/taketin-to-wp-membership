<?php
class TaketinMpMembership {

    private $_authenticate_error_message;
    
    public function __construct() {
        add_action('admin_menu', array(&$this, 'menu'));
        
        add_action('init', array(&$this, 'init_hook'));
        //init is too early for settings api.
        add_action('admin_init', array(&$this, 'admin_init_hook'));
        add_action('admin_notices', array(&$this, 'do_admin_notices'));
        add_action('wp_enqueue_scripts', array(&$this, 'do_wp_enqueue_scripts'));
        
        add_action('wp', array(&$this, 'tmp_authenticate'));                    //認証
        add_action('pre_get_posts', array(&$this, 'tmp_filter_list'));          //記事一覧出力制御

        add_shortcode('tmp_login_form', array(&$this, 'login'));
        add_shortcode('tmp_logout_form', array(&$this, 'logout'));
        add_shortcode('tmp_reset_form', array(&$this, 'reset'));
        
        //AJAX hooks
        add_action('wp_ajax_api_user', 'TaketinMpAjax::api_user');
        add_action('wp_ajax_api_user_ticket', 'TaketinMpAjax::api_user_ticket');
        add_action('wp_ajax_api_ticket_wizard', 'TaketinMpAjax::wizard_api_ticket');
        add_action('wp_ajax_save_configurator_wizard', 'TaketinMpAjax::wizard_save_configurator');
        add_action('wp_ajax_get_membership_level', 'TaketinMpAjax::get_membership_level_from_tickets');
    }
    
    public function menu() {
        
        add_menu_page(
                'TAKETIN MP', // page_title
                'TAKETIN MP', // menu_title
                TMP_MANAGEMENT_PERMISSION, // capability
                TMP_MEM_PREFIX, // menu_slug
                array(&$this, "admin_members_menu"), // function
                'dashicons-id', // icon_url
                81    // position
            );
            add_submenu_page( 
                TMP_MEM_PREFIX, // parent_slug
                "会員", // page_title
                "会員", // menu_title
                TMP_MANAGEMENT_PERMISSION, // capability
                TMP_MEM_PREFIX, // menu_slug
                array(&$this, "admin_members_menu")// function
            );
            add_submenu_page( 
                TMP_MEM_PREFIX, 
                "会員レベル", 
                "会員レベル", 
                TMP_MANAGEMENT_PERMISSION, 
                TMP_MEM_PREFIX . '_levels', 
                array(&$this, "admin_membership_levels_menu")
            );
            add_submenu_page(
                TMP_MEM_PREFIX, 
                "設定", 
                "設定", 
                TMP_MANAGEMENT_PERMISSION, 
                TMP_MEM_PREFIX . '_settings', 
                array(&$this, "admin_settings_menu")
            );

        //do_action('tmp_after_main_admin_menu', $menu_parent_slug);

        //$this->meta_box();
    }
    
    /* Render the members menu in admin dashboard */
    public function admin_members_menu() {
        if ($this->admin_configurator()) return;    //ウィザード表示判定
        include_once(TMP_MEM_PATH . 'classes/taketin-mp-members.php');
        $Members = new TaketinMpMembers();
        $Members->handle_main_members_admin_menu();
    }
     
    /* Render the membership levels menu in admin dashboard */
    public function admin_membership_levels_menu() {
        if ($this->admin_configurator()) return;    //ウィザード表示判定
        include_once(TMP_MEM_PATH . 'classes/taketin-mp-membership-levels.php');
        $Levels = new TaketinMpMembershipLevels();
        $Levels->handle_main_membership_level_admin_menu();
    }
    
    /* Render the settings menu in admin dashboard */
    public function admin_settings_menu() {
        if ($this->admin_configurator()) return;    //ウィザード表示判定
        $TaketinMpSettings = TaketinMpSettings::get_instance();
        $TaketinMpSettings->handle_main_settings_admin_menu();
    }
    
    public function admin_init_hook() {
        $TaketinMpSettings = TaketinMpSettings::get_instance();
        //Initialize the settings menu hooks.
        $TaketinMpSettings->init_config_hooks();
    }
    
    public function admin_configurator() {
	    wp_enqueue_style( "options", TMP_MEM_DIR_URL. 'style/options.css' );
        //初期設定確認
        $TaketinMpConfigurator = new TmpConfigurator();
        if (!$TaketinMpConfigurator->is_finished_setup()){
            //setup未
            $TaketinMpConfigurator->wizard();
            return true; //ウィザード表示
        }
        //setup完了
        return false;
    }

    public function init_hook() {
        $init_tasks = new TaketinMpInitTimeTasks();
        $init_tasks->do_init_tasks();
    }
    
    /**
     * ログアウトボタン生成javascript出力 
     **/
    public function do_wp_enqueue_scripts() {
        $auth = TmpAuth::get_instance();
        //ログインしていなければ終了
        if (!$auth->is_logged_in()) return;
        
        //未設定であれば終了
        $settings = get_option('tmp-settings');
        if (!isset($settings['logout-button-target-style-element']) || empty($settings['logout-button-target-style-element'])) return;

        //フロント側にファイル読み込みを追加
        wp_enqueue_script('tmp-style01', TMP_MEM_DIR_URL . 'script/taketin-logout-button.js', array( 'jquery' ), TMP_MEM_VERSION, true);
        wp_localize_script('tmp-style01', 'tmp01', array(
            'logout_url' => TMP_URL_PATH_LOGOUT,
            'target_element' => $settings['logout-button-target-style-element']
        ));
    }
    
    /**
     * 記事一覧取得条件制御
     **/
    public function tmp_filter_list( $query ) {
        //管理画面はスキップ
        if ( is_admin() ) return;
        
        //ホームで記事一覧を出力する場合のクエリ
        if ( $query->is_home() && $query->is_main_query() ) {
            $auth = TmpAuth::get_instance();
            //ログインしている
            if ($auth->is_logged_in()) {
                //対象会員に許可されたカテゴリIDを取得
                $category_id = $auth->get_allow_category_id();
                if (!empty($category_id)) {
                    $query->set( 'cat', $category_id );
                }
            }
        }
        return;
    }

    /* If any message/notice was set during the execution then this function will output that message */
    public function notices() {
        $message = TaketinMpTransfer::get_instance()->get('status');
        $succeeded = false;
        if (empty($message)) {
            return false;
        }
        if ($message['succeeded']) {
            echo "<div id='message' class='updated'>";
            $succeeded = true;
        } else {
            echo "<div id='message' class='error'>";
        }
        echo $message['message'];
        $extra = isset($message['extra']) ? $message['extra'] : array();
        if (is_string($extra)) {
            echo $extra;
        } else if (is_array($extra)) {
            echo '<ul>';
            foreach ($extra as $key => $value) {
                echo '<li>' . $value . '</li>';
            }
            echo '</ul>';
        }
        echo "</div>";
        return $succeeded;
    }
     /* 
     * This function is hooked to WordPress's admin_notices action hook 
     * It is used to show any plugin specific notices/warnings in the admin interface
     */
    public function do_admin_notices(){
        $this->notices();//Show any execution specific notices in the admin interface.
        
        //Show any other general warnings/notices to the admin.
        if(is_admin()){
            //we are in an admin page for SWPM plugin.
            
            $msg = '';
            
            if(!empty($msg)){//Show warning messages if any.
                echo '<div id="message" class="error">';
                echo $msg;
                echo '</div>';
            }
        }
    }

    /*
     * TMP会員認証処理
     */
    public function tmp_authenticate() {
        //コンテンツ保護設定が無効な場合、終了
        if ($this->_check_enable_contents_block() != true) return;
        //認証処理スキップ
        if (!$this->_check_skip_page()) return;
        //除外ページ
        $this->_exclusion_page();

        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: tmp_authenticate 開始", 0);
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: REQUEST_URI=" . $_SERVER['REQUEST_URI'], 0);
        $auth = TmpAuth::get_instance();
        if ($auth->is_logged_in()) {
            // --------------------------
            //@@ ログインしている
            // --------------------------
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: ログイン中", 0);
            
            //[ログイン中の会員情報チェック]会員存在チェック
            $this->_login_in_check_tmp_member($auth);
            //[ログイン中の会員情報チェック]所持チケットと会員ランクチェック
            $this->_login_in_check_tmp_membership_level($auth);
            
            //ログイン中のトップページ表示は許可
            if (is_home()) return;
            
            //その他のページは権限より判定する
            if (!$auth->is_allow()) {
                if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: アクセス制限ページ", 0);
                $this->_redirect_not_allow_page();
            }
            return;
        } else {
            // --------------------------
            //@@ 未ログイン
            // --------------------------
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: ログインしていない", 0);
            
            if ( strpos($_SERVER['REQUEST_URI'],TMP_URL_PATH_LOGIN) !== false ) {
                if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: ログイン処理", 0);
                // --------------------------
                //@@ ログインページへ遷移中なのでログイン処理
                // --------------------------
                $success = $auth->login();
                if ($success) {
                    //ログイン成功
                    $redirect_url = home_url();
                    if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                        $query_strings = urldecode($_SERVER['QUERY_STRING']);
                        parse_str($query_strings);
                    }
                    
                    if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: リダイレクト=>" . $redirect_url, 0);
                    wp_redirect($redirect_url);
                    die();
                } else {
                    //ログイン失敗
                    //エラー情報を取得しログインページを表示する
                    $this->_authenticate_error_message = $auth->get_err_message();
                    return;
                }
            } else {
                // --------------------------
                //@@ 未ログインでログインページ以外を表示
                // --------------------------
                if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: ログインページへリダイレクト", 0);
                
                $this_url = home_url() . $_SERVER['REQUEST_URI'];
                $param = "?redirect_url=" . urlencode($this_url);
                $redirect_url = home_url() . TMP_URL_PATH_LOGIN. "/". $param;  //引数に元のページを含める
                if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: ".$redirect_url, 0);
                wp_redirect($redirect_url);
                die();
            }
        }
    }

    /**
     * 認証処理スキップページの判定
     **/
    private function _check_skip_page() {
        if (is_admin() || is_feed() || is_trackback() || is_attachment() ) return false;
        if (is_page(ltrim(TMP_URL_PATH_PASSRESET,"/"))) return false;
        if (strpos($_SERVER['REQUEST_URI'], TMP_URL_PATH_NOTALLOW) !== false ) return false;
    
        if (is_page(ltrim(TMP_URL_PATH_LOGOUT,"/"))) {
             
            //ログアウトページ
            $auth = tmpAuth::get_instance();
            $auth->logout();
            return false;
        } else if (is_page(ltrim(TMP_URL_PATH_LOGIN,"/")) && $_SERVER["REQUEST_METHOD"] != "POST" ) {
            //ログインページ、通常アクセス（ログイン処理ではない）
            $auth = tmpAuth::get_instance();
            if ($auth->is_logged_in()) {
                //ログイン状態でログインページにアクセスした場合はログイン画面を表示しない
                wp_redirect(home_url());
                die();
            }
            return false;
        }
        return true;
    }
    
    /*
     * 除外ページ判定
     */
    private function _exclusion_page() {
        //月別アーカイブは非表示
        if (is_date() ) {
            $this->_redirect_not_allow_page();
        }
    }
    
    /*
     * リダイレクトし終了する
     */
    private function _redirect_not_allow_page() {
        $redirect_url = TMP_URL_PATH_NOTALLOW;
        wp_redirect($redirect_url);
        die();
    }
    
    /*
     * コンテンツ保護設定を判定
     */
    private function _check_enable_contents_block() {
        $settings = get_option('tmp-settings');
        if (isset($settings['enable-contents-block']) && !empty($settings['enable-contents-block'])) {
            //有効
            return true;
        } else {
            //無効
        }
        return false;
    }
    /**
     * [ログイン中の会員情報チェック]
     * Cookie内の会員IDを使い会員情報が存在するかチェック
     **/
    private function _login_in_check_tmp_member($auth_instance) {
        //会員情報の存在チェック
        $is_exist = $auth_instance->login_in_is_exist_member();
        if (!$is_exist) {
            //会員情報が存在しないのでログアウト処理をしてログイン画面を再表示
            $auth_instance->logout();
            $login_url = home_url() . TMP_URL_PATH_LOGIN. "/";
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 会員情報の存在チェック= NG ログアウト処理をしてログイン画面を再表示", 0);
            wp_redirect($login_url);
            die();
        }
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 会員情報の存在チェック= OK", 0);
    }
    
    /**
     * [ログイン中の会員情報チェック]
     * Cookie内の会員ランク更新日時を使い、指定時間経過していた場合
     * APIで所持チケットを取得し会員レベルの更新を行う
     **/
    private function _login_in_check_tmp_membership_level($auth_instance) {
        //Cookieの情報より会員ランクの更新を行う
        $is_past = $auth_instance->login_in_is_past_memberships_check_date();
        if (!$is_past) {
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 会員ランク更新日時チェック= NG", 0);
            //APIを使い所持チケットを更新、会員レベルも更新する
            if (!$auth_instance->login_in_update_tmp_menber()) {
                $auth_instance->logout();
                wp_die("エラーが発生しました。再度TOPページからやり直してください。");
            }
        } else {
            //指定時間を経過していない
            if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] INFO: 会員ランク更新日時チェック= OK", 0);
        }
        //正常終了
    }
    
    public function login() {
        // エラーがあればセット
        if (!empty($this->_authenticate_error_message)) {
            $message = array("result" => "error", "mess" => $this->_authenticate_error_message);
        }
        $template_files = TMP_MEM_PATH . 'views/login.php';
        require( $template_files );
        return;
    }
    
    public function logout() {
        ob_start();
        $template_files = TMP_MEM_PATH . 'views/logout.php';
        require( $template_files );
        return ob_get_clean();
    }

    public function reset() {
        //$succeeded = $this->notices();
        //if ($succeeded) {
        //    return '';
        //}
        ob_start();
        //Load the forgot password template
        $template_files = TMP_MEM_PATH . 'views/forgot_password.php';
        require( $template_files );
        return ob_get_clean();
    }
}