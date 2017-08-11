<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap" id="tmp-profile-page" type="add">

<p>会員を追加します。</p>

<!-- APIを利用しユーザー情報、会員レベルを取得、セットする -->
<form name="tmp-get-user-from-api" id="tmp-get-user-from-api" >
<h2>1. ユニークコードの入力</h2>
<table class="form-table">
    <tbody>
   	<tr>
		<th scope="row"><label for="">ユニークコード</label></th>
		<td>
			<input class="regular-text" name="unique_code" type="text" id="unique_code" required="true" />
		</td>
	</tr>
	</tbody>
</table>   
<?php submit_button( "APIを利用し情報取得", 'success', 'apitmpuser', true, array( 'id' => 'apitmpuser' ) ); ?>
</form>

<h2>2. 入力・設定内容の確認</h2>
<p>TAKETIN MPから取得したデータを反映しています。</p>

<!-- DB登録用フォーム -->
<form action="" method="post" name="tmp-create-user" id="tmp-create-user" class="validate">
<input name="action" type="hidden" value="createuser" />
<input name="unique_code" id="unique_code" type="hidden" value="" />
<?php 
    //フォーム送信元チェック用
    wp_nonce_field( 'create_tmp_user_admin_end', '_wpnonce_create_tmp_user_admin_end' )
?>
<table class="form-table">
<tbody>
    <tr class="swpm-registration-email-row">
        <th><label for="email">メールアドレス</label></th>
        <td><input type="text" id="email" class="" size="50" name="email" required="true" /></td>
    </tr>
    <tr>
		<th scope="row"><label for="name_sei">名前 姓</label></th>
		<td><input class="regular-text" name="name_sei" type="text" id="name_sei" required="true" /></td>
	</tr>
	<tr>
		<th scope="row"><label for="name_mei">名前 名</label></th>
		<td><input class="regular-text" name="name_mei" type="text" id="name_mei" required="true" /></td>
	</tr>
	<tr>
		<th scope="row"><label for="tickets">所持チケット一覧</label></th>
		<td>
			<p id="tickets"></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="memberships_id">会員レベル</label></th>
		<td>
		    <select class="regular-text" name="memberships_id" id="memberships_id">
            <?php foreach ($levels as $level):?>
            <option value="<?php echo $level['id'];?>"> <?php echo $level['name']?> (LevelClass: <?php echo $level['levelclass']?>)</option>
            <?php endforeach;?>
			</select>
		</td>
	</tr>
</tbody>
</table>
<?php submit_button( "登録", 'primary', 'createtmpuser', true, array( 'id' => 'createtmpusersub' ) ); ?>
</form>
</div>
<script>
var json_ticket_mst = JSON.parse('<?php echo $json_ticket_mst; ?>');

jQuery(document).ready(function($){

	// ユーザー取得ボタンクリック
	$('#tmp-get-user-from-api #apitmpuser').click(function() {
		var unique_code = $("#unique_code").val();
		if (!unique_code) {
			alert("ユニークコードを入力してください。");
			return false;
		}
		ajaxApiUser(unique_code);
		ajaxApiUserTicket(unique_code);
		$("#tmp-create-user input#unique_code").val(unique_code);
		return false;
	});
	
	function ajaxApiUser(unique_code) {
		// APIへの通信
		$.ajax(
			ajaxurl,
			{
				type: 'post',
				data: {
					'action' : 'api_user',
					'unique_code' : unique_code
				},
				dataType: 'json'
			}
		)
		// 検索成功時にはページに結果を反映
		.done(function(data) {
			// 結果
			// console.log(data);
			if (data.result) {
				if (data.hUser.User.name_sei) {
					$("#tmp-create-user input#name_sei").val(data.hUser.User.name_sei);
				}
				if (data.hUser.User.name_mei) {
					$("#tmp-create-user input#name_mei").val(data.hUser.User.name_mei);
				}
				if (data.hUser.User.mail) {
					$("#tmp-create-user input#email").val(data.hUser.User.mail);
				}
				return true;
			}
			return false;
		})
		// 検索失敗時には、その旨をダイアログ表示
		.fail(function(errors) {
				alert("該当するユーザーデータを取得できませんでした。");
				console.log(errors);
				return false;
		});
	}
	
	function ajaxApiUserTicket(unique_code) {
		// APIへの通信
		$.ajax(
			ajaxurl,
			{
				type: 'post',
				data: {
					'action' : 'api_user_ticket',
					'unique_code' : unique_code
				},
				dataType: 'json'
			}
		)
		// 検索成功時にはページに結果を反映
		.done(function(data) {
			// 結果
			//console.log(data);
	
			var result= [];
			var ticket_names= [];
			if (data.hTickets) {
				$.each(data.hTickets, function(){
					var _ticket_id = this.ticket_id;
					result.push(_ticket_id);
					var _html = '<input class="regular-text" name="tickets[]" type="hidden" id="tickets" value="' + _ticket_id + '"/>'
					$("#tmp-create-user").append(_html);
					
					var _ticket = json_ticket_mst.filter(function(item, index){
						if (item.id == _ticket_id) return true;
					});
					if (_ticket.length) {
						$("p#tickets").append(_ticket[0].name + ", ");
					}
				});
				
				decisionMembershipLevel(unique_code, result);
				return true;
			}
			console.log(result);
		})
		// 検索失敗時には、その旨をダイアログ表示
		.fail(function(errors) {
			alert("該当するユーザー所持チケットを取得できませんでした。");
			console.log(errors);
			return false;
		});
	}
	
	function decisionMembershipLevel(unique_code, ticket_ids) {
		// APIへの通信
		$.ajax(
			ajaxurl,
			{
				type: 'post',
				data: {
					'action' : 'get_membership_level',
					'unique_code' : unique_code,
					'ticket_ids' : ticket_ids
				},
				dataType: 'json'
			}
		)
		// 検索成功時にはページに結果を反映
		.done(function(data) {
			// 結果
			if (data.id) {
				console.log(data);
				var membership_level_id = data.id;
				$("#tmp-create-user #memberships_id").val(membership_level_id);
				return true;
			}
		})
		// 検索失敗時には、その旨をダイアログ表示
		.fail(function(errors) {
			alert("所持チケットから会員レベルを判定できませんでした。");
			console.log(errors);
			return false;
		});
	}
});
</script>
