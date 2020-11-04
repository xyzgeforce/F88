<?php
// ----------------------------------------------------------------------------
// Features:	後台 -- 現金(GCASH) 後台單筆紀錄審查
// File Name:	withdrawalgcash_company_audit_review.php
// Author:		Yuan
// Log:
// ----------------------------------------------------------------------------
/*
主要操作的DB表格：
root_withdrawgcash_review 現金申請審查表
root_member_gcashpassbook 現金存款紀錄

前台
wallets.php 錢包顯示連結--取款、存簿都由這裡進入。
transactiongcash.php 前台現金的存簿
withdrawapplicationgcash.php 現金(GCASH)線上取款前台程式, 操作界面
withdrawapplicationgcash_action.php 現金(GCASH)線上取款前台動作, 會先預扣提款款項

後台
member_transactiongcash.php 後台的會員GCASH轉帳紀錄,預扣款項及回復款項會寫入此紀錄表格
withdrawalgcash_company_audit.php  後台GCASH提款審查列表頁面
withdrawalgcash_company_audit_review.php  後台GCASH提款單筆紀錄審查
withdrawalgcash_company_audit_review_action.php 後台GCASH提款審查用的同意或是轉帳動作SQL操作
*/
// ----------------------------------------------------------------------------


session_start();

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
//$tr['Illegal test'] = '(x)不合法的測試。';
if(isset($_GET['id'])) {
  $action = filter_var($_GET['id'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
}else{
  die($tr['Illegal test']);
}
// var_dump($action);

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 agent user account in redis server db 1
Agent_RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 檢查權限是否合法，允許就會放行。否則中止。
agent_permission();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta $tr['GCASH Application for Withdrawal'] = '加盟金取款申請審核';
$function_title 		= $tr['GCASH Application for Withdrawal'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 目前所在位置 - 配合選單位置讓使用者可以知道目前所在位置 $tr['Affiliate withdrawal application board'] = '加盟金取款申請看板';
$menu_breadcrumbs = '
<ol class="breadcrumb">
  <li><a href="home.php">'.$tr['Home'].'</a></li>
  <li><a href="#">'.$tr['Account Management'].'</a></li>
  <li><a href="withdrawalgcash_company_audit.php">'.$tr['Affiliate withdrawal application board'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ol>';
// ----------------------------------------------------------------------------


// 有登入，且身份為管理員 R 才可以使用這個功能。
if(isset($action) AND isset($_SESSION['agent']) AND $_SESSION['agent']->therole == 'R') {

  /*
    // 使用者所在的時區，sql 依據所在時區顯示 time
      // -------------------------------------
      if(isset($_SESSION['agent']->timezone) AND $_SESSION['agent']->timezone != NULL) {
        $tz = $_SESSION['agent']->timezone;
      }else{
        $tz = '+08';
      }
      // 轉換時區所要用的 sql timezone 參數
      $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."'";
      $tzone = runSQLALL($tzsql);

      if($tzone[0]==1){
        $tzonename = $tzone[1]->name;
      }else{
        $tzonename = 'posix/Etc/GMT-8';
      }
  */

  // 找出在取款審核表單中，指定的會員的資料。
  // 原版
  // $withdrawalgcash_company_sql = "
  // SELECT * FROM
  // (SELECT *, to_char((applicationtime AT TIME ZONE '$tzonename'), 'YYYY-MM-DD HH24:MI:SS' ) as applicationtime_tz FROM root_withdrawgcash_review ORDER BY id DESC LIMIT 500) as withdraw_review
  // LEFT OUTER JOIN (SELECT id as member_id,account FROM root_member) as member
  // ON withdraw_review.account = member.account WHERE withdraw_review.id = '$action';";

  // 2019/12/3
  $withdrawalgcash_company_sql = <<<SQL
  SELECT * FROM
  (SELECT *, to_char((applicationtime AT TIME ZONE 'AST'), 'YYYY-MM-DD HH24:MI:SS' ) as applicationtime_tz FROM root_withdrawgcash_review ORDER BY id DESC LIMIT 500) as withdraw_review
  LEFT OUTER JOIN (SELECT id as member_id,account FROM root_member) as member
  ON withdraw_review.account = member.account WHERE withdraw_review.id = '{$action}'
SQL;

//  var_dump($withdrawalgcash_company_sql);
  $withdrawalgcash_company_result = runSQLALL($withdrawalgcash_company_sql);
//  var_dump($withdrawalgcash_company_result);
  if($withdrawalgcash_company_result[0] == 1){

    // 判斷審核的狀態
    if($withdrawalgcash_company_result[1]->status == 2){
      // $withdrawalgcash_status_html = "
      // <a href=\"withdrawapplgtoken_action.php?a=withdrawalgtoken_submit&id=".$withdrawalgcash_company_result[1]->id."\" class=\"btn btn-success btn-sm active\" role=\"button\">同意</a>
      // <a href=\"withdrawapplgtoken_action.php?a=withdrawalgtoken_cancel&id=".$withdrawalgcash_company_result[1]->id."\" class=\"btn btn-danger btn-sm active\" role=\"button\">取消</a>
      // ";
      $withdrawalgcash_status_html = "
      <button id=\"agreen_ok\" class=\"btn btn-success btn-sm active\" role=\"button\">".$tr['agree']."</button>&nbsp;&nbsp;&nbsp;
      <button id=\"agreen_cancel\"class=\"btn btn-danger btn-sm active\" role=\"button\">".$tr['disagree']."</button>
      ";
      //$tr['seq examination passed'] = '已审核通过';$tr['application reject'] = '审核退回';
    }else if($withdrawalgcash_company_result[1]->status == 1){
      $withdrawalgcash_status_html = "
      <label class=\"label label-success btn-sm active\" role=\"label\">".$tr['seq examination passed']."</label>
      ";
    }else if($withdrawalgcash_company_result[1]->status == 3){
      $withdrawalgcash_status_html = "
      <label class=\"label label-danger btn-sm active\" role=\"label\"><span class=\"glyphicon glyphicon-lock\"><span></label>
      ";
    }else{
      $withdrawalgcash_status_html = "
      <label class=\"label label-danger role=\"label\">".$tr['application reject']."</label>
      ";
    }

    // 會員帳號查驗連結  $tr['Check membership details'] = '檢查會員的詳細資料'
    $member_check_html = '<a href="member_account.php?a='.$withdrawalgcash_company_result[1]->member_id.'" target="_BLANK" title="'. $tr['Check membership details'].'">'.$withdrawalgcash_company_result[1]->account.'</a>';

    // 列出資料, 主表格架構
    $show_list_tbody_html = '';

    // 交易單號
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Transaction order number'].'</strong></td>
      <td>'.$withdrawalgcash_company_result[1]->transaction_id.'</td>
      <td></td>
    </tr>
    ';

    // 会员帐号 $tr['Account'] = '帳號';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Account'].'</strong></td>
      <td>'.$member_check_html.'</td>
      <td></td>
    </tr>
    ';

    // $tr['Check the user cash register'] = '檢查使用者的現金存簿';
    $amount_html = '<a href="member_transactiongcash.php?a='.$withdrawalgcash_company_result[1]->member_id.'" title="'.$tr['Check the user cash register'].'">$'.$withdrawalgcash_company_result[1]->amount.'</a>';
    // 取款金额 $tr['withdrawal amount'] = '提款金額'; $tr['Has been withheld'] = '已經預扣, 不同意取款會將預扣退還。';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['withdrawal amount'].'</strong></td>
      <td>'.$amount_html.'</td>
      <td>'.$tr['Has been withheld'].'</td>
    </tr>
    ';


    // 取款手續費 $tr['Withdrawal fee'] = '取款手續費';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['withdrawal fee'].'</strong></td>
      <td>$'.$withdrawalgcash_company_result[1]->fee_amount.'</td>
      <td>'.$tr['Has been withheld'] .'</td>
    </tr>
    ';

    // 申请时间 $tr['application time'] = '申請時間';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['application time'].'</strong></td>
      <td>'.$withdrawalgcash_company_result[1]->applicationtime_tz.'</td>
      <td></td>
    </tr>
    ';

    // 银行帐号户名 $tr['Bank account name'] = '銀行帳號戶名';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Bank account name'].'</strong></td>
      <td>'.$withdrawalgcash_company_result[1]->accountnumber.'</td>
      <td></td>
    </tr>
    ';

    // 提款银行 $tr['Withdrawal Bank'] = '提款銀行';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Withdrawal Bank'].'</strong></td>
      <td>'.$withdrawalgcash_company_result[1]->companyname.'</td>
      <td></td>
    </tr>
    ';

    // 提款銀行所在省  $tr['Province where the bank where the money is withdrawn'] = '提款銀行所在省';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Province where the bank where the money is withdrawn'].'</strong></td>
      <td>'.$withdrawalgcash_company_result[1]->accountprovince.'</td>
      <td></td>
    </tr>
    ';

    // 提款銀行所在縣市 $tr['Where the bank where the bank is located'] = '提款銀行所在縣市';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Where the bank where the bank is located'].'</strong></td>
      <td>'.$withdrawalgcash_company_result[1]->accountcounty.'</td>
      <td></td>
    </tr>
    ';

    // 提款申請時間 $tr['Withdrawal application time'] = '提款申請時間';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'. $tr['Withdrawal application time'].'</strong></td>
      <td>'.(gmdate('Y-m-d H:i:s', strtotime($withdrawalgcash_company_result[1]->applicationtime) + -4 * 3600)).'</td>
      <td></td>
    </tr>
    ';


    $contactuser_html = '<p>
    Mobile: '.$withdrawalgcash_company_result[1]->mobilenumber.'<br>
    WebChat: '.$withdrawalgcash_company_result[1]->wechat.'<br>
    Email: '.$withdrawalgcash_company_result[1]->email.'<br>
    QQ: '.$withdrawalgcash_company_result[1]->qq.'<br>
    </p>';
    // 聯絡方式 $tr['contact method'] = '聯絡方式';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['contact method'].'</strong></td>
      <td>'.$contactuser_html.'</td>
      <td></td>
    </tr>
    ';
    //$tr['Browser fingerprint'] = '瀏覽器指紋'; $tr['Find out the records in the system'] = '找出曾經在系統內的紀錄'; $tr['Query the IP address may be the address'] = '查詢IP來源可能地址位置';
    $geoinfo_html = '<p>
    '.$tr['Browser fingerprint'].': <a href="member_log.php?fp='.$withdrawalgcash_company_result[1]->fingerprinting.'" title="查询指纹码" target="_BLANK">'.$withdrawalgcash_company_result[1]->fingerprinting.'</a><br>
    IP: <a href="member_log.php?ip='.$withdrawalgcash_company_result[1]->applicationip.'" target="_BLANK" title="'.$tr['Query the IP address may be the address'].'">'.$withdrawalgcash_company_result[1]->applicationip.'</a><br>
    </p>';
    // 地理位置及瀏覽器指紋資訊 $tr['Geographic location and browser fingerprint'] = '地理位置及瀏覽器指紋'; $tr['User Geographic Device Information submitted by withdrawal'] = '提款提交的使用者地理裝置資訊';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'. $tr['Geographic location and browser fingerprint'].'</strong></td>
      <td>'.$geoinfo_html.'</td>
      <td>'. $tr['User Geographic Device Information submitted by withdrawal'].'</td>
    </tr>
    ';

    // 對帳處理人員帳號 $tr['Account processing staff account'] = '對帳處理人員帳號';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Account processing staff account'].'</strong></td>
      <td>'.$withdrawalgcash_company_result[1]->processingaccount.'</td>
      <td></td>
    </tr>
    ';

    // 對帳完成的時間 $tr['Reconciliation completed time'] = '對帳完成的時間';
    if(isset($withdrawalgcash_company_result[1]->processingtime) AND $withdrawalgcash_company_result[1]->processingtime != null){
      $processingtime = gmdate('Y-m-d H:i:s',strtotime($withdrawalgcash_company_result[1]->processingtime)-4*3600);
    }else{
      $processingtime = '';
    }
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Processing time'].'</strong></td>
      <td>'.$processingtime.'</td>
      <td></td>
    </tr>
    ';

    // 处理资讯紀錄 $tr['update'] = '更新';
    $notes_form_html = '
    <div class="form-group">
      <form class="form-horizontald" role="form" id="note">
        <textarea class="form-control validate[maxSize[500]]" rows="5" maxlength="500" id="notes_common" placeholder="('.$tr['max'].'500'.$tr['word'].')">'.$withdrawalgcash_company_result[1]->notes.'</textarea>
      </form>
      <button type="button" class="btn btn-default btn-sm mt-2" id="notes_common_update">'.$tr['update'].'</button>
    </div>
    ';
    // 处理资讯紀錄 $tr['agent review process info'] = '处理资讯';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'. $tr['agent review process info']. ' NOTES</strong></td>
      <td colspan="2">'.$notes_form_html.'</td>
    </tr>
    ';

    // ------------------------------------------------
    $submit_desc_html = '<p>'.$tr['Agree, immediately update this record'].'<p>';
    // 审核状态 $tr['Approval Status'] = '審核狀態';
    $show_list_tbody_html = $show_list_tbody_html.'
    <tr>
      <td><strong>'.$tr['Approval Status'].'</strong></td>
      <td>'.$withdrawalgcash_status_html.'</td>
      <td>'.$submit_desc_html.'</td>
    </tr>
    ';

    //  $show_list_html = $show_list_html.'<strong>审核状态:</strong>'.$withdrawalgcash_status_html.'
    //  <p><a href= class="btn btn-success btn-sm active" role="button" onclick="self.location=document.referrer;">返回上一页</a></p>';

    // 返回上一页  $tr['go back to the last page'] = '返回上一頁';
    // $show_list_return_html = '<p align="right"><a href="withdrawalgcash_company_audit.php" class="btn btn-success btn-sm active" role="button">'.$tr['go back to the last page'].'</a></p>';
    $show_list_return_html = '
    <form>
      <div class="row">
        <div class="col text-lift">
          <a href="transaction_query.php?passbook_query[]=cash&trans_id='.$withdrawalgcash_company_result[1]->transaction_id.'" class="btn btn-success btn-sm" role="button">'.$tr['View debit transaction details'].'</a>
        </div>
        <div class="col text-right">
          <a href="withdrawalgcash_company_audit.php" class="btn btn-success btn-sm" role="button">'.$tr['go back to the last page'].'</a>
        </div>
      </div>
    </form>
    ';

    // 欄位標題 tr['field'] = '欄位'; $tr['content'] = '內容';$tr['Remark'] = '備註';
    $show_list_thead_html = '
    <tr>
      <th>'.$tr['field'].'</th>
      <th>'.$tr['content'].'</th>
      <th>'.$tr['Remark'].'</th>
    </tr>
    ';

    // 以表格方式呈現
    $show_list_html = '
    <table class="table">
      <thead>
      '.$show_list_thead_html.'
      </thead>
      <tbody>
      '.$show_list_tbody_html.'
      </tbody>
    </table>
    ';


    // ----------------------------------------------------------------------------
    // 審核及更新按鈕的動作 JS  $tr['Are you sure to agree to this withdrawal'] = '是否確認同意此提款';$tr['Are you sure to cancel this withdrawal request'] = '是否確認取消此提款申請';
    // ----------------------------------------------------------------------------
    $audit_js = "
    $('#agreen_ok').click(function(){
      $('#agreen_ok, #agreen_cancel').prop('disabled', true);
      var r = confirm('". $tr['Are you sure to agree to this withdrawal']."?');
      var id = ".$_GET['id'].";
      if(r == true){
        $.post('withdrawalgcash_company_audit_review_action.php?a=withdrawalgcash_submit',
          {
            withdrawapplgcash_id: id
          },
          function(result){
            $('#preview_result').html(result);
          } );
      }else{
        $('#agreen_ok, #agreen_cancel').prop('disabled', null);
      }
    });";

    $audit_js2 ="
    $('#agreen_cancel').click(function(){
      $('#agreen_ok, #agreen_cancel').prop('disabled', true);
      var r = confirm('".$tr['Are you sure to cancel this withdrawal request']."?');
      var id = ".$_GET['id'].";
      if(r == true){
        $.post('withdrawalgcash_company_audit_review_action.php?a=withdrawalgcash_cancel',
          {
            withdrawapplgcash_id: id
          },
          function(result){
            $('#preview_result').html(result);
          } );
      }else{
        $('#agreen_ok, #agreen_cancel').prop('disabled', null);
      }
    });
    ";
    //$tr['Whether to confirm the update note information'] = '是否确认更新備註資訊';
    $audit_js3 ="
    $('#notes_common_update').click(function(){
      $('#notes_common_update').attr('disabled', 'disabled');
      var notes_common = $('#notes_common').val();
      var r = confirm('".$tr['Whether to confirm the update note information']."?');
      var id = ".$_GET['id'].";
      if(r == true){
        $.post('withdrawalgcash_company_audit_review_action.php?a=withdrawalgcash_notes_common_update',
          {
            withdrawapplgcash_id: id ,
            notes_common:notes_common
          },
          function(result){
            $('#preview_result').html(result);
          } );
      }else{
        $('#notes_common_update').removeAttr('disabled');
      }
    });
    ";

    // JS 放到最後面
    $extend_js = $extend_js.
        '<script>
    $(document).ready(function(){
      '.$audit_js.'
      '.$audit_js2.'
      '.$audit_js3.'
    });
    </script>';

    // ----------------------------------------------------------------------------
    // 有成立才做的行為

  }else{
    // $tr['Query error, there is no specified single number'] = '查詢錯誤，沒有指定的單號。'; $tr['go back to the last page'] = '返回上一页';
    $logger = '(x)'.$tr['Query error, there is no specified single number'].'';
    memberlog2db($_SESSION['agent']->account,'withdrawal','error', "$logger");
    $show_list_html = $logger;
    $show_list_return_html = '<p align="right"><a href="withdrawalgcash_company_audit.php" class="btn btn-success btn-sm active" role="button">'.$tr['go back to the last page'].'</a></p>';
  }
  // ----------------------------------------------------------------------------


  // 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $indexbody_content.'
  <div class="row">
		<div class="col-12 col-md-12">
    '.$show_list_html.'
		</div>
	</div>
	<hr>
  '.$show_list_return_html.'
	<div class="row">
  <div class="col-12 col-md-12">
		<div id="preview_result"></div>
  </div>
	</div>
	';
}else{
  // 沒有登入的顯示提示俊息 $tr['only management and login mamber'] = '(x) 只有管理員或有權限的會員才可以登入觀看。';
  $show_transaction_list_html  =  $tr['only management and login mamber'];

  // 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $indexbody_content.'
	<div class="row">
	  <div class="col-12 col-md-12">
	  '.$show_transaction_list_html.'
	  </div>
	</div>
	<hr>
	<div class="row">
  <div class="col-12 col-md-12">
		<div id="preview_result"></div>
  </div>
	</div>
	';
}
// ----------------------------------------------------------------------------

	// JS 開頭
  $extend_head = $extend_head. <<<HTML
  <script src="./in/jQuery-Validation-Engine/js/languages/jquery.validationEngine-zh_CN.js" type="text/javascript" charset="utf-8"></script>
  <script src="./in/jQuery-Validation-Engine/js/jquery.validationEngine.js" type="text/javascript" charset="utf-8"></script>
  <link rel="stylesheet" href="./in/jQuery-Validation-Engine/css/validationEngine.jquery.css" type="text/css"/>

  <script type="text/javascript" language="javascript" class="init">
  $(document).ready(function () {
    $("#note").validationEngine();
  });
</script>
HTML;  


// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------

// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $tr['host_descript'];
$tmpl['html_meta_author']	 				= $tr['host_author'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$tr['host_name'];

// 頁面大標題
$tmpl['page_title']								= $menu_breadcrumbs;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= '<span class="glyphicon glyphicon-bookmark" aria-hidden="true"></span>'.$function_title;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include("template/beadmin.tmpl.php");

?>
