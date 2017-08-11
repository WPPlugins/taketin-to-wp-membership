<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="swpm-yellow-box">    
    <p>
        それぞれの会員レベルに応じて、閲覧することができるカテゴリを設定します。
    </p>
</div>
<form id="category_list_form" method="post">
    <?php wp_nonce_field( 'edit_tmp_mem_category_admin_end', '_wpnonce_edit_tmp_mem_category_admin_end' ) ?>
    <p class="tmp-select-box-left">
	    <h2>１．設定する会員レベルを選択</h2>
        <label for="membership_level_id">会員レベル：</label>
        <select id="membership_level_id" name="membership_level_id">
            <?php echo TaketinMpUtils::membership_level_dropdown($category_list->selected_level_id); ?>
        </select>                
    </p>
    <h2>２．閲覧できるカテゴリをチェック</h2>
        <?php $category_list->prepare_items(); ?>   
        <?php $category_list->display(); ?>
         <p class="tmp-select-box-left"><input type="submit" class="button-primary" name="update_category_list" value="<?php echo '更新'; ?>"></p>
</form>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#membership_level_id').change(function() {
            $('#category_list_form').submit();
        });
    });
</script>
