<?php
/************************************************************************
 * @project Gutuma Newsletter Managment
 * @author Rowan Seymour
 * @copyright This source is distributed under the GPL
 * @file The compose page
 * @modifications Cyril Maguire
 *
 * Gutama plugin package
 * @version 1.6
 * @date	01/10/2013
 * @author	Cyril MAGUIRE
*/
include_once 'inc/gutuma.php';
include_once 'inc/newsletter.php';
include_once 'inc/mailer.php';
gu_init();
gu_theme_start();
// Get the modified flag of the current newsletter
$is_modified = is_post_var('is_modified') ? get_post_var('is_modified') : FALSE;
$autosave = (is_post_var('autosave') ? get_post_var('autosave') : (isset($_SESSION['gu_timer']) ? $_SESSION['gu_timer'] : 300000));
$_SESSION['gu_timer'] = $autosave;
// Get all available lists
$lists = gu_list::get_all();
if (is_get_var('msg'))// Load newsletter from draft if one was specified
	$newsletter = gu_newsletter::get((int)get_get_var('msg'));
else{// Create empty newsletter, and fill from post vars if they exist
	$newsletter = new gu_newsletter();
	if (is_post_var('msg_id')) 			$newsletter->set_id((int)get_post_var('msg_id'));
	if (is_post_var('msg_recips')) 	$newsletter->set_recipients(get_post_var('msg_recips'));
	if (is_post_var('msg_subject'))	$newsletter->set_subject(get_post_var('msg_subject'));
	if (is_post_var('msg_html')) 		$newsletter->set_html(get_post_var('msg_html'));
	if (is_post_var('msg_text'))
		$newsletter->set_text(get_post_var('msg_text'));
	else
		$newsletter->generate_text();// html_to_text(msg_text)
}
if ($newsletter->get_recipients() == '' && is_get_var('list')){// Take recipient list from querystring if none specified thus far
	foreach ($lists as $list){
		$list_id = (int)get_get_var('list');
		if ($list->get_id() == $list_id){
			$newsletter->set_recipients($list->get_name());
			break;
		}
	}
}
$edit_mode = is_post_var('edit_submit');// After Preview the newsletter
$preview_mode = is_post_var('preview_submit');// Preview the newsletter
if (is_post_var('send_submit')){// Send the newsletter
	if ($newsletter->send_prepare()){// Saves newsletter to outbox
		$mailer = new gu_mailer();
		if ($mailer->init()){
			if ($newsletter->send_batch($mailer)){
				if ($newsletter->is_sending())
					gu_success(t('Newsletter sent to first batch of recipients'));
				else
					gu_success(t('Newsletter sent to all recipients'));
			}
		}
		$newsletter = new gu_newsletter();
		$is_modified = FALSE;
	}
}
elseif (is_post_var('attach_submit') && $_FILES['attach_file']['name'] != ''){// Add an attachment
	if ($newsletter->store_attachment($_FILES['attach_file']['tmp_name'], $_FILES['attach_file']['name']))
		gu_success(t('Attachment <b><i>%</i></b> added',array($_FILES['attach_file']['name'])));
}
elseif (is_post_var('remove_submit')){// Remove an attachment
	$attachment = get_post_var('msg_attachments');
	if ($newsletter->delete_attachment($attachment))
		gu_success(t('Attachment <i>%</i> removed',array($attachment)));
}
elseif (is_post_var('save_submit')){
	if ($newsletter->save()){
		$is_modified = FALSE;
		gu_success(t('Newsletter saved as draft'));
	}
}
$mailbox = gu_newsletter::get_mailbox();// Get all newsletters as mailbox
$attachments = $newsletter->get_attachments();// Get list of attachments
if (!$preview_mode){
	$tiny_opt = $spell_opt = '';
	$tiny_tools = gu_config::get('tiny_tools');
	if ($tiny_tools != 'no'){//tinyMCE
		$spell_check = gu_config::get('spell_check');
		if ($spell_check != 'no'){//spellcheck
			$spell_opt = "		browser_spellcheck: true,".PHP_EOL;//false by default
		}
	//toolslist 'save anchor autolink charmap code codesample colorpicker contextmenu emoticons fullpage fullscreen help hr image imagetools insertdatetime link lists media nonbreaking noneditable pagebreak paste print searchreplace spellchecker tabfocus table template textcolor textpattern toc visualblocks visualchars wordcount';//ok
		$mce_plug = 'save anchor autolink charmap code codemirror codesample colorpicker emoticons fullscreen help hr image imagetools insertdatetime link lists media nonbreaking noneditable pagebreak paste print searchreplace tabfocus table template textcolor textpattern toc visualblocks visualchars wordcount';//ok
		$mce_too1 = 'fullscreen | save | insert | code | undo redo';#1
		$mce_too1.= ' | cut copy paste | pastetext | searchreplace';#1 pasteword (old?)
		$mce_too1.= ' | visualblocks | charmap | emoticons';#1 cleanup (old?)
		$mce_too1.= ' | table | image | media';#1 iespell (old?) toc?
		$mce_too1.= ' | link unlink | anchor';#1
		$mce_too1.= ' | forecolor backcolor';#1 colorpicker?
		$mce_too1.= ' | blockquote hr';#1
		$mce_too2 = 'bold italic underline strikethrough';#2
		$mce_too2.= ' | alignleft alignright';#2 miss : justifyleft justifycenter justifyright justifyfull?
		$mce_too2.= ' | aligncenter alignjustify';#2
		$mce_too2.= ' | sub sup';#2
		$mce_too2.= ' | outdent indent | bullist numlist';
		$mce_too3 = 'formatselect fontselect fontsizeselect';#3
		$mce_too3.= ' | print | help';#3
		switch ($tiny_tools){
			case 'menu':
				$tiny_opt = "		toolbar: false,".PHP_EOL;//false by default
				break;
			case 'tools':
				$tiny_opt .= "		menubar:false,".PHP_EOL;
			case 'all':
				$tiny_opt .= "
			toolbar: '".$mce_too1." | ".$mce_too2." | ".$mce_too3."',".PHP_EOL;//v4 emulated of old dvanced theme
		}
			echo '<style style="display:none;">
/* Tiny theme skins/lightgray */
.mce-menubtn.mce-fixed-width span{width:initial !important;max-width:90px}
.mce-btn button {border: 0px solid #eee;}
.mce-menubtn button:hover .mce-txt,.mce-btn button:hover,.mce-btn:hover .mce-ico{color:#efefef;background-color:#555;}
/* PluCss et + */
img {height: auto !important;max-width: 100% !important;}
/* shortcuts codemirror table */
.mce-container.mce-fullscreen table {border-collapse: initial;}
</style>'.PHP_EOL;
?>
<script type='text/javascript' src='js/tinymce/tinymce.min.js?v=492c'></script>
<script type='text/javascript'>
 /* <![CDATA[ */
	tinyMCE.init({// General options
		mode: 'textareas',
//~ 		selector: 'textarea',// work
		skin: 'lightgray',
		theme: 'modern',
		language: '<?php echo $_SESSION['lang'] ?>',
		relative_urls: false,
		remove_script_host: false,
		paste_data_images: true,
		plugins: '<?php echo $mce_plug ?>',<?php echo $tiny_opt.$spell_opt ?>
		// Example word content CSS (should be your site CSS) this one removes paragraph margins
		content_css: 'themes/<?php echo gu_config::get('theme_name') ?>/css/editor.css',
		save_onsavecallback: function(){document.getElementById('save_submit').click();},//Fix : on save normal event call window.onbeforeunload & launch alert    //console.log(tinyMCE.activeEditor.selection.getStart(false), tinyMCE.activeEditor.selection.getNode().nodeName, tinyMCE.activeEditor.selection.getBookmark(), tinyMCE.activeEditor.selection.getRng(1));//<span style="overflow:hidden;line-height:0px" data-mce-style="overflow:hidden;line-height:0px" id="mce_1_start" data-mce-type="bookmark">﻿</span> ::: before send to save (pos of cursor) ::: https://stackoverflow.com/questions/9178785/tinymce-get-content-up-to-cursor-position?rq=1
		setup: function(ed){ ed.on('change',function(e){ gu_set_modified(true); });},

		file_browser_callback: mediaMan,//PluXml media manager (gutuma 2.2.0)

		codemirror: {//gutuma 2.2.0
			indentOnInit: true,// Whether or not to indent code on init.
			fullscreen: true,// Default setting is false
			path: '../../../codemirror',// Path to CodeMirror distribution
			config: {
				theme: '<?php echo gu_config::get('cmtheme') ?>',// Set this to the theme you wish to use (codemirror themes) //default,neo , abcdef ...
				lineNumbers: true,// Whether or not you want to show line numbers
				lineWrapping: true,// Whether or not you want to use line wrapping
				matchBrackets: true,// Whether or not you want to highlight matching braces
				autoCloseTags: true,// Whether or not you want tags to automatically close themselves
				autoCloseBrackets: true,// Whether or not you want Brackets to automatically close themselves
				enableSearchTools: true,// Whether or not to enable search tools, CTRL+F (Find), CTRL+SHIFT+F (Replace), CTRL+SHIFT+R (Replace All), CTRL+G (Find Next), CTRL+SHIFT+G (Find Previous)
				enableCodeFolding: true,// Whether or not you wish to enable code folding (requires 'lineNumbers' to be set to 'true')
				enableCodeFormatting: true,// Whether or not to enable code formatting
				autoFormatOnStart: true,// Whether or not to automatically format code should be done when the editor is loaded
				autoFormatOnModeChange: true,// Whether or not to automatically format code should be done every time the source view is opened
				autoFormatOnUncomment: true,// Whether or not to automatically format code which has just been uncommented
				mode: 'htmlmixed',// Define the language specific mode 'htmlmixed' for html including (css, xml, javascript), 'application/x-httpd-php' for php mode including html, or 'text/javascript' for using java script only
				showSearchButton: true,// Whether or not to show the search Code button on the toolbar
				showTrailingSpace: true,// Whether or not to show Trailing Spaces
				highlightMatches: true,// Whether or not to highlight all matches of current word/selection
				showFormatButton: true,// Whether or not to show the format button on the toolbar
				showCommentButton: true,// Whether or not to show the comment button on the toolbar
				showUncommentButton: true,// Whether or not to show the uncomment button on the toolbar
				showAutoCompleteButton: true,// Whether or not to show the showAutoCompleteButton button on the toolbar
				styleActiveLine: true// Whether or not to highlight the currently active line
			},
			width: 800,// Default value is 800
			height: 600,// Default value is 550
			saveCursorPosition: true ,// Insert caret marker
			jsFiles: [// Additional JS files to load
				'mode/clike/clike.js',
				'mode/css/css.js',
				'mode/javascript/javascript.js',
				'mode/php/php.js'
			],
//			cssFiles: [// Additional css files to load
//				'theme/solarized.css'
//			]
		}//fi CodeMirror config

	});//fi tinyinit
//Inspiré par RoxyFileBrowser (field_name, url, type, win)
	function mediaMan(field_name, url, type, win) {//gutuma 2.2.0
		var plxMedMan = '<?php echo plxUtils::getRacine() ?>core/admin/medias.php';//?integration=tinymce4
		if (plxMedMan.indexOf("?") < 0) {
			plxMedMan += "?type=" + type;   
		}
		else {
			plxMedMan += "&type=" + type;
		}
		plxMedMan += '&input=' + field_name;<?php // + '&value=' + win.document.getElementById(field_name).value;
/* js hide by php comment
		if(tinyMCE.activeEditor.settings.language){
			plxMedMan += '&langCode=' + tinyMCE.activeEditor.settings.language;
		}
*/
?>
		tinyMCE.activeEditor.windowManager.open({
		file: plxMedMan,
		title: '<?php echo L_MEDIAS_TITLE ?> : gutuma',
		width: window.innerWidth-150,//850
		height: window.innerHeight-150,//650
		resizable: "yes",
		plugins: "media",
		inline: "yes",
		close_previous: "no",
		}, {window: win,input: field_name});
		return false;
	}
/* ]]> */
</script>
<?php
	}//FI tinyMCE
##js init : memory
//		toolbar1: '".$mce_too1."',
//		toolbar2: '".$mce_too2."',
//		 Drop lists for link/image/media (old opt ?)
//		external_link_list_url : 'lists/link_list.js',
//		external_image_list_url : 'lists/image_list.js',
//		media_external_list_url : 'lists/media_list.js',
/* advanced theme options (old tiny.2x) 
		theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,formatselect,fontselect,fontsizeselect',
		theme_advanced_buttons2 : 'bullist,numlist,|,outdent,indent,blockquote,hr,|,sub,sup,|,link,unlink,anchor,image,charmap,emotions,iespell,media,|,forecolor,backcolor,|,undo,redo,cleanup,code,print,help',
		theme_advanced_buttons3 : null,
		theme_advanced_buttons4 : null,
		theme_advanced_toolbar_location : 'top',
		theme_advanced_toolbar_align : 'left',
		theme_advanced_statusbar_location : 'bottom',
		theme_advanced_resizing : true,
		theme_advanced_resizing_max_width : 680,
*/
}//fi !$preview_mode
?>
<script type="text/javascript">
/* <![CDATA[ */
	var is_post_back = false;
	var timeoutHandle = false;
	var is_edited = <?php echo (int)$edit_mode ?>;
	var is_previewed = <?php echo (int)$preview_mode ?>;
	var gu_plx_domain = gu_now_domain = "<?php echo $_SESSION['domain'] ?>";
	function gu_presend_check(){
		if (document.send_form.msg_recips.value == ""){
			alert("<?php echo t('Please specify at least one recipient list!');?>");
			return false;
		}
		if (document.send_form.msg_subject.value == "")
			return confirm("<?php echo t('Are you sure you want to send a message with an empty subject?');?>");
		return true;
	}
	function gu_add_recipient(){
		var txtRecips = document.send_form.msg_recips;
		var lstRecips = document.send_form.send_lists;
		txtRecips.value = gu_trim(txtRecips.value);		
		if (txtRecips.value != '')
			txtRecips.value += "; ";
		txtRecips.value += lstRecips.value;
		gu_set_modified(true);
	}
	function gu_cancel_unsaved_warning(){
		is_post_back = true;
	}
	function gu_set_modified(modified){
		document.getElementById('is_modified').value = (modified ? 1 : 0);
	}
	function gu_auto_save_renew(){
		var time = document.getElementById('gu_timer').value;
		var is_modified = document.getElementById('is_modified').value;
		if(timeoutHandle)
			window.clearTimeout(timeoutHandle);
		if(gu_plx_domain && gu_plx_domain == gu_now_domain){//NEW TEP
			gu_plx_domain = 0;
			timeoutHandle = setTimeout(gu_auto_save, time);
		}else{window.location = "<?php echo PLX_MORE.'admin'.__GDS__ ?>auth.php?d=1";/*alert("disconnected");*/}
	}
	function gu_auto_save(){
		var cm = document.querySelector('[aria-label="HTML source code"]');//aria-label="HTML source code"//codemirror
		if (document.querySelector('#mceu_1[aria-disabled="false"]'))//tiny save btn method : yep
			gu_set_modified(true);
		var time = document.getElementById('gu_timer').value;
		var is_modified = document.getElementById('is_modified').value;
		if (is_modified && is_edited && is_previewed){
			setTimeout(function(){
				if(window.location.hash=='#gu_auto_save_overlay'){
					document.getElementById('gu_no_save_btn').style.display='none';//hide cancel btn
// The first element that matches (or null if none do):
					var cm = document.querySelector('[aria-label="HTML source code"]');//codemirror div iframe is opened
					if(cm){//codemirror
						setTimeout(function(){//classic is timeout
							document.getElementById('save_submit').click();
						},1111);
						cm.querySelectorAll('button')[1].click();//save codemirror code with ok button to tiny
					}
					else{//classic
						document.getElementById('save_submit').click();
					}
				}else if(time>0){
					if(timeoutHandle)
						window.clearTimeout(timeoutHandle);
					timeoutHandle = setTimeout(gu_auto_save, time);
				}
			},5000);
			if(time>1){
				window.location.hash='gu_auto_save_overlay';//active overlay;
				var to = document.querySelector("#gu_auto_save_overlay > span > b")
				var too = setInterval(function(){// timer before save
					if(to.innerText > 0){to.innerText = to.innerText - 1;}
					else{clearInterval(too);}
				},1000);
			}
		}else{
			if(time>1){
				is_edited = is_previewed = !0;
				if(!gu_plx_domain){
					var mysack = new sack("<?php echo PLX_MORE.'admin'.__GDS__ ?>profil.php");
					mysack.execute = 1;
					mysack.method = "GET";
					mysack.setVar("gu_plx_domain", "ajax");
					mysack.runResponse = function(){try{eval(this.response);}catch(e){console.log('Error gu_auto_save',e);}};
					mysack.onError = function(){ gu_error("<?php echo t('An error occured whilst making AJAX request');?>"); gu_messages_display(0); };
					mysack.onCompletion = function(){
						if(mysack.xmlhttp.status == 200){gu_auto_save_renew();}
					};
					mysack.runAJAX();
				}else{
					if(timeoutHandle)
						window.clearTimeout(timeoutHandle);
					if(gu_plx_domain && gu_plx_domain == gu_now_domain){//NEW TEP
						gu_plx_domain = 0;
						timeoutHandle = setTimeout(gu_auto_save, time);
					}
				}
			}
		}
	}
	function gu_auto_save_is_active(){
		var time = document.getElementById('gu_timer').value;
		document.getElementById('autosave').value = time;
		if((!timer && time) || timer != time){
			if(timeoutHandle)
				window.clearTimeout(timeoutHandle);
			timeoutHandle = setTimeout(gu_auto_save, time);
		}
		timer = time;
	}
	window.onbeforeunload = function (ev){
		var is_modified = document.getElementById('is_modified').value;
		if (!is_post_back && is_modified)
			return "<?php echo t('Your message has not been sent or saved, and will be lost if you leave this page.');?>";
	}
//add overlay css link in dom
	var fileref=document.createElement("link")
	fileref.setAttribute("rel", "stylesheet")
	fileref.setAttribute("type", "text/css")
	fileref.setAttribute("href", "inc/overlay.css")
	document.getElementsByTagName("head")[0].appendChild(fileref)
/* ]]> */
</script>
<?php
include_once 'themes/'.gu_config::get('theme_name').'/_compose.php';//Body
?>
	<div class="formfieldset" id="gu_auto_save_field" style="display:none">
		<div class="formfield">
			<div class="formfieldlabel"><?php echo t('Auto save');?>:</div>
			<div class="formfieldcontrols">
<?php
$maxlifetime = (ini_get("session.gc_maxlifetime") - 60);//php garbage collector : 1440 seconds (24m)-1min :
gu_theme_list_control('gu_timer',
	array(
		array(($maxlifetime * 1000),t('Every % Minutes', array(($maxlifetime / 60)))),
		array('600000',t('Every % Minutes', array(10))),
		array('300000',t('Every % Minutes', array(5))),
		array('180000',t('Every % Minutes', array(3))),
		array('120000',t('Every % Minutes', array(2))),
#		array('60000',t('Every % Minutes', array(1))),#dev
#		array('30000',t('Every % Minutes', array(0.5))),#dev
		array('1', t('Inactive')),
	),
	$autosave,//$control = FALSE, valeur OU $_SESSION['gu_timer'] (inc/theme.php)
	'onchange="gu_auto_save_is_active();"'
);
?></div>
		</div>
	</div>
<div class="gu_overlay" id="gu_auto_save_overlay" style="display:none">
	<span><?php echo t('Your message go to be <i>auto saved</i> in <b>5</b> seconds.');?><br /><a class="button blue" id="gu_no_save_btn" href="#gu_no_save"><?php echo t('Cancel');?></a></span>
</div>
<script type="text/javascript">
	timer = document.getElementById('gu_timer').value;//global var
	window.onload = function(){gu_auto_save();}
	document.getElementById('gu_auto_save_field').style.display='';
	document.getElementById('gu_auto_save_overlay').style.display='';
</script>
<?php
gu_theme_end();