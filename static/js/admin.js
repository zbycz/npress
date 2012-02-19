/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */

//TODO optimize by http://www.artzstudio.com/2009/04/jquery-performance-rules/
// and using http://api.jquery.com/on/


function tag2npmacro(newContent){
	//newContent = newContent.replace(/<img src="[^#"]*#-file-(\d+)(_[^_#]+)*-#">/gi, "#-file-$1$2-#");
	newContent = newContent.replace(/<[^#>]*#-(file-.+?)-#[^>]*>/gi, function(tag, macro){
		var matches, size, align, pos;
		var opts = macro.split("_");

		//match html attribute or css style
		if( matches = /width[^0-9]{1,2}([0-9]+)/.exec(tag) ){
			size = matches[1];
			//if( matches = /height[^0-9]{1,2}([0-9]+)/.exec(tag) ) //TODO smazat - pamatujeme si jen šířku
			//	size += 'x' + matches[1];

			if(opts[1] && opts[1].match(/^\d+(x\d+)?$/))
				opts[1] = size;
			else
				opts.splice(1, 0, size);
		}

		//remove align
		if( (pos = opts.indexOf("left")) != -1) opts.splice(pos, 1);
		if( (pos = opts.indexOf("right")) != -1)	opts.splice(pos, 1);

		//add align from html attribute
		if( matches = /align=[^0-9](left|right)/.exec(tag) )
				opts.push(matches[1]);

		return "#-"+opts.join("_")+'-#';
	});

	return newContent;
}
function npmacro2tag(newContent){
	//$('#toc-files').append('set-');

	//TODO [someday] for sound dont allow resizing
	//TODO fix resizing options to first position
	newContent = newContent.replace(/#-(file-(\d+)(_[^_#]+)*)-#/gi, function(str, macro, id){
		var html = '';
		var opts = macro.split("_");  opts.shift();

		//remove align from url opts and write the html attribute
		if( (pos = opts.indexOf("left")) != -1){
			opts.splice(pos, 1);
			html += ' align="left"';
		}
		else if( (pos = opts.indexOf("right")) != -1){
			opts.splice(pos, 1);
			html += ' align="right"';
		}

		//construct url
		opts.push("control");
		var url = '/data/thumbs/' + id + '.' + opts.join('_') + '.png'; //TODO dynamic
		html += ' src="'+url+'#-'+macro+'-#"';

		/*size html attributes  TODO:je to k něčemu dobré? zakoment aby se nepřenášel stretch
		var size = opts[0];
		if(size){
			var ss = size.split("x");
			html += ' width="'+ss[0]+'"';
			if(ss[1])
				html += ' height="'+ss[1]+'"';
		}/**/

		return '<img'+html+'>';
	});
	return newContent;
}



function subpageslist(){
	$("#js-subpageslist").sortable({
		stop: function(event, ui){
			var data = $(this).sortable("serialize");
			$.post($(this).attr('data-sortlink'), data);
		}
	});
}

function filelist_init(){
	//files deleter, insertlink
	$("#js-filelist")
		.delegate('.del', 'click', function (){ $(this).parent().fadeOut() })
		.delegate('.insertlink', 'click', function(event){
			if(!event.ctrlKey){
				$('#frmpageEditForm-text').wysiwyg('insertHtml', npmacro2tag($(this).attr('data-embed')));
				return false;
			}
		});

	filelist();
}
function filelist(){
	var handleEmptyList = function(){
		var h4 = $(this).prev();
		if($(this).children().length <= 1){ //always contains div.clearitem
			h4.add(this).addClass('emptyList');
			$(this).prepend('<div class="item placeholder" />');
		}
		else{
			h4.add(this).removeClass('emptyList');
			$(this).find('.placeholder').remove();
		}
	};

	// files sorter
	$("#js-filelist .list").each(handleEmptyList).sortable({
		items: "> div.item",
		cancel: ".placeholder",
		connectWith: "#js-filelist .list",

		start: function(){ $("#js-filelist").addClass('ui-dragging') },

		receive: function(event, ui) {
			
			//send request
			var num = $(this).attr('data-num');
			var fid = ui.item.attr('id').split('-')[1];
			var data = 'changedId='+fid+'&num='+num+'&' + $(this).sortable("serialize");
			$.post($('#js-filelist').attr('data-sortlink'), data);

			ui.sender.add(this).find('.infoitem small').css('opacity',0.05);
			ui.sender.data('handled', true);
			handleEmptyList.call(this);
			$(this).append($('.clearitem', this));//clearitem must be last element
		},

		stop: function(event, ui){
			$("#js-filelist").removeClass('ui-dragging');
			handleEmptyList.call(this);

			//send data only if not send in receive
			if($(this).data('handled'))
				$(this).data('handled', false);
			else {
				var data = $(this).sortable("serialize");
				$.post($('#js-filelist').attr('data-sortlink'), data);
			}
		}
	});


	/*files visibility toggler
	$("#js-filelist .toggle").click(function (){
		$.get(this.href);
		if($(this).hasClass("visible1")){
			$(this).removeClass("visible1").addClass("visible0").attr('title', 'zobrazit obrázek v galerii')
			$(this).children('span').html($(this).attr('title'));
			this.href = this.href.replace('visible=[01]', 'visible=1');
		}
		else {
			$(this).removeClass("visible0").addClass("visible1").attr('title', 'skrýt obrázek v galerii');
			$(this).children('span').html($(this).attr('title'));
			this.href = this.href.replace('visible=[01]', 'visible=0');
		}
		return false;
	});*/
}

function np_uploadify(){
	$('#np-uploadify').uploadify({
		'script'         : $("#np-uploadify").attr('data-uploadifyHandler'),
		'uploader'       : basePath + '/static/uploadify/uploadify.swf',
		'cancelImg'      : basePath + '/static/uploadify/cancel.png',
		'buttonText'     : $("#np-uploadify").html(),
		'multi'          : true,
		'auto'           : true,
		'scriptData'     : { 'uploadify_session': $("#np-uploadify").attr('data-session')},
		//'fileExt'        : '*.jpg;*.gif;*.png',
		//'fileDesc'       : 'Image Files (.JPG, .GIF, .PNG)',
		'queueID'        : 'np-uploadify-queue',
		//'queueSizeLimit' : 3,
		'simUploadLimit' : 3,
		'sizeLimit'      : 100*1000*1000,
		'removeCompleted': false,
		'onSelectOnce'   : function(event,data) {
				//$('#status-message').text(data.filesSelected + ' files have been added to the queue.');
			},
		'onAllComplete'  : function(event,data) {
				$.get($("#np-uploadify").attr('data-afterUploadLink'));
				$('#np-uploadify').uploadifyClearQueue();
			}
	});
}

function ajax_upload(){
  $(".ajax_upload").submit(function () {
		var form = this;
		$.ajaxFileUpload({
			url: $(form).attr('action')+"&ajax_upload=true",
			secureuri: false,
			fileElementId: $('input[type=file]',form).attr('id'),
			dataType: 'json',
			success: function (data, status) {
				if (typeof(data.error) != 'undefined') {
					if (data.error != '') {
					} else {
						//alert(data.msg);
						//$.get($('#frm-uploadForm').attr('action'));
						$.get($("#np-uploadify").attr('data-afterUploadLink'));

						//if we were uploading just new preview - reload the image
						var img = $("#snippet--editform_editfile .thumbnail");
						img.attr('src', img.attr('src')+"&x=1")
						//img.get(0).reload();
					}
				}
			},
			error: function (data, status, e) {
				alert(e);
			}
		});
		return false;
  });
	}

function pageEditForm_jwysiwyg(){
	if(!wysiwygConfig) wysiwygConfig = {contentStyle: false, minHtmlHeading: 2};

	$('#frmpageEditForm-text').wysiwyg({
		css: basePath + wysiwygConfig.contentStyle,
		//autoGrow: true, //nefunguje dobře
		//autoSave: true,
		initialContent: "<p>&nbsp;</p>",
		plugins: {
			i18n: {lang: 'cs'}
		},
		resizeOptions: false,
		tableFiller: 'text',
		formHeight: 50,
		maxHeight: 1000,			// see autoGrow



		controls: {
			bold          : { visible : true },
			italic        : { visible : true },
			underline     : { visible : true },
			strikeThrough : { visible : true },

			justifyLeft   : { visible : true },
			justifyCenter : { visible : true },
			justifyRight  : { visible : true },
			justifyFull   : { visible : false },

			indent  : { visible : true },
			outdent : { visible : true },

			subscript   : { visible : true },
			superscript : { visible : true },

			undo : { visible : true },
			redo : { visible : true },

			insertOrderedList    : { visible : true },
			insertUnorderedList  : { visible : true },
			insertHorizontalRule : { visible : false },

			h1 : { visible : 1 >= wysiwygConfig.minHtmlHeading },
			h2 : { visible : 2 >= wysiwygConfig.minHtmlHeading },
			h3 : { visible : 3 >= wysiwygConfig.minHtmlHeading },

			h4: {
				groupIndex: 7,
				visible: 4 >= wysiwygConfig.minHtmlHeading,
				className: 'h4',
				command: ($.browser.msie || $.browser.safari) ? 'formatBlock' : 'heading',
				arguments: ($.browser.msie || $.browser.safari) ? '<h4>' : 'h4',
				tags: ['h4'],
				tooltip: 'Header 4'
			},

// 			cut   : { visible : true },
// 			copy  : { visible : true },
// 			paste : { visible : true },
			html  : { visible: true },
// 			increaseFontSize : { visible : true },
// 			decreaseFontSize : { visible : true },
//			exam_html: {
//				exec: function() {
//					this.insertHtml('<abbr title="exam">Jam</abbr>');
//					return true;
//				},
//				visible: true
//			}
		},

		events: {  // doesnt work, needs jwysiwyg modification
//			//when inputing in wysiwyg
//			getContent: function (orig) {
//				alert(orig);
//				return orig;
//			},
//
//			//on written char, after setContent
//			save: function(event) {
//
//			}
		}

	});
}

function fill_headings(s){
	var fmenu =    $('#menuid-' + $('#frmpageEditForm-id_page').val() + ' a:first')
	var fheading = $('#frmpageEditForm-heading')
	var fname =    $('#frmpageEditForm-name')
	var fseoname = $('#frmpageEditForm-seoname')

	fheading.val(s.heading);
	fmenu.val(s.name);
	fname.val(s.name).keyup();
	fseoname.val(s.seoname).keyup();
}

function live_typing(){ //live typing (on newly created page)
	var fheading = $('#frmpageEditForm-heading')
	var fname =    $('#frmpageEditForm-name')
	var fseoname = $('#frmpageEditForm-seoname')
	var fheadingPrevVal = fheading.val();

	if(window.location.hash == '#newpage'){
		fheading.focus().keyup(function(){
			if(make_url(fheadingPrevVal) == fseoname.val().substr(1)){
				fseoname.val('/'+make_url(this.value));
			}
			fheadingPrevVal = fheading.val();
		});
	}

	//prefill the heading when editing "in menu" name
	fname.focus(function(){
		if(!fname.val()) fname.val(fheading.val());
	}).blur(function(){
		if(fname.val() == fheading.val()) fname.val('');
	});
	if(fname.val() == fheading.val()) fname.val('');

	if( !('placeholder' in document.createElement('input')) ){ //html5 compatibility
		fname.focus(function() {
			if (fname.val() == fname.attr('placeholder')) {
				fname.val('');
				fname.removeClass('placeholder');
			}
		}).blur(function() {
			if (fname.val() == '' || fname.val() == fname.attr('placeholder')) {
				fname.addClass('placeholder');
				fname.val(fname.attr('placeholder'));
			}
		}).blur();
		fname.parents('form').submit(function() {
			if (fname.val() == fname.attr('placeholder')) {
				fname.val('');
			}
		});
	}

	//help box for link
	$('#frm-pageEditForm')
		.delegate('#frmpageEditForm-seoname', 'focus', function(){ $('#js-linkhelp').show();})
		.delegate('#frmpageEditForm-seoname', 'blur', function(){ $('#js-linkhelp').hide();})

}

function editform_seoname_update(){
	$('#frm-pageEditForm').each(function() {
			$(this).data('initialForm', $(this).serialize()); //TODO update just the seoname
	});
	$('#js-linkhelp').hide();
}

function ctrl_s_saving(){// catching ctrl+s in body and wysiwyg
	var save = function(event){
		if(event.ctrlKey && event.keyCode == 83 && !event.shiftKey && !event.altKey){
			$('#frm-pageEditForm').submit();
			return false;
		}
	};
	$('body').keydown(save);
	var doc = $('#frmpageEditForm-text').wysiwyg('document');
	if(doc) doc.keydown(save);
}

function menu_reordering(){
	$('#js-menu ul').each(function(){
		$this = $(this);

		//do not fold empty UL
		if($this.children().length == 0)
			return;

		//folding (+)/(-)
		var span = $('<span>+</span>').click(function(){
			$ul = $(this).parent().next();
			if($ul.css('display') == 'none'){
				$ul.show();
				$(this).html('&ndash;');
			}
			else{
				$ul.hide();
				$(this).html('+');
			}
		});

		//displayed UL - show (-)
		if($this.css('display') != 'none')
				span.html('&ndash;');

		$this.prev().addClass('foldable').prepend(span);
	})

	// de/activating of reordering
	$('#js-menu-reordering').click(function(){
		$.cookie("menu-ordering", this.checked, {path: '/'});
		$('#js-menu').nestedSortable(this.checked ? "enable" : "disable");
	});

	// reordering
	$('#js-menu').nestedSortable({
		disableNesting: 'no-nest',
		forcePlaceholderSize: true,
		handle: 'div',  //TODO inner <a> not catchable
		helper:	'clone',
		items: 'li',
		maxLevels: 6,
		opacity: .6,
		placeholder: 'placeholder',
		revert: 250,
		tabSize: 15,
		tolerance: 'pointer',
		toleranceElement: '> div',
		listType: 'ul',
		disabled: true,
		stop: function(event, ui){
			var data = $('#js-menu').nestedSortable("serialize");
			$.post($('#js-menu').attr('data-pagesortLink'), data);
		}
	});
	if($.cookie("menu-ordering") == 'true'){  //remember the choice
		$('#js-menu').nestedSortable("enable");
		$('#js-menu-reordering').prop("checked", true);
	}
}

function metalist_reflectCheckbox(){
		var state = $('#js-showHiddenMeta').prop("checked");
		$('#js-meta li.jshidden').toggle(state);
		$('#js-meta-sql').toggle(state);
	};
function metalist(){
	var jsmeta = $("#js-meta");

	// button showing hidden meta
	$('#js-showHiddenMeta').show().click(metalist_reflectCheckbox);
	metalist_reflectCheckbox();

	// delete button
	jsmeta.delegate('.del','click',function (){ $(this).parent().fadeOut() });

	// show Edit button on focus
	jsmeta.delegate('.js-meta-key', 'focus', function(){
		$(this).next().fadeIn(500);
	});

	jsmeta.delegate('.js-meta-key', 'blur', function(){
		if($(this).attr('data-saved') == $(this).val())
			$(this).next().fadeOut(500);
	});
}


function pageEditForm_changes_catcher(){
	//form changes catcher, updated for one form: http://misterdai.wordpress.com/2010/06/04/jquery-form-changed-warning/
  $(window).bind('beforeunload', function() {
	  var changed = false;
	  $('#frm-pageEditForm').each(function() {
	    if ($(this).data('initialForm') != $(this).serialize()) {
	      changed = true;
	      $(this).addClass('changed');
	    } else {
	      $(this).removeClass('changed');
	    }
	  });
	  if (changed) {
	    return 'Máte neulo\u017eené změny v editačním formuláři!';
	  }
	});

  $('#frm-pageEditForm').each(function() {
    $(this).data('initialForm', $(this).serialize());
  });
}


$(function(){
	menu_reordering();

	live_typing();

	pageEditForm_jwysiwyg();
	pageEditForm_changes_catcher();
	ctrl_s_saving();

	subpageslist();

	ajax_upload();
	np_uploadify();
	filelist_init();

	metalist();
	
	
	//commons AJAX for a.ajax & form.ajax
	$("a.ajax").live("click", function (event) {
		if(!event.ctrlKey){
			event.preventDefault();
			$.get(this.href);
		}
	});
  $("form.ajax").live("submit", function () {	 // odeslání na formulářích
    $(this).ajaxSubmit();
    $(this).data('initialForm', $(this).serialize());
    return false;
  });
  /*$("form.ajax :submit").live("click", function () {	// odeslání pomocí tlačítek
    $(this).ajaxSubmit();
    return false;
  });*/

	//ajax loading spinner
	$("#ajax-spinner").ajaxStart(function () {$(this).addClass('show');}).ajaxComplete(function () {$(this).removeClass('show');});

});




var nodiac = { 'á': 'a', 'č': 'c', 'ď': 'd', 'é': 'e', 'ě': 'e', 'í': 'i', 'ň': 'n', 'ó': 'o', 'ř': 'r', 'š': 's', 'ť': 't', 'ú': 'u', 'ů': 'u', 'ý': 'y', 'ž': 'z' };
/** Vytvoření přátelského URL
* @param s řetězec, ze kterého se má vytvořit URL
* @return string řetězec obsahující pouze čísla, znaky bez diakritiky, podtržítko a pomlčku
* @copyright Jakub Vrána, http://php.vrana.cz/
*/
function make_url(s) {
    s = s.toLowerCase();
    var s2 = '';
    for (var i=0; i < s.length; i++) {
        s2 += (typeof nodiac[s.charAt(i)] != 'undefined' ? nodiac[s.charAt(i)] : s.charAt(i));
    }
    return s2.replace(/[^a-z0-9_]+/g, '-').replace(/^-|-$/g, '');
}


var formatXml = function (xml) {
        var reg = /(>)(<)(\/*)/g;
        var wsexp = / *(.*) +\n/g;
        var contexp = /(<.+>)(.+\n)/g;
        xml = xml.replace(reg, '$1\n$2$3').replace(wsexp, '$1\n').replace(contexp, '$1\n$2');
        var pad = 0;
        var formatted = '';
        var lines = xml.split('\n');
        var indent = 0;
        var lastType = 'other';
        // 4 types of tags - single, closing, opening, other (text, doctype, comment) - 4*4 = 16 transitions
        var transitions = {
            'single->single': 0,
            'single->closing': -1,
            'single->opening': 0,
            'single->other': 0,
            'closing->single': 0,
            'closing->closing': -1,
            'closing->opening': 0,
            'closing->other': 0,
            'opening->single': 1,
            'opening->closing': 0,
            'opening->opening': 1,
            'opening->other': 1,
            'other->single': 0,
            'other->closing': -1,
            'other->opening': 0,
            'other->other': 0
        };

        for (var i = 0; i < lines.length; i++) {
            var ln = lines[i];
            var single = Boolean(ln.match(/<.+\/>/)); // is this line a single tag? ex. <br />
            var closing = Boolean(ln.match(/<\/.+>/)); // is this a closing tag? ex. </a>
            var opening = Boolean(ln.match(/<[^!].*>/)); // is this even a tag (that's not <!something>)
            var type = single ? 'single' : closing ? 'closing' : opening ? 'opening' : 'other';
            var fromTo = lastType + '->' + type;
            lastType = type;
            var padding = '';

            indent += transitions[fromTo];
            for (var j = 0; j < indent; j++) {
                padding += '\t';
            }
            if (fromTo == 'opening->closing')
                formatted = formatted.substr(0, formatted.length - 1) + ln + '\n'; // substr removes line break (\n) from prev loop
            else
                formatted += padding + ln + '\n';
        }

        return formatted;
    };
