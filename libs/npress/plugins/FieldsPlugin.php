<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */



class FieldsPlugin extends Control{
	static $events = array(
					'filterPageEditForm_create',
					'filterPageEditForm_render',
					'filterPageEditForm_values',
					'filterPageEditForm_defaults'
			);

	private $page;

	public function __construct(){
		parent::__construct();
		$this->monitor('Nette\Application\UI\Presenter');
	}


	protected function attached($presenter){
		parent::attached($presenter);
		if (!($presenter instanceof /*Nette\Application\UI\*/Presenter))
			return;

		//připojen presenter
		if(!isset($presenter->page))
						throw new InvalidStateException('ShopControl attached to uncompatible Presenter');
		$this->page = $presenter->page;
		$this->template->page = $presenter->page;
		$this->template->lang = $presenter->lang;
		$this->template->setTranslator(new TranslationsModel($presenter->lang));
	}

	function filterPageEditForm_create(AppForm $form){
		if (!$json = $this->page->getParent()->getMeta('.fields')) return $form;
		if (!$fields = json_decode($json, 1)) return $form;

		foreach($fields as $k=>$v){
			if(substr($k,-1) == '_') $k .= $this->page->lang;
			$form->addText("fields_$k", (is_array($v)&&isset($v['label']))?$v['label']:$k);
		}

		return $form;
	}

	function filterPageEditForm_defaults(AppForm $form){
		if (!$json = $this->page->getParent()->getMeta('.fields')) return $form;
		if (!$fields = json_decode($json)) return $form;

		foreach($fields as $k=>$v){
			if(substr($k,-1) == '_') $k .= $this->page->lang;
			$form["fields_$k"]->setValue($this->page->getMeta($k));
		}

		return $form;
	}
	
	function filterPageEditForm_render(AppForm $_form){
		if (!$json = $this->page->getParent()->getMeta('.fields')) return $_form;
		if (!$fields = json_decode($json)) return $_form;

		foreach($fields as $k=>$v){
			if(substr($k,-1) == '_') $k .= $this->page->lang;
				?>
				<div class="control-group">
				<?php if ($_label = $_form["fields_$k"]->getLabel()) echo $_label->addAttributes(array("class"=>"control-label" )) ?>
				<div class="controls"><?php echo $_form["fields_$k"]->getControl(); ?></div></div><?php
		}
		return $_form;
	}

	function filterPageEditForm_values($values){
		if (!$json = $this->page->getParent()->getMeta('.fields')) return $values;
		if (!$fields = json_decode($json)) return $values;

		foreach($fields as $k=>$v){
			if(substr($k,-1) == '_') $k .= $this->page->lang;
			$this->page->addMeta($k, $values["fields_$k"]);
			unset($values["fields_$k"]);
		}
		return $values;
	}




}
