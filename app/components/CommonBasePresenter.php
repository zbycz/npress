<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


/** Base presenter for both Admin and Front module
 *
 * @author     Pavel Zbytovský (pavel@zby.cz)
 * @package    nPress
 */
abstract class CommonBasePresenter extends Presenter
{
	/** @persistent */
	public $lang = 'cs'; //TODO from config (https://github.com/nette/nette/pull/445)

	public $pages;

	public function startup() {
		parent::startup();

		//lang settings
		$this->template->lang = PagesModel::$lang = $this->lang;
		$this->template->langs = $this->getContext()->params["langs"];
		$this->template->setTranslator(new TranslationsModel($this->lang));


		//pages tree
		$this->pages = PagesModel::getRoot();
		$this->template->pages = $this->pages;
		$this->template->crumbs = array();

		//configuration
		$this->template->config = $this->getContext()->params['npress'];
		$this->template->frontjslatte = Environment::getVariable("appDir") . '/FrontModule/templates/frontjs.latte';
		$this->template->npLayoutFile = Environment::getVariable("appDir") . '/FrontModule/templates/@layout.latte';
	}

	// Link to this page's mutation, or homepage
	public function langSwitch($l){
		if(isset($this->page) AND $this->page->lang($l))
			return $this->link('this', array('lang'=>$l)); //other lang mutation
		return $this->link('Pages:', array('lang'=>$l)); //default page
	}

	//returns true also for "category" parent
	public function isCurrent($link_page_id){
		if(!isset($this->page) OR $this->page==false)
			return false;
		if($link_page_id == $this->page->id)
			return true;
		if($link_page_id == $this->page->getParent()->id AND $this->page->getParent()->getMeta('.category'))
			return true;
		return false;
	}

	//Plugins are direct components
	protected function createComponent($name){
		$plugins = $this->context->plugins->getPlugins();
		if(in_array($name, $plugins))
			return new $name;

		return parent::createComponent($name);
	}

	//Trigger plugin event as filter
	public function triggerEvent_filter($eventname, $filter){
		$triggers = $this->context->plugins->getEventTriggers($eventname);
		foreach($triggers as $plugin){
			$filter = call_user_func(callback($this[$plugin], $eventname), $filter);
		}
		return $filter;
	}

	//Trigger plugin event, observing returned false
	public function triggerEvent($eventname){
		$triggers = $this->context->plugins->getEventTriggers($eventname);
		$ret = true;
		foreach($triggers as $plugin){
			$r = call_user_func(callback($this[$plugin], $eventname));
			if($r === false)
				$ret = false;
		}
		return $ret;
	}
}
