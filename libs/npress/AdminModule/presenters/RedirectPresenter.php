<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

namespace AdminModule;

use Nette\Application\UI\Form;
use RedirectModel;

/** Redirect presenter
 */
class RedirectPresenter extends BasePresenter
{
  public function startup()
  {
    parent::startup();
    $this->template->redirects = RedirectModel::getAll();
    $this->template->editid = 0;
  }

  public function handleEdit($id)
  {
    $this->template->editid = $id;
    $this['redirectEditForm']->setDefaults($this->template->redirects[$id]);
    $this->invalidateControl('redirecttable');
  }

  public function handleDelete($id)
  {
    RedirectModel::delete($id);
    $orig = $this->template->redirects[$id];

    $this->template->redirects = RedirectModel::getAll();
    $this->flashMessage("Přesměrování smazáno ($orig[oldurl]->$orig[newurl])");
    $this->invalidateControl('redirecttable');

    if (!$this->isAjax()) {
      $this->redirect('this');
    }
  }

  public function createComponentRedirectAddForm()
  {
    $form = $this->createComponentRedirectEditForm();
    return $form;
  }

  public function createComponentRedirectEditForm()
  {
    $form = new Form();
    $form->getElementPrototype()->class('ajax');

    $form->addHidden('id');
    $form->addText('oldurl', 'stará adresa');
    $form->addText('newurl', 'nová adresa');
    $form->addSubmit('submit1', 'OK');
    $form->onSuccess[] = callback($this, 'redirectEditFormSubmitted');

    return $form;
  }

  public function redirectEditFormSubmitted(Form $form)
  {
    $values = $form->values;
    RedirectModel::replace($values);

    $this->template->redirects = RedirectModel::getAll();
    $this->flashMessage('Přesměrování přidáno/upraveno');
    $this->invalidateControl('redirecttable');
    $form->setValues(array(), true);

    if (!$this->isAjax()) {
      $this->redirect('this');
    }
  }
}
