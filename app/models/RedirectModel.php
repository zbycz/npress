<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


class RedirectModel extends Object {

	public static function getAll(){
		return dibi::query('SELECT * FROM redirect')->fetchAssoc('id');
	}

	public static function replace($values){
		dibi::query('REPLACE INTO redirect', $values);
	}

	public static function delete($id){
		dibi::query('DELETE FROM redirect WHERE id=%i', $id);
	}

	public static function hit($oldurl){
		dibi::query('UPDATE redirect SET hits=hits+1 WHERE oldurl=%s',$oldurl);
	}
	
	public static function getByOldUrl($oldurl){
		return dibi::fetchSingle("SELECT newurl FROM redirect WHERE oldurl=%s",$oldurl);
	}

}



class RedirectRouter implements IRouter {
	function match(IHttpRequest $httpRequest){
		$path = $httpRequest->getUrl()->getPath();
		$newurl = RedirectModel::getByOldUrl($path);
		if($newurl){
			if($newurl{0} == '#'){
				list($id,$lang) = explode('-', substr($newurl,1)."-");
				$page = PagesModel::getPageById($id, $lang);
				if(!$page) return NULL;
				$newurl = $page['seoname'];
				//TODO implementovat np-url-macra
			}
			header("Location: $newurl", true, 301); //TODO (ask) if comented, output twice :/ better?
			echo "Moved permanetly to <a href='$newurl'>$newurl</a>";
			RedirectModel::hit($path);
			exit;
		}

		return NULL;
	}

	function constructUrl(PresenterRequest $appRequest, Url $ref) {
		return NULL;
	}

}

