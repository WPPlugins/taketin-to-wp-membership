<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<!-- This file outputs the settings form fields for a lot of the settings pages -->
<form action="options.php" method="POST">
    <input type="hidden" name="tab" value="<?php echo $current_tab; ?>" />
    <?php settings_fields('tmp-settings-tab-' . $current_tab); ?>
    <?php do_settings_sections('taketin_mp_membership_settings'); ?>
    <?php submit_button(); ?>
</form>
