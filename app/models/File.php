<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


/** Files active-record object, see derived classes
 */
class File {
	/** Contains comma separated file suffixed acquired by specific class.
	 * The classname must be in FilesModel::$types
	 */
	public static $suffixes = "";

	/** Database row as assoc array, magic getter also works
	 * @var array
	 */
	public $data;

	
	/** Generate preview link
	 *
	 * 1) Has the file got any preview image, the link leads to Files:preview.
	 * 2) Routes in bootstrap are set to /data/thumbs/$id.png
	 *    but .htaccess points to Nette ONLY for non-existant files
	 * 3) First the file doesnt exist, so Nette is invoked and routes point
	 *    to Files:preview, which calls getPreviewHttpResponse()
	 * 4) The image is created from image located on getPreviewPath(),
	 *    is processed by getPreviewImage() and saved to getPreviewPathCache()
	 * 5) So now the /data/thumbs/$id.png image exists and for on-going request
	 *    apache serves it directly.
	 *
	 * TODO [feature] automatically delete long "unaccessed" thumbnails
	 */
	public function previewLink($zoom=null){
		if(!$this->dimensions)
			return Environment::getHttpRequest()->getUrl()->getBasePath()
						. "static/icons/icons48.php?file=$this->suffix";
		return Environment::getApplication()->getPresenter()
						->link(':Front:Files:preview', array($this->id, $zoom, 'lang'=>NULL)); 
		//TODO quickfix lang - Files iherits directly from Presenter (?)
	}

	/** Response for Files:preview
	 */
	public function getPreviewHttpResponse($o=null){
		$opts = self::parseOptions($o ? $o : $this->dimensions);

		//preview N/A - show icon
		if(!$this->dimensions && !isset($opts['control']))
			return new ImageResponse($this->getIconImage(), 'image/png');


		//custom caching mechanism (dont use Nette\Cache!)
		$cachePath = $this->getPreviewPathCache($o);
		if(file_exists($cachePath))
			return new ImageResponse(file_get_contents($cachePath), 'image/png');

		
		//get $image
		if(isset($opts["control"]))
			$image = $this->getControlImage($opts);
		else
			$image = $this->getPreviewImage($opts);

		//cache it
		if(!isset($opts["nocache"])) $image->save($cachePath, Image::PNG);

		//output it
		return new ImageResponse($image, 'image/png');
	}

	/** Get preview disk path - ie. ImageFile returns the original's path
	 */
	public function getPreviewPath(){
		return Environment::getVariable("dataDir")."/files/$this->id.preview.png";
	}

	/** Get disk path for preview image with options
	 */
	public function getPreviewPathCache($opts=null){
		if($opts) return Environment::getVariable("dataDir")."/thumbs/$this->id.$opts.png";
		return Environment::getVariable("dataDir")."/thumbs/$this->id.png";
	}

	/** Nette\Image object with applied options (@see applyImageOptions)
	 */
	public function getPreviewImage($opts){
		$image = Image::fromFile($this->getPreviewPath());
		
		//enable alpha channel
		$image->savealpha(true);
		$image->alphablending(false);
		
		self::applyImageOptions($image, $opts);
		return $image;
	}



	/** Link to provide original file download
	 */
	public function downloadLink(){
		return Environment::getApplication()->getPresenter()
						->link(':Front:Files:default', array($this->id, 'lang'=>NULL));
	}

	/** Response for downloading original file
	 */
	public function getDownloadHttpResponse(){
			return new FileResponse($this->getDownloadPath(), "$this->filename.$this->suffix"); 
			//TODO nevracet application/octet-stream
	}

	/** Path to the original file
	 */
	public function getDownloadPath(){
		if($this->origpath)
			return Environment::getVariable("dataDir")."/".$this->origpath;
		else
			return Environment::getVariable("dataDir")."/files/$this->id.orig.$this->suffix";
	}



	/** Image which looks like ControlHtml - for wysiwyg purpose
	 */
	public function getControlImage($opts){
		return $this->getPreviewImage($opts);
	}

	/** Html for np-macro, should provide html interface for showing the file
	 */
	public function getControlHtml($optstr=null){
		$opts = self::parseOptions($optstr);

		$size = array($this->width(), $this->height());
		$linksize = null;
		if($opts['w']){
			$size = Image::calculateSize($this->width(), $this->height(), $opts['w'], $opts['h']);
			$linksize = "$size[0]x$size[1]";
		}

		$img = Html::el('img');
		$img->src = $this->previewLink($linksize);
		$img->width = $size[0];
		$img->height = $size[1];
		$img->alt = $this->filename.'.'.$this->suffix;
		$img->title = $this->description ."\n".$this->keywords;

		if(isset($opts['left'])) $img->align = 'left';
		if(isset($opts['right'])) $img->align = 'right';

		//$img = apply_filter('controlhtml_file', $img, $opts, $this);
		//$img = apply_filter('controlhtml', $img, $opts);

		return $img;
	}

	/* Called to sanitize the text with macros
	 */
	public function getControlMacroOptions($optstr){
		return $optstr;
	}
	
	/** Html containing a macro to embed in text
	 */
	public function getEmbedCode(){
		return "#-file-{$this->id}-#";
	}

	/** Extended info in admin
	 */
	public function getAdminInfo(){
		return "";
	}


	// ------------- usually not overriden methods ------------------------------

	public function __construct($data){ //inject $httprequest, $router, $params
		if(!$data)
			throw new InvalidArgumentException("New File object must get data array");
		$this->data = (array) $data;
	}

	public function __get($name){ //todo &__get, extends Object
		if(isset($this->data[$name]))
					return $this->data[$name];
		return "";//parent::__get($name);
	}

	public function __set($name, $value){
		$this->data[$name] = $value;
	}

	public function width(){
		return substr($this->dimensions, 0, strpos($this->dimensions,'x'));
	}
	public function height(){
		return substr($this->dimensions, strpos($this->dimensions,'x')+1);
	}

	public function getType(){
		return get_class($this);
	}

	public function getIconImage(){
		$icons = array();
		@include Environment::getVariable("staticDir")."/icons/icons48.php";
		if(isset($icons[$this->suffix]))
			$icon = base64_decode($icons[$this->suffix]);
		else{
			$icon = Image::fromBlank(48, 48, Image::rgb(255,255,255));
			$icon->string(5, 5, 6, $this->suffix, Image::rgb(0,0,0));
		}
		return $icon;
	}

	public function save($newdata=false){
		//(is_array or arrayHash)
		if($newdata){
			foreach($newdata as $k=>$v) //merge changes to orig data array
				$this->data[$k] = $v;
		}

		return FilesModel::edit($this->data);
	}

	//compares database with the real file
	public function fileMetricsChanged(){
		//$lm = filemtime($this->getDownloadPath());
		$size = filesize($this->getDownloadPath());
		return $size != $this->filesize;
	}

	//updates database from the real file state
	public function fileMetricsUpdate(){
		//$lm = filemtime($this->getDownloadPath());
		$this->filesize = filesize($this->getDownloadPath());
		$this->save();
	}


	public function clearPreviewCache($filter=false){
		$path = Environment::getVariable("dataDir")."/thumbs/";
		$handle = opendir($path);
		if(!$handle)
			throw new InvalidStateException('Unable to open dir '.$path);

		if(!$this->id)
			throw new InvalidStateException('This->id is false!');

		while(($filename = readdir($handle)) !== false){
			if(strpos($filename, $this->id.'.') === 0 AND 
							!($filter AND strpos($filename, $filter) === false))
				unlink($path.'/'.$filename);
		}
	}

	//tries to create a preview image for the original file
	public function generatePreviewImage(){}

	/** Parse text options
	 * "800x600_text-hello_nocache" -> array w=800 h=600 text=hello nocache=true
	 * "120_control" -> array w=120 **h=120** control=true
	 */
	public static function parseOptions($opts){
		if(!is_array($opts)) $opts = explode("_", $opts);
		$result = array();
		$w = false; $h = false;
		foreach($opts as $o){
			if(!$w && preg_match("~([0-9]+)(?:x([0-9]+))?~",$o,$matches)){
				$w = $matches[1];
				$h = isset($matches[2]) ? $matches[2] : $w;
				continue;
			}

			list($key,$val) = explode("-", "$o-");
			$result[$key] = $val ? $val : true;
		}
		$result['w'] = $w;
		$result['h'] = $h;
		return $result;
	}

	/** Applies any options to the supplied image
	 * resize, crop, text, fadeframe
	 * TODO: introduce API for other filters
	 */
	public static function applyImageOptions(Image $image, $opts){
			//crop image to square or just resize
			if($opts['w'] && $opts['h']){ //not isset - always availible
				if(isset($opts["crop"])){
					$image->resize($opts['w'], $opts['h'], Image::FILL);
					$image->crop('50%', '20%', $opts['w'], $opts['h']);
				}
				else
					$image->resize($opts['w'], $opts['h']);
			}

			//insert text
			if(isset($opts["text"])){
				$image->string(7, 0.1*$image->width, 0.4*$image->height, $opts["text"], Image::rgb(255,255,255));
			}

			//fade out frame
			if(isset($opts["fadeframe"])){
				$size = (int)$opts["fadeframe"];
				if($size < 2) $size = 10;
				$w = $image->width;
				$h = $image->height;
				for($i=0;$i<$size;$i++){
					$image->line(0,$i,    $w,$i,    Image::rgb(255,255,255,round(127/$size*$i)));
					$image->line(0,$h-$i, $w,$h-$i, Image::rgb(255,255,255,round(127/$size*$i)));
					$image->line($i,0,    $i,$h,    Image::rgb(255,255,255,round(127/$size*$i)));
					$image->line($w-$i,0, $w-$i,$h, Image::rgb(255,255,255,round(127/$size*$i)));
				}
			}
	}
}



class UnknownFile extends File {
	public function getEmbedCode(){
		return "<a href='#-f$this->id-#'>$this->filename</a>";
	}
}



class ActiveSourceFile extends File {
	public static $suffixes = "php,php3,php4,php5,phtml";

	function getDownloadPath() {
		return parent::getDownloadPath() . "s";
	}

	public function getEmbedCode(){
		return "<a href='#-f$this->id-#'>$this->filename</a>";
	}
}



class ImageFile extends File {
	public static $suffixes = "jpg,jpeg,png,gif,ico,psd,tiff"; //TODO rozšířit podle možností gd


	/** Overridden - if its control, redirect with 301
	 */
	public function getPreviewHttpResponse($zoom = null) {
		$ctrlzoom = str_replace("_control", "", $zoom);
		$ctrlzoom = str_replace("control_", "", $ctrlzoom);
		if(strpos($zoom, "control") !== false)
			return new RedirectResponse($this->previewLink($ctrlzoom), HttpResponse::S301_MOVED_PERMANENTLY);

		return parent::getPreviewHttpResponse($zoom);
	}

	public function getPreviewPath(){
			return $this->getDownloadPath();
	}

	public function getEmbedCode(){
		$size = explode("x",$this->dimensions);
		if(200 > $size[0])
			return "#-file-{$this->id}-#";
		return "#-file-{$this->id}_200-#";
	}

	public function getAdminInfo(){
		return $this->dimensions." px";
	}

	public function generatePreviewImage(){
		$info = self::getImageInfo($this->getDownloadPath());
		$this->data['keywords'] = implode(",", $info["iptc"]["Keywords"]);
		$this->data['description'] = $info["iptc"]["Caption"];
		$this->data['dimensions'] = $info["wxh"];
		$this->save();

		//auto-rotate+exif http://www.php.net/manual/en/function.exif-read-data.php#76964
	}

	public function previewLink($zoom = null) {
		$zoom = str_replace("control", "", $zoom);
		return parent::previewLink($zoom);
	}

 	public static function rotateImage($id){
// 		dibi::query('UPDATE [::fotky] SET rotated=rotated+1 WHERE id=%i',$id);
//
// 		$path = $this->path($id, '640px');
// 		if(!file_exists($path)) return;
//
// 		$image = Image::fromFile($path);
// 		$image = $image->rotate(90, 0);
// 		$image->save($path);
//
// 		$thumb = /*clone*/$image;
// 		$thumb->resize(150, 150);
// 		$image->save($this->path($id, '150px'));
//
 	}

	/** Pomocná funkce vrátí velikost a IPTC
	 */
	static public function getImageInfo($filename){
		$r = getimagesize($filename, $extinfo);
		$iptc = self::parseIPTC($extinfo);
		return array('w'=>$r[0], 'h'=>$r[1], 'wxh'=>"$r[0]x$r[1]", 'iptc'=>$iptc);
	}

	/** Parse IPTC tags from getimagesize(,$info)
	 * @link http://www.justskins.com/forums/extracting-exif-iptc-info-100250.html
	 */
	static function parseIPTC($info) {  //TODO rozpoznávátko na codepage :-/
		$IPTC_data = array('Keywords'=>array(), 'Caption'=>"");
		if (isset($info["APP13"])) {
			$iptc = iptcparse($info["APP13"]);
			if (is_array($iptc)) {
				$IPTC_data = array(
					//"Version" => @$iptc["2#000"][0], # Max 2 octets,binary number
					//"Title" => @$iptc["2#005"][0], # Max 65 octets, non-repeatable,alphanumeric
					//"Urgency" => @$iptc["2#010"][0], # Max 1 octet, non-repeatable,numeric, 1 - High, 8 - Low
					//"Category" => @$iptc["2#015"][0], # Max 3 octets, non-repeatable, alpha"SubCategories" => @$iptc["2#020"], # Max 32 octets, repeatable,alphanumeric
					"Keywords" => (array) @$iptc["2#025"], # Max 64 octets, repeatable,alphanumeric
					//"Instructions" => @$iptc["2#040"][0], # Max 256 octets,non-repeatable, alphanumeric
					//"CreationDate" => @$iptc["2#055"][0], # Max 8 octets,non-repeatable, numeric, YYYYMMDD
					//"CreationTime" => @$iptc["2#060"][0], # Max 11 octets,non-repeatable, numeric+-, HHMMSS(+|-)HHMM
					//"ProgramUsed" => @$iptc["2#065"][0], # Max 32 octets,non-repeatable, alphanumeric
					//"Author" => @$iptc["2#080"][0], #!Max 32 octets, repeatable,alphanumeric
					//"Position" => @$iptc["2#085"][0], #!Max 32 octets, repeatable,alphanumeric
					//"City" => @$iptc["2#090"][0], # Max 32 octets, non-repeatable,alphanumeric
					//"State" => @$iptc["2#095"][0], # Max 32 octets, non-repeatable,alphanumeric
					//"Country" => @$iptc["2#101"][0], # Max 64 octets, non-repeatable,alphanumeric
					//"TransmissionReference" => @$iptc["2#103"][0], # Max 32 octets,non-repeatable, alphanumeric
					//"Headline" => @$iptc["2#105"][0], # Max 256 octets, non-repeatable,alphanumeric
					//"Credit" => @$iptc["2#110"][0], # Max 32 octets, non-repeatable,alphanumeric
					//"Source" => @$iptc["2#115"][0], # Max 32 octets, non-repeatable,alphanumeric
					//"Copyright" => @$iptc["2#116"][0], # Max 128 octets,non-repeatable, alphanumeric
					"Caption" => @$iptc["2#120"][0], # Max 2000 octets, non-repeatable,alphanumeric
					//"CaptionWriter" => @$iptc["2#122"][0] # Max 32 octets,non-repeatable, alphanumeric
				);

				foreach($IPTC_data['Keywords'] as &$v)
					$v = self::convertToUTF8($v);
				$IPTC_data['Caption'] = self::convertToUTF8($IPTC_data['Caption']);
			}
		}
		return $IPTC_data;
	}

	//http://www.php.net/manual/en/function.mb-detect-encoding.php#68607
	public static function convertToUTF8($str){
		$isUTF = preg_match('%(?:
			[\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
			|\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
			|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
			|\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
			|\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
			|[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
			|\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
			)+%xs', $str);

		return $isUTF ? $str : iconv("CP1250", "UTF-8", $str); //TODO only for czech!
	}
}



class MapsFile extends File {
	public static $suffixes = "gpx,kml";

	public function generatePreviewImage() {
		$p = self::saveGoogleMapsPreview($this);
		if(!$p){
			$this->data['info'] = $p['info'];
			$this->data['dimensions'] = $p['wxh'];
			$this->save();
		}
	}

	public static function saveGoogleMapsPreview($f){ //TODO show also markers
		$xml = simplexml_load_file($f->getDownloadPath());

		$points = array();
		if($f->suffix == 'gpx'){
			$xml->registerXPathNamespace('gpx', 'http://www.topografix.com/GPX/1/1');
			$res = $xml->xpath('//gpx:trkpt');
			foreach($res as $r)
				$points[] = array(floatval((string)$r['lat']), floatval((string)$r['lon']));
		}
		elseif($f->suffix == 'kml'){
			$res = $xml->coordinates[0];
			$res = explode(" ", $res);
			foreach($res as $r){
				$p = explode(",", $r);
				$points[] = array($p[0], $p[1]);
			}
		}
		/*preg_match_all("~<gpx([^>]*)>([^<]*)</text>~isU", $xml, $texts, PREG_SET_ORDER);
		$res = array();
		foreach($texts as $text){
			preg_match("~l=.([0-9]+)~s", $text[1], $l);
			preg_match("~t=.([0-9]+)~s", $text[1], $t);
			$res[] = array("l"=>$l[1], "t"=>$t[1], "text"=>html_entity_decode($text[2]));
		}*/

		$gp = new googlePolyLine;
		$enc = $gp->dpEncode($points);

		for($x=0.00001; strlen($enc[2]) > 1500;$x*=2){ //url limit is 2000
				$gp->verySmall = $x;
				$enc = $gp->dpEncode($points);
		}

		$url = "http://maps.googleapis.com/maps/api/staticmap?size=120x120&path=weight:3%7Ccolor:blue%7Cenc:" . $enc[2] . "&sensor=false";
		$image = file_get_contents($url);
		if(!$image)
			return false;

		file_put_contents($f->getPreviewPath(), $image);

		return array(
				'wxh'=>'120x120',
				'info' => '{"length":"'.'", "segments": "'.'"}');
	}
}



class DocumentFile extends File {
	public static $suffixes = "pdf,doc,docx,ppt,pptx,xls,xlsx,pages,ai,psd,dxf,svg,eps,ps,ttf,xps";

	public function generatePreviewImage(){

		$url = Environment::getHttpRequest()->getUrl()->getHostUrl() . $this->downloadLink(); //absolute URL
		$preview = self::getGoogleViewerPreview($url);
		if($preview){
			file_put_contents($this->getPreviewPath(), $preview['image']);
			$info = ImageFile::getImageInfo($this->getPreviewPath()); //TODO rozmyslet kam metodu umístit
			$this->data['info'] = str_pad($preview['numPages'],5) . $preview['text'];
			$this->data['dimensions'] = $info["wxh"];
			$this->save();
		}
	}

	public function getAdminInfo(){
		$link = Environment::getApplication()->getPresenter()->link(':Front:Files:preview', $this->id);
		return "Počet stránek: ".substr($this->info, 0,5)
						."<br><a href='$link'>preview</a>";
	}

	public function getEmbedCode(){
		return "<a href='#-f$this->id-#'>$this->filename</a>";
	}

	public static function getGoogleViewerPreview($url){
		if(strpos($url, 'localhost') !== false)
			return false;
		
		$url = urlencode($url);
		$response = file_get_contents("http://docs.google.com/viewer?url=$url&embedded=true");
		$response = strtr($response, array(
			"\\75" => "=",
			"\\46" => "&",
		));

		if(!preg_match("~numPages:([0-9]+)~isU", $response, $numPages)){
			return false; //without numPages its not a document
		}
		preg_match("~biUrl:'(.+)'~isU", $response, $biUrl);
		preg_match("~gtUrl:'(.+)'~isU", $response, $gtUrl);

		$image = file_get_contents("http://docs.google.com/viewer$biUrl[1]&pagenumber=1&w=600");
		$text = file_get_contents("http://docs.google.com/viewer$gtUrl[1]");

		return array(
			"image" => $image,
			"text" => $text,
			"numPages" => $numPages[1],
		);
	}
}



class SoundFile extends File {
	public static $suffixes = "mp3";
	public static $controlResizable = false;
	
	//TODO: [feature] http://getid3.org/

	public function getControlHtml($opts=null){
		//$link = $this->downloadLink();
		$link = Environment::getHttpRequest()->getUrl()->getBasePath()
						. "data/files/$this->id.orig.$this->suffix";
		return "\n<audio src='$link' type='audio/mp3' controls='controls'></audio>\n";
	}

	public function getControlImage($opts) {
		$image = Image::fromFile(dirname(__FILE__)."/SoundFile-control.png");
		$image->string(7, 80, 6, "$this->filename.$this->suffix", Image::rgb(255,255,255));
		return $image;
	}

	public function getControlMacroOptions($optstr){
		$optstr = preg_replace('~^_\d+(?:x\d+)?(_|$)~', '\\1', $optstr);
		return $optstr;
	}

	public function save($newdata=false) {
		parent::save($newdata);
		$this->clearPreviewCache('control');
	}
}



class VideoFile extends File {
	public static $suffixes = "mp4,flv";

	public function getControlHtml($opts=null){
		$preview = $this->previewLink("480x330");

		//$link = $this->downloadLink();
		$link = Environment::getHttpRequest()->getUrl()->getBasePath()
						. "data/files/$this->id.orig.$this->suffix";

		return "\n<video src='$link' width='480' height='330' poster='$preview'>\n"
						."Pokud vidíte tento text, váš prohlížeč zřejmě neumí přehrávat video.\n"
						."<br>Video můžete alespoň <a href='$link'>stáhnout</a> a zkusit ho přehrát mimo prohlížeč.\n"
						."</video>\n";
		//TODO proč nefunguje downloadLink??
		//TODO dát to do šablony
		//TODO do disable textu přidat preview
	}

	public function getControlImage($opts) {
		$image = parent::getControlImage($opts); //image preview with applied opts
		//$image = Image::fromFile($this->getPreviewPath());

		$button = Image::fromFile(dirname(__FILE__)."/VideoFile-control.png");
		$button->resize(0.8*$image->width, 0.9*$image->height);
		$image->place($button, "50%", "50%");

		return $image;
	}
}



class ArchiveFile extends File {
	public static $suffixes = "zip,rar,tar,tgz,gz";

	public function getEmbedCode(){
		return "<a href='#-f$this->id-#'>$this->filename</a>";
	}
}



class MissingFile extends File {
	public static $suffixes = "";

	public function __construct() {}

	public function previewLink($zoom = null) {
		return Environment::getHttpRequest()->getUrl()->getBasePath()
						."static/images/missing-image.png";
	}

	public function save($newdata=false){
		throw new InvalidStateException("Instance of MissingFile cannot be saved.");

	}
}
