<?php
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class TaketinMpMembers extends WP_List_Table {

    function handle_main_members_admin_menu() {

        $action = filter_input(INPUT_GET, 'member_action');
        $action = empty($action) ? filter_input(INPUT_POST, 'action') : $action;
        $selected = $action;
    ?>
        <div class="wrap tmp-admin-menu-wrap"><!-- start wrap -->

            <h1><?php echo 'TAKETIN MP Membership::会員'; ?></h1>

            <h2 class="nav-tab-wrapper tmp-members-nav-tab-wrapper"><!-- start nav menu tabs -->
                <a class="nav-tab <?php echo ($selected == "") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership">会員一覧</a>
                <a class="nav-tab <?php echo ($selected == "add") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership&member_action=add">追加</a>
                
                <?php
                if ($selected == 'edit') {//Only show the "edit member" tab when a member profile is being edited from the admin side.
                    echo '<a class="nav-tab nav-tab-active" href="#">編集</a>';
                }

                //Trigger hooks that allows an extension to add extra nav tabs in the members menu.
                do_action('tmp_members_menu_nav_tabs', $selected);

                $menu_tabs = apply_filters('tmp_members_additional_menu_tabs_array', array());
                foreach ($menu_tabs as $member_action => $title) {
                    ?>
                    <a class="nav-tab <?php echo ($selected == $member_action) ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership&member_action=<?php echo $member_action; ?>" ><?php TmpUtils::e($title); ?></a>
                    <?php
                }
                ?>
            </h2><!-- end nav menu tabs -->
    <?php
        do_action('tmp_members_menu_after_nav_tabs');

        //Trigger hook so anyone listening for this particular action can handle the output.
        do_action('tmp_members_menu_body_' . $action);

        //Allows an addon to completely override the body section of the members admin menu for a given action.
        $output = apply_filters('tmp_members_menu_body_override', '', $action);
        if (!empty($output)) {
            //An addon has overriden the body of this page for the given action. So no need to do anything in core.
            echo $output;
            echo '</div>'; //<!-- end of wrap -->
            return;
        }

        //Switch case for the various different actions handled by the core plugin.
        switch ($action) {
            case 'members_list':
                //Show the members listing
                $this->index();
                break;
            case 'add':
                //Process member profile add
                $this->process_form_request();
                break;
            case 'edit':
                //Process member profile edit
                $this->process_form_request();
                break;
            case 'bulk':
                //Handle the bulk operation menu
                $this->bulk_operation_menu();
                break;
            case 'delete':
            	//削除
                $this->delete();
            default:
                //Show the members listing page by default.
                $this->index();
                break;
        }

        echo '</div>'; //<!-- end of wrap -->
        
        wp_enqueue_style( "options", TMP_MEM_DIR_URL. 'style/options.css' );
    }

    function process_form_request() {
        if (isset($_REQUEST['member_id'])) {
            //This is a member profile edit action
            $record_id = sanitize_text_field($_REQUEST['member_id']);
            if (!is_numeric($record_id)) {
                wp_die('選択した会員情報が不正です。');
            }
            return $this->edit(absint($record_id));
        }

        //This is an profile add action.
        return $this->add();
    }
	/**
	 * 追加画面表示
	 */
    function add() {
    	$form = apply_filters('tmp_admin_registration_form_override', '');
        if (!empty($form)) {
            echo $form;
            return;
        }
        
        //API接続用パラメータ取得
        if( $hSettings = get_option( 'tmp-settings' ) ) {
        	$endpoint = $hSettings['taketin-system-url'];
	        $apipass = $hSettings['taketin-app-secret'];
        }
        //プルダウン用会員レベル
        $levels = $this->get_membership_level_list();
        //利用するチケット一覧をjson形式で取得
        $json_ticket_mst = $this->getJsonUseTicketSettings();
		
		include_once(TMP_MEM_PATH . 'views/admin_member/add.php');
        return false;
    }
	/**
	 * 追加画面表示
	 */
    function edit($id) {
        global $wpdb;
$query = <<< EOM
SELECT tm.*, tms.name as memberships_name
FROM %s tm LEFT JOIN %s tms 
ON tm.memberships_id = tms.id
WHERE tm.id = %s
EOM;
		$query = sprintf($query, $wpdb->prefix . "tmp_members", $wpdb->prefix . "tmp_memberships", absint($id));
        $member = $wpdb->get_row($query, ARRAY_A);
        extract($member, EXTR_SKIP);
        
        //API接続用パラメータ取得
        if( $hSettings = get_option( 'tmp-settings' ) ) {
        	$endpoint = $hSettings['taketin-system-url'];
	        $apipass = $hSettings['taketin-app-secret'];
        }
        //プルダウン用会員レベル
        $levels = $this->get_membership_level_list();
        //利用するチケット一覧をjson形式で取得
        $json_ticket_mst = $this->getJsonUseTicketSettings();

        include_once(TMP_MEM_PATH . 'views/admin_member/edit.php');
        return false;
    }
	/**
	 * 削除処理
	 */
    function delete() {
        if (isset($_REQUEST['member_id'])) {
            //Check nonce
            if (!isset($_REQUEST['delete_tmp_member_nonce']) || !wp_verify_nonce($_REQUEST['delete_tmp_member_nonce'], 'delete_tmp_member_admin_end')) {
                //Nonce check failed.
                wp_die('Nonce認証エラー 不正なアクセスです。');
            }

            $id = sanitize_text_field($_REQUEST['member_id']);
            
			global $wpdb;
            $query = $wpdb->prepare("DELETE FROM " . $wpdb->prefix . "tmp_members WHERE id = %d",  absint($id));
            $wpdb->query($query);
    
            echo '<div id="message" class="updated fade"><p>削除しました。</p></div>';
        }
    }
	/**
	 * 一括削除処理
	 */
    function process_bulk_action() {
        //Detect when a bulk action is being triggered... then perform the action.
        $members = isset($_REQUEST['members']) ? $_REQUEST['members'] : array();
        $members = array_map('sanitize_text_field', $members);

        $current_action = $this->current_action();
        if (!empty($current_action)) {
            //Bulk operation action. Lets make sure multiple records were selected before going ahead.
            if (empty($members)) {
                echo '<div id="message" class="error"><p>一括操作対象の会員を選択してください。</p></div>';
                return;
            }
        } else {
            //No bulk operation.
            return;
        }

        global $wpdb;
        //perform the bulk operation according to the selection
        if ('bulk_delete' === $current_action) {
            foreach ($members as $record_id) {
                if (!is_numeric($record_id)) {
                    wp_die('選択した会員情報が不正です。');
                }
                $query = $wpdb->prepare("DELETE FROM " . $wpdb->prefix . "tmp_members WHERE id = %d", $record_id);
                $wpdb->query($query);
            }
            echo '<div id="message" class="updated fade"><p>選択した会員を削除しました。</p></div>';
            return;
        }
    }
	/**
	 * 一覧表示
	 */
    function index() {
		//一括削除処理
		$this->process_bulk_action();

		$wp_table = new WPListTable();
		include_once(TMP_MEM_PATH . 'views/admin_member/index.php');
		return false;
    }
    
    /**
     * 会員レベルリストを取得 
     **/
    function get_membership_level_list() {
        global $wpdb;
        //
        $query = "SELECT * FROM {$wpdb->prefix}tmp_memberships ORDER BY levelclass";
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * 利用するチケット一覧をjson形式で取得
     **/
    function getJsonUseTicketSettings() {
        $result = "";
		//DBから利用中のチケットIDを取得し、そのチケット情報をAPIから取得する
		list($error, $api_tickets) = TaketinMpUtils::get_api_tickets();
        
		if (empty($error)) {
		    $ticket_data = array();
		    foreach ($api_tickets as $ticket) {
		        //id,nameだけを配列にする
		        $ticket_data[] = array(
		            'id' => $ticket['ticket_id'],
		            'name' => $ticket['name'],
		        );
		    }
		    $result = json_encode($ticket_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		}
		return $result;
    }
}

class WPListTable extends WP_List_Table {
	/** ************************************************************************
	 * 必須。親のコンストラクタを参照して初期化を行います。
	 * 親の参照を使用していくつかの設定のセットを行います。
	 ***************************************************************************/
	function __construct(){
		global $status, $page;
		//　親のデフォルトをセット
        parent::__construct(array(
            'singular' => 'member',
            'plural' => 'members',
            'ajax' => false
        ));
	}
	/**
	 * カラム（列）の設定
	 */
	function get_columns() {
		return $columns= array(
				'cb' => '<input type="checkbox" />',
				'name' => "名前",
				'email' => "メールアドレス",
				'memberships_name' => "会員レベル",
				'memberships_check_date' => "会員レベル判定日時",
				'last_login' => "最終ログイン日時"
		);
	}
	/**
	 * カラムのソートの設定
	 */
	function get_sortable_columns() {
		return array(
				'name' => array('name', true),
				'email' => array('email', true),
				'memberships_name' => array('memberships_name', true),
				'memberships_check_date' => array('memberships_check_date', true),
				'last_login' => array('last_login', true),
		);
	}
	function sanitize_value_by_array($val_to_check, $valid_values) {
		$keys = array_keys($valid_values);
		$keys = array_map('strtolower', $keys);
		if (in_array($val_to_check, $keys)) {
			return $val_to_check;
		}
		return reset($keys); //Return he first element from the valid values
	}
	// 最終ログイン日時が0の時は空欄表示
	function column_last_login($item) {
		if ($item['last_login'] == '0000-00-00 00:00:00' ) {
			$item['last_login'] = " ";
		}
		return $item['last_login'];
	}
	function column_name($item) {
		$delete_tmp_member_nonce = wp_create_nonce( 'delete_tmp_member_admin_end' );
	
		$actions = array(
			'edit' => sprintf('<a href="admin.php?page=%s&member_action=edit&member_id=%s">%s</a>', TMP_MEM_PREFIX, $item['id'], '編集'),
			'delete' => sprintf(
				'<a href="admin.php?page=%s&member_action=delete&member_id=%s&delete_tmp_member_nonce=%s" onclick="return confirm(\'削除してもよろしいですか？\')">%s</a>',
				TMP_MEM_PREFIX,
				$item['id'],
				$delete_tmp_member_nonce,
				'削除'
			),
		);
		return $item['name'] . $this->row_actions($actions);
	}
	
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="members[]" value="%s" />', $item['id']
		);
	}
	function get_bulk_actions() {
		$actions = array(
				'bulk_delete' => '削除'
		);
		return $actions;
	}
	function column_default($item, $column_name) {
		 
		if ($column_name == 'role') {
			return ucfirst($item['role']);
		}
		return stripslashes($item[$column_name]);
	}
    function no_items() {
        _e('No member found.');
    }
	function prepare_items() {
		global $wpdb;
$query = <<< EOM
SELECT tm.*, tms.name as memberships_name
FROM %s tm LEFT JOIN %s tms 
ON tm.memberships_id = tms.id
EOM;
		$query = sprintf($query, $wpdb->prefix . "tmp_members", $wpdb->prefix . "tmp_memberships");
		
		//検索キーワード
		//Get the search string (if any)
        $s = filter_input(INPUT_GET, 's');
        if (empty($s)) {
            $s = filter_input(INPUT_POST, 's');
        }

        $status = filter_input(INPUT_GET, 'status');
        $filter1 = '';
		//Add the search parameter to the query
        if (!empty($s)) {
            $s = sanitize_text_field($s);
            $s = trim($s); //Trim the input
            $filter1 .= "WHERE ( tm.name LIKE '%" . strip_tags($s) . "%' "
                    . " OR tm.email LIKE '%" . strip_tags($s) . "%' )";
        }
		
		//Read and sanitize the sort inputs.
		$orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'tm.id';
		$order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : 'DESC';
		
		$sortable_columns = $this->get_sortable_columns();
		$orderby = $this->sanitize_value_by_array($orderby, $sortable_columns);
		$order = $this->sanitize_value_by_array($order, array('DESC' => '1', 'ASC' => '1'));
		
		if (!empty($orderby) && !empty($order)) {
			$query.=' ORDER BY ' . $orderby . ' ' . $order;
		}
		
		$totalitems = $wpdb->query($query); //Return the total number of affected rows
		$perpage = 50;
		$paged = !empty($_GET["paged"]) ? sanitize_text_field($_GET["paged"]) : '';
		if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
			$paged = 1;
		}
		$totalpages = ceil($totalitems / $perpage);
		if (!empty($paged) && !empty($perpage)) {
			$offset = ($paged - 1) * $perpage;
			$query.=' LIMIT ' . (int) $offset . ', ' . (int) $perpage;
		}
		$this->set_pagination_args(array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
		));
		//カラム（列）の設定
		$columns = $this->get_columns();
		//カラムのソートの設定
		$sortable = $this->get_sortable_columns();
		$hidden = array(
			"id",
			"unique_code",
			"progress",
			"created",
		);
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $wpdb->get_results($query, ARRAY_A);
	}
}