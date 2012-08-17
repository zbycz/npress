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
	public function actionAdd($id_parent, $sibling=false){
		if($id_parent){
			$page = PagesModel::getPageById($id_parent);
			if(!$page)
				return $this->displayMissingPage($id_parent, $this->lang);

			if($sibling AND $page = $page->getParent()) $id_parent = $page->id;
		}

		$newid = PagesModel::addPage(array(
				'id_parent' => intval($id_parent) ? intval($id_parent) : 0,
				'lang' => $this->lang,
				'ord' => 9999,
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

	}


	
	//page-edit form
	public function createComponentPageEditForm(){
		$form = new AppForm;
    $form->getElementPrototype()->class('ajax');

		$form->addHidden("id_page");
				
		$form->addText("heading", "nadpis");
		$form->addText("name", "v menu");
		$form->addText("seoname", "adresa")
						->setAttribute('placeholder', '(automatická)');

		$form->addCheckbox("published", "zobrazeno v menu");

		
		$form->addTextArea("text", "obsah stránky");
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

		//name field may be 'disabled' by checkbox
		if(!$values['name']) $values['name'] = $values['heading'];

		//handle additional input values
		$values = $this->triggerEvent_filter('filterPageEditForm_values', $values);

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




	//npFiles control
	protected function createComponentNpFilesControl(){
		return new NpFilesControl($this->context->httpRequest, $this->page);
	}
	
	//meta control
	protected function createComponentMetaControl(){
		return new MetaControl;
	}

}


