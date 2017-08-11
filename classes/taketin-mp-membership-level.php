<?php

/**
 * Description of TaketinMpMembershipLevel
 */
class TaketinMpMembershipLevel {

    private static $_instance = null;

    private function __construct() {
        //NOP
    }

    public static function get_instance() {
        self::$_instance = empty(self::$_instance) ? new TaketinMpMembershipLevel() : self::$_instance;
        return self::$_instance;
    }

    // 会員レベル登録
    public function create_level() {
        //Check nonce
        if (
            !isset($_POST['_wpnonce_create_tmp_mem_level_admin_end']) 
            || !wp_verify_nonce($_POST['_wpnonce_create_tmp_mem_level_admin_end'], 'create_tmp_mem_level_admin_end' )
        ){
            //Nonce check failed.
            wp_die('Nonce認証エラー 不正なアクセスです。');
        }
        global $wpdb;
        $default_level_fields = array(
            'tickets' => '',
            'levelclass' => '',
            'name' => '',
        );
        $tmmessage = new TaketinMpMessages();
        $form = new TaketinMpLevelForm($default_level_fields);
        if ($form->is_valid()) {
            $level_info = $form->get_sanitized();
            //tmp_membershipテーブル登録
            $wpdb->insert(
                $wpdb->prefix . "tmp_memberships", 
                $insert_data = array('name' => $level_info['name'], 'levelclass' => $level_info['levelclass']), 
                $format = array('%s')
            );
            $membership_id = $wpdb->insert_id;
            //tmp_memberships_ticketsテーブル登録
            foreach ($level_info['tickets'] as $ticket_id) {
                $wpdb->insert(
                    $wpdb->prefix . "tmp_memberships_tickets", 
                    $insert_data = array('membership_id' => $membership_id, 'ticket_id' => $ticket_id), 
                    $format = array('%d','%d')
                );
            }
            $message = array('succeeded' => true, 'message' => '<p>会員レベルを登録しました。</p>');
            $tmmessage->set('status', $message);
            wp_redirect('admin.php?page=taketin_mp_membership_levels');
            exit(0);
        }
        $message = array('succeeded' => false, 'message' => '登録に失敗しました。エラーメッセージをご確認ください。', 'extra' => $form->get_errors());
        $tmmessage->set('status', $message);
    }

    // 会員レベル更新
    public function edit_level($id) {
        //Check nonce
        if (
            !isset($_POST['_wpnonce_edit_tmp_mem_level_admin_end'])
            || !wp_verify_nonce($_POST['_wpnonce_edit_tmp_mem_level_admin_end'], 'edit_tmp_mem_level_admin_end' )
        ){
            //Nonce check failed.
            wp_die('Nonce認証エラー 不正なアクセスです。');
        }
        
        global $wpdb;
        $default_level_fields = array(
            'name' => '',
            'tickets' => '',
            'levelclass' => '',
        );
        $tmmessage = new TaketinMpMessages();
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "tmp_memberships WHERE id = %d", $id);
        $level = $wpdb->get_row($query, ARRAY_A);
        $form = new TaketinMpLevelForm($default_level_fields);
        if ($form->is_valid()) {
            $level_info = $form->get_sanitized();
            
            try {
                //トランザクション開始
                $wpdb->query('START TRANSACTION');
                //
                $wpdb->update($wpdb->prefix . "tmp_memberships", array('name' => $level_info['name'], 'levelclass' => $level_info['levelclass']), array('id' => $id),array('%s'), array('%d'));
                //tmp_memberships_ticketsテーブル削除
                $wpdb->delete($wpdb->prefix . "tmp_memberships_tickets", array('membership_id' => $id), array("%d"));
    
                //tmp_memberships_ticketsテーブル更新
                foreach ($level_info['tickets'] as $ticket_id) {
                    $wpdb->insert(
                        $wpdb->prefix . "tmp_memberships_tickets", 
                        $insert_data = array('membership_id' => $id, 'ticket_id' => $ticket_id), 
                        $format = array('%d','%d')
                    );
                }
                //コミット
                $wpdb->query('COMMIT');
                $message = array('succeeded' => true, 'message' => '<p>対象の会員レベルを更新しました。</p>');
                $tmmessage->set('status', $message);
                wp_redirect('admin.php?page=taketin_mp_membership_levels');
                exit(0);
            } catch (Exception $ex) {
                $wpdb->query('ROLLBACK');
                $message = array('succeeded' => false, 'message' => '更新に失敗しました。エラーメッセージをご確認ください。', 'extra' => $ex->getMessage());
                $tmmessage->set('status', $message);
                exit(0);
            }
        }
        $message = array('succeeded' => false, 'message' => '更新に失敗しました。エラーメッセージをご確認ください。', 'extra' => $form->get_errors());
        $tmmessage->set('status', $message);
    }

    // 対象の会員に許可されたカテゴリIDを返す
    public function get_member_allow_category_ids($membership_level_id) {
        global $wpdb;
        $sql = "SELECT category_id FROM " . $wpdb->prefix . "tmp_memberships_categories WHERE membership_id = %d"; 
        $query = $wpdb->prepare($sql, $membership_level_id);
        $result = $wpdb->get_results($query, ARRAY_N);
        return $result;
    }
    
    // 対象の会員に許可されたカテゴリページかどうか判定する
    public function is_member_allow_category($membership_level_id, $category_id) {
        $is_allow = false;
        global $wpdb;
        $sql = "SELECT COUNT(id) FROM " . $wpdb->prefix . "tmp_memberships_categories WHERE membership_id = %d AND category_id = %d"; 
        $query = $wpdb->prepare($sql, $membership_level_id, $category_id);
        $result_count = $wpdb->get_var($query);
        if ($result_count == 1) {
            //許可される
            $is_allow = true;
        }
        return $is_allow;
    }
}
