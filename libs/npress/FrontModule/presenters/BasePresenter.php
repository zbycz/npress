<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

namespace FrontModule;


/** Base class for front presnters
 */
abstract class BasePresenter extends \CommonBasePresenter
{
  public function startup()
  {
    parent::startup();

    $this->triggerStaticEvent('event_Front_Base_startup', $this);
  }

  /** Enable theme specific layout
   */
  public function formatLayoutTemplateFiles()
  {
    $list = parent::formatLayoutTemplateFiles();
    array_unshift(
      $list,
      $this->context->params["npDir"] . "/FrontModule/templates/@layout.latte"
    );
    array_unshift($list, $this->context->params["themeDir"] . "/@layout.latte");
    return $list;
  }
}
