<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


class PasswordPlugin extends Control {

	static $events = array('allowPageContentDisplay', 'allowFileDownload');

	function getConfig(){
		return $this->parent->context->params['plugins']['PasswordPlugin']
						+ array('logoutlink' => true); //default
	}

	function allowPageContentDisplay(){
		if ($this->parent->page->getInheritedMeta('.password')){
			if(!$this->isLoggedIn()){
				$link = $this->link('login');
				echo "
				<p>Pro přístup ke stránce se prosím přihlašte:
				<form action='$link' method='post'>
				Heslo: <input type='password' name='heslo'><input type='submit' value='Přihlásit'>
				</form>
				";
				return false;
			}
			
			if($this->config['logoutlink']){
				$link = $this->link('logout');
				echo "<a href='$link'>Odhlásit</a>";
			}
		}
		return true;
	}

	function allowFileDownload($file){
		if($file->getPage()->getInheritedMeta('.password'))
			return $this->isLoggedIn();
		return true;
	}

	public function isLoggedIn(){
		return $this->parent->session->getSection('PasswordPlugin')->logged;
	}

	public function handleLogin(){
		$heslo = str_replace(' ','',trim($this->parent->context->httpRequest->post['heslo']));
		if($heslo == $this->config['password'])
		 $this->parent->session->getSection('PasswordPlugin')->logged = true;
		 $this->parent->redirect('this');
	}
	public function handleLogout(){
		 $this->parent->session->getSection('PasswordPlugin')->logged = false;
		 $this->parent->redirect('this');
	}

}
