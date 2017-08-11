<?php
include_once('taketin-mp-protection-base.php');
class TmpProtection extends TmpProtectionBase {
	
	private static $_this;
	
	protected $posts;
	protected $categories;
	
	private function __construct() {
		$this->msg = "";
        $this->init(1);
    }
    
    public static function get_instance() {
        self::$_this = empty(self::$_this) ? (new TmpProtection()) : self::$_this;
        return self::$_this;
    }
    
    public function get_last_message() {
        return $this->msg;
    }
    public function is_protected($id) {
        /*
        if ($this->post_in_parent_categories($id) || $this->post_in_categories($id)) {
            $this->msg = '<p style="background: #FFF6D5; border: 1px solid #D1B655; color: #3F2502; margin: 10px 0px 10px 0px; padding: 5px 5px 5px 10px;">
                    The category or parent category of this post is protected. You can change the category protection settings 
                    from the <a href="admin.php?page=simple_wp_membership_levels&level_action=category_list" target="_blank">category protection</a> menu.
                    </p>';
            return true;
        }
        */
        return $this->in_posts($id) || $this->in_pages($id) || $this->in_attachments($id) || $this->in_custom_posts($id);
    }

	
	
}