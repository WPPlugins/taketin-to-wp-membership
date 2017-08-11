<?php
/**
 * Description of BAjax
 *
 * @author nur
 */
class TaketinMpAjax {
    
    
    public static function api_user() {
        //ユニークコードを取得
        $unique_code = filter_input(INPUT_POST, 'unique_code');
        //API通信
        echo TaketinMpUtils::get_api_user($unique_code);
        exit;
    }
    
    public static function api_user_ticket() {
        //ユニークコードを取得
        $unique_code = filter_input(INPUT_POST, 'unique_code');
        //API通信
        echo TaketinMpUtils::get_api_user_ticket($unique_code);
        exit;
    }
    
    public static function wizard_api_ticket() {
        //APIのURLを取得
        $endpoint = filter_input(INPUT_POST, 'endpoint');
        //APIのキーを取得
        $api_key = filter_input(INPUT_POST, 'api_key');
        echo TaketinMpUtils::wizard_get_api_ticket($endpoint, $api_key);
        exit;
    }
    
    public static function wizard_save_configurator() {
        //AJAXから送信されたデータを取得
        $params = filter_input( INPUT_POST, "params", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        // if (TMP_MEM_DEBUG) error_log(__CLASS__ . ":" . __FUNCTION__ . " [LINE:" . __LINE__ . "] TEST: " . print_r($params, true), 0);
        $result = TaketinMpUtils::save_configurator($params);
        echo json_encode($result);
        exit;
    }

    //所持するすべてのチケットIDを元に会員レベル判定
    public static function get_membership_level_from_tickets() {
        //チケットIDを取得
        $ticket_ids = filter_input( INPUT_POST, "ticket_ids", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        
        if (count($ticket_ids) == 0) {
            return json_encode(array("result"=> false));
        }
        $membership_level = TaketinMpUtils::get_membership_level($ticket_ids);
        echo json_encode($membership_level);
        exit;
    }
}
