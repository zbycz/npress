<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


class PagesModel extends Object {
	public static $showUnpublished = false;
	public static $lang = 'cs'; //TODO default from config

	private static $cache = array(); //[id][lang]
	private static $cacheByParent = array(); //[lang][id_parent][]
	//deleted or published not filtered in the cache

	public static function getPageById($id, $lang=false){
		if(!$lang) $lang = self::$lang;

		if(isset(self::$cache[$id][$lang]))
			return self::$cache[$id][$lang];

		$page = dibi::fetch("
				SELECT *
				FROM pages
				WHERE id_page = %i", $id, "
					AND lang = %s",$lang);
		if($page)
			return self::pagesModelNode($page);

		//TODO můžeme vybrat všechny jazyky a kešnout
		//TODO refactoring - .category mít přímo v tabulce pages
		//TODO refactoring - meta se načítá až když je potřeba

		return false;
	}

	public static function getChildNodesUnfiltered($id, $lang=false){
		if(!$lang)
			$lang = self::$lang;

		if(isset(self::$cacheByParent[$lang][$id]))
			return self::$cacheByParent[$lang][$id];

		$pages = dibi::query('
			SELECT *
			FROM pages
			WHERE lang = %s',$lang,' AND id_parent = %i',$id,'
			ORDER BY ord
			')->fetchAssoc("id_page");

		//bardump($pages);

		if($pages)
			$metas = dibi::query("
				SELECT *
				FROM pages_meta
				WHERE id_page IN %in",array_keys($pages),"
				ORDER BY id_page,`key`")->fetchAssoc('id_page|key=value');

		$children = array();
		foreach($pages as $id_page => $data){
			$meta = isset($metas[$id_page]) ? $metas[$id_page] : array();
			$children[] = self::pagesModelNode($data, $meta);
		}
		self::$cacheByParent[$lang][$id] = $children;
		return $children;
	}

	public static function getChildNodes($id, $lang=false){  //used by Node methods
		$children = self::getChildNodesUnfiltered($id, $lang);

		//filtering deleted pages
		$result = array();
		foreach($children as $k=>$r)
			if(!$r['deleted'] AND (self::$showUnpublished OR $r['published']))
				$result[] = $r;

		return $result;
	}

	public static function getRoot($lang=false){
		if(!$lang) $lang = self::$lang;

		if(isset(self::$cache["0"][$lang])) //fiktivní root
			return self::$cache["0"][$lang];

		self::cacheAllPages();

		return self::pagesModelNode(array(
				"id_page" => "0",
				"id_parent" => NULL,
				"lang" => $lang,
				"name" => "root"), array());
	}

	public static function getPageBySeoname($seoname, $lang){
		$page = dibi::fetch("SELECT * FROM pages WHERE lang = %s",$lang," AND seoname = %s",$seoname," AND deleted=0");
		if($page)
			return self::pagesModelNode($page);

		return false;
	}

	//
	public static function getDeletedPages(){
		$pages = dibi::query("
			SELECT p.*, m.value as deleted_date FROM pages p LEFT JOIN pages_meta m ON p.id_page=m.id_page AND m.key='deleted'
			WHERE lang = %s",self::$lang," AND deleted=1
			ORDER BY deleted_date DESC")->fetchAssoc("id_page");

		$metas = dibi::query("
			SELECT *
			FROM pages_meta
			WHERE id_page IN %in",array_keys($pages),"
			ORDER BY id_page,`key`")->fetchAssoc('id_page|key=value');

		$deleted = array();
		foreach($pages as $id_page => $data){
			$meta = isset($metas[$id_page]) ? $metas[$id_page] : array();
			$deleted[] = self::pagesModelNode($data, $meta);
		}
		
		return $deleted;
	}

	/** Načítá i "smazané" stránky, filtrujeme až v getChildNodes() */
	public static function cacheAllPages(){
		$pages = dibi::query('
			SELECT *
			FROM pages
			WHERE lang = %s',self::$lang,'
			ORDER BY ord
			')->fetchAssoc("id_page");
			
		$metas = dibi::query("SELECT * FROM pages_meta ORDER BY id_page,`key`")->fetchAssoc('id_page|key=value');
		
		foreach($pages as $id_page => $data){
			$meta = isset($metas[$id_page]) ? $metas[$id_page] : array();
			$obj = self::pagesModelNode($data, $meta);
			@self::$cacheByParent[$obj->lang][$obj->id_parent][] = $obj;

			if(!isset(self::$cacheByParent[$obj->lang][$obj->id])) //leafs childs
				self::$cacheByParent[$obj->lang][$obj->id] = array();

		}
	}

	//creates&cache new pagesModelNode or returns cached
	public static function pagesModelNode($data, $meta=NULL){ //lang in data
		if(isset(self::$cache[$data['id_page']][$data['lang']]))
			return self::$cache[$data['id_page']][$data['lang']];

		if(!isset($meta))
			$meta = (array) dibi::query("SELECT * FROM pages_meta WHERE id_page = %i",$data['id_page']," ORDER BY `key` ")->fetchAssoc('key=value');

		$obj = new PagesModelNode($data, $meta);
		@self::$cache[$obj->id][$data['lang']] = $obj;
		return $obj;
	}
	
	/// additional model methods

	/** Get pages with spcified meta key&value
	 * @return PagesCollection
	 */
	public static function getPagesByMeta($key, $value){
		$result = dibi::query('
			SELECT pages.*
			FROM pages_meta LEFT JOIN pages USING(id_page)
			WHERE lang=%s',self::$lang,' AND `key`=%s',$key,' AND value=%s',$value,'
			ORDER BY id_parent, ord
			');
		if(!$result) return false;
		$out = new PagesCollection;
		foreach($result as $r)
			$out[] = self::pagesModelNode($r);
		return $out;
	}

	//TODO doesnt update cache
	public static function addPage($data){ //lang supplied in $data
		if(!isset($data['published']))
			$data['published'] = 0;
		
		dibi::query('INSERT INTO pages', $data);
		$new_page_id = dibi::insertId();

		return $new_page_id;
	}
	
	public static function sort($data){
		$order=0;
		foreach($data as $id)
			dibi::query('UPDATE pages SET ord=%i',$order++,' WHERE id_page=%i',$id,' AND lang=%s',self::$lang);
	}

	public static function nestedsort($data){
		$order=0;
		foreach($data as $id=>$id_parent){
			if($id_parent == 'root') $id_parent = 0;
			dibi::query('UPDATE pages SET id_parent=%i',$id_parent,', ord=%i',$order,' WHERE id_page=%i',$id,' AND lang=%s',self::$lang);
			$order++;
		}
	}

	
	//shortucts
	public static function getPagesFlat(){
		return self::getRoot()->getDescendantsFlat();
	}
}





interface ITreeViewNode{
	/**
	 * Return all avaible child Nodes
	 * @return ITreeViewNode
	 */
	public function getChildNodes();

	/**
	 * Checks if current node has ChildNodes
	 * @return bool
	 */
	public function hasChildNodes();

	/**
	 *
	 * @return string
	 */
	public function getNodeCaption();
}

interface IExpandableTreeViewNode {
	/**
	 *
	 * @return int
	 */
	public function getNodeId();
}


class PagesCollection extends ArrayList {
	/** Gets array indexed by id_page and containing indented nodeCaption
	 * @param $sep   string used for indentation of children
	 * @return array indented page-names indexed by page-id
	 */
	public function getPairs($sep = "  "){ //!! <- two non-breakable spaces (ascii#160)
		$output = array();
		foreach($this as $r){
			$output[$r->id] = ($sep ? str_repeat($sep, $r->level-1):'') . $r->name;
		}
		return $output;
	}

}


class PagesModelNode extends Object  implements ArrayAccess, ITreeViewNode, IExpandableTreeViewNode  { //TODO ITree* nějak ošetřit dependencies
	public $data; //sql row
	public $level; //needed for flat view
	
	/** @var array indexed by meta key */
	public $meta;

	//předat sql-row: id_page,id_parent,name,...
	public function __construct($data, $meta){
		$this->data = (array) $data;
		$this->meta = (array) $meta;

		//avoiding duplicity in database
		if($this->data['name'] == '')
			$this->data['name'] = $this->data['heading'];
	}

	public function getId(){ return $this->data['id_page']; }
	public function getId_parent(){ return $this->data['id_parent']; }
	public function getName(){
		if($this->data['name'])
			return $this->data['name'];
		if($this->data['heading'])
			return $this->data['heading'];
		return "…";
	}
	public function getLang(){ return $this->data['lang']; }
	public function getContent(){
		return Environment::getNpMacros()->process($this->data['text'], $this);
	}
	public function getPublished(){ return $this->data['published']; }



	/** gets the parent node */
	public function getParent(){
		return PagesModel::getPageById($this->id_parent, $this->lang);
		//TODO maybe place here integrity check - no parent -> error
	}

	/** gets all parents nodes to root (includes itself!!) */
	public function getParents(){
		$parents = array();
		for($x = $this; $x['id_parent'] != NULL; $x = $x->getParent())
			array_unshift($parents, $x);

		return $parents; //TODO find cycles in structure?
	}
	
	/** funkce getChildNodes() */
	public function getChildNodes(){
		return PagesModel::getChildNodes($this->id, $this->lang);
	}

	/** returns all children in tree */
	public function getChildNodesUnfiltered(){
		return PagesModel::getChildNodesUnfiltered($this->id, $this->lang);
	}

	/** Gets array of all pages with correct index and level
	 * @return PagesCollection   works as array
	 */
	public function getDescendantsFlat($maxDepth = 100){
		$this->level = 0;
		$flatResult = new PagesCollection;
		$stack = array($this); //process own children

		while( ($parent = array_pop($stack)) ){ //recursion using stack
			if($parent != $this)
				$flatResult[] = $parent;

			if($parent->level >= $maxDepth) break;
			
			$children = $parent->getChildNodes();
			foreach(array_reverse($children) as $child){
				$child->level = $parent->level + 1;
				$stack[] = $child;
			}
		}

		return $flatResult;
	}

	/** funkce hasChildNodes() vrací, zda má aktuální node nějaké potomky */
	public function hasChildNodes(){
		return count($this->getChildNodes()); //kešované
	}
	
	/** funkce getNodeCaption() vrací řetězec, který bude vykreslen, pokud není definováno jinak v události komponenty onRenderNode.*/
	public function getNodeCaption(){
		return $this->name;
	}
	
	/** funkce getNodeId() vrací jednoznačný identifikátor nodu, v případě použití DB typicky Primary Key. */
	public function getNodeId(){
		return $this->id;
	}

	//ArrayAccess	
	public function offsetGet($offset){return $this->data[$offset];}
	public function offsetExists($offset){return array_key_exists($offset, $this->data);}
	public function offsetSet($offset, $value){}
	public function offsetUnset($offset){}
	
	
	//custom funkce
	public function getFiles($key=NULL){
		$files = FilesModel::getFiles($this->id);
		if(isset($key))	return isset($files[$key]) ? $files[$key] : new MissingFile;
		return $files;
	}
	public function getGalleries($addBlank=false){
		return FilesModel::getGalleries($this->id, $addBlank);
	}
	public function getFileByName($name){
		return FilesModel::getFileByName($this->id, $name);
	}
	public function getFilesWhere($sqlwhere=false){
		$sqlwhere = array('id_page'=>$this->id, 'deleted'=>'0') + (array) $sqlwhere;
		return FilesModel::getFilesWhere($sqlwhere);
	}


	//implement support for redirecting pages
	public function getRedirectLink(){
		$pagelink = $this->data['seoname'];
		$presenter = Environment::getApplication()->getPresenter();

		//not a redirect URL
		if(!$pagelink OR $pagelink{0} == '/')
			return NULL;

		//TODO 		NpMacros::processUrlMacros($pagelink);
		//p12 -> link to different page (better not get the target - beware of cycles)
		if(preg_match('~^#-p([0-9]+)-#$~', $pagelink, $m))
			return $presenter->link(':Front:Pages:', array($m[1], 'lang'=>$this->lang));


		//f12  -> link to file download
		if(preg_match('~^#-f([0-9]+)-#$~', $pagelink, $m))
			return $presenter->link(':Front:Files:', $m[1]);

		//absolute URL to (probably) different website
		return $pagelink;
	}

	public function link($absolute=false){
		$redirect = $this->getRedirectLink();
		if($redirect)
			return $redirect;

		$presenter = Environment::getApplication()->getPresenter();
		$target = ($absolute ? '//' : '') . ':Front:Pages:';
		return $presenter->link($target, array($this->id, 'lang'=>$this->lang));
	}

	//active record
	public function getMeta($key){
		return isset($this->meta[$key]) ? $this->meta[$key] : false;
	}

	public function getInheritedMeta($key){
		foreach(array_reverse($this->getParents()) as $r)
			if($r->getMeta($key) !== false)
				return $r->getMeta($key);
	}

	public function addMeta($key, $val){
		if(empty($key)) return;
		$data = array(
			'id_page' => $this->id,
			'key'=>$key,
			'value'=>$val,
			'ord' => 1 + dibi::fetchSingle("SELECT max(ord) FROM pages_meta"),
		);
		dibi::query('REPLACE INTO pages_meta', $data);
		$this->meta[$key] = $val;
	}

	public function deleteMeta($key){
		dibi::query('
			DELETE FROM pages_meta
			WHERE id_page = %i', $this->id,'
				AND `key` = %s', $key);
		if(isset($this->meta[$key]))
			unset($this->meta[$key]);
	}

	public function delete(){
		$this->save(array("deleted" => true));
		$this->addMeta('deleted', date('Y-m-d H:i:s'));

		foreach($this->getChildNodes() as $child) //delete all childs recursively
			$child->delete();
	}

	public function undelete(){
		$this->save(array(
			"deleted" => false,
			"id_parent" => $this->getFirstLivingParent()->id
			));
		$stamp = $this->getMeta('deleted');
		$this->deleteMeta('deleted');

		//undelete all childs recursively with the same deleted stamp
		if($stamp)
			foreach($this->getChildNodesUnfiltered() as $child)
				if($stamp == $child->getMeta('deleted'))
					$child->undelete();
	}

	public function save($newdata){
		//merge changes to orig data array
		foreach($newdata as $k=>$v){
			if($this->data[$k] == $v) unset($newdata[$k]);
			else $this->data[$k] = $v;
		}

		//strip duplicate column from database
		if(isset($newdata['name']) AND $newdata['name'] == $this->data['heading'])
			$newdata['name'] = '';

		if(count($newdata))
			dibi::query('
				UPDATE pages
				SET',$newdata,'
				WHERE id_page = %i', $this->data['id_page'],'
					AND lang = %s',$this->data['lang'],'
			');

	}

	// returns first parent who is not deleted
	public function getFirstLivingParent(){
		$x = $this;
		while($x->id_parent != 0){ //$x != childs of root
			$x = $x->getParent();
			if($x['deleted'] == 0)
				return $x;
		}
		return PagesModel::getRoot();
	}
	
	// returns first parent who has $lang mutation
	public function getFirstParentInLang($lang){
		$x = $this;
		while($x->id_parent != 0){ //$x != childs of root
			$x = $x->getParent();
			$mutace = $x->lang($lang);
			if($mutace AND !$mutace['deleted'])
				return $mutace;
		}
		return PagesModel::getRoot($lang);
	}

	public function createLang($lang){
		$id_parent = $this->getFirstParentInLang($lang)->id;

		$data = array(
				'id_page' => $this->id,
				'lang' => $lang,
				'id_parent' => $id_parent,
		);
		dibi::query('INSERT INTO pages', $data);

		return $this->lang($lang); //perform db query to get the full data array
	}
	
	public function lang($lang){
		return PagesModel::getPageById($this->id, $lang);
	}
	
}



