<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
//API接続用パラメータ取得
if( $hSettings = get_option( 'tmp-settings' ) ) :
?>
<?php
$post_url = $hSettings['taketin-system-url'];
$post_url .= "/login/";
$login_url = '/membership-login/';
$msg = TaketinMpUtils::getMessage();
?>
<div class="tmp-pw-reset-widget-form">
	<?php if( !isset($msg['result']) ):?>
	<p class="message">ユーザー名またはメールアドレスを入力してください。新しいパスワードを作成するためのリンクをメールでお送りします。</p>
	<?php else:?>
	<div <?php if( $msg['result'] == 'success' ):?>class="message"<?php else:?>id="login_error"<?php endif;?>>
		<?php echo $msg['mess'];?><br>
	</div>
	<?php endif;?>
<form id="tmp-pw-reset-form" name="tmp-reset-form" method="post" action="">
    <?php
        //フォーム送信元チェック用
        wp_nonce_field( 'reset_tmp_end', '_wpnonce_reset_tmp_end' )
    ?>
    <div class="tmp-pw-reset-widget-inside">
        <div class="tmp-pw-reset-email tmp-margin-top-10">
            <label for="tmp_reset_email" class="tmp_label tmp-pw-reset-email-label">メールアドレス</label>
        </div>
        <div class="tmp-pw-reset-email-input tmp-margin-top-10">
            <input type="hidden" name="_method" value="POST" />
            <input name="Forgotten" type="hidden" value="1" />
            <input type="text" name="tmp_reset_email" class="tmp-text-field tmp-pw-reset-text" id="tmp_reset_email"  value="" size="60" />
        </div>
        <div class="tmp-pw-reset-submit-button tmp-margin-top-10">
            <input type="submit" name="tmp-reset" class="button button-primary button-large tmp-pw-reset-submit" value="<?php echo the_title();?>" />
        </div>
    </div>
</form>
</div>
<p id="nav">
    <a id="forgot_pass" class="tmp-login-form-pw-reset-link"  href="<?php echo $login_url; ?>">ログイン</a>
</p>
<?php else: ?>
<div class="tmp-pw-reset-widget-form">
    <?php echo __( 'Please Do Settings.', 'my-custom-admin' ); ?>
</div>
<?php endif; ?>
