<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

namespace FrontModule;

use Nette\Application\BadRequestException;

/** Pages display presenter
 */
class PagesPresenter extends BasePresenter
{
  /** @var PagesModelNode
   */
  public $page = false;

  public function actionDefault($id_page)
  {
    $this->page = \PagesModel::getPageById($id_page);
    if ($this->page === false) {
      throw new BadRequestException(
        "Stránka nenalezena. (id=$id_page lang=$this->lang)"
      );
    }

    if ($this->page['deleted']) {
      throw new BadRequestException(
        "Stránka smazána. (id=$id_page lang=$this->lang)"
      );
    }

    //page should display different URL?
    $this->doPageRedirects();

    $this->invalidateControl('content');

    // bread crumbs
    $this->template->crumbs = $this->page->getParents();
    $this->template->page = $this->page;
  }

  public function doPageRedirects()
  {
    $redirect = $this->page->getRedirectLink();
    if ($redirect) {
      return $this->redirectUrl($redirect);
    }
  }

  public function beforeRender()
  {
    parent::startup();

    $this->triggerStaticEvent('event_Front_Pages_beforeRender', $this);
  }

  //page specific template
  function formatTemplateFiles()
  {
    $list = parent::formatTemplateFiles();

    array_unshift(
      $list,
      $this->context->params["themeDir"] . "/defaultPage.latte"
    );

    if ($tpl = $this->page->getInheritedMeta(".sectionTemplate")) {
      array_unshift($list, $this->context->params["themeDir"] . "/$tpl.latte");
    }

    if ($tpl = $this->page->getMeta(".template")) {
      array_unshift($list, $this->context->params["themeDir"] . "/$tpl.latte");
    }

    return $list;
  }
}
