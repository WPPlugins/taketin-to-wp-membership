<?php
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class TmpCategoryList extends WP_List_Table {

    public $selected_level_id = 1;
    public $viewableCategoryIds;
    
	function __construct() {
        parent::__construct( array(
				'singular'  => 'tmp2',     // 一覧データの単数形の名前
				'plural'    => 'tmp2s',    // 一覧データの複数形の名前
				'ajax'      => false        // このテーブルがAjaxをサポートしているか
		) );
		
        $selected = filter_input(INPUT_POST, 'membership_level_id');
        if( !$selected ){
	        $selected = filter_input( INPUT_GET, 'level' );
        }
        $this->selected_level_id = empty($selected) ? 1 : $selected;
        
        $this->viewableCategoryIds = TaketinMpUtils::get_viewable_categories($this->selected_level_id);

    }
	
	function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />'
            , 'term_id' => 'カテゴリID'
            , 'name' => 'カテゴリ名'
            , 'taxonomy' => 'タクソノミー'
            , 'description' => '説明'
            , 'count' => 'カウント'
        );
    }
    
    function get_sortable_columns() {
        return array();
    }
    
    function column_default($item, $column_name) {
        return stripslashes($item->$column_name);
    }

    function column_term_id($item) {
        return $item->term_id;
    }

    function column_taxonomy($item) {
        $taxonomy = $item->taxonomy;
        if ($taxonomy == 'category'){
            $taxonomy = 'Post Category';
        } else {
            $taxonomy = 'Custom Post Type ('.$taxonomy.')';
        }
        return $taxonomy;
    }
   
    function column_cb($item) {
        return sprintf(
            '<input type="hidden" name="ids_in_page[]" value="%s">
            <input type="checkbox" %s name="ids[]" value="%s" />', $item->term_id, in_array($item->term_id,$this->viewableCategoryIds) ? "checked" : "", $item->term_id
        );
    }
    
    public static function update_category_list() {
        //Check nonce
        if (
        	!isset($_POST['_wpnonce_edit_tmp_mem_category_admin_end'])
        	|| !wp_verify_nonce($_POST['_wpnonce_edit_tmp_mem_category_admin_end'], 'edit_tmp_mem_category_admin_end' )
        ){
        	//Nonce check failed.
        	wp_die('Nonce認証エラー 不正なアクセスです。');
        }
        
        $selected = filter_input(INPUT_POST, 'membership_level_id');
        
        $selected_level_id = empty($selected) ? 1 : $selected;

        $args = array('ids' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
        ));
        $filtered = filter_input_array(INPUT_POST, $args);
        $ids = $filtered['ids'];
        $args = array('ids_in_page' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
        ));
        $filtered = filter_input_array(INPUT_POST, $args);
        $ids_in_page = $filtered['ids_in_page'];
        $tmmessage = new TaketinMpMessages();

        global $wpdb;
        if ($ids) {
            
            try {
                //トランザクション開始
                $wpdb->query('START TRANSACTION');
                
                //tmp_memberships_ticketsテーブル削除
                $wpdb->delete($wpdb->prefix . "tmp_memberships_categories", array('membership_id' => $selected_level_id), array("%d"));
    
                //tmp_memberships_ticketsテーブル更新
                foreach ($ids as $category_id) {
                    $wpdb->insert(
                        $wpdb->prefix . "tmp_memberships_categories", 
                        $insert_data = array('membership_id' => $selected_level_id, 'category_id' => $category_id), 
                        $format = array('%d','%d')
                    );
                }
                //コミット
                $wpdb->query('COMMIT');
                $message = array('succeeded' => true, 'message' => '<p>限定カテゴリの設定を行いました。</p>');
                $tmmessage->set('status', $message);
                wp_redirect('admin.php?page=taketin_mp_membership_levels&level_action=category_list&level='.$selected_level_id);
                exit(0);
            } catch (Exception $ex) {
                $wpdb->query('ROLLBACK');
                $message = array('succeeded' => false, 'message' => '登録に失敗しました。エラーメッセージをご確認ください。', 'extra' => $ex->getMessage());
                $tmmessage->set('status', $message);
                exit(0);
            }
        }
        $message = array('succeeded' => true, 'message' => '<p>限定カテゴリの設定を更新しました。</p>');
        $tmmessage->set('status', $message);
        
    }
    
    function prepare_items() {
        $all_categories = array();
        $taxonomies = get_taxonomies($args = array('public' => true,'_builtin'=>false));
        $taxonomies['category'] = 'category';
        $all_terms = get_terms( $taxonomies, 'orderby=count&hide_empty=0&order=DESC');  
        $totalitems = count($all_terms);
        $perpage = 100;
        $paged = !empty($_GET["paged"]) ? sanitize_text_field($_GET["paged"]) : '';
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);
        $offset = 0;
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
        }
        for ($i = $offset; $i < ((int) $offset + (int) $perpage) && !empty($all_terms[$i]); $i++) {
            $all_categories[] = $all_terms[$i];
        }
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $all_categories;
    }

    function no_items() {
        'No category found.';
    }

    
}