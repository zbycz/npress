<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

//Page meta .fileds = {"date":{"label":"datum"}} --> all child pages have these form fileds
class FieldsPlugin extends Control
{
  static $events = array(
    'filterPageEditForm_create',
    'filterPageEditForm_render',
    'filterPageEditForm_values',
    'filterPageEditForm_defaults'
  );

  private $page;
  private $fields = array();
  private $error = "";

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
        'FieldsPlugin attached to uncompatible Presenter'
      );
    }
    $this->page = $presenter->page;
    $this->template->page = $presenter->page;
    $this->template->lang = $presenter->lang;
    $this->template->setTranslator(new TranslationsModel($presenter->lang));

    //parse fields
    $json = $this->page->getParent()->getMeta('.sectionFields');
    $sectionFields = array();
    try {
      if ($json) {
        $sectionFields = Neon::decode($json);
      }
    } catch (Exception $e) {
      $m = htmlspecialchars($e->getMessage());
      $this->error .= "<div class='control-group' title=\"$m\">.sectionFields not valid</div>";
    }

    $fields = array();
    $json = $this->page->getMeta('.fields');
    try {
      if ($json) {
        $fields = Neon::decode($json);
      }
    } catch (Exception $e) {
      $m = htmlspecialchars($e->getMessage() . "\"");
      $this->error .= "<div class='control-group' title=\"$m\">.fields not valid</div>";
    }

    $fields = array_merge($fields, $sectionFields);
    foreach ($fields as $k => $f) {
      if (substr($k, -1) == '_') {
        $k .= $this->page->lang;
      }
      $this->fields[$k] = $f;
    }
  }

  function filterPageEditForm_create(AppForm $form)
  {
    foreach ($this->fields as $k => $v) {
      $label = is_array($v) && isset($v['label']) ? $v['label'] : $k;

      if (isset($v['type']) and $v['type'] == "textarea") {
        $form->addTextarea("fields_$k", $label);
      } elseif (isset($v['type']) and $v['type'] == "checkbox") {
        $form->addCheckbox("fields_$k", $label);
      } else {
        $form->addText("fields_$k", $label);
      }
    }

    return $form;
  }

  function filterPageEditForm_defaults(AppForm $form)
  {
    foreach ($this->fields as $k => $v) {
      $form["fields_$k"]->setValue($this->page->getMeta($k));
    }

    return $form;
  }

  function filterPageEditForm_render(AppForm $_form)
  {
    echo $this->error;
    foreach ($this->fields as $k => $v) { ?>
				<div class="control-group">
				<?php if ($_label = $_form["fields_$k"]->getLabel()) {
      echo $_label->addAttributes(array("class" => "control-label"));
    } ?>
				<div class="controls"><?php echo $_form["fields_$k"]->getControl(); ?>
					<span class="help-inline"><?php echo @$v['desc']; ?></span>
					</div></div><?php }
    return $_form;
  }

  function filterPageEditForm_values($values)
  {
    foreach ($this->fields as $k => $v) {
      $val = $values["fields_$k"];
      unset($values["fields_$k"]);

      if (
        isset($v['type']) and
        $v['type'] == 'link' and
        !preg_match('~^[a-z]+://~i', $val)
      ) {
        $val = "http://$val";
      }

      $this->page->addMeta($k, $val);
    }
    return $values;
  }
}
