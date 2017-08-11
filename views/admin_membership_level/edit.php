<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap" id="tmp-level-page">
<form action="" method="post" name="tmp-edit-level" id="tmp-edit-level" class="validate"<?php do_action('level_edit_form_tag');?>>
<input name="action" type="hidden" value="editlevel" />
<?php wp_nonce_field( 'edit_tmp_mem_level_admin_end', '_wpnonce_edit_tmp_mem_level_admin_end' ) ?>

<p>会員レベルを編集します。</p>
<p>編集中の会員レベルは[<strong><?php echo stripslashes($name); ?></strong>]です。</p>
<table class="form-table">
    <tbody>
	<tr>
		<th scope="row">
		    <label for="name">会員レベル名称</label>
		</th>
		<td>
		    <input class="regular-text validate[required]" name="name" type="text" id="name" value="<?php echo stripslashes($name);?>" aria-required="true" required="true" />
		</td>
	</tr>
	<tr>
		<th scope="row">
		    <label for="levelclass">階級</label>
		</th>
		<td>
		    <input class="regular-text validate[required]" name="levelclass" type="number" id="levelclass" value="<?php echo stripslashes($levelclass);?>" aria-required="true" required="true" />
		</td>
	</tr>
	<tr class="form-field form-required">
	    <th scope="row">
	        <label for="tickets">対象となるチケット（複数可）</label>
        </th>
		<td>
            <?php echo empty($error_membership_lebel_edit) ? '' : $error_membership_lebel_edit ?>
            
            <?php if (!empty($tickets_membership_lebel_edit)) : ?>
            <?php foreach( $tickets_membership_lebel_edit as $hTicket ):?>
            	<?php
            		//登録済みチケットにチェックを入れる
            		$checked = '';
            		if (in_array($hTicket['ticket_id'], $regist_tickets)) {
            			$checked = 'checked';
            		}
            	?>
		        <label for="tmp_use_tickets_<?php echo $hTicket['ticket_id'];?>" style="margin-right: 10px;">
		        	<input type="checkbox" id="tmp_use_tickets_<?php echo $hTicket['ticket_id'];?>" name="tickets[]" size="30" value="<?php echo $hTicket['ticket_id'];?>" <?php echo $checked; ?> />
		        	<?php echo $hTicket['name'];?>
		        </label>
		        <br>
			<?php endforeach;?>
			<?php endif;?>
        </td>
	</tr>
    <?php echo  apply_filters('tmp_admin_edit_membership_level_ui', '', $id);?>
</tbody>
</table>
<?php submit_button("更新", 'primary', 'edittmplevel', true, array( 'id' => 'edittmplevelsub' ) ); ?>
</form>
</div>
