<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap" id="tmp-level-page">

<form action="" method="post" name="tmp-create-level" id="tmp-create-level" class="validate">
<input name="action" type="hidden" value="createlevel" />
<p>会員レベルを追加します。</p>
<?php 
    //フォーム送信元チェック用
    wp_nonce_field( 'create_tmp_mem_level_admin_end', '_wpnonce_create_tmp_mem_level_admin_end' )
?>

<table class="form-table">
<tbody>
	<tr>
        <th scope="row">
        	<label for="name">会員レベル名称</label>
        </th>
        <td>
        	<input class="regular-text validate[required]" name="name" type="text" id="name" value="" aria-required="true" required="true" />
        </td>
	</tr>
	<tr>
		<th scope="row">
		    <label for="levelclass">階級</label>
		    
		</th>
		<td>
		    <input class="regular-text validate[required]" name="levelclass" type="number" id="levelclass" value="10" min="1" aria-required="true" required="true" />
		    ※数字が低い方が上位の会員レベルとなります。
		</td>
	</tr>
	<tr class="form-field form-required">
	    <th scope="row">
	        <label for="tickets">対象となるチケット（複数可）</label>
        </th>
        <td>
            <?php echo empty($error_membership_lebel_add) ? '' : $error_membership_lebel_add ?>
            
            <?php if (!empty($tickets_membership_lebel_add)) : ?>
            <?php foreach( $tickets_membership_lebel_add as $hTicket ):?>
		        <label for="tmp_use_tickets_<?php echo $hTicket['ticket_id'];?>" style="margin-right: 10px;">
				<input type="checkbox" id="tmp_use_tickets_<?php echo $hTicket['ticket_id'];?>" name="tickets[]" size="30" value="<?php echo $hTicket['ticket_id'];?>" />
				<?php echo $hTicket['name'];?> <!--(ticket_id=<?php echo $hTicket['ticket_id'];?>)-->
		        </label>
		        <br>
			<?php endforeach;?>
			<?php endif;?>
        </td>
	</tr>
    <?php echo  apply_filters('tmp_admin_add_membership_level_ui', '');?>
</tbody>
</table>

<?php submit_button("登録", 'primary', 'createtmplevel', true, array( 'id' => 'createtmplevelsub' ) ); ?>
</form>
</div>

