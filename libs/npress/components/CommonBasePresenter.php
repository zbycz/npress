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
		$this->template->langs = $this->context->params["langs"];
		$this->template->setTranslator(new TranslationsModel($this->lang));


		//pages tree
		$this->pages = PagesModel::getRoot();
		$this->template->pages = $this->pages;
		$this->template->crumbs = array();

		//configuration
		$this->template->config = $this->context->params['npress'];
		$this->template->frontjslatte = $this->context->params['npDir'] . '/FrontModule/templates/frontjs.latte';
		$this->template->npLayoutFile = $this->context->params['npDir'] . '/FrontModule/templates/@layout.latte';
	}

	// Link to page mutation, or homepage
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


	//Allow Plugins as direct components of presenter
	protected function createComponent($name){
		$plugins = $this->context->plugins->getPlugins();
		if(in_array($name, $plugins))
			return new $name;

		return parent::createComponent($name);
	}

	//Allow to use helpers as a latte macros
	public function templatePrepareFilters($template) {
		$template->registerFilter($e = new /*Nette\Latte\Engine*/LatteFilter());
		$s = new /*Nette\Latte\Macros\*/MacroSet($e->compiler);
		$s->addMacro('helper', 'ob_start()',
			function($n) {
				$w = new /*\Nette\Latte\*/PhpWriter($n->tokenizer, $n->args);
				return $w->write('echo %modify(ob_get_clean())');
			}
		);
	}

	/** Trigger plugin event as filter supplied value
	 * @param $eventname
	 * @param $filter    value to supply to filter chain
	 * @return string    resulting value
	 */
	public function triggerEvent_filter($eventname, $filter){
		$triggers = $this->context->plugins->getEventTriggers($eventname);
		foreach($triggers as $plugin){
			$filter = call_user_func(callback($this[$plugin], $eventname), $filter);
		}
		return $filter;
	}

	/** Trigger plugin event, observing returned false
	 * @param $eventname
	 * @param [$arg0]  argument to filter function 
	 * @param [$arg1]  ...
	 * @return bool    true if each event returned true
	 */
	public function triggerEvent($eventname){
		$args = array_slice(func_get_args(), 1);
		
		$triggers = $this->context->plugins->getEventTriggers($eventname);
		$ret = true;
		foreach($triggers as $plugin){
			$r = call_user_func_array(callback($this[$plugin], $eventname), $args);
			if($r === false)
				$ret = false;
		}
		return $ret;
	}

	/** Trigger plugin event, observing returned false
	 * @param $eventname
	 * @param [$arg0]  argument to filter function
	 * @param [$arg1]  ...
	 * @return bool    true if each event returned true
	 */
	public function triggerStaticEvent($eventname){
		$args = array_slice(func_get_args(), 1);

		$triggers = $this->context->plugins->getEventTriggers($eventname);
		$ret = true;
		foreach($triggers as $plugin){
			$r = call_user_func_array(callback($plugin, $eventname), $args);
			if($r === false)
				$ret = false;
		}
		return $ret;
	}


	/** Enable templates overriding by app folder, although theme folder overrides even this
	 */
	public function formatTemplateFiles() {
		$name = str_replace(":", ".", $this->getName());
		$list = parent::formatTemplateFiles();
		array_unshift($list, $this->context->params["appDir"] . "/templates/$name.$this->view.latte");
		return $list;
	}

	/** Enable layout overrides by app folder
	 */
	public function formatLayoutTemplateFiles() {
		$name = str_replace(':', '.', $this->getName());

		$arr = array();
		do {
			$arr[] = $this->context->params["appDir"] . "/templates/$name.@layout.latte";
		} while ($name = substr($name, 0, strrpos($name, '.')));
		$arr[] = $this->context->params["appDir"] . "/templates/@layout.latte";

		return array_merge($arr, parent::formatLayoutTemplateFiles());
	}


}
