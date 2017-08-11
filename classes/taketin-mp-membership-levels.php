<?php
if (!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class TaketinMpMembershipLevels extends WP_List_Table {

    function __construct() {
        parent::__construct(array(
            'singular' => 'Membership Level',
            'plural' => 'Membership Levels',
            'ajax' => false
        ));
    }
    
    function handle_main_membership_level_admin_menu(){
        wp_enqueue_style( "options", TMP_MEM_DIR_URL. 'style/options.css' );
	    $level_action = filter_input(INPUT_GET, 'level_action');
        $action = $level_action;
        $selected= $action;
        
        ?>
        <div class="wrap tmp-admin-menu-wrap"><!-- start wrap -->

        <!-- page title -->
        <h1><?php echo  'TAKETIN MP Membership::会員レベル'; ?></h1>
        <!-- start nav menu tabs -->
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ($selected == "") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership_levels">会員レベル一覧</a>
            <a class="nav-tab <?php echo ($selected == "add") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership_levels&level_action=add">追加</a>
            <!--<a class="nav-tab <?php echo ($selected == "manage") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership_levels&level_action=manage">限定コンテンツ</a>-->
            <?php
            if ($selected == 'edit') {
                    echo '<a class="nav-tab nav-tab-active" href="#">編集</a>';
                }
            ?>
            <a class="nav-tab <?php echo ($selected == "category_list") ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership_levels&level_action=category_list">限定カテゴリ</a>
        </h2>
        <!-- end nav menu tabs -->
        <?php echo $this->membership_level_contents($action); ?>
        </div><!-- end wrap -->
        <?php
    }
    
    function membership_level_contents($action) {
        switch ($action) {
			case 'add':
				//追加
			case 'edit':
				//編集
				$this->process_form_request();
				break;
            case 'manage':
            	//限定コンテンツ
                $this->manage();
                break;
            case 'category_list':
            	//限定カテゴリ
                $this->manage_categroy();
                break;
            case 'delete':
            	//削除
                $this->delete_level();
			default:
				//一覧表示
                $this->level_index();
				break;
		}
    }
    
    /**
     * 
     **/
    function process_form_request() {
    	//idがあれば編集処理、なければ追加処理
        if (isset($_REQUEST['id'])) {
            //This is a level edit action
            $record_id = sanitize_text_field($_REQUEST['id']);
            if(!is_numeric($record_id)){
                wp_die('Error! ID must be numeric.');
            }
            return $this->edit($record_id);
        }
        
        //Level add action
        return $this->add();
    }
	
    function process_bulk_action() {
        //Detect when a bulk action is being triggered...
        global $wpdb;
        if ('bulk_delete' === $this->current_action()) {
            if (!isset($_REQUEST['ids'])) {
                echo '<div id="message" class="error"><p>一括操作対象の会員レベルを選択してください。</p></div>';
                return;
            }

            $records_to_delete = array_map( 'sanitize_text_field', $_REQUEST['ids'] );
            if (empty($records_to_delete)) {
                echo '<div id="message" class="error"><p>一括操作対象の会員レベルを選択してください。</p></div>';
                return;
            }
            foreach ($records_to_delete as $record_id) {
                if( !is_numeric( $record_id )){
                    wp_die('選択した会員レベル情報が不正です。');
                }
                $query = $wpdb->prepare("DELETE FROM " . $wpdb->prefix . "tmp_memberships WHERE id = %d", $record_id);
                $wpdb->query($query);
                
                $query = $wpdb->prepare("DELETE FROM " . $wpdb->prefix . "tmp_memberships_tickets WHERE membership_id = %d", $record_id);
		        $wpdb->query($query);
            }
            echo '<div id="message" class="updated fade"><p>選択した会員レベルを削除しました。</p></div>';
        }
    }
	/**
	 * 追加用Viewを表示
	 * @return boolean
	 */
	function add() {
    	//チケット情報
		list($error_membership_lebel_add, $tickets_membership_lebel_add) = TaketinMpUtils::get_api_tickets();
		//Level add interface
		include_once(TMP_MEM_PATH . 'views/admin_membership_level/add.php');
		return false;
	}
	/**
	 * 編集用Viewを表示
	 * @param integer $id
	 */
	function edit($id) {
    	//チケット情報
		list($error_membership_lebel_edit, $tickets_membership_lebel_edit) = TaketinMpUtils::get_api_tickets();
		global $wpdb;
		//該当データを取得
		$query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tmp_memberships WHERE id = %d", absint($id));
		$membership = $wpdb->get_row($query, ARRAY_A);
		
		$query = $wpdb->prepare("SELECT ticket_id FROM {$wpdb->prefix}tmp_memberships_tickets WHERE membership_id = %d", absint($id));
		$tickets = $wpdb->get_results($query, ARRAY_A);

		$regist_tickets = array();
		
		if (count($tickets) > 0) {
			foreach ($tickets as $ticket) {
				$regist_tickets[] = $ticket['ticket_id'];
			}
		}
		//配列を展開
		extract($membership, EXTR_SKIP);
		include_once(TMP_MEM_PATH . 'views/admin_membership_level/edit.php');
		return false;
	}
	/**
	 * 一覧表示
	 */
    function level_index() {
		//一括削除処理
		$this->process_bulk_action();

		$wp_table = new WPListTable();
		//DBから利用中のチケットIDを取得し、そのチケット情報をAPIから取得する
		list($error, $api_tickets) = TaketinMpUtils::get_api_tickets();
		$wp_table->api_tickets_data = $api_tickets;
		include_once(TMP_MEM_PATH . 'views/admin_membership_level/index.php');
		return false;
    }
	/**
	 * 削除
	 */
    function delete_level() {
        global $wpdb;
        if (isset($_REQUEST['id'])) {
            //Check nonce
            if (
            	!isset($_REQUEST['delete_tmp_mem_level_nonce']) || 
            	!wp_verify_nonce($_REQUEST['delete_tmp_mem_level_nonce'], 'nonce_delete_tmp_mem_level_admin_end' )
            ){
                //Nonce check failed.
                wp_die('Nonce認証エラー 不正なアクセスです。');
            }
            
            $id = sanitize_text_field($_REQUEST['id']);
            $id = absint($id);
            $query = $wpdb->prepare("DELETE FROM " . $wpdb->prefix . "tmp_memberships WHERE id = %d", $id);
            $wpdb->query($query);
            
            $query = $wpdb->prepare("DELETE FROM " . $wpdb->prefix . "tmp_memberships_tickets WHERE membership_id = %d", $id);
            $wpdb->query($query);
            
            echo '<div id="message" class="updated fade"><p>削除しました。</p></div>';
        }
    }
    
	/**
	 * 限定カテゴリ
	 */
    function manage_categroy() {
	    $selected = "category_list";
        include_once('taketin-mp-category-list.php');
        $category_list = new TmpCategoryList();
        include_once(TMP_MEM_PATH . 'views/admin_membership_level/manage_categroy.php');
    }
}


class WPListTable extends WP_List_Table {
	//APIのチケット情報
	var $api_tickets_data = array();
	/** ************************************************************************
	 * 必須。親のコンストラクタを参照して初期化を行います。
	 * 親の参照を使用していくつかの設定のセットを行います。
	 ***************************************************************************/
	function __construct(){
		global $status, $page;
		//　親のデフォルトをセット
		parent::__construct( array(
				'singular'  => 'tmp2',     // 一覧データの単数形の名前
				'plural'    => 'tmp2s',    // 一覧データの複数形の名前
				'ajax'      => false        // このテーブルがAjaxをサポートしているか
		) );
	}
	/**
	 * カラム（列）の設定
	 */
	function get_columns() {
		return $columns= array(
				'cb' => '<input type="checkbox" />'
				, 'id' => "ID"
				, 'name' => "会員レベル"
				, 'levelclass' => "階級"
				, 'tickets' => "対象チケット"
		);
	}
	/**
	 * カラムのソートの設定
	 */
	function get_sortable_columns() {
		return array(
				'id' => array('id', true),
				'name' => array('alias', true),
				'levelclass' => array('levelclass', true)
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
	function column_id($item) {
		$delete_tmp_mem_level_nonce = wp_create_nonce( 'nonce_delete_tmp_mem_level_admin_end' );
	
		$actions = array(
				'edit' => sprintf('<a href="admin.php?page=%s_levels&level_action=edit&id=%s">%s</a>', TMP_MEM_PREFIX, $item['id'], '編集'),
				'delete' => sprintf(
					'<a href="admin.php?page=%s_levels&level_action=delete&id=%s&delete_tmp_mem_level_nonce=%s" onclick="return confirm(\'削除してもよろしいですか？\')">%s</a>',
					TMP_MEM_PREFIX,
					$item['id'],
					$delete_tmp_mem_level_nonce,
					'削除'
				),
		);
		return $item['id'] . $this->row_actions($actions);
	}
	
	function column_cb($item) {
		return sprintf(
				'<input type="checkbox" name="ids[]" value="%s" />', $item['id']
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
	function prepare_items() {
		global $wpdb;
		
		$query = "SELECT * FROM " . $wpdb->prefix . "tmp_memberships ";
		if (isset($_POST['s'])){
			//検索キーワード
			// $query .= " AND alias LIKE '%" . sanitize_text_field($_POST['s']) . "%' ";
			$query .= " AND name LIKE '%" . sanitize_text_field($_POST['s']) . "%' ";
		}
		//Read and sanitize the sort inputs.
		$orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'id';
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
			$query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
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
		$hidden = array();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$items = $wpdb->get_results($query, ARRAY_A);

		//取得した会員レベル情報にチケット名を追加する
		foreach ($items as &$item) {
			//チケット名配列
			$tickets_values = array();

			$query = $wpdb->prepare("SELECT ticket_id FROM {$wpdb->prefix}tmp_memberships_tickets WHERE membership_id = %d", $item['id']);
			$tickets = $wpdb->get_results($query, ARRAY_A);
			if ($tickets) {
				foreach ($tickets as $ticket) {
					if (!$this->api_tickets_data) continue;
					foreach ($this->api_tickets_data as $api_ticket) {
						//会員レベルで選択しているチケットIDとAPIから取得したチケットIDが一致した場合
						if ($ticket["ticket_id"] == $api_ticket["ticket_id"]) {
							$tickets_values[] = $api_ticket["name"] . " ";
							break;
						}
					}
				}
				$item["tickets"] = implode(", ", $tickets_values);
			}
		}
		$this->items = $items;
	}
}