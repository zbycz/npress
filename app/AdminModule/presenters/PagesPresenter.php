<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */

/** Pages admin presenter
 */
class Admin_PagesPresenter extends Admin_BasePresenter
{
	public $page; 


	public function actionDefault(){
		$this->redirect("Admin:");
	}
	public function actionTrash(){
		$this->template->deletedPages = PagesModel::getDeletedPages();
	}
	public function actionAdd($id_parent){
		$newid = PagesModel::addPage(array(
				'id_parent' => intval($id_parent) ? intval($id_parent) : 0,
				'lang' => $this->lang,
			));
		$this->redirect('edit#newpage', $newid);
	}

	public function displayMissingPage($id_page,$lang){
			$this->template->error_id_page = $id_page;
			$this->template->error_lang = $lang;
			$this->setView('error');
	}

	public function actionEdit($id_page){
		if($id_page == 0) $this->redirect("Admin:");
		$this->page = PagesModel::getPageById($id_page);

		//creating or deriving page from another
		$fromLang = $this->getParam('from');
		if($fromLang){
			$fromPage = PagesModel::getPageById($id_page, $fromLang);
			if(!$fromPage)
				return $this->displayMissingPage($id_page, $fromLang);
			else
				$this->template->fromPage = $fromPage;

			if(!$this->page)
					$this->page = $fromPage->createLang($this->lang);
					//TODO PagesMode::$cache[0][$this->fromLang] = root ve fromLang jazyce neexistuje!!! tzn instancovat a vytvořit v konstuktoru
		}

		//page still doesnt exist? error
		if(!$this->page)
			return $this->displayMissingPage($id_page, $this->lang);
		else
			$this->template->page = $this->page;

		// bread crumbs
		$this->template->crumbs = $this->page->getParents();
		
		//default values for editform
		$form = $this['pageEditForm'];
		if (!$form->isSubmitted()){
			$form->setValues($this->page->data);
			$this->triggerEvent_filter('filterPageEditForm_defaults', $form);
		}


		//filelist max related
		$this->template->filelistMaxNum = -1;
		$this->template->filelistMax = new ArrayWithDefault(9);
	}


	
	//page-edit form
	public function createComponentPageEditForm(){
		$form = new AppForm;
    $form->getElementPrototype()->class('ajax');

		$form->addHidden("id_page");
				
		$form->addText("heading", "nadpis");
		$form->addText("name", "v menu");
		$form->addText("seoname", "adresa");

		$form->addCheckbox("published", "zobrazeno");
		
		$form->addTextArea("text", "text");
		$form['text']->getControlPrototype()->class('textarea');

		$form->addSubmit("submit1", "Uložit");
		$form->onSuccess[] = callback($this, 'pageEditFormSubmitted');

		//add additional inputs
		$this->triggerEvent_filter('filterPageEditForm_create', $form);

		return $form;
	}
	public function pageEditFormSubmitted(AppForm $form){
		$values = (array) $form->values;
		$values['text'] = preg_replace_callback('~#-(.+?)-#~', array($this, 'npMacroControlOptions'), $values['text']);

		//handle additional input values
		$values = $this->triggerEvent_filter('filterPageEditForm_values', $values);

		//instead of blank menu, we display heading
		if($values['name'] == $values['heading'])
			$values['name'] = '';

		//menu control changes only sometimes
		if($this->page['published'] != $values['published']	OR $this->page['name'] != $values['name'] OR $this->page['heading'] != $values['heading'])
			$this->invalidateControl('menu');

		//url must be unique
		$i=1;
		$newname = $values['seoname'];
		while(!PagesRouter::isSeonameOk($newname, $this->page))
					 $newname = $values['seoname'] .'-'.$i++;
		$values['seoname'] = $newname;

		//update the new URL in the form
		$form['seoname']->value = $newname;
		$this->invalidateControl('editform_seoname');

		//save values
		$this->page->save($values);
		$this->flashMessage('Obsah stránky uložen ('.date('y-m-d H:i:s').')');

		if(!$this->isAjax()) $this->redirect('this');
	}
	public function npMacroControlOptions($macro){ //TODO combine with Front_BasePresenter (extract to class NpMacros)
		if(preg_match('~^file-([0-9]+)(_.+)?$~', $macro[1], $m))
			return "#-file-$m[1]" . FilesModel::getFile($m[1])->getControlMacroOptions(isset($m[2])?$m[2]:NULL)."-#";
		else
			return $macro[0];
	}


	//delete & undelete
	public function handleDeletePage($undo = false){
		if($undo)
			$this->page->undelete();
		else
			$this->page->delete();

		$this->flashMessage($undo ? "Stránka navrácena zpět" : "Stránka smazána");
		$this->invalidateControl('editform_deleted');
		$this->invalidateControl('menu');
		if(!$this->isAjax()) $this->redirect("this");
	}
	
	
	//subpageslist - sorting
	public function handleSubpagessort(){
		PagesModel::sort($this->getHttpRequest()->post['pageid'], $this->lang);
		$this->flashMessage("Pořadí článků upraveno");
		//$this->payload->hack = 'ok';
	}


	

	//upload form
	public function createComponentUploadForm(){
		$form = new AppForm;
    //$form->getElementPrototype()->class('ajax_upload');
		$form->addHidden("id_page");
		$form->addUpload("file", "soubor");
		$form->addSubmit("submit1", "Nahrát");
		$form->onSuccess[] = callback($this, 'uploadFormSubmitted');
		return $form;
	}
	public function uploadFormSubmitted(AppForm $form){
		$file = $form->values['file'];
		if($file->isOK()){
			FilesModel::upload($this->getParam('id_page'), $file);
			$this->flashMessage('Soubor úspěšně nahrán');
		}
		else{
			$this->flashMessage('Nahrání se nepovedlo :-(');
		}

		//iframe umí interpretovat jen text/html, nelze poslat snippety
		//TODO bylo by fajn posílat aspoň pravdu
		//also see @previewUploadFormSubmitted
		if($this->getParam('ajax_upload')){
			/*Nette\Diagnostics\*/Debugger::$bar = FALSE;  //TODO (ask) nešlo by to nějak líp?
			$this->sendResponse(new TextResponse("{error: '',msg: 'ok'}", 'text/html'));
			exit;
		}
		
		$this->redirect('this#toc-files');
	}
	public function handleRefreshFileList(){
		$this->invalidateControl('editform_filelist');
	}
	public function handleUploadify(){
		$file = $this->context->httpRequest->getFile('Filedata');
		if(!$file)
			$this->sendResponse(new TextResponse("File not uploaded"));

		FilesModel::upload($this->getParam('id_page'), $file);
		$this->sendResponse(new TextResponse("Upload ok."));
	}

	
	//Edit File form + handle
	public function handleEditFile($fid){
		$this->template->editFile = $file = FilesModel::getFile($fid);
		$this->invalidateControl('editform_editfile');

		$form = $this['editFileForm'];
		$form['filename']->setOption('description', '.'.$file->suffix);
		
		//fix - after submitting changing $id_page
		$data = $file->data;	$data['id_page_change'] = $data['id_page'];
		if (!$form->isSubmitted()) $form->setValues($data); //výchozí hodnoty

		$form = $this['previewUploadForm'];
		if (!$form->isSubmitted()) $form->setValues($file->data); //výchozí hodnoty
	}
	public function createComponentEditFileForm(){
		$form = new AppForm;
    $form->getElementPrototype()->class('ajax'); //todo:zlobí
		$form->addHidden("id");
		$form->addSelect("id_page_change", "Přesun", PagesModel::getPagesFlat()->getPairs());
		$form->addText("filename", "Název");
		$form->addTextarea("description", "Popis")->getControlPrototype()->setRows(3);
		$form->addText("keywords", "Keywords");
		$form->addText("timestamp", "Timestamp");
		$form->addText("gallerynum", "Číslo galerie")->getControlPrototype()->style('width:50px'); //TODO ->addRule(Form::INTEGER,'%label musí být číslo')  .. nefunguje js(ajax) validace
		$form->addSubmit("submit1", "Uložit");
		$form->onSuccess[] = callback($this, 'editFileFormSubmitted');
		return $form;
	}
	public function editFileFormSubmitted(AppForm $form){
		$values = (array)$form->values;
		$values['id_page'] = $values['id_page_change']; //fix - after submitting changing $id_page
		unset($values['id_page_change']);

		$file = FilesModel::getFile($values['id']);
		$file->save($values);

		$this->flashMessage("Popis souboru #$file->id uložen");
		$this->invalidateControl('editform_filelist');

		//TODO (ask) zrušit submitted signál (jak redirect na action?)

		$this->handleEditFile($file->id); //fill form

		if(!$this->isAjax()) $this->redirect('this#toc-files');
	}

	//preview Upload form
	public function createComponentPreviewUploadForm(){
		$form = new AppForm;
    $form->getElementPrototype()->class('ajax_upload');
		$form->addHidden("id");
		$form->addUpload("file", "soubor");
		$form->addSubmit("submit1", "Nahrát");
		$form->onSuccess[] = callback($this, 'previewUploadFormSubmitted');
		return $form;
	}
	public function previewUploadFormSubmitted(AppForm $form){
		if($form->values['file']->isOK())
			FilesModel::uploadPreview($form->values);

		if($this->getParam('ajax_upload')){ //@see uploadFormSubmitted
			Debugger::$bar = FALSE; 
			$this->sendResponse(new TextResponse("{error: '',msg: 'ok'}", 'text/html'));
			exit;
		}
		else
			$this->redirect("editFile!#toc-files", $form->values['id']);
	}


	//filelist - sorting, delete
	public function handleFilesort(){
		FilesModel::sort($this->getHttpRequest()->post);
		//dom is enoug //$this->invalidateControl('editform_filelist'); //(all dynamic snippets)
		$this->flashMessage("Pořadí souborů upraveno");
	}
	public function handleDeleteFile($fid, $undo=false){
		if(!$undo){
			FilesModel::edit(array('id' =>$fid, 'deleted'=>true));
			$undolink = $this->link('deleteFile!#toc-files', $fid, true); //undo=true
			$this->flashMessage("Soubor #$fid smazán")->undolink = $undolink;
		}
		else{
			FilesModel::edit(array('id' =>$fid, 'deleted'=>false));
			$this->flashMessage("Soubor #$fid navrácen zpět");
			$this->invalidateControl('editform_filelist');
		}
		if(!$this->isAjax()) $this->redirect('this#toc-files');
		//TODO we can break the files order when undoing
	}
	/*public function handleToggleFile($fid, $visible=null){
		//if(!isset($visible)) $visible = FilesModel::getFile($fid)->visible;
		FilesModel::edit(array(
				'id' => $fid,
				'visible' => $visible,
				));
		$this->flashMessage("Soubor #$fid " . ($visible ? "zobrazen" : "schován"));
		//$this->invalidateControl('editform_filelist');
		if(!$this->isAjax()) $this->redirect('this#toc-files');
	}*/
	public function handleSortFilesByName(){
		FilesModel::sortFilesBy($this->page->id, 'filename');
		$this->invalidateControl('editform_filelist');
		if(!$this->isAjax()) $this->redirect('this#toc-files');
	}
	public function handleFilesSync(){
		$log = FilesModel::filesSync($this->page->id, $this->page->getMeta('.filesSync'));
		$this->template->filesSyncLog = $log;
	}
	public function handleFilelistMore($num, $max){
		$this->template->filelistMax[$num] = $max;
		$this->template->filelistMaxNum = $num;
		$this->invalidateControl('editform_filelist'); //dynamic snippets used here!
	}


	
	//meta control
	protected function createComponentMetaControl(){
		return new MetaControl;
	}

}



/** Behaves like normal array, but non-existent keys return supplied default value
 */
class ArrayWithDefault implements ArrayAccess {
	private $data;
	private $defaultValue;
	function __construct($defaultValue, $initArray=array()){$this->defaultValue=$defaultValue; $this->data=$initArray;}
	function offsetGet($k){return isset($this->data[$k]) ? $this->data[$k] : $this->defaultValue;}
	function offsetExists($k){return true;}
	function offsetSet($k, $v){$this->data[$k] = $v;}
	function offsetUnset($k){unset($this->data[$k]);}
}