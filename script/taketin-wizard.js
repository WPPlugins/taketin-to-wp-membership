/*!
 * Taketin Plugin
 * Wizard Script
 */
jQuery(document).ready(function($){
    //スワイプ画面設定
    var swiper = new Swiper('.swiper-container', {
    	pagination: '.swiper-pagination',
    	paginationClickable: true,
    	releaseFormElements : true,
    	watchSlidesProgress: true,
    	watchSlidesVisibility: true,
    	simulateTouch: false,
    	speed: 1000,
    });
    //テーブルスクロール設定
    $('.scrollTable').scrolltable({
        stripe: true,
        maxHeight: 140,
        oddClass: 'odd'
    });
    //テーブルにclassを追加
    $('.st-body-table').addClass("striped");
    //スワイプ「戻る」ボタン
    $('.btn-prev').click(function(){
        console.log('.btn-prev');
        swiper.slidePrev();
        return false;
    });
    //スワイプ「次へ」ボタン
    $('.btn-next').click(function(){
        console.log('.btn-next');
        swiper.slideNext();
        return false;
    });
    //API設定入力後イベント
    $('.btn-tmp-api').click(function(){
        console.log('.btn-tmp-api');
        var api_key = $('#taketin-app-secret').val();
        if (!api_key) {
			alert("API接続キーを入力してください。チケット情報が取得できません。");
            swiper.slidePrev();
			return false;
        }
        var endpoint = $('#taketin-system-url').val();
        if (!endpoint) {
			alert("API連携用URLを入力してください。チケット情報が取得できません。");
            swiper.slidePrev();
			return false;
        }
        setTicketList(api_key, endpoint);
        return false;
    });
    //利用するチケット選択後イベント
    $('.btn-use-ticket').click(function(){
        console.log('.btn-use-ticket');
        if ($('#use_ticket_list input:checked').length == 0) {
			alert("利用するチケットは最低１つ選択してください。");
            swiper.slidePrev();
			return false;
        }
        var use_tickets = $('#use_ticket_list input:checked').map(function() {
            return $(this).val();
        });
		var use_tickets_id_list = use_tickets.toArray();
		//hidden項目を追加
		var hidden_tag = '<input type="hidden" name="params[use_tickets]" value="' + use_tickets_id_list.join() + '" />';
		$('#form-hidden').html(""); //クリア
		$('#form-hidden').append( hidden_tag);
        return false;
    });
    //会員レベルテーブル行削除イベント
    $('.btn-table-delete').live('click',function(){
        console.log('.btn-table-delete');
        var no = $(this).data('membership-no');
        $('.membership_no_' + no).remove();
        $(this).parent('td').parent('tr').remove();
        return false;
    });
    //登録ボタン
    $('.btn-submit').click(function(){
        console.log('.btn-submit');
        
        var params = $('input[name^="params"]').map(function() {
        	return {name: $(this).attr("name"), value: $(this).val() };
        });
        
        saveConfiguratorWizard(params.toArray());
        return false;
    });
    
    // モーダルコンテンツを指定
    var modal = ".modal-content";
    //----------------------------------------
    // 「.modal-open」をクリック
    //----------------------------------------
    $('.modal-open').click(function(){
        // オーバーレイ用の要素を追加
        $('body').append('<div class="modal-overlay"></div>');
        // オーバーレイをフェードイン
        $('.modal-overlay').fadeIn('slow');
        
        // モーダルコンテンツの表示位置を設定
        modalResize();
        
        //モーダルにチケットのチェックボックスを生成する
	    $(".modal-content #membership_tickets").html("");
        $('#use_ticket_list input:checked').map(function() {
        	var tag = '<li><label>'
        	+'<input type="checkbox" name="membership_tickets[]" value="' + $(this).val() + '" />' + $(this).parent('label').text()
        	+ '</label></li>';
            $(".modal-content #membership_tickets").append(tag);
        });
        
         // モーダルコンテンツフェードイン
        $(modal).fadeIn('slow');

        // リサイズしたら表示位置を再取得
        $(window).on('resize', function(){
            modalResize();
        });

        return false;
    });
    
    //----------------------------------------
    // 「.modal-submit」をクリック
    //----------------------------------------
    $('.modal-submit').click(function(){
        //行番号
        var ran = getUniqueStr();
        //名前
        var modal_membership_name = $('#membership_name').val();
        if (!modal_membership_name) {
			alert("会員レベル名称を入力してください。");
			return false;
        }
        //階級
        var modal_membership_levelclass = $('#membership_levelclass').val();
        if (!modal_membership_levelclass) {
			alert("階級を正しく選択してください。");
			return false;
        }
        //選択チケット
        if ($('.modal-content #membership_tickets input:checked').length == 0) {
			alert("チケットを選択してください。");
			return false;
        }
        var modal_membership_tickets = $('.modal-content #membership_tickets input:checked').map(function() {
            return {"id": $(this).val(), "name": $(this).parent('label').text()};
        });
		var modal_membership_tickets_id_list = modal_membership_tickets.map(function(i, val) { return val['id'] }).toArray();
		var modal_membership_tickets_name_list = modal_membership_tickets.map(function(i, val) { return val['name'] }).toArray();
		
        //選択カテゴリ
        if ($('.modal-content #membership_cat input:checked').length == 0) {
			alert("カテゴリを選択してください。");
			return false;
        }
        var modal_membership_cat = $('.modal-content #membership_cat input:checked').map(function() {
            return {"id": $(this).val(), "name": $(this).parent('label').text()};
        });
		var modal_membership_cat_id_list = modal_membership_cat.map(function(i, val) { return val['id'] }).toArray();
		var modal_membership_cat_name_list = modal_membership_cat.map(function(i, val) { return val['name'] }).toArray();
		
		//テーブル行追加
		var tr_tag = '<tr><td data-no="' + ran + '">' + modal_membership_name + '</td><td>' + modal_membership_levelclass + '</td>'
		           + '<td>' + modal_membership_tickets_name_list.join() + '</td><td>' + modal_membership_cat_name_list.join() + '</td>'
		           + '<td><a href="#" class="btn-table-delete" data-membership-no="'+ran+'">削除</a></td></tr>';
		$('table.st-body-table tbody#the-list').append( tr_tag );
		
		//hidden項目を追加
		var hidden_tag = '<input type="hidden" class="membership_no_' + ran + '" name="params[membership][' + ran + '][no]" value="' + ran + '" />'
                       + '<input type="hidden" class="membership_no_' + ran + '" name="params[membership][' + ran + '][name]" value="' + modal_membership_name + '" />'
                       + '<input type="hidden" class="membership_no_' + ran + '" name="params[membership][' + ran + '][levelclass]" value="' + modal_membership_levelclass + '" />'
                       + '<input type="hidden" class="membership_no_' + ran + '" name="params[membership][' + ran + '][memberships_categories]" value="' + modal_membership_cat_id_list.join() + '" />'
                       + '<input type="hidden" class="membership_no_' + ran + '" name="params[membership][' + ran + '][memberships_tickets]" value="' + modal_membership_tickets_id_list.join() + '" />';
		$('#form-hidden').append( hidden_tag);
		
        modalClose();
        return false;
    });
    //----------------------------------------
    // 「.modal-overlay」あるいは「.modal-close」をクリック
    //----------------------------------------
    $('.modal-overlay, .modal-close').off().click(function(){
        modalClose();
        return false;
    });
    //----------------------------------------
    // モーダルコンテンツの表示位置を設定する関数
    //----------------------------------------
    function modalResize(){
        // ウィンドウの横幅、高さを取得
        var w = $(window).width();
        var h = $(window).height();
        // モーダルコンテンツの表示位置を取得
        var x = (w - $(modal).outerWidth(true)) / 2;
        var y = (h - $(modal).outerHeight(true)) / 2;
        // モーダルコンテンツの表示位置を設定
        $(modal).css({'left': x + 'px','top': y + 'px'});
    }
    //----------------------------------------
    // モーダルコンテンツを閉じる
    //----------------------------------------
    function modalClose() {
        // モーダルコンテンツとオーバーレイをフェードアウト
        $(modal).fadeOut('slow');
        //カテゴリのチェックをすべて外す
        $('.modal-content #membership_cat input:checked').map(function() {
            $(this).prop('checked', false);
        });
        //チケットチェッククリア
	    $(".modal-content #membership_tickets").html('').html('<li>チケット読み込み中...</li>');
        //名前クリア
        $('#membership_name').val('');
        //階級クリア
        $('#membership_levelclass').val(10);
        
        $('.modal-overlay').fadeOut('slow',function(){
            // オーバーレイを削除
            $('.modal-overlay').remove();
        });
    }
    //----------------------------------------
    //全チケット一覧を取得し、画面にセット
    //----------------------------------------
    function setTicketList(api_key, endpoint) {
        console.log('setTicketList');
        $.ajax(
			ajaxurl,
			{
				type: 'post',
				data: {
					'action' : 'api_ticket_wizard',
					'api_key' : api_key,
					'endpoint' : endpoint
				},
				dataType: 'json'
			}
		)
		// 検索成功時にはページに結果を反映
		.done(function(data) {
			// 結果
			console.log(data);
			if (data.result) {
			    $("#use_ticket_list").html("");
				if (data.hTickets) {
				    $.each(data.hTickets, function() {
            			var tag = '<li><label><input type="checkbox" name="tmp_use_tickets[]" size="30" value="' + this.ticket_id + '" />'
            			+ this.name	+ '</label></li>';
            			$("#use_ticket_list").append(tag);
				    });
				}
			}
		})
		// 検索失敗時には、その旨をダイアログ表示
		.fail(function(errors) {
			alert("API連携用URLまたはAPI接続キーが正しくありません。連携設定に戻って再度ご確認ください。");
			console.log(errors);
		});
    }
    //----------------------------------------
    //登録処理
    //----------------------------------------
    function saveConfiguratorWizard(params = array()) {
        console.log('registWizard');
        $.ajax(
			ajaxurl,
			{
				type: 'post',
				data: {
					'action' : 'save_configurator_wizard',
					'params' : params,
					'wp_nonce': wizard_params.ajax_nonce
				},
				dataType: 'json'
			}
		)
		// 成功時にはページに結果を反映
		.done(function(data) {
			// 結果
// 			console.log(data);
			if (data.result) {
			   alert("登録に成功しました。");
			   location.reload();
			} else {
			    if (data.message) {
			        alert(data.message);
			    } else {
			        alert("エラーが発生しました。リロードしてウィザードをやり直してください。");
			    }
			    if (data.log) {
			        console.log(data.log);
			    } 
			}
		})
		// 失敗時には、その旨をダイアログ表示
		.fail(function(errors) {
			alert("通信エラーが発生しました。リロードしてウィザードをやり直してください。");
			console.log(errors);
		});
    }
    //----------------------------------------
    //乱数生成
    //----------------------------------------
    function getUniqueStr(myStrong){
        var strong = 1000;
        if (myStrong) strong = myStrong;
        return new Date().getTime().toString(16)  + Math.floor(strong*Math.random()).toString(16)
    }
});