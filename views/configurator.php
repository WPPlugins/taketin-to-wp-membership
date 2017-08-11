<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="wrap tmp-admin-menu-wrap">

<form>
<!-- Swiper -->
<div class="swiper-container">
    <h1>TAKETIN MP Membership::初期設定ウィザード</h1><!-- page title -->
    <h2 class="nav-tab-wrapper"></h2>

    <div class="swiper-wrapper">
        <div class="swiper-slide">
            <h2 class="title" data-swiper-parallax="-100">連携設定</h2>
            <div class="text" data-swiper-parallax="-300">
                <div class="swiper-contents">
                    <p>TAKETIN MPとの連携情報を登録します。</p>
                    <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="taketin-system-url">API連携用URL</label></th>
                        <td>
                            <input type="text" id="taketin-system-url" class="regular-text" name="params[taketin-system-url]" value="" />
                            <p>API連携用のURLを登録します。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="taketin-app-secret">API接続キー</label></th>
                        <td>
                            <input type="text" id="taketin-app-secret" class="regular-text" name="params[taketin-app-secret]" value="" />
                            <p></p>
                        </td>
                    </tr>
                    </tbody>
                    </table>
                </div>
                
                <!-- Add Navigation -->
                <div class="nav-button">
                    <input type="submit" name="submit" id="submit" class="btn-tmp-api btn-next button button-large" value="次 へ" />
                </div>
    
            </div>
        </div>
        
        <div class="swiper-slide">
            <h2 class="title" data-swiper-parallax="-100">利用するチケットの登録</h2>
            <div class="text" data-swiper-parallax="-300">
                
                <div class="swiper-contents">
                    <p>この会員制サイトで利用するチケットを登録します。</p>
                    <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="use_ticket_list">利用するチケット</label></th>
                        <td>
                            <ul id="use_ticket_list"><li>チケット読み込み中...</li></ul>
                        </td>
                    </tr>
                    </tbody>
                    </table>
                </div>
                
                <div class="nav-button">
                    <input type="submit" name="submit" id="submit" class="btn-prev button button-large" value="戻 る" />
                    <input type="submit" name="submit" id="submit" class="btn-next btn-use-ticket button button-large" value="次 へ" />
                </div>
            </div>
        </div>
        
        <div class="swiper-slide">
            <h2 class="title" data-swiper-parallax="-100">会員レベルの作成</h2>
            <div class="text" data-swiper-parallax="-300">
                <div class="swiper-contents">
                    <div>
                        <a href="#" class="modal-open page-title-action">追加</a>
                    </div>
                    <br />
                    <table class="membership wp-list-table widefat fixed stripes scrollTable">
                        <thead>
                            <tr>
                                <th class="name-column">会員レベル</th>
                                <th class="levelclass-column">階級</th>
                                <th class="ticket-column">チケット</th>
                                <th class="cat-column">カテゴリ</th>
                                <td class="check-column"> </td>
                            </tr>
                        </thead>
                        <tbody id="the-list">
                            <tr style="height:1px;"><td></td><td></td><td></td><td></td><td></td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Add Navigation -->
                <div class="nav-button">
                    <input type="submit" name="submit" id="submit" class="btn-prev button button-large" value="戻 る" />
                    <input type="submit" name="submit" id="submit" class="btn-next btn-membership button button-large" value="次 へ" />
                </div>
            </div>
        </div>
        
        <div class="swiper-slide">
            <h2 class="title" data-swiper-parallax="-100">基本設定</h2>
            <div class="text" data-swiper-parallax="-300">
                <div class="swiper-contents">
                    <p>コンテンツの保護設定を行います。</p>
                    <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="enable-contents-block">コンテンツ保護の開始</label></th>
                        <td>
                            <input type='checkbox' id='enable-contents-block' name='params[enable-contents-block]'  value="checked='checked'" checked />
                            ログインした会員のみがコンテンツを見れるようする。
                        </td>
                    </tr>
                    </tbody>
                    </table>
                </div>
                
                <br clear="all" />
                <div id="form-hidden"></div>
                <br clear="all" />
                <!-- Add Navigation -->
                <div class="nav-button">
                    <input type="submit" name="submit" id="submit" class="btn-prev button button-large" value="戻 る" />
                    <input type="submit" name="submit" id="submit" class="btn-submit button button-primary button-large" value="登 録" />
                </div>
            </div>
        </div>
    </div>
    <br />
    <!-- Add Pagination -->
    <div class="swiper-pagination swiper-pagination-blue"></div>
</div>
</form>

</div>

<!-- モーダル -->
<div id="con5" class="modal-content">
    <h2>会員レベルの新規作成</h2>
    <table class="form-table">
    <tbody>
    	<tr class="form-field">
            <th scope="row">
            	<label for="membership_name">会員レベル名称</label>
            </th>
            <td>
            	<input class="" name="membership_name" type="text" id="membership_name" value="" />
            </td>
    	</tr>
    	<tr class="form-field">
    		<th scope="row">
    		    <label for="membership_levelclass">階級</label>
    		</th>
    		<td>
    		    <input class="" name="membership_levelclass" type="number" id="membership_levelclass" value="10" min="1" />
    		    <p>※数字が低い方が上位の会員レベルとなります。</p>
    		</td>
    	</tr>
    	<tr class="form-field">
    	    <th scope="row">
    	        <label for="membership_tickets">対象となるチケット（複数可）</label>
            </th>
            <td>
                <ul id="membership_tickets"><li>チケット読み込み中...</li></ul>
            </td>
    	</tr>
    	<tr class="form-field form-required">
    	    <th scope="row">
    	        <label for="membership_cat">カテゴリ</label>
            </th>
            <td>
                <ul id="membership_cat"><?php wp_category_checklist(); ?></ul>
            </td>
    	</tr>
    </tbody>
    </table>
    
    <div class="nav-button">
        <a class="modal-close button button-large">閉じる</a>
        <a class="modal-submit button button-large">追加</a>

    </div>
    
</div>
<!-- モーダル -->

<style>
.swiper-contents {
    height: 230px;
}
.nav-button {
    height: 55px;
    width: 95%;
    margin-right: 5px;
    text-align: right;
}
#use_ticket_list {
    overflow:auto;
    width:350px;
    height:180px;
    border:1px solid #ccc;
    padding:8px;
}
table.membership {
    width: 95%;
}
table.membership thead, table.membership tbody{}
table.membership tbody {}


.modal-content #membership_tickets {
    overflow:auto;
    width:95%;
    height:120px;
    border:1px solid #ccc;
    padding:8px;
}
.modal-content #membership_cat {
    overflow:auto;
    width:95%;
    height:120px;
    border:1px solid #ccc;
    padding:8px;
}

.modal-content {
    position:fixed;
    display:none;
    z-index:2;
    width:50%;
    margin:0;
    padding:10px 20px;
    border:2px solid #aaa;
    background:#fff;
}

.modal-content p {
    margin:0;
    padding:0;
}

.modal-overlay {
    z-index:1;
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:120%;
    background-color:rgba(0,0,0,0.75);
}

.modal-open {
    color:#00f;
    text-decoration:underline;
}

.modal-open:hover {
    cursor:pointer;
    color:#f00;
}

.modal-close {
    color:#00f;
    text-decoration:underline;
}

.modal-close:hover {
    cursor:pointer;
    color:#f00;
}
</style>