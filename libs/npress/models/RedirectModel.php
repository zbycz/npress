<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

class RedirectModel extends Object
{
  public static function getAll()
  {
    $res = dibi::query('SELECT * FROM redirect')->fetchAssoc('id');

    foreach ($res as &$r) {
      if ($p = RedirectModel::parseHashIdLang($r['newurl'])) {
        $r['page'] = PagesModel::getPageById($p[0], $p[1]);
      } else {
        $r['page'] = false;
      }
    }
    return $res;
  }

  public static function replace($values)
  {
    dibi::query('REPLACE INTO redirect', $values);
  }

  public static function delete($id)
  {
    dibi::query('DELETE FROM redirect WHERE id=%i', $id);
  }

  public static function hit($oldurl)
  {
    dibi::query(
      'UPDATE redirect SET hits=hits+1 WHERE %s',
      $oldurl,
      ' LIKE oldurl LIMIT 1'
    );
  }

  public static function getByOldUrl($oldurl)
  {
    return dibi::fetchSingle(
      "SELECT newurl FROM redirect WHERE %s",
      $oldurl,
      " LIKE oldurl"
    );
  }

  public static function parseHashIdLang($newurl)
  {
    if ($newurl[0] == '#') {
      return explode('-', substr($newurl, 1) . "-");
    }
    return false;
  }
}

class RedirectRouter implements IRouter
{
  function match(IHttpRequest $httpRequest)
  {
    $path = $httpRequest->getUrl()->getPath();

    //look for redirect record
    $newurl = RedirectModel::getByOldUrl($path);

    //fix pathinfo bug - strip "/index.php/" from url
    if (!$newurl and preg_match('~^/index\.php~', $path)) {
      $newurl = preg_replace('~^/index\.php~', '', $path);
    }

    if ($newurl) {
      RedirectModel::hit($path);

      if ($p = RedirectModel::parseHashIdLang($newurl)) {
        $newurl = Environment::getLinkHelper()->pageLink($p[0], $p[1]);
        if ($newurl == null) {
          return null;
        }
      }

      header("Location: $newurl", true, 301); //TODO (ask) if comented, output twice :/ better?
      echo "Moved permanetly to <a href='$newurl'>$newurl</a>";
      exit();
    }

    return null;
  }

  function constructUrl(PresenterRequest $appRequest, Url $ref)
  {
    return null;
  }
}
