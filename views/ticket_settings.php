<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	if( $hSettings = get_option( 'tmp-settings' ) ):
	$endpoint = $hSettings['taketin-system-url'];
	$apipass = $hSettings['taketin-app-secret'];
?>
<?php
    $endpoint = preg_replace('/\/$/', '', $endpoint);
    $Tmp = wp_remote_post(
    	$endpoint.'/api/ticket/', 
    	array( 'body'=>array(
    		'apipass'=>$apipass
    		)
    	)
    );

    if(isset($Tmp->errors['http_request_failed'])){
        $hApiResult = array('result'=>false);
    }else{
        $hApiResult = json_decode($Tmp['body'],true);
    }
?>
<div class="wrap">
	<h1><?php echo esc_html( __( '利用するチケットの登録', 'my-custom-admin' ) ); ?></h1>
	<p>この会員制サイトで利用するチケットを登録します。</p>
	<?php 
	if($hApiResult['result'] && $hApiResult['hTickets']):
		$hTickets = $hApiResult['hTickets'];
	    
	    if(!$hUseTickets = get_option( 'tmp-use-tickets' )){
		    $hUseTickets = array();
	    }
	    //echo '<pre>';
	    //print_r($hUseTickets);	
	?>
	<form method="post" action="options.php">
		<input type="hidden" name="tab" value="<?php echo $current_tab; ?>" />
		<?php settings_fields('tmp-settings-tab-' . $current_tab); ?>
		<?php do_settings_sections('taketin_mp_membership_use_tickets'); ?>
	<?php
     //settings_fields( 'tmp-options-ticket' );
     //do_settings_sections( 'default' );
	?> 
	<table class="form-table">
     <tbody>
     <tr>
          <th scope="row"><label for="active">利用するチケット</label></th>
          <td>
               <?php foreach( $hTickets as $hTicket ):?>
				<?php $checked = (in_array($hTicket['ticket_id'],$hUseTickets))? 'checked="checked"' : '' ;?>
			        <label for="tmp_use_tickets_<?php echo $hTicket['ticket_id'];?>" style="margin-right: 10px;">
			        	<input type="checkbox" id="tmp_use_tickets_<?php echo $hTicket['ticket_id'];?>" name="tmp-use-tickets[]" size="30" value="<?php echo $hTicket['ticket_id'];?>" <?php echo $checked; ?>/><?php echo $hTicket['name'];?>
			        </label><br>
				<?php endforeach;?>	 
          </td>
     </tr>
          </tbody>
</table>
	
	<?php submit_button(); // 送信ボタン ?>
	 
	</form>
	<?php else:?>
	<p><?php echo esc_html( __( 'No tickets found. Check the API endpoint.', 'my-custom-admin' ) ); ?></p>
	
	<?php endif;?>
</div><!-- .wrap -->
<?php endif;?>
