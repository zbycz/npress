<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

/** Pages admin presenter
 */
class NpFilesControl extends Control
{
	public $page;
	private $httpRequest;

	public function __construct(HttpRequest $httpRequest, $page){
		$this->httpRequest = $httpRequest;
		$this->page = $page;
		parent::__construct();
	}



	public function render(){

		//filelist max related
		if(!isset($this->template->filelistMaxNum))
			$this->template->filelistMaxNum = -1;
		if(!isset($this->template->filelistMax))
			$this->template->filelistMax = new ArrayWithDefault(9);

		$template = $this->getTemplate();
		$template->setFile(dirname(__FILE__) . '/template.latte');
		$template->page = $this->page;
		$template->render();
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
		if(!$this->presenter->editAllowed()) return;
	
		$file = $form->values['file'];
		if($file->isOK()){
			FilesModel::upload($this->page->id, $file);
			$this->presenter->flashMessage('Soubor úspěšně nahrán');
		}
		else{
			$this->presenter->flashMessage('Nahrání se nepovedlo :-(');
		}

		//iframe umí interpretovat jen text/html, nelze poslat snippety
		//TODO bylo by fajn posílat aspoň pravdu
		//also see @previewUploadFormSubmitted
		if($this->getParam('ajax_upload')){
			/*Nette\Diagnostics\*/Debugger::$bar = FALSE;  //TODO (ask) nešlo by to nějak líp?
			$this->presenter->sendResponse(new TextResponse("{error: '',msg: 'ok'}", 'text/html'));
			exit;
		}

		$this->redirect('this#toc-files');
	}
	public function handleRefreshFileList(){
		$this->invalidateControl('editform_filelist');
	}
	public function handleUploadify(){
		if(!$this->presenter->editAllowed()) return;

		$file = $this->httpRequest->getFile('Filedata');
		if(!$file)
			$this->presenter->sendResponse(new TextResponse("File not uploaded"));

		FilesModel::upload($this->page->id, $file);
		$this->presenter->sendResponse(new TextResponse("Upload ok."));
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
		if(!$this->presenter->editAllowed()) return;

		$values = (array)$form->values;
		$values['id_page'] = $values['id_page_change']; //fix - after submitting changing $id_page
		unset($values['id_page_change']);

		$file = FilesModel::getFile($values['id']);
		$file->save($values);

		$this->presenter->flashMessage("Popis souboru #$file->id uložen");
		$this->invalidateControl('editform_filelist');

		//TODO (ask) zrušit submitted signál (jak redirect na action?)

		$this->handleEditFile($file->id); //fill form

		if(!$this->presenter->isAjax()) $this->redirect('this#toc-files');
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
		if(!$this->presenter->editAllowed()) return;

		if($form->values['file']->isOK())
			FilesModel::uploadPreview($form->values);

		if($this->getParam('ajax_upload')){ //@see uploadFormSubmitted
			Debugger::$bar = FALSE;
			$this->presenter->sendResponse(new TextResponse("{error: '',msg: 'ok'}", 'text/html'));
			exit;
		}
		else
			$this->redirect("editFile!#toc-files", $form->values['id']);
	}


	//filelist - sorting, delete
	public function handleFilesort(){
		if(!$this->presenter->editAllowed()) return;

		FilesModel::sort($this->httpRequest->post);
		//dom is enough //$this->invalidateControl('editform_filelist'); //(all dynamic snippets)
		$this->presenter->flashMessage("Pořadí souborů upraveno");
	}
	public function handleDeleteFile($fid, $undo=false){
		if(!$this->presenter->editAllowed()) return;

		if(!$undo){
			FilesModel::edit(array('id' =>$fid, 'deleted'=>true));
			$undolink = $this->link('deleteFile!#toc-files', $fid, true); //undo=true
			$this->presenter->flashMessage("Soubor #$fid smazán")->undolink = $undolink;
		}
		else{
			FilesModel::edit(array('id' =>$fid, 'deleted'=>false));
			$this->presenter->flashMessage("Soubor #$fid navrácen zpět");
			$this->invalidateControl('editform_filelist');
		}
		if(!$this->presenter->isAjax()) $this->redirect('this#toc-files');
		//TODO we can break the files order when undoing
	}
	
	public function handleSortFilesByName(){
		if(!$this->presenter->editAllowed()) return;

		FilesModel::sortFilesBy($this->page->id, 'filename');
		$this->invalidateControl('editform_filelist');
		if(!$this->presenter->isAjax()) $this->redirect('this#toc-files');
	}
	public function handleFilesSync(){
		if(!$this->presenter->editAllowed()) return;

		$log = FilesModel::filesSync($this->page->id, $this->page->getMeta('.filesSync'));
		$this->template->filesSyncLog = $log;
	}
	public function handleFilelistMore($num, $max){
		if(!isset($this->template->filelistMax)) {
			$this->template->filelistMax = new ArrayWithDefault(9);
		}
		$this->template->filelistMax[$num] = $max;
		$this->template->filelistMaxNum = $num;
		$this->invalidateControl('editform_filelist'); //dynamic snippets used here!
	}

}
