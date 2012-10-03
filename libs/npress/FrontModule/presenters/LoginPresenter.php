<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


/** Login presenter
 */
class Front_LoginPresenter extends Front_BasePresenter
{
	public function actionDefault(){
		if($this->user->isLoggedIn())
			$this->redirect(":Admin:Admin:");
	}

	public function createComponentLoginForm(){
		$form = new AppForm;
		$form->addText('username', 'Uživatelské jméno:')
			->addRule(Form::FILLED, 'Vyplňte prosím uživatelské jméno.');
		$form->addPassword('password', 'Heslo:')
			->addRule(Form::FILLED, 'Vyplňte, prosím, heslo.');
		$form->addCheckbox('remember', 'Trvale přihlásit na tomto počítači');
		$form->addSubmit('login', 'Přihlásit se');
		$form->onSuccess[] = callback($this, 'loginFormSubmitted');

		return $form;
	}
	public function loginFormSubmitted(AppForm $form){
		try {
			$values = $form->values;
			//if ($values['remember']) {
				$this->user->setExpiration('+ 1 month', FALSE); //also in config.neon#session
			//} else {
			//	$this->user->setExpiration(0, TRUE);
			//}
			//TODO expiration(0) breaks uploadify

			$this->user->login($values['username'], $values['password']);

			if(isset($values['backlink']))
				$this->application->restoreRequest($values['backlink']);
			$this->redirect(":Admin:Admin:");

		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	
	/*/ -----------------------------------------   LOST PASS  ---------------------
	public function createComponentLostPassForm(){
		$usersModel = new UsersModel();

		$form = new AppForm;
		$form->addText('email', 'E-mail kontaktní osoby:')
			->addRule(Form::FILLED, 'Vyplňte prosím e-mail kontaktní osoby.')
			->addRule(array($usersModel, 'isExistingEmail'), 'Tento e-mail nemáme v databázi. Pokud si nejste jisti, prosíme, kontaktujte nás.');

		$form->addSubmit('send', 'Poslat');

		$form->onSuccess[] = callback($this, 'lostPassFormSubmitted');
		return $form;
	}

	public function lostPassFormSubmitted(AppForm $form){
		$auth = md5(uniqid(rand()));
		dibi::query('UPDATE [::users] SET auth_lost_pass = %s',$auth,' WHERE email= %s',$form['email']->value);
		
		//pošleme mail
		$template = $this->createTemplate();
		$template->registerFilter(new LatteFilter);
		$template->auth  = $auth;
		$template->setFile(APP_DIR.'/templates/Login/lostPass_email.phtml'); // -> newPass

		//posíláme
		$mail = new Mail;
		$mail->setEncoding(Mail::ENCODING_QUOTED_PRINTABLE);
		$mail->setFrom(Environment::getVariable('registerRobotEmail'), Environment::getVariable('serverName'));
		$mail->addTo($form['email']->getValue());
		$mail->setHtmlBody($template);

		if(!Environment::isProduction())
			$mail->setMailer(new MyMailer);
		
    try {
            $mail->send();
    } catch (InvalidStateException $e) {
            throw new IOException('Nepodařilo se odeslat e-mail, zkuste to prosím za chvíli.');
    }

		$this->flashMessage('Zkontrolujte prosím svůj email a postupujte dle instrukcí.');
		$this->redirect('Login:form');
	}
	
	// -----------------------------------------   NEW PASS  ---------------------
	public function actionNewPass($auth){
		$data = dibi::fetch('SELECT  * FROM [::users] WHERE auth_lost_pass= %s',$auth);
		if(!$data){
			$this->flashMessage('Bohužel odkaz pro obnovu hesla je chybný, zkuste ho správně zkopírovat, nebo vygenerujte nový.');
			$this->redirect('Login:lostPass');		
		}
		
		$this->template->data = $data;
		$this['newPassForm']['auth']->setValue($auth);
	}
	
	public function createComponentNewPassForm(){
		$usersModel = new UsersModel();

		$form = new AppForm($this, 'newPassForm');
		$form->addHidden('auth');
		$form->addPassword('pass', 'Nové heslo')
			->addRule(Form::FILLED, 'Zvolte si své heslo')
			->addRule(Form::MIN_LENGTH, 'Heslo je příliš krátké, alespoň %d znaků', 5);
		$form->addPassword('pass2', 'Potvrzení hesla')
			->addConditionOn($form['pass'], Form::VALID)
				->addRule(Form::FILLED, 'Vložte heslo ještě jednou pro potvrzení')
				->addRule(Form::EQUAL, 'Hesla se neshodují, vypište znovu', $form['pass']);

		$form->addSubmit('send', 'Uložit');

		$form->onSubmit[] = callback($this, 'newPassFormSubmitted');
		return $form;
	}
	
	public function newPassFormSubmitted(AppForm $form){
		$pass = sha1($form['pass']->value);
		dibi::query('UPDATE [::users] SET pass = %s',$pass,', auth_lost_pass=\'\' WHERE auth_lost_pass= %s',$form['auth']->value);
		
		$this->flashMessage('Nové heslo bylo nastaveno, můžete se přihlásit.');
		$this->redirect('Login:form');
		
	}

*/
}
