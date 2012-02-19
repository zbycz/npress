<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


/** Pages display presenter
 */
class Front_PagesPresenter extends Front_BasePresenter
{
	/** @var PagesModelNode
	 */
	public $page = false;

	public function actionDefault($id_page){
		$this->page = PagesModel::getPageById($id_page);
		if($this->page === false)
			throw new BadRequestException("Stránka nenalezena. (id=$id_page lang=$this->lang)");

		if($this->page['deleted'])
			throw new BadRequestException("Stránka smazána. (id=$id_page lang=$this->lang)");

		//page should display different URL?
		$this->doPageRedirects();

		// bread crumbs
		$this->template->crumbs = $this->page->getParents();
		$this->template->page = $this->page;
	}

	public function doPageRedirects(){
		$redirect = $this->page->getRedirectLink();
		if($redirect)
			return $this->redirectUrl($redirect);
	}

	//page specific template
	function formatTemplateFiles() {
		$list = parent::formatTemplateFiles();

		$tpl = $this->page->getMeta(".template");
		if($tpl)
			array_unshift($list, $this->context->params["wwwDir"]."/theme/$tpl.latte");
		return $list;
	}
}
