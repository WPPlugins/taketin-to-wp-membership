<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php $wp_table->prepare_items(); ?>
<br />

<form method="post">
	<?php $wp_table->display(); ?>
</form>

<?php echo sprintf('<p><a href="admin.php?page=%s_levels&level_action=add" class="button-primary">追加</a></p>', TMP_MEM_PREFIX); ?>
	