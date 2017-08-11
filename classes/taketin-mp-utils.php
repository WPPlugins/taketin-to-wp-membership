<?php

abstract class TaketinMpUtils {

    public static function is_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
    
    /*
     * This function handles various initial setup tasks that need to be executed very early on (before other functions of the plugin is called).
     */
    public static function do_misc_initial_plugin_setup_tasks(){
        
        //Management role/permission setup
        $admin_dashboard_permission = TaketinMpSettings::get_instance()->get_value('admin-dashboard-access-permission');
        if (empty($admin_dashboard_permission)) {
            //By default only admins can manage/see admin dashboard
            define("TMP_MANAGEMENT_PERMISSION", "manage_options");
        } else {
            define("TMP_MANAGEMENT_PERMISSION", $admin_dashboard_permission);
        }
        
        //Set timezone preference (if enabled in settings)
        $use_wp_timezone = TaketinMpSettings::get_instance()->get_value('use-wordpress-timezone');
        if (!empty($use_wp_timezone)){//Set the wp timezone
            $wp_timezone_string = get_option('timezone_string');
            if(!empty($wp_timezone_string)){
                date_default_timezone_set($wp_timezone_string);
            }
        }
        
    }
    
    /**
     * APIからチケット情報を取得する
     **/
    public static function get_api_tickets() {
    	//チケット情報
		$error_membership_lebel_add = $tickets_membership_lebel_add = '';
		try {
            if( !$hSettings = get_option( 'tmp-settings' ) ){
                throw new Exception( __( 'Please Do Settings.', 'my-custom-admin' ));
            }
            if( empty($hSettings['taketin-system-url']) || empty($hSettings['taketin-app-secret'])) {
                throw new Exception( __( 'Check the API endpoint.', 'my-custom-admin' ));
            }
            $use_tickets = array();
            if(get_option( 'tmp-use-tickets' )){
                $use_tickets = get_option( 'tmp-use-tickets' );
            }
            if (count($use_tickets) == 0) {
                //使用するチケットが未設定なのでエラー
                throw new Exception(__( 'Please set Tickets Information.', 'my-custom-admin' ));
            }

			$api_endpoint = $hSettings['taketin-system-url'];
			$api_key = $hSettings['taketin-app-secret'];
			$endpoint = preg_replace('/\/$/', '', $api_endpoint);
			
            $response_api = wp_remote_post(
                $endpoint.'/api/ticket/', 
                    array( 'body'=>array(
                        'apipass'=>$api_key
                    )
                )
            );
            if(!isset($response_api['body'])){
                //bodyがない場合はエラー
                throw new Exception(__( 'Failed Connect API endpoint. Please confirm Api settings. And try again.' ));
            }
            $result_body = json_decode($response_api['body'],true);
            if(!$result_body['result'] || !isset($result_body['hTickets'])){
    	        //データが取得できなかったのでエラー
    	        throw new Exception(__( 'Failed get API Data. Try again.', 'my-custom-admin' ));
    	    }
        	    
	        $tickets = $result_body['hTickets'];
			
    	    foreach( $tickets as $ticket ){
    	    	if (in_array($ticket['ticket_id'], $use_tickets)){
					$tickets_membership_lebel_add[] = $ticket;
    	    	}
			}
        } catch (Exception $ex) {
			$error_membership_lebel_add = $ex->getMessage();
        }
        return array($error_membership_lebel_add, $tickets_membership_lebel_add);
    }
    
    /**
     * APIからチケット情報を取得する(ウィザード用)
     **/
    public static function wizard_get_api_ticket($endpoint, $api_key) {
	    //チケット情報
        $result = wp_remote_post(
            $endpoint.'/api/ticket/',
                array( 'body'=>array(
                    'apipass'=>$api_key
                )
            )
        );
        return $result['body'];
    }
    
    /**
     * APIからユニークコードを元にユーザー情報を取得する
     **/
    public static function get_api_user($hash) {
        //チケット情報
		$result = '';
		try {
			if( !$hSettings = get_option( 'tmp-settings' ) ){
				throw new Exception( __( 'Please Do Settings.', 'my-custom-admin' ));
			}
			if( empty($hSettings['taketin-system-url']) || empty($hSettings['taketin-app-secret'])) {
				throw new Exception( __( 'Check the API endpoint.', 'my-custom-admin' ));
			}
			$api_endpoint = $hSettings['taketin-system-url'];
			$api_key = $hSettings['taketin-app-secret'];
			$endpoint = preg_replace('/\/$/', '', $api_endpoint);
			
            $response_api = wp_remote_post(
                $endpoint.'/api/user/', 
                    array( 'body'=>array(
                        'apipass'=>$api_key,
                        'hash'=>$hash
                    )
                )
            );
            if(!isset($response_api['body'])){
                //bodyがない場合はエラー
                throw new Exception(__( 'Failed Connect API endpoint. Please confirm Api settings. And try again.'));
            }
            $result = $response_api['body'];
			
        } catch (Exception $ex) {
			$result = json_encode(array("error" => $ex->getMessage()));
        }
        return $result;
    }
    
    
    /**
     * APIからユニークコードを元にユーザーの所持するチケット情報を取得する
     **/
    public static function get_api_user_ticket($hash) {
        //チケット情報
		$result = '';
		try {
			if( !$hSettings = get_option( 'tmp-settings' ) ){
				throw new Exception( __( 'Please Do Settings.', 'my-custom-admin' ));
			}
			if( empty($hSettings['taketin-system-url']) || empty($hSettings['taketin-app-secret'])) {
				throw new Exception( __( 'Check the API endpoint.', 'my-custom-admin' ));
			}
			$api_endpoint = $hSettings['taketin-system-url'];
			$api_key = $hSettings['taketin-app-secret'];
			$endpoint = preg_replace('/\/$/', '', $api_endpoint);
			
            $response_api = wp_remote_post(
                $endpoint.'/api/user_ticket/', 
                    array( 'body'=>array(
                        'apipass'=>$api_key,
                        'hash'=>$hash
                    )
                )
            );
            if(!isset($response_api['body'])){
                //bodyがない場合はエラー
                throw new Exception(__( 'Failed Connect API endpoint. Please confirm Api settings. And try again.'));
            }
	        $result = $response_api['body'];
			
        } catch (Exception $ex) {
			$result = json_encode(array("error" => $ex->getMessage()));
        }
        return $result;
    }
    
    /**
     * APIからユーザー認証しユニークコードを取得する
     **/
    public static function get_api_user_login($mail, $password) {
        //API結果
		$result = '';
		try {
			if( !$hSettings = get_option( 'tmp-settings' ) ){
				throw new Exception( __( 'Please Do Settings.', 'my-custom-admin' ));
			}
			if( empty($hSettings['taketin-system-url']) || empty($hSettings['taketin-app-secret'])) {
				throw new Exception( __( 'Check the API endpoint.', 'my-custom-admin' ));
			}
			$api_endpoint = $hSettings['taketin-system-url'];
			$api_key = $hSettings['taketin-app-secret'];
			$endpoint = preg_replace('/\/$/', '', $api_endpoint);
			
            $response_api = wp_remote_post(
                $endpoint.'/api/auth_user/', 
                    array( 'body'=>array(
                        'apipass'=>$api_key,
                        'mail'=>$mail,
                        'passwd'=>$password
                    )
                )
            );
            if(!isset($response_api['body'])){
                //bodyがない場合はエラー
                throw new Exception(__( 'Failed Connect API endpoint. Please confirm Api settings. And try again.'));
            }
	        $result = $response_api['body'];
			
        } catch (Exception $ex) {
			$result = json_encode(array("error" => $ex->getMessage()));
        }
        return $result;
    }
    
    public static function get_membership_level($own_ticket_ids = array()) {
        $membership_level = null;
        global $wpdb;
        $query = "SELECT id, name, levelclass FROM " . $wpdb->prefix . "tmp_memberships ORDER BY levelclass";
        $levels = $wpdb->get_results($query, ARRAY_A);
		//取得した会員レベル情報にチケット名を追加する
		foreach ($levels as $level) {
			$query = $wpdb->prepare("SELECT ticket_id FROM {$wpdb->prefix}tmp_memberships_tickets WHERE membership_id = %d", $level['id']);
			$ticket_ids = $wpdb->get_results($query, ARRAY_A);
			if ($ticket_ids) {
				foreach ($ticket_ids as $val) {
					if (in_array($val["ticket_id"], $own_ticket_ids)) {
					    //含まれている
					    $membership_level = $level + array("ticket_id" => $val["ticket_id"]);
					    break;
					}
				}
			}
			if (!is_null($membership_level)) {
			    break;
			}
		}
		return $membership_level;
    }
    
    public static function membership_level_dropdown($selected = 0) {
        $options = '';
        global $wpdb;
        $query = "SELECT name, id FROM " . $wpdb->prefix . "tmp_memberships";
        $levels = $wpdb->get_results($query);
        foreach ($levels as $level) {
            $options .= '<option ' . ($selected == $level->id ? 'selected="selected"' : '') . ' value="' . $level->id . '" >' . $level->name . '</option>';
        }
        return $options;
    }
    
    public static function get_viewable_categories( $membership_level = 1 ){
	    global $wpdb;        
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "tmp_memberships_categories WHERE membership_id = %d", $membership_level);
        if( !$tmps = $wpdb->get_results($query, ARRAY_A) ){
	        return array();
        }
        foreach( $tmps as $key => $val ){
	        $cates[] = $val['category_id'];
        }
        return $cates;
    }
	
	public static function send_reset_passwd_mail( $email = null ){
		
		if( !$email ){
			return false;
		}
		$result = '';
		try {
			if( !$hSettings = get_option( 'tmp-settings' ) ){
				throw new Exception( __( 'Please Do Settings.', 'my-custom-admin' ));
			}
			if( empty($hSettings['taketin-system-url']) || empty($hSettings['taketin-app-secret'])) {
				throw new Exception( __( 'Check the API endpoint.', 'my-custom-admin' ));
			}
			$api_endpoint = $hSettings['taketin-system-url'];
			$api_key = $hSettings['taketin-app-secret'];
			$endpoint = preg_replace('/\/$/', '', $api_endpoint);
			
            $response_api = wp_remote_post(
                $endpoint.'/api/send_pass/', 
                    array( 'body'=>array(
                        'apipass'=>$api_key,
                        'login_url'=>get_option( 'siteurl' ).'/membership-login/',
                        'mail'=>$email
                    )
                )
            );
            if(!isset($response_api['body'])){
                //bodyがない場合はエラー
                throw new Exception(__( 'Failed Connect API endpoint. Please confirm Api settings. And try again.'));
            }
	        $result = $response_api['body'];
		} catch (Exception $ex) {
			$result = json_encode(array("error" => $ex->getMessage()));
        }
        return json_decode($result,true);
	}
	
	
	public static function save_configurator($params) {
	    $result = array();
	    $log = '';
	    try { 
	        //インスタンス生成
            $TaketinMpConfigurator = new TmpConfigurator();
            $security = $TaketinMpConfigurator->check_security();   //セキュリティチェック
            if (!$security) {
                throw new Exception('WordPressのセキュリティチェックエラーです。リロードしてウィザードをやり直してください。');
            }
            
            $TaketinMpConfigurator->set($params);               //値解析、セット
            $validate = $TaketinMpConfigurator->validate();     //値のバリエーションチェック
            if (!$validate) {
                $err_msg = $validate['message'];
				throw new Exception(sprintf('送信されたデータが不正なため登録に失敗しました [%s]', $err_msg));
            }
            $success = $TaketinMpConfigurator->save();          //保存
            if (isset($success['message'])) {
                $err_msg = $success['message'];
                $log = $success['error-log'];
				throw new Exception(sprintf('登録に失敗しました [%s]', $err_msg));
            }
        	$result = array("result" => true);
	    } catch (Exception $ex) {
			$result = array("result" => false, "message" => $ex->getMessage(), "log" => $log);
	    }
	    return $result;
	}
	
	
	public static function setMessage( $mode='success', $mess=null){
		if( !$mess ){ 
			setcookie(TMP_ERR_MSG_COOKIE_KEY, null, time() - 1800 , COOKIEPATH, COOKIE_DOMAIN );
			return false; 
		}
		$hTmp = array( 'result'=>$mode, 'mess'=>$mess );
		setcookie(TMP_ERR_MSG_COOKIE_KEY, null, time() - 1800 , COOKIEPATH, COOKIE_DOMAIN );
		setcookie(TMP_ERR_MSG_COOKIE_KEY, base64_encode(serialize($hTmp)), TMP_ERR_MSG_COOKIE_EXPIRE);
		return true;
	}
	
	public static function getMessage(){
		if(!isset($_COOKIE[TMP_ERR_MSG_COOKIE_KEY])){
			return false;
		}
		$mess = unserialize(base64_decode($_COOKIE[TMP_ERR_MSG_COOKIE_KEY]));
		if( !isset($mess['result']) || !$mess['result'] ){
			return false;
		}
		return $mess;		
	}
	
	
// Cookie Method Start
    /**
     * Cookieへ値をセット 
     **/
    public static function set_cookie_tmp_member($tmp_member, $auto_login) {
        //クッキー有効期限
        $expire = $auto_login == "1" ? 0 : TMP_MEM_COOKIE_EXPIRE;
        
        //各情報をクッキーにセットする
        setcookie(TMP_MEM_COOKIE_KEY . "[unique_code]", $tmp_member["unique_code"], $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[member_id]", $tmp_member["id"], $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[ticket_list_serialized]", $tmp_member["ticket_list_serialized"], $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[memberships_id]", $tmp_member["memberships_id"], $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[memberships_check_date]", strtotime($tmp_member["memberships_check_date"]), $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[last_login]", $tmp_member["last_login"], $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[cookie_expire]", $expire, $expire, COOKIEPATH, COOKIE_DOMAIN);
    }
    
    /**
     * Cookieの値を取得する
     */
    public static function get_cookie_data($key) {
        if (isset( $_COOKIE[TMP_MEM_COOKIE_KEY][$key])) {
            return $_COOKIE[TMP_MEM_COOKIE_KEY][$key];
        }
    }
    
    /**
     * ログイン中の必要な項目のCookieの値を更新する
     */
    public static function login_in_update_cookie_data($tmp_member) {
        $expire = self::get_cookie_data("cookie_expire");
        if (!$expire) return false;
        setcookie(TMP_MEM_COOKIE_KEY . "[ticket_list_serialized]", $tmp_member["ticket_list_serialized"], $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[memberships_id]", $tmp_member["memberships_id"], $expire, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[memberships_check_date]", strtotime($tmp_member["memberships_check_date"]), $expire, COOKIEPATH, COOKIE_DOMAIN);
    }
    
    /**
     * Cookieの値をクリアする
     */
     public static function clear_cookie() {
        setcookie(TMP_MEM_COOKIE_KEY . "[unique_code]", ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[member_id]", ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[ticket_list_serialized]", ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[memberships_id]", ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[memberships_check_date]", ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[last_login]", ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(TMP_MEM_COOKIE_KEY . "[cookie_expire]", ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }
// Cookie Method End
}




