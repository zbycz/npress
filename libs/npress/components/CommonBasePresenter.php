<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

use Nette\Application\UI\Presenter;

/** Base presenter for both Admin and Front module
 *
 * @author     Pavel Zbytovský (pavel@zby.cz)
 * @package    nPress
 */
abstract class CommonBasePresenter extends Presenter
{
  /** @persistent */
  public $lang = 'cs'; //TODO from config (https://github.com/nette/nette/pull/445)

  public $pages;

  public function startup()
  {
    parent::startup();

    PagesModel::$lang = $this->lang;
    $this->pages = PagesModel::getRoot();
  }

  public function createTemplate($class = null)
  {
    $template = parent::createTemplate($class);

    //lang settings
    $template->lang = $this->lang;
    $template->langs = $this->context->parameters["langs"];
    $template->setTranslator(new TranslationsModel($this->lang));

    //pages tree
    $template->pages = $this->pages;
    $template->crumbs = array();

    //configuration
    $template->config = $this->context->parameters['npress'];
    $template->frontjslatte =
      $this->context->parameters['npDir'] . '/FrontModule/templates/frontjs.latte';
    $template->npLayoutFile =
      $this->context->parameters['npDir'] . '/FrontModule/templates/@layout.latte';

    //bardump(array($this->formatLayoutTemplateFiles(), $this->formatTemplateFiles()));
    
    $template->getLatte()->addFilter('timeago', function (
      $s,
      $format = false,
      $formatAfterDays = 15
    ) {
      return Helpers::timeAgoInWords($s, $format, $formatAfterDays);
    });

    $template->getLatte()->addFilter('modified', function ($s) {
      return $s . "?" . dechex(filemtime(WWW_DIR . $s));
    });

    $template->getLatte()->addFilter('talkstub', function ($mail) {
      return strstr($mail, '@', true) . "-" . substr(md5($mail), -5);
    });

    $template->getLatte()->addFilter('gravatar', function ($mail) {
      return "https://www.gravatar.com/avatar/" . md5(strtolower($mail)) . "?s=32&d=mm";
    });

    $template->getLatte()->addFilter('excerpt', function ($text, $query) {
      return Helpers::excerpt($text, $query);
    });

    $template->getLatte()->addFilter('talkMailBody', function ($s) {
      return Helpers::talkMailBody($s);
    });

    $template->getLatte()->addFilter('czechMonth', function ($num) {
      $mesicetxt = array(
        1 => "Leden",
        "Únor",
        "Březen",
        "Duben",
        "Květen",
        "Červen",
        "Červenec",
        "Srpen",
        "Září",
        "Říjen",
        "Listopad",
        "Prosinec"
      );
      return $mesicetxt[$num];
    });
    
    return $template;
  }

  // Link to page mutation, or homepage
  public function langSwitch($l)
  {
    if (isset($this->page) and $this->page->lang($l)) {
      return $this->link('this', array('lang' => $l));
    } //other lang mutation
    return $this->link('Pages:', array('lang' => $l)); //default page
  }

  //returns true also for "category" parent
  public function isCurrent($link_page_id)
  {
    if (!isset($this->page) or $this->page == false) {
      return false;
    }
    if ($link_page_id == $this->page->id) {
      return true;
    }
    if (
      $link_page_id == $this->page->getParent()->id and
      $this->page->getParent()->getMeta('.category')
    ) {
      return true;
    }
    return false;
  }

  //Allow Plugins as direct components of presenter
  protected function createComponent($name)
  {
    $plugins = $this->context->plugins->getPlugins();
    if (in_array($name, $plugins)) {
      return new $name();
    }

    return parent::createComponent($name);
  }

/*
  //Allow to use helpers as a latte macros
  public function templatePrepareFilters($template)
  {
    $e = $template->getEngine();
    $s = new Nette\Latte\Macros\MacroSet($e->getCompiler());
    $s->addMacro('helper', 'ob_start()', function ($n) {
      $w = new \Nette\Latte\PhpWriter($n->tokenizer, $n->args);
      return $w->write('echo %modify(ob_get_clean())');
    });
    //$template->registerFilter($e);
    //
  }
 */

  /** Trigger plugin event as filter supplied value
   * @param $eventname
   * @param $filter    value to supply to filter chain
   * @return string    resulting value
   */
  public function triggerEvent_filter($eventname, $filter)
  {
    $triggers = $this->context->plugins->getEventTriggers($eventname);
    foreach ($triggers as $plugin) {
      $filter = call_user_func(callback($this[$plugin], $eventname), $filter);
    }
    return $filter;
  }

  /** Trigger plugin event, observing returned false
   * @param $eventname
   * @param [$arg0]  argument to filter function
   * @param [$arg1]  ...
   * @return bool    true if each event returned true
   */
  public function triggerEvent($eventname)
  {
    $args = array_slice(func_get_args(), 1);

    $triggers = $this->context->plugins->getEventTriggers($eventname);
    $ret = true;
    foreach ($triggers as $plugin) {
      $r = call_user_func_array(callback($this[$plugin], $eventname), $args);
      if ($r === false) {
        $ret = false;
      }
    }
    return $ret;
  }

  /** Trigger plugin event, observing returned false
   * @param $eventname
   * @param [$arg0]  argument to filter function
   * @param [$arg1]  ...
   * @return bool    true if each event returned true
   */
  public function triggerStaticEvent($eventname)
  {
    $args = array_slice(func_get_args(), 1);

    $triggers = $this->context->plugins->getEventTriggers($eventname);
    $ret = true;
    foreach ($triggers as $plugin) {
      $r = call_user_func_array(callback($plugin, $eventname), $args);
      if ($r === false) {
        $ret = false;
      }
    }
    return $ret;
  }

  /** Enable templates overriding by app folder, although theme folder overrides even this
   */
  public function formatTemplateFiles()
  {
    $name = str_replace(":", ".", $this->getName());
    $list = parent::formatTemplateFiles();
    array_unshift(
      $list,
      $this->context->parameters["appDir"] . "/templates/$name.$this->view.latte"
    );
    return $list;
  }

  /** Enable layout overrides by app folder
   */
  public function formatLayoutTemplateFiles()
  {
    $name = str_replace(':', '.', $this->getName());

    $arr = array();
    do {
      $arr[] =
        $this->context->parameters["appDir"] . "/templates/$name.@layout.latte";
    } while ($name = substr($name, 0, strrpos($name, '.')));
    $arr[] = $this->context->parameters["appDir"] . "/templates/@layout.latte";

    return array_merge($arr, parent::formatLayoutTemplateFiles());
  }
}
