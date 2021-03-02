<?php

use Nette\Environment;
use Nette\Object;
use Nette\Utils\Finder;

/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

class FilesModel extends Object
{
  public static $cache = array();
  public static $cacheByPage = array();

  public static $types = array(
    "ImageFile",
    "MapsFile",
    "DocumentFile",
    "SoundFile",
    "VideoFile",
    "ArchiveFile",
    "ActiveSourceFile",
    "UnknownFile"
    //TODO sourceCodeFile
  );

  /** Get files by id_page (not expanding dot syntax)
   * @deprecated
   */
  public static function getFiles($id_page)
  {
    if (!isset(self::$cacheByPage[$id_page])) {
      self::$cacheByPage[$id_page] = array();

      $result = dibi::query(
        'SELECT * FROM pages_files WHERE id_page = %i',
        $id_page,
        ' AND deleted=0 ORDER BY gallerynum,ord'
      );
      foreach ($result as $r) {
        self::$cacheByPage[$id_page][] = self::createFileObject($r);
      }
    }

    return self::$cacheByPage[$id_page];
  }

  public static function getGalleries($id_page, $addBlank = false)
  {
    $galleries = array();
    $maxnum = 0;
    foreach (self::getFiles($id_page) as $f) {
      $k = intval($f->gallerynum);
      if (!isset($galleries[$k])) {
        $galleries[$k] = array();
      }
      $galleries[$k][] = $f;
      $maxnum = max($maxnum, $k);
    }

    //add blank galleries and one more
    if ($addBlank) {
      for ($i = 0; $i <= $maxnum + 1; $i++) {
        if (!isset($galleries[$i])) {
          $galleries[$i] = array();
        }
      }
    }

    ksort($galleries);
    return $galleries;
  }

  public static function getByPageDotGallery(
    $pid,
    $paginator = false,
    $order = 'ord'
  ) {
    return self::getFilesWhere(array('id_page' => $pid), $paginator, $order);
  }

  /** Get array of File by custom where clause
   * ex: array('YEAR(timestamp)=2012', 'id_page'=>'12.1', array('filesize > %i',1000))
   * @param array $sqlwhere implicitly deleted=0, id_page with dot expands to gallerynum
   * @param [VisualPaginator] $paginator
   * @param string $order
   */
  public static function getFilesWhere(
    array $sqlwhere,
    $paginator = false,
    $order = 'ord'
  ) {
    if (!isset($sqlwhere['deleted'])) {
      $sqlwhere['deleted'] = 0;
    }

    if (isset($sqlwhere['id_page'])) {
      $pos = strpos($sqlwhere['id_page'], '.');
      if ($pos !== false) {
        $sqlwhere['gallerynum'] = (int) substr($sqlwhere['id_page'], $pos + 1);
        $sqlwhere['id_page'] = (int) substr($sqlwhere['id_page'], 0, $pos);
      }
    }

    $output = array();
    $result = dibi::query(
      'SELECT * FROM pages_files	WHERE %and',
      $sqlwhere,
      ' ORDER BY %sql',
      $order
    );

    if ($paginator) {
      $paginator->itemCount = count($result);
      $result = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
    }

    foreach ($result as $r) {
      $output[] = self::createFileObject($r);
    }

    return $output;
  }

  public static function getFile($id)
  {
    //TODO cache
    if (isset(self::$cache[$id])) {
      return self::$cache[$id];
    }

    $r = dibi::fetch('SELECT * FROM pages_files WHERE id = %i', $id);
    return $r ? self::createFileObject($r) : false;
  }

  /** Get one file by filename
   * @param int $id_page
   * @param string $filename  filename without extension
   * @return File
   */
  public static function getFileByName($id_page, $filename)
  {
    //TODO cache
    $pathinfo = pathinfo($filename);
    $r = dibi::fetch('SELECT * FROM pages_files WHERE %and', array(
      'id_page' => $id_page,
      'filename' => $pathinfo['filename'],
      'suffix' => $pathinfo['extension']
    ));
    return $r ? self::createFileObject($r) : false;
  }

  public static function getFileByOrigpath($origpath)
  {
    //TODO cache
    $r = dibi::fetch('SELECT * FROM pages_files WHERE %and', array(
      'origpath' => $origpath
    ));
    if ($r) {
      return self::createFileObject($r);
    }
    return false;
  }

  public static function delete($id)
  {
    dibi::query('DELETE FROM pages_files WHERE id=%i', $id);
  }

  public static function sort($data)
  {
    if (
      isset($data['changedId']) and ($f = self::getFile($data['changedId']))
    ) {
      //update position DOWN of removed gallery
      dibi::query(
        'UPDATE pages_files
				SET ord = ord-1
				WHERE id_page = %i',
        $f->id_page,
        '
					AND gallerynum = %i',
        $f->gallerynum,
        '
					AND ord > %i',
        $f->ord
      );

      //insert into new gallery
      $f->gallerynum = (int) $data['num'];
      $f->save();

      //update position UP of new gallery (hidden files not influenced by the bottom one)
      dibi::query(
        'UPDATE pages_files
				SET ord = ord+1
				WHERE id_page = %i',
        $f->id_page,
        '
					AND gallerynum = %i',
        $f->gallerynum,
        '
					AND ord > %i',
        $f->ord
      );
    }

    //TODO maybe? SET ord = CASE id WHEN 10 THEN 1 END CASE
    $order = 0;
    foreach ($data['fileid'] as $id) {
      dibi::query(
        'UPDATE pages_files SET ord=%i',
        $order++,
        ' WHERE id=%i',
        $id
      );
    }

    self::$cache = array();
    self::$cacheByPage = array();
  }

  public static function edit($data)
  {
    return dibi::query(
      'UPDATE pages_files SET',
      $data,
      ' WHERE id = %i',
      $data['id']
    );
  }

  public static function createNew($id_page, $path, $options = '')
  {
    //insert into database
    $pathinfo = pathinfo($path);
    $ord = 0;
    if ($options == 'end') {
      $ord =
        1 +
        (int) dibi::fetchSingle(
          'SELECT max(ord) FROM pages_files WHERE id_page=%i',
          $id_page,
          ' AND gallerynum=0'
        );
    } else {
      dibi::query(
        'UPDATE pages_files SET ord=ord+1 WHERE id_page=%i',
        $id_page,
        ' AND gallerynum=0'
      );
    }

    dibi::query('INSERT INTO pages_files', array(
      'id_page' => $id_page,
      'filename' => $pathinfo['filename'],
      'suffix' => isset($pathinfo['extension'])
        ? strtolower($pathinfo['extension'])
        : '',
      'ord' => $ord,
      'filesize' => 0,
      'description' => '',
      'keywords' => '',
      'info' => '',
      'dimensions' => 0,
      'timestamp' => 0,
      'origpath' => 0,
      'deleted' => 0,
      'gallerynum' => 0
    ));

    //instance of File (or the specific file type)
    return self::getFile(dibi::insertId());
  }

  public static function upload($id_page, $upFile)
  {
    $f = self::createNew($id_page, $upFile->name);
    $f->filesize = $upFile->size;
    $f->save();

    //move original to final destination
    $upFile->move($f->getDownloadPath());

    $f->generatePreviewImage();
  }

  public static function filesSync($id_page, $syncDir)
  {
    $log = array('new' => array(), 'changed' => array());
    $dir = Environment::getVariable('dataDir');

    foreach (
      Finder::findFiles('*')->from("$dir/filesSync/$syncDir")
      as $fullpath
    ) {
      $origpath = str_replace($dir, '', $fullpath);

      $f = self::getFileByOrigpath($origpath);

      $new = false;
      if (!$f) {
        $new = true;
        $f = self::createNew($id_page, $origpath, 'end');
        $f->origpath = str_replace('\\', '/', $origpath);
        $f->visible = 1;
      }

      if ($new or $f->fileMetricsChanged()) {
        $f->fileMetricsUpdate(); //calls save()  (for the origpath above)
        $f->generatePreviewImage();
        $f->clearPreviewCache();

        $log[$new ? 'new' : 'changed'][] = $f->name;
      }
    }

    return $log;
  }

  public static function sortFilesBy($id_page, $orderBy)
  {
    $result = dibi::query('SELECT * FROM pages_files ORDER BY %by', $orderBy);

    $i = 0;
    foreach ($result as $r) {
      dibi::query(
        "UPDATE pages_files SET ord = %i",
        $i++,
        " WHERE id=%i",
        $r['id']
      );
    }
  }

  public static function uploadPreview($r)
  {
    $file = self::getFile($r['id']); //instance of File (or the specific file type)
    $r['file']->move($file->getPreviewPath()); //move preview to its location

    $info = ImageFile::getImageInfo($file->getPreviewPath());
    $file->data['dimensions'] = $info["wxh"];
    $file->save();

    $file->clearPreviewCache();
  }

  private static $suffixCache;

  /** Returns new File object or cached instance by id
   * @param type $data sql row
   * @return instanceof File
   */
  private static function createFileObject($data)
  {
    if (isset(self::$cache[$data['id']])) {
      return self::$cache[$data['id']];
    }

    //generate quick access suffixes table
    if (!self::$suffixCache) {
      foreach (self::$types as $type) {
        $fileObjectVars = get_class_vars($type); //$type must exist!
        $suffixes = explode(',', $fileObjectVars['suffixes']);
        foreach ($suffixes as $suf) {
          self::$suffixCache[$suf] = $type;
        }
      }
    }

    //find right class by suffix
    if (isset(self::$suffixCache[$data['suffix']])) {
      $class = self::$suffixCache[$data['suffix']];
      $obj = new $class($data);
    } else {
      $obj = new UnknownFile($data);
    }

    self::$cache[$obj->id] = $obj; //cache by id
    return $obj;
  }
}
