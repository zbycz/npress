<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

namespace AdminModule;

use FilesModel;
use MetaControl;
use Nette\Application\UI\Form;
use NpFilesControl;
use PagesModel;
use PagesRouter;

/** Pages admin presenter
 */
class PagesPresenter extends BasePresenter
{
  public $page;

  public function editAllowed($id = false)
  {
    if ($id === false) {
      $id = $this->page->id;
    }

    if ($this->triggerStaticEvent('allow_page_edit', $this, $id)) {
      return true;
    }

    $this->flashMessage('Nedostatečné oprávnění pro editaci této stránky.');
    if (!$this->isAjax()) {
      if ($this->action == 'add') {
        $this->redirect('Admin:');
      } else {
        $this->redirect('this');
      }
    }
    return false;
  }

  public function actionDefault()
  {
    $this->redirect("Admin:");
  }
  public function actionTrash()
  {
    $this->template->deletedPages = PagesModel::getDeletedPages();
  }
  public function actionAdd($id_parent, $sibling = false)
  {
    if ($id_parent) {
      $page = PagesModel::getPageById($id_parent);
      if (!$page) {
        return $this->displayMissingPage($id_parent, $this->lang);
      }

      if ($sibling and ($page = $page->getParent())) {
        $id_parent = $page->id;
      }
    }

    $id_parent = intval($id_parent) ? intval($id_parent) : 0;
    if (!$this->editAllowed($id_parent)) {
      return;
    }

    $newid = PagesModel::addPage(array(
      'id_parent' => $id_parent,
      'lang' => $this->lang,
      'text' => '',
      'ord' => 0,
      'published' => 0,
      'deleted' => 0
    ));
    $this->redirect('edit#newpage', $newid);
  }

  public function displayMissingPage($id_page, $lang)
  {
    $this->template->error_id_page = $id_page;
    $this->template->error_lang = $lang;
    $this->setView('error');
  }

  public function actionEdit($id_page)
  {
    if ($id_page == 0) {
      $this->redirect("Admin:");
    }
    $this->page = PagesModel::getPageById($id_page);

    //creating or deriving page from another
    $fromLang = $this->getParam('from');
    if ($fromLang) {
      $fromPage = PagesModel::getPageById($id_page, $fromLang);
      if (!$fromPage) {
        return $this->displayMissingPage($id_page, $fromLang);
      } else {
        $this->template->fromPage = $fromPage;
      }

      if (!$this->page) {
        $this->page = $fromPage->createLang($this->lang);
      }
      //TODO PagesMode::$cache[0][$this->fromLang] = root ve fromLang jazyce neexistuje!!! tzn instancovat a vytvořit v konstuktoru
    }

    //page still doesnt exist? error
    if (!$this->page) {
      return $this->displayMissingPage($id_page, $this->lang);
    } else {
      $this->template->page = $this->page;
    }

    // bread crumbs
    $this->template->crumbs = $this->page->getParents();

    //default values for editform
    $form = $this['pageEditForm'];
    if (!$form->isSubmitted()) {
      $form->setValues($this->page->data);
      $this->triggerEvent_filter('filterPageEditForm_defaults', $form);
    }
  }

  //page-edit form
  public function createComponentPageEditForm()
  {
    $form = new Form();
    $form->getElementPrototype()->class('ajax');

    $form->addHidden("id_page");

    $form->addText("heading", "nadpis");
    $form->addText("name", "v menu");
    $form
      ->addText("seoname", "adresa")
      ->setAttribute('placeholder', '(automatická)');

    $form->addCheckbox("published", "publikováno");

    $form->addTextArea("text", "obsah stránky");
    $form['text']->getControlPrototype()->class('textarea');

    $form->addSubmit("submit1", "Uložit");
    $form->onSuccess[] = callback($this, 'pageEditFormSubmitted');

    //add additional inputs
    $this->triggerEvent_filter('filterPageEditForm_create', $form);

    return $form;
  }

  public function pageEditFormSubmitted(Form $form)
  {
    if (!$this->editAllowed()) {
      return;
    }

    $values = (array) $form->values;
    $values['text'] = preg_replace_callback(
      '~#-(.+?)-#~',
      array($this, 'npMacroControlOptions'),
      $values['text']
    );

    //name field may be 'disabled' by checkbox
    if (!$values['name']) {
      $values['name'] = $values['heading'];
    }

    //handle additional input values
    $values = $this->triggerEvent_filter('filterPageEditForm_values', $values);

    //menu control changes only sometimes
    if (
      $this->page['published'] != $values['published'] or
      $this->page['name'] != $values['name'] or
      $this->page['heading'] != $values['heading']
    ) {
      $this->invalidateControl('menu');
    }

    //url must be unique
    $i = 1;
    $newname = $values['seoname'];
    while (!PagesRouter::isSeonameOk($newname, $this->page)) {
      $newname = $values['seoname'] . '-' . $i++;
    }
    $values['seoname'] = $newname;

    //update the new URL in the form
    $form['seoname']->value = $newname;
    $this->invalidateControl('editform_seoname');

    //save values
    PagesModel::addVersion(
      $values['id_page'],
      $this->lang,
      $values['name'],
      $values['seoname'],
      $values['heading'],
      $values['text']
    );
    unset($values['id_page']);
    $this->page->save($values);
    $this->flashMessage('Obsah stránky uložen (' . date('y-m-d H:i:s') . ')');

    if (!$this->isAjax()) {
      $this->redirect('this');
    }
  }

  public function npMacroControlOptions($macro)
  {
    //TODO combine with Front_BasePresenter (extract to class NpMacros)
    if (preg_match('~^file-([0-9]+)(_.+)?$~', $macro[1], $m)) {
      return "#-file-$m[1]" .
        FilesModel::getFile($m[1])->getControlMacroOptions(
          isset($m[2]) ? $m[2] : null
        ) .
        "-#";
    } else {
      return $macro[0];
    }
  }

  //delete & undelete
  public function handleDeletePage($undo = false)
  {
    if (!$this->editAllowed()) {
      return;
    }

    if ($undo) {
      $this->page->undelete();
    } else {
      $this->page->delete();
    }

    $this->flashMessage($undo ? "Stránka navrácena zpět" : "Stránka smazána");
    $this->invalidateControl('editform_deleted');
    $this->invalidateControl('menu');
    if (!$this->isAjax()) {
      $this->redirect("this");
    }
  }

  public function actionHistory($id_page)
  {
    $this->page = PagesModel::getPageById($id_page);
    if (!$this->page) {
      return $this->displayMissingPage($id_page, $this->lang);
    }

    $this->template->page = $this->page;
    $this->template->pagesHistory = PagesModel::getAllVersions();
  }

  public function handleRevertVersionUndo($id_page)
  {
    if (!$this->editAllowed()) {
      return;
    }

    PagesModel::deleteLastVersion($id_page); // this version was just created in revertVersion!

    $lastVersion = PagesModel::getLastVersion($id_page);
    $this->page->save(array(
      "name" => $lastVersion->name,
      "seoname" => $lastVersion->seoname,
      "heading" => $lastVersion->heading,
      "text" => $lastVersion->text
    ));

    $this->flashMessage('Návrat na předchozí verzi byl zrušen', 'danger');
    $this->redirect('edit', $id_page);
  }

  public function handleRevertVersion($id_version)
  {
    if (!$this->editAllowed()) {
      return;
    }

    $version = PagesModel::getVersionData($id_version);
    $this->page->save(array(
      "name" => $version->name,
      "seoname" => $version->seoname,
      "heading" => $version->heading,
      "text" => $version->text
    ));
    PagesModel::addVersion(
      $version->id_page,
      $version->lang,
      $version->name,
      $version->seoname,
      $version->heading,
      $version->text
    );

    $undolink = $this->link('revertVersionUndo!', $version->id_page);
    $flashMessage = $this->flashMessage(
      'Obsah stranky z ' . $version->updated_at . ' ',
      'danger'
    );
    $flashMessage->undolink = $undolink;
    $this->redirect('edit', $this->page->id);
  }

  //subpageslist - sorting
  public function handleSubpagessort()
  {
    if (!$this->editAllowed()) {
      return;
    }

    PagesModel::sort($this->getHttpRequest()->post['pageid'], $this->lang);
    $this->flashMessage("Pořadí článků upraveno");
    //$this->payload->hack = 'ok';
  }

  //npFiles control
  protected function createComponentNpFilesControl()
  {
    return new NpFilesControl($this->context->httpRequest, $this->page);
  }

  //meta control
  protected function createComponentMetaControl()
  {
    return new MetaControl();
  }
}
