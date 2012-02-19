<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


class NewsletterPlugin extends AppForm {
	static $events = array();

	private $lang;

	public function __construct(){
		parent::__construct();
		$this->lang = Environment::getApplication()->getPresenter()->lang;

		$this->addText('email', 'email')
						->addRule(Form::FILLED, 'Please, fill in the email')
						->addRule(Form::EMAIL, 'E-mail is not valid');

		$this->addSubmit('submit', 'subscribe the newsletter');
		$this->onSuccess[] = callback($this, 'submitted');
		$this->setTranslator(new TranslationsModel($this->lang));
}

	public function submitted(){
		$presenter = $this->parent;
		if(!isset($presenter->context))
						throw new InvalidStateException('Submitted component is not attached!');

		$email = $this['email']->value;

		file_put_contents($presenter->context->params['dataDir']."/newsletter_0a5cf.txt",
				"$this->lang, $email,\n", FILE_APPEND);

		$presenter->redirect('this', array('newsletterAdded'=>1));
	}
}

?>
