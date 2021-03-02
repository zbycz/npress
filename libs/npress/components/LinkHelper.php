<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

use Nette\Application\IRouter;
use Nette\Http\Request;
use Nette\Object;

/** Service for processing np-macros (#-xxx-#)
 */
class LinkHelper extends Object
{
  private $router;
  private $request;
  private $i18n;

  function __construct(IRouter $router, Request $request, I18n $i18n)
  {
    $this->router = $router;
    $this->request = $request;
    $this->i18n = $i18n;
  }

  /** Returns page URL or NULL
   */
  function pageLink($id, $lang = false)
  {
    if (!$lang) {
      $lang = $this->i18n->getDefaultLang();
    }

    $params = array(
      'id_page' => $id,
      'lang' => $lang
    );

    $pagesRouter = new PagesRouter(); //$this->router; then 'Front:Pages'
    $url = $pagesRouter->constructUrl(
      new Nette\Application\Request('Pages', 'GET', $params),
      $this->request->url
    );
    return $url;
  }
}
