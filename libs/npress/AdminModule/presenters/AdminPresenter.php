<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

namespace AdminModule;

use PagesModel;

class AdminPresenter extends BasePresenter
{
  public function actionPagesort()
  {
    //TODO rather use a signal
    if ($this->isAjax()) {
      PagesModel::nestedsort(
        $this->getHttpRequest()->post['menuid'],
        $this->lang
      );
      $this->flashMessage("Menu upraveno");
    }
    $this->setView('default');
  }

  public function actionLogout()
  {
    $this->user->logout();
    $this->flashMessage("Odhlášení proběhlo úspěšně");
    $this->redirect(":Front:Login:");
  }
}
