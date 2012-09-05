<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */

/** Base class for front presnters
 */
abstract class Front_BasePresenter extends CommonBasePresenter {

	/** Enable theme specific layout
	 */
	public function formatLayoutTemplateFiles() {
		$list = parent::formatLayoutTemplateFiles();
		array_unshift($list, $this->context->params["themeDir"] . "/@layout.latte");
		return $list;
	}

	/** Allow to use helpers as a latte macros
	 */
	public function templatePrepareFilters($template) {
		$template->registerFilter($e = new Nette\Latte\Engine());
		$s = new Nette\Latte\Macros\MacroSet($e->compiler);
		$s->addMacro('helper', 'ob_start()',
			function($n) {
				$w = new \Nette\Latte\PhpWriter($n->tokenizer, $n->args);
				return $w->write('echo %modify(ob_get_clean())');
			}
		);
	}

}
