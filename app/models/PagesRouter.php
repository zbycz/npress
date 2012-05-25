<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


//Instantiated also in LinkHelper service
class PagesRouter implements IRouter
{
	const PRESENTER = 'Pages';

	static function isSeonameOk($link, $page){
		if(empty($link) OR $link{0} != '/')
			return true;
		
		$found = PagesModel::getPageBySeoname($link, $page->lang);
		return ($found == false OR $found->id == $page->id);
	}

	function getDefaultLang(){
		return 'cs'; //TODO get from config (also in CommonBasePresenter)
	}

	function match(IHttpRequest $httpRequest){
		$url = $httpRequest->getUrl();

		$lang = $this->getDefaultLang();
		$langs = Environment::getVariable("langs");
		$langDomains = Environment::getVariable("langDomains");

		//domains for languages or just directory prefix?
		if(count($langDomains)){
			$path = '//' . $url->getHost() . $url->getPath();
			foreach($langDomains as $lang=>$pattern){
				$pattern = preg_quote($pattern, '~');
				$pattern = preg_replace('~^//(www\\.)?~', '//(www\.)?', $pattern); //www not mandatory
				if(preg_match("~^$pattern~", $path, $m)){ //matching absolute path
					$seoname = substr($path, strlen($m[0])); //what follows
					break;
				}
			}
		}
		else{ //just language prefixes
			$path = $url->pathInfo;
			foreach($langs as $lang=>$txt){
				if(preg_match("~^$lang/~", $path, $m)){  //matching relative path
					$seoname = substr($path, strlen($m[0])); //what follows
					break;
				}
			}
		}

		if(!isset($seoname)){ //default language possible without prefix
			$keys = array_keys($langs);
			$lang = array_shift($keys);
			$seoname = $url->pathInfo;
		}

		$page = PagesModel::getPageBySeoname('/'.$seoname, $lang); //just one level
		if(!$page){
			if(preg_match('~^p([0-9]+)(-|$)~', $seoname, $matches)){
				$page = PagesModel::getPageById($matches[1], $lang);
				if(!$page)
					return NULL;
			}
			else
				return NULL;
		}

		$params = array();
		$params += $httpRequest->getQuery();
		$params['id_page'] = $page['id_page'];
		$params['lang'] = $lang;

		return new PresenterRequest(
			self::PRESENTER,
			$httpRequest->getMethod(),
			$params,
			$httpRequest->getPost(),
			$httpRequest->getFiles(),
			array(PresenterRequest::SECURED => $httpRequest->isSecured())
		);
	}

	//must return absolute url
	function constructUrl(PresenterRequest $appRequest, Url $ref) {
		if($appRequest->getPresenterName() !== self::PRESENTER)
			return NULL;

		$params = $appRequest->getParameters();

		//find the friendly-url in the database
		$lang = $params['lang'];
		if($lang == NULL)
			$lang = $this->getDefaultLang();

		//lang base url (domain or just relative prefix?)
		$langDomains = Environment::getVariable("langDomains");
		if($langDomains)
			$baseUrl = $ref->scheme . ':' . $langDomains[$lang];
		else
			$baseUrl = $ref->getBaseUrl() . ($lang==$this->getDefaultLang() ? '' : "$lang/");


		//NULL page = /
		if(!isset($params['id_page']))
			return $baseUrl;

		//nonexisting page - do not route
		$page = PagesModel::getPageById($params['id_page'], $lang);
		if(!$page)
			return NULL;

		
		unset($params['lang']);
		unset($params['id_page']);
		unset($params['action']);


		// appended parameters
		$params = http_build_query($params, '', '&');
		if($params) $params = "?$params";


		// no pagelink -> do /p123-friendly-name
		if(!$page['seoname'])
			return $baseUrl . "p$page->id"
						. (Strings::webalize($page->name) ? '-' : '')
						. Strings::webalize($page->name) . $params;

		// /sth  -> normal friendly url for that page
		if($page['seoname']{0} == '/')
			return $baseUrl . substr($page['seoname'],1) . $params;

		return NULL;
	}

}

