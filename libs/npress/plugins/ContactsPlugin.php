<?php

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

class ContactsPlugin extends Control
{
  static $events = array('displayPageContent');

  static $oddily = array(
    3 => "--nezařazen--",
    1 => "Černý šíp",
    2 => "Modrý klíč",
    4 => "Dobromysl",
    5 => "Říčany",
    6 => "Keya",
    7 => "Orlové",
    8 => "Mayové",
    9 => "Kunratice",
    10 => "Protěž",
    11 => "Statečná srdce",
    12 => "Inkové",
    13 => "Havrani",
    14 => "Scarabeus",
    15 => "Utahové"
  );

  function displayPageContent()
  {
    if (!$this->parent->page->getMeta('ContactsPlugin')) {
      return true;
    }

    $this->template->oddily = self::$oddily;
    $this->template->adresar = dibi::query('SELECT * FROM lide ORDER BY oddil');

    $this->template->setFile(dirname(__FILE__) . '/ContactsPlugin.latte');
    echo $this->template->render();
    return false;
  }

  public function handleAdresy()
  {
    $this->template->adresy = true;
  }

  public function handleEdit($id)
  {
    $this->template->edit = true;

    if (!$this['form']->isSubmitted()) {
      $this['form']->setValues(
        dibi::query(
          'select * from lide where id=%i',
          $id,
          ' order by oddil'
        )->fetch()
      );
    }
  }

  protected function createComponentForm()
  {
    $form = new Form();
    $form->addHidden('id');
    $form
      ->addText('jmeno', 'Jméno:')
      ->setOption('description', 'Prázdné jméno smaže záznam.');
    $form->addText('prezdivka', 'Přezdívka:');
    $form->addText('adresa', 'Adresa:');
    $form->addText('pevny_telefon', 'Pevná linka:');
    $form
      ->addText('mobilni_telefon', 'Mobil:')
      ->setOption('description', 'STS přidejte nakonec do závorky');
    $form->addText('email', 'E-mail:');
    $form->addSelect('oddil', 'Oddíl:', self::$oddily);
    $form->addText('poznamka', 'Poznámka:');
    $form->addSubmit('save', 'Uložit');
    $form->onSuccess[] = callback($this, 'editFormSubmitted');
    return $form;
  }

  public function editFormSubmitted(Form $form)
  {
    if (!$form['jmeno']->value and $form['id']->value) {
      dibi::query('DELETE FROM lide WHERE id = %i', $form['id']->value);
    } else {
      dibi::query('REPLACE INTO lide ', $form->values);
    }
    $this->redirect('this');
  }
}
