<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

/** Service for processing np-macros (#-xxx-#)
 */
class NpMacros extends Object
{
  private $macros = array(
    'file' => 'NpMacros::fileMacro'
  );

  private $router;
  private $url;
  private $i18n;
  function __construct(
    array $macros,
    IRouter $router,
    HttpRequest $req,
    I18n $i18n
  ) {
    $this->router = $router;
    $this->url = $req->getUrl();
    $this->i18n = $i18n;
    $this->macros = $this->macros + $macros;
  }

  /** @var PagesModelNode */
  private $pageContext;

  function process($string, PagesModelNode $pageContext)
  {
    $this->pageContext = $pageContext;
    return preg_replace_callback(
      '~#-(.+?)-#~',
      callback($this, 'processMacro'),
      $string
    );
  }

  function processMacro($macroMatch, $onlyUrl = false)
  {
    $url = $this->processUrlMacros($macroMatch[0]);
    if ($url) {
      return $url;
    }

    $macro = $macroMatch[1];
    $pos = strpos($macro, '-');
    $macroName = $pos === false ? $macro : substr($macro, 0, $pos);
    $macroOpts = $pos === false ? '' : substr($macro, $pos + 1);

    //generic macro $sth-$opts
    if (isset($this->macros[$macroName])) {
      $macroTarget = $this->macros[$macroName];

      //is latte macro
      if (substr($macroTarget, -6) == '.latte') {
        return $this->processTemplateMacro(
          $macroName,
          $macroTarget,
          $macroOpts
        );
      }

      //is function macro
      return call_user_func($macroTarget, $macroOpts, $this->pageContext);
    }

    return "error:" . $macro;
  }

  function processUrlMacros($macro)
  {
    //p12 -> page link...  p12-de   -> specific language link
    if (preg_match('~^#-p([0-9]+)(?:-([a-z]+))?-#$~', $macro, $m)) {
      $params = array(
        'id_page' => $m[1],
        'lang' => isset($m[2]) ? $m[2] : $this->pageContext['lang']
      );

      return $this->router->constructUrl(
        new PresenterRequest('Front:Pages', 'GET', $params),
        $this->url
      );
    }

    //f12  -> file download link
    if (preg_match('~^#-f([0-9]+)-#$~', $macro, $m)) {
      return $this->router->constructUrl(
        new PresenterRequest('Front:Files', 'GET', array(
          'id' => $m[1],
          'action' => 'default'
        )),
        $this->url
      );
    }
    //TODO [feature] onmouseover could show preview+size+previewPage link

    return false;
  }

  function processTemplateMacro($macro, $file, $optstr)
  {
    $template = new FileTemplate($file);
    $template->registerFilter(Environment::getNette()->createLatte());
    $template->registerHelperLoader('TemplateHelpers::loader');
    $template->setCacheStorage(
      Environment::getContext()->nette->templateCacheStorage
    );

    $template->page = $this->pageContext; //TODO disable macros in getContent()
    $template->opts = $opts = self::parseOptions($optstr);

    //from Nette\Application\UI\Control
    $template->baseUri = $template->baseUrl = rtrim(
      $this->url->getBaseUrl(),
      '/'
    );
    $template->basePath = preg_replace(
      '#https?://[^/]+#A',
      '',
      $template->baseUrl
    );

    //lang settings
    $template->lang = $this->pageContext->lang;
    $template->langs = $this->i18n->langs;
    $template->setTranslator(new TranslationsModel($this->pageContext->lang));

    try {
      $template = $template->__toString(true);
    } catch (Exception $e) {
      if (Debugger::$productionMode) {
        Debugger::log($e);
        return "<span class='zprava'>Error: $macro not availible</span>";
      } else {
        return "<span class='zprava'>Error: " . $e->getMessage() . "</span>";
      }
    }

    return $template;
  }

  /** OptsString to array
   * @param string $optstr  - ex: '13.2_size-20'
   * @return array          - array('13.2', 'size-20', '13.2'=>true, 'size'=>20)
   */
  public static function parseOptions($optstr)
  {
    $result = explode("_", $optstr);

    foreach ($result as $key) {
      //add associative options
      $val = true;
      $pos = strpos($key, '-');
      if ($pos !== false) {
        $val = substr($key, $pos + 1);
        $key = substr($key, 0, $pos);
      }
      $result[$key] = $val;
    }
    return $result;
  }

  //file-12_fadeframe_nocache  -> render file control (video, sound, document, ...)
  function fileMacro($opts)
  {
    $opts = explode('_', $opts);
    $id = array_shift($opts);
    return FilesModel::getFile($id)->getControlHtml($opts);
  }
}
