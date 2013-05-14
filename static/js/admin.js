/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */

//TODO optimize by http://www.artzstudio.com/2009/04/jquery-performance-rules/
// and using http://api.jquery.com/on/


if (!Array.prototype.indexOf) {
	Array.prototype.indexOf = function(obj, start) {
     for (var i = (start || 0), j = this.length; i < j; i++) {
         if (this[i] === obj) { return i; }
     }
     return -1;
	}
}



function tag2npmacro(newContent){
	//newContent = newContent.replace(/<img src="[^#"]*#-file-(\d+)(_[^_#]+)*-#">/gi, "#-file-$1$2-#");
	newContent = newContent.replace(/<[^#>]*#-(file-.+?)-#[^>]*>/gi, function(tag, macro){
		var matches, size, align, pos;
		var opts = macro.split("_");

		//match html attribute or css style (width)
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
		if( matches = /(float|align)[^a-z]+(left|right)/i.exec(tag) )
				opts.push(matches[2]);

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
			html += ' style="float: left"';
		}
		else if( (pos = opts.indexOf("right")) != -1){
			opts.splice(pos, 1);
			html += ' style="float: right"';
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

function pageEditForm_jwysiwyg(){
	if(!wysiwygConfig) wysiwygConfig = {contentStyle: false, minHtmlHeading: 2};



	var wysiwyg = $('#frmpageEditForm-text').wysiwyg({
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
			
			paragraph: { visible: true},
 			increaseFontSize : { visible : true },
 			decreaseFontSize : { visible : true },

// 			cut   : { visible : true },
// 			copy  : { visible : true },
// 			paste : { visible : true },
//			exam_html: {
//				exec: function() {
//					this.insertHtml('<abbr title="exam">Jam</abbr>');
//					return true;
//				},
//				visible: true
//			},
			html  : { visible: true }
		},
		events: {}  // these are html events on editorDoc
	})//.getWysiwyg();

	//wysiwyg.events.bind("getContent", tag2npmacro);
	//wysiwyg.events.bind("setContent", npmacro2tag);
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
			if(fname.val() == fheadingPrevVal)
				fname.val(fheading.val());
			fheadingPrevVal = fheading.val();
		});
	}


	//un-published disables in-menu text field
	var helptext = $('#js-published-helper');
	var fpublished = $('#frmpageEditForm-published')
	function published_helper(){
		if (fpublished.attr('checked')) { helptext.addClass('hide')}
    else { helptext.removeClass('hide')}
	}
	fpublished.click(published_helper);
	published_helper();


}

function editform_seoname_update(){
	var $frm = $('#frm-pageEditForm');
			$frm.data('initialForm', $frm.serialize());
		
	$('#frmpageEditForm-seoname').popover({
		content: $('#js-linkhelp').html(),
		trigger: 'focus',
		placement: 'bottom'
	});

	$('#snippet--editform_seoname a').tooltip();

}

function editform_editfile_update(){
	$('.lightbox').lightBox({
			overlayBgColor: '#000',
			overlayOpacity: 0.2,
			containerResizeSpeed: 350,
			txtImage: '',
			txtOf: '/'
		});
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

function foldable_helper(){
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
}

function menu_reordering(){
	var $jsmenu = $('#js-menu');

	//menu folding
	$jsmenu.find('ul').each(foldable_helper);

	// activating of reordering
	$('#js-menu-reordering').click(function(){
		var state = ! $(this).hasClass('active');
		$.cookie("menu-ordering", state, {path: '/'});
		$jsmenu.nestedSortable(state ? "enable" : "disable");

	});

	// reordering
	$jsmenu.nestedSortable({
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
				var data = $jsmenu.nestedSortable("serialize");
				$.post($jsmenu.attr('data-pagesortLink'), data);

				$jsmenu.find('span').remove();
				$jsmenu.find('.foldable').removeClass('foldable');
				$jsmenu.find('ul').each(foldable_helper);
			}
		});
		
		$jsmenu.on('click', 'a', function(){
				if(!$jsmenu.nestedSortable('option','disabled')) return false;
			});

	if($.cookie("menu-ordering") == 'true'){  //remember the choice
		$('#js-menu').nestedSortable("enable");
		$('#js-menu-reordering').addClass("active");
	}
}

function metalist_reflectCheckbox(){
		var state = $('#js-showHiddenMeta').prop("checked");
		$('#js-meta div.jshidden').toggle(state);
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
	editform_seoname_update();
	ctrl_s_saving();

	subpageslist();

	metalist();
	
	
	//common AJAX handlers for a.ajax & form.ajax
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


function formatXml(xml) {
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
