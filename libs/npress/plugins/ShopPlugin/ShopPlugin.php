<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

class ShopPlugin extends Control
{
  static $events = array(
    'displayPageContent',
    'renderAdminList',
    'filterPageEditForm_create',
    'filterPageEditForm_render',
    'filterPageEditForm_values',
    'filterPageEditForm_defaults'
  );
  public $currency = "Kč";

  private $page;

  public function __construct()
  {
    parent::__construct();
    $this->monitor('Nette\Application\UI\Presenter');
  }

  protected function attached($presenter)
  {
    parent::attached($presenter);
    if (!($presenter instanceof /*Nette\Application\UI\*/ Presenter)) {
      return;
    }

    //připojen presenter
    if (!isset($presenter->page)) {
      throw new InvalidStateException(
        'ShopControl attached to uncompatible Presenter'
      );
    }
    $this->page = $presenter->page;
    $this->template->page = $presenter->page;
    $this->template->lang = $presenter->lang;
    $this->template->setTranslator(new TranslationsModel($presenter->lang));
  }

  function filterPageEditForm_create(AppForm $form)
  {
    if ($this->page->getParent()->getMeta('.category') == 'shop') {
      $form->addSelect(
        "id_parent_change",
        "kategorie",
        PagesModel::getPagesByMeta('.category', 'shop')->getPairs('')
      );
      $form->addText("price", "cena");
      $form->addCheckbox("akce", "akce");
    }
    return $form;
  }

  function filterPageEditForm_defaults(AppForm $form)
  {
    if ($this->page->getParent()->getMeta('.category') == 'shop') {
      $form['price']->setValue($this->page->getMeta('price'));
      $form['akce']->setValue($this->page->getMeta('akce'));
      $form['id_parent_change']->setValue($this->page['id_parent']);
    }
    return $form;
  }

  function filterPageEditForm_render(AppForm $_form)
  {
    if ($this->page->getParent()->getMeta('.category') == 'shop') { ?>
				<div class="control-group">
				<?php if ($_label = $_form["id_parent_change"]->getLabel()) {
      echo $_label->addAttributes(array("class" => "control-label"));
    } ?>
				<div class="controls"><?php echo $_form[
      "id_parent_change"
    ]->getControl(); ?></div></div><?php  ?>
				<div class="control-group">
				<?php if ($_label = $_form["price"]->getLabel()) {
      echo $_label->addAttributes(array("class" => "control-label"));
    } ?>
				<div class="controls"><?php echo $_form[
      "price"
    ]->getControl(); ?> Kč</div></div><?php  ?>
				<div class="control-group">
				<?php if ($_label = $_form["akce"]->getLabel()) {
      echo $_label->addAttributes(array("class" => "control-label"));
    } ?>
				<div class="controls"><?php echo $_form[
      "akce"
    ]->getControl(); ?></div></div><?php }
    return $_form;
  }

  function filterPageEditForm_values($values)
  {
    if ($this->page->getParent()->getMeta('.category') == 'shop') {
      $values['id_parent'] = $values['id_parent_change'];
      $this->page->addMeta('price', $values['price']);
      $this->page->addMeta('akce', $values['akce']);
      unset($values['akce']);
      unset($values['id_parent_change']);
      unset($values['price']);
    }
    return $values;
  }

  function displayPageContent()
  {
    if ($this->page->getMeta('.category') == 'shop') {
      $this->renderList();
      return false;
    }
    if ($this->page->getParent()->getMeta('.category') == 'shop') {
      $this->renderProduct();
      return false;
    }
  }

  public function renderList()
  {
    if ($this->page->id == 8) {
      $this->template->products = array();
      foreach ($this->page->getChildNodes() as $r) {
        $this->template->products = array_merge(
          $this->template->products,
          $r->getChildNodes()
        );
      }
    } else {
      $this->template->products = $this->page->getChildNodes();
    }

    $this->template->setFile(dirname(__FILE__) . '/list.latte');
    $this->template->render();

    //TODO funkce která vrátí "upravenou" šablonu - proměnné, macra, atd
    //TODO [ask] jak DI??
  }
  public function renderProduct()
  {
    $this->template->setFile(dirname(__FILE__) . '/product.latte');
    $this->template->render();
  }

  public function renderAkce()
  {
    $this->template->products = PagesModel::getPagesByMeta('akce', '1');

    $this->template->setFile(dirname(__FILE__) . '/list.latte');
    $this->template->render();
  }

  public function renderAdminList()
  {
    if ($this->page->getMeta('.category') == 'shop') {
      $this->template->setFile(dirname(__FILE__) . '/adminList.latte');
      $this->template->page = $this->parent->page;
      $this->template->render();
    }
  }
}
