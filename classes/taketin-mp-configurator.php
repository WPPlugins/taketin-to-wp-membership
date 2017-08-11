<?php

class TmpConfigurator {
    
    //Nonce-Key
    const WPNONCE_TMP_WIZARD = "create_tmp_wizard";
    
    //設定項目キー
    private $settings_keys = array(
        'taketin-system-url',
        'taketin-app-secret',
        'enable-contents-block'
    );
    
    //会員レベル項目キー
    private $memberships_keys = array(
        'name',
        'levelclass',
        'memberships_categories',
        'memberships_tickets'
    );
    
    public function __construct() {
    }
    
    //ウィザードを表示する必要があるかどうか
    public function is_finished_setup() {
        $finish_setup = get_option('tmp-finish-setup');
        if ($finish_setup == true) {
            return true;
        }
        return false;
    }
    
    //ウィザード画面を開く
    public function wizard() {
        
        $swiper_version = "3.4.2";
        wp_enqueue_script('swiper', "https://cdnjs.cloudflare.com/ajax/libs/Swiper/{$swiper_version}/js/swiper.min.js", array( 'jquery' ), $swiper_version, false);
        wp_enqueue_script('swiper', "https://cdnjs.cloudflare.com/ajax/libs/Swiper/{$swiper_version}/js/swiper.jquery.min.js", array( 'jquery' ), $swiper_version, false);
        wp_enqueue_style('swiper', "https://cdnjs.cloudflare.com/ajax/libs/Swiper/{$swiper_version}/css/swiper.min.css", array(), $swiper_version, false);
        
        wp_enqueue_script('jquery-ui', "https://code.jquery.com/ui/1.12.1/jquery-ui.js", array('jquery'), "", false);
        wp_enqueue_script('jquery.scrolltable', TMP_MEM_DIR_URL . 'script/lib/jquery.scrolltable.js', array( 'jquery' ), "", false);
        
        wp_enqueue_script('tmp-wizard', TMP_MEM_DIR_URL . 'script/taketin-wizard.js?v=' . time(), array( 'jquery' ), "", false);
        
        $ajax_nonce = wp_create_nonce( self::WPNONCE_TMP_WIZARD );
        wp_localize_script(
            'tmp-wizard', //値を渡すjsファイルのハンドル名
            'wizard_params', //任意のオブジェクト名
            array('ajax_nonce' => $ajax_nonce) //プロバティ
        );
    
	    include_once(TMP_MEM_PATH . 'views/configurator.php');
    }
    
    //保存データ
    private $save_data = null;
    
    
    //ajax接続時のセキュリティチェックを行う
    public function check_security() {
        $res = check_ajax_referer(self::WPNONCE_TMP_WIZARD, 'wp_nonce', false);
        return $res;
    }
    
    //ajaxから送信された値を配列にセットする
    public function set($params = array()) {
        foreach ($params as $param) {
            $key_name = 'params[use_tickets]';
            if ($param['name'] == $key_name) {
                $this->save_data['use_tickets'] = $param['value'];
            }
        }
        
        foreach ($this->settings_keys as $key) {
            $key_name = 'params[' . $key . ']';
            foreach ($params as $param) {
                if ($param['name'] == $key_name) {
                    $this->save_data['tmp_settings'][$key] = $param['value'];
                }
            }
        }
        
        foreach ($params as $param) {
            $key_name = sprintf('params[membership][%s][no]', $param['value']);
            if ($param['name'] == $key_name) {
                //箱だけ用意
                $this->save_data['membership'][$param['value']] = array();
            }
        }
        
        foreach ($this->save_data['membership'] as $membership_num => $membership) {
            foreach ($params as $param) {
                foreach ($this->memberships_keys as $key) {
                    $key_name = sprintf('params[membership][%s][%s]', $membership_num, $key);
                    if ($param['name'] == $key_name) {
                        $this->save_data['membership'][$membership_num][$key] = $param['value'];
                    }
                }
            }
        }
        if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] TEST: " . print_r($this->save_data, true), 0);
    }
    
    //バリデーションチェック
    public function validate() {
        try {
            foreach ($this->settings_keys as $key) {
                if (empty($this->save_data[$key])) {
                    //値が空なのでエラー
                    throw new Exception("値{$key} が未設定");
                }
            }
        
            foreach ($this->save_data['membership'] as $membership_key => $membership) {
                
                foreach ($this->memberships_keys as $key) {
                    if (empty($this->save_data['membership'][$membership_key][$key])) {
                        //値が空なのでエラー
                        throw new Exception("値membership-{$key} が未設定");
                    }
                }
            }
        
            return true;
        } catch (Exception $ex) {
            return array("message" => $ex->getMessage());
        }
    }
    
    //保存処理
    public function save() {
        global $wpdb;
        try {
            //トランザクション開始
            $wpdb->query('START TRANSACTION');
            //--------------------------------
            //wp_optionsへ保存
            //--------------------------------
            //各種設定情報
            $data_settings = $this->save_data['tmp_settings'];
            $saved_settings = (array) get_option('tmp-settings');
            $insert_data = !empty($saved_settings) ? array_merge($data_settings, $saved_settings) : $data_settings;
            $res = $wpdb->update(
                'wp_options', 
                array('option_value' => serialize($insert_data)), 
                array('option_name' => 'tmp-settings'), 
                array('%s'), 
                array('%s') 
            );
            if ($res != 1) throw new Exception("tmp-settingsの保存失敗");
            
            //使用するチケットID
            $data_use_ticket = $this->save_data['use_tickets'];
            $list_use_ticket = explode(',',$data_use_ticket);
            $insert_data = array('option_name' => 'tmp-use-tickets', 'option_value' => serialize($list_use_ticket));
            $wpdb->insert(
                'wp_options',
                $insert_data,
                array('%s','%s')
            );
            if ($wpdb->insert_id == 0) throw new Exception("use-ticketsの保存失敗");
            
            //--------------------------------
            //wp_tmp_membershipsへ保存
            //wp_tmp_memberships_ticketsへ保存
            //wp_tmp_memberships_categoriesへ保存
            //--------------------------------
            $idx = 1;
            $data_memberships = $this->save_data['membership'];
            foreach ($data_memberships as $membership) {
                //カンマ区切りのチケット、カテゴリ文字列をそれぞれ配列に変換する
                $memberships_tickets = $membership['memberships_tickets'];
                $list_memberships_tickets = explode(',', $memberships_tickets);
                $memberships_cat = $membership['memberships_categories'];
                $list_memberships_cat = explode(',', $memberships_cat);
                
                //tmp_membershipテーブル登録
                $wpdb->insert(
                    $wpdb->prefix . "tmp_memberships", 
                    $insert_data = array('name' => $membership['name'], 'levelclass' => $membership['levelclass']), 
                    $format = array('%s','%s')
                );
                if ($wpdb->insert_id == 0) throw new Exception("{$idx}番目のmembershipの保存失敗");
                
                $membership_id = $wpdb->insert_id;
                //wp_tmp_memberships_ticketsへ保存
                foreach ($list_memberships_tickets as $ticket_id) {
                    $wpdb->insert(
                        $wpdb->prefix . "tmp_memberships_tickets", 
                        $insert_data = array('membership_id' => $membership_id, 'ticket_id' => $ticket_id), 
                        $format = array('%d','%d')
                    );
                    if ($wpdb->insert_id == 0) throw new Exception("{$idx}番目のticket_idの保存失敗");
                }
                //wp_tmp_memberships_categoriesへ保存
                foreach ($list_memberships_cat as $cat_id) {
                    $wpdb->insert(
                        $wpdb->prefix . "tmp_memberships_categories", 
                        $insert_data = array('membership_id' => $membership_id, 'category_id' => $cat_id), 
                        $format = array('%d','%d')
                    );
                    if ($wpdb->insert_id == 0) throw new Exception("{$idx}番目のcategory_idの保存失敗");
                }
                $idx++;
            }
            //--------------------------------
            //wp_optionsへ保存
            //--------------------------------
            //セットアップ完了
            $insert_data = array('option_name' => 'tmp-finish-setup', 'option_value' => true);
            $wpdb->insert(
                'wp_options',
                $insert_data,
                array('%s','%s')
            );
            if ($wpdb->insert_id == 0) throw new Exception("tmp-finish-setupsの保存失敗");
            
            //コミット
            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $ex) {
            $wp_error = $wpdb->last_error;
            $wpdb->query('ROLLBACK');
            return array('message' => $ex->getMessage(), 'error-log' => $wp_error);
        }
    }
    
    
}