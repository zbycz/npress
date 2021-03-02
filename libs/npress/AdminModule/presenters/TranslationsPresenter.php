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
use TranslationsModel;

/** Translations presenter
 */
class TranslationsPresenter extends BasePresenter
{
  public function startup()
  {
    parent::startup();
    $this->template->translations = TranslationsModel::getAll();
    $this->template->editid = 0;
  }

  public function handleEdit($id)
  {
    $this->template->editid = $id;
    $this['translationsEditForm']->setDefaults(
      $this->template->translations[$id]
    );
    $this->invalidateControl('translationstable');
  }

  public function handleDelete($id)
  {
    TranslationsModel::delete($id);

    $this->template->translations = TranslationsModel::getAll();
    $this->flashMessage("Překlad smazán"); //TODO undolink
    $this->invalidateControl('translationstable');

    if (!$this->isAjax()) {
      $this->redirect('this');
    }
  }

  public function createComponentTranslationsEditForm()
  {
    $form = $this->createComponentTranslationsAddForm();
    $form['key']->type = 'hidden';
    //			getControlPrototype()->readonly('readonly');
    return $form;
  }

  public function createComponentTranslationsAddForm()
  {
    $form = new Form();
    $form->getElementPrototype()->class('ajax');

    $form->addHidden('id');
    $form->addText('key', 'Klíč');

    foreach ($this->context->params["langs"] as $l => $txt) {
      $form->addText($l, $txt);
    }
    $form->addSubmit('submit1', 'OK');
    $form->onSuccess[] = callback($this, 'editFormSubmitted');

    return $form;
  }

  public function editFormSubmitted(AppForm $form)
  {
    $values = $form->values;
    TranslationsModel::replace($values);

    $this->template->translations = TranslationsModel::getAll();
    $this->flashMessage('Překlad přidán/upraven');
    $this->invalidateControl('translationstable');
    $form->setValues(array(), true);

    if (!$this->isAjax()) {
      $this->redirect('this');
    }
  }
}
