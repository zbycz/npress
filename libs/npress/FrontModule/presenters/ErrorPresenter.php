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
use Nette\Diagnostics\Debugger;

/** Error presenter.
 */
class ErrorPresenter extends BasePresenter
{
  /**
   * @param  Exception
   * @return void
   */
  public function renderDefault($exception)
  {
    if ($this->isAjax()) {
      // AJAX request? Just note this error in payload.
      $this->payload->error = true;
      $this->terminate();
    } elseif ($exception instanceof BadRequestException) {
      $code = $exception->getCode();
      $this->setView(
        in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx'
      ); // load template 403.latte or 404.latte or ... 4xx.latte
    } else {
      $this->setView('500'); // load template 500.latte
      Debugger::log($exception, Debugger::ERROR); // and log exception
    }
  }
}
