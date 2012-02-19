<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


/** Service for processing np-macros (#-p15-#)
 */
class NpMacros extends Object {
	private static $macros = array(
		'file' => 'NpMacros::fileMacro',
		'gallery' => 'NpMacros::galleryMacro',
		'filelist' => 'NpMacros::filelistMacro',
		'slideshow' => 'NpMacros::slideshowMacro',
		'translatefrom' => 'NpMacros::translatefromMacro',
		'subpagesblog' => 'NpMacros::subpagesblogMacro',
	);


	private $router;
	private $url;
	private $i18n;
	function __construct(IRouter $router, HttpRequest $req, I18n $i18n){
		$this->router = $router;
		$this->url = $req->getUrl();
		$this->i18n = $i18n;
	}


	/** @var PagesModelNode */
	private $pageContext;

	function process($string, PagesModelNode $pageContext){
		$this->pageContext = $pageContext;
		return preg_replace_callback('~#-(.+?)-#~', callback($this,'processMacro'), $string);
	}

	function processMacro($macroMatch, $onlyUrl=false){
		$url = $this->processUrlMacros($macroMatch[0]);
		if($url) return $url;

		$macro = $macroMatch[1];
		$pos=strpos($macro, '-');
		$macroname = $pos === false ? $macro : substr($macro, 0, $pos);
		$macroopts = $pos === false ? '' : substr($macro, $pos+1);

		//generic macro $sth-$opts
		if(isset(self::$macros[$macroname]))
			return call_user_func(self::$macros[$macroname], $macroopts);

		return "error:".$macro;
	}

	function processUrlMacros($macro){
		//p12 -> page link...  p12-de   -> specific language link
		if(preg_match('~^#-p([0-9]+)(?:-([a-z]+))?-#$~', $macro, $m)){
			$params = array(
					'id_page' => $m[1],
					'lang' => isset($m[2]) ? $m[2] : $this->pageContext['lang'],
					);

			return $this->router->constructUrl(
							new PresenterRequest('Front:Pages', 'GET', $params),
							$this->url);
		}

		//f12  -> file download link
		if(preg_match('~^#-f([0-9]+)-#$~', $macro, $m))
			return $this->router->constructUrl(
							new PresenterRequest('Front:Files', 'GET', array('id'=>$m[1], 'action'=>'default')),
							$this->url);
		//TODO [feature] onmouseover could show preview+size+previewPage link

		return false;
	}


	//file-12_fadeframe_nocache  -> render file control (video, sound, document, ...)
	function fileMacro($opts){

		$opts = explode('_', $opts);
		$id = array_shift($opts);
		return FilesModel::getFile($id)->getControlHtml($opts);
	}

	function galleryMacro($opts){
		if(!preg_match('~^(?P<id>[0-9]+)\.(?P<num>[0-9]+)$~', $opts, $m))
			return 'error gallery syntax';

		$page = PagesModel::getPageById($m['id']);
		if(!$page)
			return "error gallery page_id $m[id] missing";

		$gallery = $page->getFilesWhere(array('gallerynum'=>$m['num']));

		//render
		$html = "<div class='np-gallery'>";
		foreach($gallery as $f){
			$href = $f->previewLink('800x600'); //this is parsed by Lightbox - dont change
			$img = $f->previewLink('200');
			$html .= "<div><a href='$href' class='lightbox' title='{$f->description}'>".
							"<img src='$img'></a></div>";
		}
		$html .= "</div>";
		$html .= "<div class='clear'>&nbsp;</div>";
		return $html;
	}

	function filelistMacro($opts){
		if(!preg_match('~^(?P<id>[0-9]+)\.(?P<num>[0-9]+)$~', $opts, $m))
			return 'error filelist syntax';

		$page = PagesModel::getPageById($m['id']);
		if(!$page)
			return "error filelist page_id $m[id] missing";

		$gallery = $page->getFilesWhere(array('gallerynum'=>$m['num']));

		//render
		$html = "<ul>";
		foreach($gallery as $f){
			$href = $f->downloadLink();
			$img = $this->url->getBasePath() . "static/icons/icons16.php?file=$f->suffix";
			$b = round($f->filesize/1000/1000, 1).' MB';
			$html .= "<li><img src='$img' width='16' height='16' alt='$f->suffix'> ".
							"<a href='$href' title=''>$f->filename.$f->suffix</a> ($b) $f->description";
		}
		$html .= "</ul>";
		return $html;
	}

	function slideshowMacro($opts){
		if(!preg_match('~^(?P<id>[0-9]+)\.(?P<num>[0-9]+)$~', $opts, $m))
			return 'error gallery syntax';
		
		$page = PagesModel::getPageById($m['id']);
		if(!$page)
			return "error gallery page_id $m[id] missing";

		$gallery = $page->getFilesWhere(array('gallerynum'=>$m['num']));

		$out = array();
		foreach($gallery as $f){
			$src = $f->previewLink('700x300');
			$out[] = json_encode(array('src'=>$src));
		}

		//render
		return '
			<script src="/theme/modules/slide/js/jquery.cross-slide.js" type="text/javascript"></script>
			<div id="slideshow" style="width:700px;height:300px;"></div>
			<script type="text/javascript">
			//$(function() {
				$("#slideshow").crossSlide({sleep: 4,fade: 1},[ '.implode(',',$out).' ]);
			//});
			</script>';
	}

	function translatefromMacro($lang){
	}

	function subpagesblogMacro($opts){
		$opts = explode('_', $opts);
		$page = PagesModel::getPageById($opts[0]);
		if(!$page)
			return "error page_id $id missing";
		
		//render
		$html = "";
		foreach($page->getChildNodes() as $r){
			$html .= "<h3>" . (($x=$r->getMeta('date')) ? "<i class='small'>$x</i> " : "");
			if(in_array('nolink', $opts))
				$html .= "$r->name</h3>";
			else
				$html .= "<a href='".$r->link()."'>$r->name</a></h3>";

			if(in_array('truncate', $opts))
				$html .= Strings::truncate($r->content, 300);
			else
				$html .= $r->content;
		}
		return $html;
	}

}

