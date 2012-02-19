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

}
