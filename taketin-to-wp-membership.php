<?php
/*
Plugin Name: TAKETIN To WP Membership
Plugin URI: http://taketin.com/wp/
Description: TAKETIN Marketing Platformと連携したメンバーサイトをWordpressで作成するプラグインです。
Version: 1.0
Author: TAKETIN
Author URI:http://taketin.com/
License: GPL2
Copyright 2017 TAKETIN (email : support@taketin.com)
*/

//Direct access to this file is not permitted
if (!defined('ABSPATH')){
    exit("Do not access this file directly.");
}

include_once('classes/taketin-mp-membership.php');
include_once('classes/taketin-mp-settings.php');
include_once('classes/taketin-mp-utils.php');

include_once('classes/taketin-mp-transfer.php');
include_once('classes/taketin-mp-messages.php');
include_once('classes/taketin-mp-membership-level.php');
include_once('classes/taketin-mp-membership-level-form.php');
include_once('classes/taketin-mp-member.php');
include_once('classes/taketin-mp-member-form.php');
include_once('classes/taketin-mp-ajax.php');
include_once('classes/taketin-mp-init-time-tasks.php');
include_once('classes/taketin-mp-auth.php');
include_once('classes/taketin-mp-configurator.php');

include_once('classes/taketin-mp-protection.php');
include_once('classes/taketin-mp-permission.php');

// Initialize constants.
define( 'TMP_MEM_VERSION', '1.0.0' );
define( 'TMP_MEM_DB_VERSION', '1.0' );
define( 'TMP_MEM_DEBUG', true );
define( 'TMP_MEM_PREFIX', 'taketin_mp_membership' );
define( 'TMP_MEM_SITE_HOME_URL', home_url());
define( 'TMP_MEM_DIR_URL',  plugin_dir_url ( __FILE__ ) );
define( 'TMP_MEM_PATH', plugin_dir_path( __FILE__ ) );
define( 'TMP_MEM_DIRNAME', dirname(plugin_basename(__FILE__)) );
define( 'TMP_ERR_MSG_COOKIE_KEY', 'taketin_mp_error');
define( 'TMP_ERR_MSG_COOKIE_EXPIRE', time() + 60);
define( 'TMP_MEM_COOKIE_KEY', 'taketin_mp_membership');
define( 'TMP_MEM_COOKIE_EXPIRE', time() + 60 * 60 * 24);
define( 'TMP_MEM_CHECK_MEMBERLEVEL_UPDATE', 60 * 60 * 24);

define( 'TMP_URL_PATH_LOGIN', "/membership-login");
define( 'TMP_URL_PATH_LOGOUT', "/membership-logout");
define( 'TMP_URL_PATH_PASSRESET', "/membership-login/password-reset");

$hSettings = get_option( 'tmp-settings' );
$pageNotAllow = ( isset($hSettings['notallow-page-url']) && $hSettings['notallow-page-url'] )? $hSettings['notallow-page-url'] : '/membership-notallow' ;
define( 'TMP_URL_PATH_NOTALLOW', $pageNotAllow);

$TaketinMpMembership = new TaketinMpMembership();
TaketinMpUtils::do_misc_initial_plugin_setup_tasks();

if( TMP_MEM_DEBUG ){
	// エラー出力する場合
    ini_set('log_errors',1);
    ini_set('display_errors',1);
    // ログの保存先
    ini_set('error_log',WP_CONTENT_DIR . '/debug.log');
    error_log("\n==========================================",0);
}


register_activation_hook( __FILE__, 'activate' );
register_deactivation_hook( __FILE__, 'deactivate' );

/*以下は現在未使用*/
	function activate() {
        add_user_setting();
        create_database_tables();
    }

    function deactivate() {
        remove_user_setting();
        remove_database_tables();
    }
    
    function add_user_setting() {}
    function remove_user_setting() {
    	//delete_option('tmp-settings');
    	delete_option('tmp_use_tickets');
    }
    
	function create_database_tables(){
		
		$settings = TaketinMpSettings::get_instance();
		
        global $wpdb;
        $sql = '';
        $charsetCollate = '';

        $tableName = $wpdb->prefix. 'tmp_memberships';

        // charset
        if(!empty($wpdb->charset)){
            $charsetCollate = "DEFAULT CHARACTER SET ".$wpdb->charset;
        }

        if(!empty($wpdb->collate)){
            $charsetCollate .= " COLLATE ".$wpdb->collate;
        }

        // テーブルを作成
        $sql = sprintf("
CREATE TABLE %s (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `levelclass` int(11) NOT NULL DEFAULT '0' COMMENT '階級',
  `category_list` longtext COLLATE utf8mb4_unicode_ci,
  `page_list` longtext COLLATE utf8mb4_unicode_ci,
  `post_list` longtext COLLATE utf8mb4_unicode_ci,
  `attachment_list` longtext COLLATE utf8mb4_unicode_ci,
  `custom_post_list` longtext COLLATE utf8mb4_unicode_ci,
  `comment_list` longtext COLLATE utf8mb4_unicode_ci,
PRIMARY KEY (id)
) %s;
",
                       $tableName,
                       $charsetCollate
                       );
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
       
        // テーブルを作成
        $tableName = $wpdb->prefix. 'tmp_memberships_tickets';
        $sql = sprintf('
CREATE TABLE %s (
id int(11) AUTO_INCREMENT NOT NULL,
membership_id int(11),
ticket_id int(11),
PRIMARY KEY (id)
) %s;
',
                      $tableName,
                      $charsetCollate
                      );

        dbDelta($sql);
        
        // テーブルを作成
        $tableName = $wpdb->prefix. 'tmp_memberships_categories';
        $sql = sprintf('
CREATE TABLE %s (
id int(11) AUTO_INCREMENT NOT NULL,
membership_id int(11),
category_id int(11),
PRIMARY KEY (id)
) %s;
',
                       $tableName,
                       $charsetCollate
                       );

        dbDelta($sql);
        
        // テーブルを作成
        $tableName = $wpdb->prefix. 'tmp_members';
        $sql = sprintf("
CREATE TABLE %s (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) NOT NULL COMMENT '名前',
  `email` varchar(255) NOT NULL COMMENT 'メールアドレス',
  `unique_code` varchar(255) NOT NULL COMMENT 'ユニークコード',
  `ticket_list_serialized` varchar(32) NOT NULL COMMENT '所持チケットID一覧',
  `progress` float NOT NULL COMMENT '',
  `memberships_id` int(11) DEFAULT 0 COMMENT '会員レベルID',
  `memberships_check_date` datetime NOT NULL COMMENT '会員レベル判定日時',
  `last_login` datetime NOT NULL COMMENT '最終ログイン日時',
  `created` datetime NOT NULL COMMENT '作成日時',
  PRIMARY KEY (`id`)
) %s;
",
                       $tableName,
                       $charsetCollate
                       );

        dbDelta($sql);

		//Create login page
        $tmp_login_page = array(
            'post_title' => '会員ログイン',
            'post_name' => 'membership-login',
            'post_content' => '[tmp_login_form]',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $login_page_obj = get_page_by_path('membership-login');
        $login_page_id = 0;
        if (!$login_page_obj) {
            $login_page_id = wp_insert_post($tmp_login_page);
        } else {
            $login_page_id = $login_page_obj->ID;
            if ($login_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $login_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        //$tmp_login_page_permalink = get_permalink($login_page_id);
        //$settings->set_value('login-page-url', $tmp_login_page_permalink);
        
		//Create reset page
        $tmp_reset_page = array(
            'post_title' => '新しいパスワードを取得',
            'post_name' => 'password-reset',
            'post_content' => '[tmp_reset_form]',
            'post_parent' => $login_page_id,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $reset_page_obj = get_page_by_path('membership-login/password-reset');
        if (!$reset_page_obj) {
            $reset_page_id = wp_insert_post($tmp_reset_page);
        } else {
            $reset_page_id = $reset_page_obj->ID;
            if ($reset_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $reset_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        //$tmp_reset_page_permalink = get_permalink($reset_page_id);
        //$settings->set_value('reset-page-url', $tmp_reset_page_permalink);
     
		//Create logout page
        $tmp_logout_page = array(
            'post_title' => 'ログアウト',
            'post_name' => 'membership-logout',
            'post_content' => '[tmp_logout_form]',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $logout_page_obj = get_page_by_path('membership-logout');
        $logout_page_id = 0;
        if (!$logout_page_obj) {
            $login_page_id = wp_insert_post($tmp_logout_page);
        } else {
            $logout_page_id = $logout_page_obj->ID;
            if ($logout_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $logout_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        //$tmp_logout_page_permalink = get_permalink($logout_page_id);
        //$settings->set_value('logout-page-url', $tmp_logout_page_permalink);
        
        //Create not-allow page
        $tmp_nowallow_page = array(
            'post_title' => '閲覧できないページです',
            'post_name' => 'membership-not-allow',
            'post_content' => '閲覧する権限のないページへアクセスしました。',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $notallow_page_obj = get_page_by_path('membership-not-allow');
        $notallow_page_id = 0;
        if (!$notallow_page_obj) {
            $notallow_page_id = wp_insert_post($tmp_nowallow_page);
        } else {
            $notallow_page_id = $notallow_page_obj->ID;
            if ($notallow_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $notallow_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $tmp_notallow_page_permalink = get_permalink($notallow_page_id);
        $settings->set_value('notallow-page-url', $tmp_notallow_page_permalink);
        
        $settings->save();
        //update_option('tmp2wp_db_version', $this->tmp2wp_db_version);
    }

    function update_tables() {
        $installed_ver = get_option( "tmp2db_db_version" ); // オプションに登録されたデータベースのバージョンを取得
        if ( $installed_ver != $oxy_db_version ) {
            // バージョンが異なる場合にアップデート用の処理
        }
    }

    function remove_database_tables() {

        global $wpdb;

        $tableName = $wpdb->prefix . 'tmp_memberships';
        $wpdb->query("DROP TABLE IF EXISTS ".$tableName);

        $tableName = $wpdb->prefix . 'tmp_memberships_tickets';
        $wpdb->query("DROP TABLE IF EXISTS ".$tableName);
        
        $tableName = $wpdb->prefix . 'tmp_memberships_categories';
        $wpdb->query("DROP TABLE IF EXISTS ".$tableName);
        
        $tableName = $wpdb->prefix . 'tmp_members';
        $wpdb->query("DROP TABLE IF EXISTS ".$tableName);

        delete_option("tmp-settings");
        delete_option("tmp-use-tickets");
        delete_option("tmp-finish-setup");
        delete_option( "tmp2wp_db_version" );
    }    

