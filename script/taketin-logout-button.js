/*!
 * Taketin Plugin
 * Create Logout Button Script
 */
jQuery(document).ready(function($){
    var button_html = '<a href="' + tmp01.logout_url +  '" class="tmp-logout">ログアウト</a>';
    $(tmp01.target_element).append(button_html);
});
