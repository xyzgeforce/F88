<?php
// ----------------------------------------------------------------------------
// Features:  後台 -- 平台公告訊息管理及新增功能
// File Name: systemconfig_ann.php
// Author:    Barkley, Mavis
// Related:
// DB Table:
// Log:
// ----------------------------------------------------------------------------
// 限制管理員才可以進入
// ----------------------------------------------------------------------------

session_start();

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

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
// 功能標題，放在標題列及meta
// $tr['announcement of platform management'] = '平台商公告管理';
$function_title     = $tr['announcement of platform management'];
// 擴充 head 內的 css or js
$extend_head        = '';
// 放在結尾的 js
$extend_js          = '';
// body 內的主要內容
$indexbody_content  = '';
// 目前所在位置 - 配合選單位置讓使用者可以知道目前所在位置
$menu_breadcrumbs = '
<ol class="breadcrumb">
  <li><a href="home.php">'.$tr['Home'].'</a></li>
  <li><a href="#">'.$tr['maintenance'].'</a></li>
  <li><a href="systemconfig_ann.php">'.$tr['e-business platform'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ol>';
// ----------------------------------------------------------------------------

if (isset($_SESSION['agent'] ->timezone) AND $_SESSION['agent'] ->timezone != NULL) {
  $tz = $_SESSION['agent'] ->timezone;
} else {
  $tz = '+08';
}

// ----------------------------------------------------------------------------------------------
// 編輯平台商公告內容
// ----------------------------------------------------------------------------------------------
if(isset($_GET['a']) AND filter_var($_GET['a'], FILTER_VALIDATE_INT) AND isset($_SESSION['agent']) AND $_SESSION['agent']->therole == 'R' AND  in_array($_SESSION['agent']->account, $su['ops'])) {

  $id = filter_var($_GET['a'],FILTER_VALIDATE_INT);

  $site_announcement_sql = "SELECT *, to_char((effecttime AT TIME ZONE '$tzonename'),'YYYY/MM/DD') AS effecttime,to_char((endtime AT TIME ZONE '$tzonename'),'YYYY/MM/DD') AS endtime FROM site_announcement WHERE id = '".$id."';";

  $site_announcement_sql_result = runSQLall($site_announcement_sql);


  $classification = array('1' =>'', '2' => '', '3' => '');
    // 是否啟用
  if($site_announcement_sql_result[0] >= 1) {

	for($i =1; $i<=$site_announcement_sql_result[0];$i++){

		if($site_announcement_sql_result[$i]->status == '1'){
		  $isshow_announcement = 'checked';
		} elseif($site_announcement_sql_result[$i]->status == '0') {
		  $isshow_announcement = '';
		}

		  $site_announcement['id'] = $id;
		  // 公告的名稱
		  $site_announcement['name'] = $site_announcement_sql_result[$i]->name;
		  // 公告標題顯示
		  $site_announcement['title'] = $site_announcement_sql_result[$i]->title;
		  // 公告內容
		  $site_announcement['content'] = htmlspecialchars_decode($site_announcement_sql_result[$i]->content);
		  // 是否啟用(狀態開關)
		  $site_announcement['status'] = $isshow_announcement;
		  // 發佈日期
		  $site_announcement['effecttime'] = $site_announcement_sql_result[$i]->effecttime;
		  // 截止日期
		  $site_announcement['endtime'] = $site_announcement_sql_result[$i]->endtime;
		  // 操作人員
		  $site_announcement['operator'] = $site_announcement_sql_result[$i]->operator;
	}

  }

} elseif (isset($_GET['a']) == 'add' AND $_SESSION['agent'] ->therole == 'R' AND in_array($_SESSION['agent']->account, $su['ops'])) {
  // ------------------------------------------------------------------
  // 新增平台商公告訊息
  // ------------------------------------------------------------------
  $today = gmdate('Y/m/d',time() + $tz * 3600);
  //$tomorrow = date('Y/m/d',strtotime("$today +1 day"));

  $site_announcement['id'] = '';
  // 公告名稱
  $site_announcement['name'] = '';
  // 公告標題
  $site_announcement['title'] = '';
  // 公告內容
  $site_announcement['content'] = '';
  // 公告狀態
  $site_announcement['status'] = '';
  //公告開始有效時間
  $site_announcement['effecttime'] = $today;
  // 公告結束時間
  $site_announcement['endtime'] = '';
  // 公告操作者帳號
  $site_announcement['operator'] = '';

}

if($_SESSION['agent']->therole == 'R' AND in_array($_SESSION['agent']->account, $su['ops'])){
  //if(isset($_SESSION['agent']) AND $_SESSION['agent'] ->therole == 'R' AND in_array($_SESSION['agent']->account, $su['ops'])) {
  $extend_head = $extend_head.'<link rel="stylesheet" type="text/css" href="in/datetimepicker/jquery.datetimepicker.css"/>';

  $extend_js = $extend_js.'
  <!-- 引用 datetimepicker -->
  <script src="in/datetimepicker/jquery.datetimepicker.full.min.js"></script>
  <!-- 引用 ckeditor js -->
  <script src="in\ckeditor\ckeditor.js"></script>';

  $show_list_html = '';

  // -----------------------------------------------------------------
  // 新增平台商公告
  // -----------------------------------------------------------------
  // 公告標題
  $show_list_html =<<<HTML
  <div class="row">
    <div class="col-12 col-md-1"><p class="text-right">{$tr['announcement title']}</p></div>
      <div class="col-12 col-md-4">
        <input type="text" class="form-control" id="title" placeholder="{$tr['Please fill in the display announcement title']}" value="{$site_announcement['title']}">
      </div>
    <div class="col-12 col-md-7"></div>
  </div><br>

  <!-- 公告名稱 -->
  <div class="row">
    <div class="col-12 col-md-1"><p class="text-right">{$tr['announcement name']}</p></div>
    <div class="col-12 col-md-4">
      <input type="text" class="form-control" id="name" placeholder="{$tr['Please fill in the announcement name']}" value="{$site_announcement['name']}">
    </div>
    <div class="col-12 col-md-7"></div>
  </div>
  <br>

  <!-- 預設 今天日期  -->
  <div class="row">
    <div class="col-12 col-md-1"><p class="text-right" title="美東時間">{$tr['announcement date']}</p></div>
    <div class="col-12 col-md-4">
      <div class="input-group">
        <input type="text" class="form-control" placeholder="start" aria-describedby="basic-addon1" id="start_day" value="{$site_announcement['effecttime']}">
        <span class="input-group-addon" id="basic-addon1">~</span>
        <input type="text" class="form-control" placeholder="永久有效" aria-describedby="basic-addon1" id="end_day" value="{$site_announcement['endtime']}">
      </div>
    </div>
    <div class="col-12 col-md-7"></div>
  </div>
  <br>

  <!-- 是否啟用 status -->
  <div class="row">
    <div class="col-12 col-md-1"><p class="text-right">{$tr['Enabled or not']}</p></div>
    <div class="col-12 col-md-4 material-switch">
      <input id="site_announcement_status_open" name="site_announcement_status_open" class="checkbox_switch" value="0" type="checkbox" {$site_announcement['status']} />
      <label for="site_announcement_status_open" class="label-success"></label>
    </div>
    <div class="col-12 col-md-7"></div>
  </div>
  <br>
HTML;

  // date 選擇器 https://jqueryui.com/datepicker/
  // http://api.jqueryui.com/datepicker/
  // 14 - 100 歲為年齡範圍， 25-55 為主流客戶。
  $dateyearrange_start  = date("Y/m/d");
  $dateyearrange_end    = date("Y") + 50;
  $datedefauleyear    = date("Y/m/d");

  $extend_js = $extend_js."
  <script>
  // for select day
  $('#start_day, #end_day').datetimepicker({
    defaultDate: '".$datedefauleyear."',
    minDate: '".$dateyearrange_start."',
    maxDate: '".$dateyearrange_end."/01/01',
    timepicker: true,
    format: 'Y/m/d H:i',
    lang: 'en'
  });
  </script>
  ";

  $editor_content_value = $site_announcement['content'];

  // 引入 ckeditor editor $tr['Announcement content'] = '公告內容';
  $show_list_html = $show_list_html.'
  <div class="row">
    <div class="col-12 col-md-1"><p class="text-right">'.$tr['announcement content'].'</p></div>
    <div class="col-12 col-md-10 material-switch">
      <main>
      <div class="adjoined-bottom">
        <div class="grid-container">
          <div class="grid-width-100">
            <div id="editor">
              '.$editor_content_value.'
            </div>
          </div>
        </div>
      </div>
      </main>
      <input type="hidden" id="editor_data_id" value="1122">
    </div>
    <div class="col-12 col-md-1"></div>
  </div>
  <br>
  ';

  // $tr['Save'] = '儲存'; $tr['Cancel'] = '取消';
  $show_list_html = $show_list_html.'

  <div class="row">
    <div class="col-12 col-md-10">
      <p class="text-right">
        <button id="submit_to_edit" class="btn btn-success"><span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span>&nbsp;'.$tr['Save'].'</button>
        <button id="remove_to_edit" class="btn btn-danger" onclick="javascript:location.href=\'systemconfig_ann.php\'"><span class="glyphicon glyphicon-floppy-remove" aria-hidden="true"></span>&nbsp;'.$tr['Cancel'].'</button>
      </p>
    </div>
  </div>
  ';

  // 引用 ckeditor sdk http://sdk.ckeditor.com/index.html
  $extend_js = $extend_js."
  <script>

  if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
  CKEDITOR.tools.enableHtml5Elements( document );

  // The trick to keep the editor in the sample quite small
  // unless user specified own height.
  CKEDITOR.config.height = 150;
  CKEDITOR.config.width = 'auto';

  var texteditor = ( function() {
    var wysiwygareaAvailable = isWysiwygareaAvailable(),
      isBBCodeBuiltIn = !!CKEDITOR.plugins.get( 'bbcode' );

    return function() {
      var editorElement = CKEDITOR.document.getById( 'editor' );

      // Depending on the wysiwygare plugin availability initialize classic or inline editor.
      if ( wysiwygareaAvailable ) {
        CKEDITOR.replace( 'editor' );
      } else {
        editorElement.setAttribute( 'contenteditable', 'true' );
        CKEDITOR.inline( 'editor' );

        // TODO we can consider displaying some info box that
        // without wysiwygarea the classic editor may not work.
      }
    };

    function isWysiwygareaAvailable() {
      // If in development mode, then the wysiwygarea must be available.
      // Split REV into two strings so builder does not replace it :D.
      if ( CKEDITOR.revision == ( '%RE' + 'V%' ) ) {
        return true;
      }

      return !!CKEDITOR.plugins.get( 'wysiwygarea' );
    }
  } )();

  // 啟動
  texteditor();

  </script>
  ";


  $extend_js = $extend_js."
  <script>
  $(document).ready(function() {

    $('#submit_to_edit').click(function(){
      var editor_data = CKEDITOR.instances.editor.getData();

      var editor_data_id = $('#editor_data_id').val();

      var id = '".$site_announcement['id']."';
      var name = $('#name').val();
      var title = $('#title').val();
      var start_day = $('#start_day').val();
      var end_day = $('#end_day').val();

      if($('#site_announcement_status_open').prop('checked')){
        var site_announcement_status_open = 1;
      } else{
        var site_announcement_status_open = 0;
      }

     if(jQuery.trim(name) != '' && jQuery.trim(title) != '' && jQuery.trim(editor_data) != '') {
        $.post('systemconfig_ann_action.php?a=edit_offer', {
            editor_data: editor_data ,
            editor_data_id: editor_data_id,
            site_announcement_status_open: site_announcement_status_open,
            id: id,
            name: name,
            title: title,
            start_day: start_day,
            end_day: end_day
          },
          function(result){
            $('#preview_result').html(result);
          }
        );
      } else {
        alert('".$tr['Please confirm the name、title、contents of the announcement are correctly entered']."');
      }
    });
  })
  </script>
  ";

  //都沒有以上的動作 顯示錯誤訊息
} else {//$tr['Wrong operation'] = '錯誤的操作';
    $show_list_html  = '(x) '.$tr['Wrong operation'].'';
  // 沒有登入權限的處理
  /*
  $show_list_html = $show_list_html.'
  <br>
  <div class="row">
    <div class="col-12 col-md-12">
      <div class="alert alert-danger">
      此页面只允许特定帐号 '.$allow_user_html.' 帐号存取
      </div>
    </div>
  </div>
  ';
  */
}

// 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $indexbody_content.'
  <div class="row">
    <div class="col-12 col-md-1"></div>
    <div class="col-12 col-md-11">
    '.$show_list_html.'
    </div>
  </div>
  <br>
  <div class="row">
    <div id="preview_result"></div>
  </div>
  ';

  // 將 checkbox 堆疊成 switch 的 css
  $extend_head = $extend_head. "
  <style>

  .material-switch > input[type=\"checkbox\"] {
      visibility:hidden;
  }

  .material-switch > label {
      cursor: pointer;
      height: 0px;
      position: relative;
      width: 40px;
  }

  .material-switch > label::before {
      background: rgb(0, 0, 0);
      box-shadow: inset 0px 0px 10px rgba(0, 0, 0, 0.5);
      border-radius: 8px;
      content: '';
      height: 16px;
      margin-top: -8px;
      margin-left: -18px;
      position:absolute;
      opacity: 0.3;
      transition: all 0.4s ease-in-out;
      width: 30px;
  }
  .material-switch > label::after {
      background: rgb(255, 255, 255);
      border-radius: 16px;
      box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.3);
      content: '';
      height: 16px;
      left: -4px;
      margin-top: -8px;
      margin-left: -18px;
      position: absolute;
      top: 0px;
      transition: all 0.3s ease-in-out;
      width: 16px;
  }
  .material-switch > input[type=\"checkbox\"]:checked + label::before {
      background: inherit;
      opacity: 0.5;
  }
  .material-switch > input[type=\"checkbox\"]:checked + label::after {
      background: inherit;
      left: 20px;
  }

  </style>
  ";
// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------

// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description']    = $tr['host_descript'];
$tmpl['html_meta_author']         = $tr['host_author'];
$tmpl['html_meta_title']          = $function_title.'-'.$tr['host_name'];

// 頁面大標題
$tmpl['page_title']               = $menu_breadcrumbs;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']              = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']                = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content']       = '<span class="glyphicon glyphicon-bookmark" aria-hidden="true"></span>'.$function_title;
// 主要內容 -- content
$tmpl['panelbody_content']        = $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include("template/beadmin.tmpl.php");

?>
