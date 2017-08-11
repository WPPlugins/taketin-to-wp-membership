<?php
/**
 * フォームから送信されたデータを登録 
 **/
class TaketinMpInitTimeTasks {

    public function __construct() {
    }

    public function do_init_tasks() {
        //Set up localisation. First loaded ones will override strings present in later loaded file.
        //Allows users to have a customized language in a different folder.
        $locale = apply_filters('plugin_locale', get_locale(), 'tmp');
        load_textdomain('tmp', WP_LANG_DIR . "/tmp-$locale.mo");
        load_plugin_textdomain('tmp', false, TMP_MEM_DIRNAME . '/languages/');
		$this->process_password_reset();
		
        //Do frontend-only init time tasks
        if (is_admin()) {
            //Admin dashboard side stuff
            $this->admin_init();
        }
    }
	
    public function admin_init() {
        
        $createtmpuser = filter_input(INPUT_POST, 'createtmpuser');
        if (!empty($createtmpuser)) {
            TaketinMpMember::get_instance()->create_member();
        }
        $edittmpuser = filter_input(INPUT_POST, 'edittmpuser');
        if (!empty($edittmpuser)) {
            $id = filter_input(INPUT_GET, 'member_id', FILTER_VALIDATE_INT);
            TaketinMpMember::get_instance()->edit_member($id);
        }
        $createtmplevel = filter_input(INPUT_POST, 'createtmplevel');
        if (!empty($createtmplevel)) {
            TaketinMpMembershipLevel::get_instance()->create_level();
        }
        $edittmplevel = filter_input(INPUT_POST, 'edittmplevel');
        if (!empty($edittmplevel)) {
            $id = filter_input(INPUT_GET, 'id');
            TaketinMpMembershipLevel::get_instance()->edit_level($id);
        }
        $update_category_list = filter_input(INPUT_POST, 'update_category_list');
        if (!empty($update_category_list)) {
            include_once('taketin-mp-category-list.php');
            TmpCategoryList::update_category_list();
        }
    }

    
    public function process_password_reset(){
	    
	    global $tmp_reset_email_mess;
	    $message = "";
        $tmp_reset = filter_input(INPUT_POST, 'tmp-reset');
        $tmp_reset_email = filter_input(INPUT_POST, 'tmp_reset_email', FILTER_UNSAFE_RAW);
        if ( !empty($tmp_reset) ) {
	        
            //Check nonce
            if ( !isset($_POST['_wpnonce_reset_tmp_end']) || !wp_verify_nonce($_POST['_wpnonce_reset_tmp_end'], 'reset_tmp_end' ) ){
                //Nonce check failed.
            	wp_die('Nonce認証エラー 不正なアクセスです。');
            }
            
	        if( !$tmp_reset_email ){
		        TaketinMpUtils::setMessage('error', 'メールアドレスの入力が必要です。');
	        }
			
			$res = TaketinMpUtils::send_reset_passwd_mail($tmp_reset_email);

			if(isset( $res['result'] ) && $res['result'] ){//true
				TaketinMpUtils::setMessage('success', 'メールを送信しました。');
			}else{
				TaketinMpUtils::setMessage('error', $res['message']);
			}
			$redirect_url = home_url() . '/membership-login/password-reset';
            wp_redirect($redirect_url);
	        exit;
        }
    }


}
