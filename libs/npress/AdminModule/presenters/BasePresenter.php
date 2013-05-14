<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


abstract class Admin_BasePresenter extends CommonBasePresenter
{
	public function startup()
	{
		//check permission
		if(!$this->user->isLoggedIn()){
			$backlink = $this->application->storeRequest();
			$this->redirect(':Front:Login:', array('backlink' => $backlink));
		}

		//allow this request?
		$allowed = $this->triggerStaticEvent('allow_admin_request', $this);
		if(!$allowed){
			if($this->isAjax()){
				$this->sendResponse(new JsonResponse(array("snippets" => array("snippet--flashes" => "<div class=\"flash alert\">Nedostatečné oprávnění.</div>")))); //<script>window.setTimeout(function(){ $('#flashes div div').fadeOut(8000); }, 5000);</script>
				$this->terminate();
			}
			throw new ForbiddenRequestException("Nedostatečné oprávnění.");
		}
		

		//initialize admin
		parent::startup();

		//admin things
		PagesModel::$showUnpublished = true;
		$this->template->wysiwygConfig = $this->context->params['npress']['wysiwyg'];


		//adminMenu
		$classes = $this->context->plugins->getEventTriggers('adminMenu');
		$this->template->adminMenu = array();
		foreach($classes as $class){
			$link = $this->link($class::$adminMenuLink);
			$this->template->adminMenu[$link] = $class::$adminMenu;
		}
	}


	//not in use for now, TODO should be and action
	public function handleGetPagesFlatJson(){
		$array = PagesModel::getPagesFlat()->getPairs();
		$this->sendResponse(new JsonResponse($array));
	}


	//send flashes with every AJAX response
	public function afterRender(){
	    if ($this->isAjax() && $this->hasFlashSession())
	        $this->invalidateControl('flashes');
	}


	//working expandable tree view components (not in use now)
	protected function createComponentTreeView(){
		$CategoriesTree = new ExpandableTreeView;
		$CategoriesTree->hideRootNode = true;
		$CategoriesTree->treeViewNode($this->pages);
		$CategoriesTree->expandNode();
		return $CategoriesTree;
	}


	/** Enable theme specific layout
	 */
	public function formatLayoutTemplateFiles() {
		$list = parent::formatLayoutTemplateFiles();
		array_unshift($list, $this->context->params["npDir"] . "/AdminModule/templates/@layout.latte");
		return $list;
	}


}
