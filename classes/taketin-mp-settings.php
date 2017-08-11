<?php
class TaketinMpSettings {
	
	private static $_this;
    private $settings;
    private $ticket_settings;
    public $current_tab;
    private $tabs;
    
	private function __construct() {
        $this->settings = (array) get_option('tmp-settings');
        $this->ticket_settings = (array) get_option('tmp-use-tickets');
    }
    
    public static function get_instance() {
        self::$_this = empty(self::$_this) ? new TaketinMpSettings() : self::$_this;
        return self::$_this;
    }
    
	public function init_config_hooks() {
		
		if (is_admin()) {
			
			//Read the value of tab query arg.
            $tab = filter_input(INPUT_GET, 'tab');
            $tab = empty($tab) ? filter_input(INPUT_POST, 'tab') : $tab;
            $this->current_tab = empty($tab) ? 1 : $tab;
            
            //Setup the available settings tabs array.
            $this->tabs = array(
                1 => '基本設定', 
                2 => 'チケット設定',
                //3 => 'Addons Settings'
            );
            
            add_action('tmp-draw-settings-nav-tabs', array(&$this, 'draw_tabs'));
            
            //Register the various settings fields for the current tab.
            $method = 'tab_' . $this->current_tab;
            if (method_exists($this, $method)) {
                $this->$method();
            }
		}
	}
	
    public function get_value($key, $default = "") {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return $default;
    }

    public function set_value($key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }

    public function save() {
        update_option('tmp-settings', $this->settings);
    }	
    
	public function handle_main_settings_admin_menu(){

        ?>
        <div class="wrap tmp-admin-menu-wrap"><!-- start wrap -->
        <h1>TAKETIN MP Membership::設定</h1><!-- page title -->
        
        <!-- start nav menu tabs -->
        <?php do_action("tmp-draw-settings-nav-tabs"); ?>
        <!-- end nav menu tabs -->
        <?php
        
        //Switch to handle the body of each of the various settings pages based on the currently selected tab
        $current_tab = $this->current_tab;

        switch ($current_tab) {
            case 1:
                //General settings
                include(TMP_MEM_PATH . 'views/admin_settings.php');
                break;            
            case 2:
                //Redirect settings
                include(TMP_MEM_PATH . 'views/ticket_settings.php');
                break;            
            default:
                include(TMP_MEM_PATH . 'views/admin_settings.php');
                break;
        }
        
        echo '</div>';//<!-- end of wrap -->
        
    }
    
    public function tmp_general_post_submit_check_callback() {

        //Show settings updated message
        if (isset($_REQUEST['settings-updated'])) {
            echo '<div id="message" class="updated fade"><p>設定を変更しました。</p></div>';
        }
    }
    
	private function tab_1() {
		
		//Mess
		add_settings_section('tmp-general-post-submission-check', '', array(&$this, 'tmp_general_post_submit_check_callback'), 'taketin_mp_membership_settings');
		 
        //Register settings sections and fileds for the general settings tab.
        register_setting('tmp-settings-tab-1', 'tmp-settings', array(&$this, 'sanitize_tab_1'));
        add_settings_section('general-settings', '基本設定', array(&$this, 'general_settings_callback'), 'taketin_mp_membership_settings');
        add_settings_field('enable-contents-block', 'コンテンツ保護の開始', array(&$this, 'checkbox_callback'), 'taketin_mp_membership_settings', 'general-settings', array('item' => 'enable-contents-block',
            'message' => 'ログインした会員のみがコンテンツを見れるようする。'));
        
        add_settings_section('api-settings', '連携設定', array(&$this, 'general_settings_callback'), 'taketin_mp_membership_settings');
        add_settings_field('taketin-system-url', 'API連携用URL', array(&$this, 'textfield_long_callback'), 'taketin_mp_membership_settings', 'api-settings', array('item' => 'taketin-system-url',
            'message' => 'API連携用のURLを登録します。'));
        add_settings_field('taketin-app-secret', 'API接続キー', array(&$this, 'textfield_long_callback'), 'taketin_mp_membership_settings', 'api-settings', array('item' => 'taketin-app-secret',
            'message' => ''));
        add_settings_section('page-settings', 'ページ設定', array(&$this, 'general_settings_callback'), 'taketin_mp_membership_settings');
        add_settings_field('notallow-page-url', '閲覧する権限のないページへのアクセス時', array(&$this, 'textfield_long_callback'), 'taketin_mp_membership_settings', 'page-settings', array('item' => 'notallow-page-url','message' => ''));
        
        add_settings_section('logout-button-settings', 'ログアウトボタン設定', array(&$this, 'general_settings_callback'), 'taketin_mp_membership_settings');
        add_settings_field('logout-button-target-style-element', 
            'ログアウトボタンの挿入箇所（css 要素指定）', 
            array(&$this, 'textfield_long_callback'), 
            'taketin_mp_membership_settings',
            'logout-button-settings',
            array('item' => 'logout-button-target-style-element','message' => '')
        );
    }
    
    public function sanitize_tab_1($input) {
        if (empty($this->settings)) {
            $this->settings = (array) get_option('tmp-settings');
        }
        $output = $this->settings;
        //general settings block
        $output['enable-contents-block'] = isset($input['enable-contents-block']) ? esc_attr($input['enable-contents-block']) : "";

        $output['taketin-system-url'] = isset($input['taketin-system-url']) ? esc_attr($input['taketin-system-url']) : "";
		$output['taketin-app-secret'] = isset($input['taketin-app-secret']) ? esc_attr($input['taketin-app-secret']) : "";
		$output['login-page-url'] = isset($input['login-page-url']) ? esc_attr($input['login-page-url']) : "";
		$output['notallow-page-url'] = isset($input['notallow-page-url']) ? esc_attr($input['notallow-page-url']) : "";
		$output['logout-button-target-style-element'] = isset($input['logout-button-target-style-element']) ? esc_attr($input['logout-button-target-style-element']) : "";
		
		
        return $output;
    }
    
    private function tab_2() {
		
		//Mess
		add_settings_section('tmp-general-post-submission-check', '', array(&$this, 'tmp_general_post_submit_check_callback'), 'taketin_mp_membership_use_tickets');
		
        //Register settings sections and fileds for the general settings tab.
        register_setting('tmp-settings-tab-2', 'tmp-use-tickets', array(&$this, 'sanitize_tab_2'));

    }
    
    public function sanitize_tab_2($input) {

        $output = $input;
        
        //general settings block
        
        return $output;
    }

    public function checkbox_callback($args) {
        $item = $args['item'];
        $msg = isset($args['message']) ? $args['message'] : '';
        $is = esc_attr($this->get_value($item));
        echo "<input type='checkbox' id='".TMP_MEM_PREFIX.'_'.$item."' $is name='tmp-settings[" . $item . "]'  value=\"checked='checked'\" />";
        echo "<label for='".TMP_MEM_PREFIX.'_'.$item."'>" . $msg . "</label>";
    }
    
    public function textfield_long_callback($args) {
	    
        $item = $args['item'];
        $msg = isset($args['message']) && $args['message'] ? '<p class="exp">' . $args['message'] . '</p>' : '';
        $text = esc_attr($this->get_value($item));
        echo "<input type='text' name='tmp-settings[" . $item . "]'  size='100' value='" . $text . "' />";
        echo $msg;
    }
    

/*
    public function set_value($key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }

    public function save() {
        update_option('tmp-settings', $this->settings);
    }
*/
    public function draw_tabs() {
        $current = $this->current_tab;

        ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($this->tabs as $id => $label){ ?>
                <a class="nav-tab <?php echo ($current == $id) ? 'nav-tab-active' : ''; ?>" href="admin.php?page=taketin_mp_membership_settings&tab=<?php echo $id ?>"><?php echo $label ?></a>
            <?php } ?>
        </h2>
        <?php
    }
    
    public function general_settings_callback($args) {
	    switch ($args['id']) {
            case 'general-settings':
                echo 'コンテンツの保護設定を行います。';
                break;            
            case 'api-settings':
            	echo 'TAKETIN MPとの連携情報を登録します。';
                break;
            case 'page-settings':
                echo '転送先のページURLを設定します。通常は変更の必要はありません。';
                break;
            case 'logout-button-settings':
                echo 'javascriptで動的にログアウトボタンを生成します。';
                break;
            default:
                echo '設定を行います。';
                break;
        }
    }
}