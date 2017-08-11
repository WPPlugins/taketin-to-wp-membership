<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$password_reset_url = 'password-reset';
?>
<div class="tmp-login-widget-form">
    <?php if( isset($message['result'])):?>
    <div class="tmp-error-msg" id="login_error"><p><?php echo $message['mess']; ?></p></div>
    <?php endif; ?>
    <form id="tmp-login-form" name="tmp-login-form" method="post" action="">
        <?php
            //フォーム送信元チェック用
            wp_nonce_field( 'login_tmp_end', '_wpnonce_login_tmp_end' )
        ?>
        <div class="tmp-login-form-inner">
            <div class="tmp-mail-label">
                <label for="tmp_mail" class="tmp-label">メールアドレス</label>
            </div>
            <div class="tmp-mail-input">
                <input type="text" class="tmp-text-field tmp-mail-field" id="tmp_mail" value="" size="25" name="tmp_mail" />
            </div>
            <div class="tmp-password-label">
                <label for="tmp_password" class="tmp-label">パスワード</label>
            </div>
            <div class="tmp-password-input">
                <input type="password" class="tmp-text-field tmp-password-field input" id="tmp_password" value="" size="25" name="tmp_password" />
            </div>
            <div class="forgetmenot tmp-remember-me">
                <span class="tmp-remember-checkbox"><input type="checkbox" id="auto_login" name="auto_login" value="1"></span>
                <label class="tmp-rember-label" for="auto_login"> ログイン状態を保存する</label>
            </div>
            
            <div class="tmp-login-submit">
                <input type="submit" class="button button-primary button-large tmp-login-form-submit" name="tmp-login" value="<?php echo the_title();?>"/>
            </div>
            
            <div class="tmp-login-action-msg">
                <span class="tmp-login-widget-action-msg"><?php //echo $auth->get_message(); ?></span>
            </div>
        </div>
    </form>
</div>
<p id="nav">
    <a id="forgot_pass" class="tmp-login-form-pw-reset-link"  href="<?php echo $password_reset_url; ?>">パスワードをお忘れですか？</a>
</p>