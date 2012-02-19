nPress - opensource cms
=======================

nPress je nový systém pro správu obsahu postavený na Nette Frameworku. Umožňuje snadno upravovat strukturu více jazyčného webu a dobře pracuje s přílohami.

Více informací na www.npress.info


Instalace
=========
1) Stáhněte obsah repozitáře
2) Připravte MySQL databázi a naimportujte soubor /data/init.sql
3) Soubor "/data/config.neon.sample" zkopírujte na "/data/config.neon" 
  - upravte připojení k databázi
  - nastavte heslo do administrace a případně další možnosti
	- ověřte, že soubor "/data/config.neon" není dostupný z webu
4) Pokud spouštíte aplikaci v podsložce, v .htaccess zakomentujte RewriteBase
5) Administrace je na adrese <webova-cesta-k-npress>/admin/
6) Enjoy!

Systém je stále v beta verzi, takže jistě obsahuje množství chyb. Hlásit bugy prosím hlašte na adrese https://github.com/zbycz/nPress/issues

Stejně tak budu rád za jakékoliv pull requesty či nápady.


Požadavky
=========
- PHP 5.2 a vyšší
- požadavky Nette: http://doc.nette.org/cs/requirements
- Apache + mod_rewrite
- MySQL databáze


Další featury
=============
- Nepovinná složka /theme/ může obsahovat šablony pro vlastní vzhled. Výchozí layout potom bude @layout.latte a stránku s metou: .template=neco zobrazí pomocí šablony /theme/neco.latte.

- Stránku lze změnit v kategorii článků pomocí mety: .category=yes Následně se v menu přestanou podstránky zobrazovat a nabídne se rozhraní pro správu článků. Do textu kategorie je nutno přidat například makro #-subpagesblog-<id_page>-#

- Makra najdete v /app/components/NpMacros.php, zatím nejsou content aware a v nejbližší době je očekává refactoring.

- Pluginy jsou zatím ve zcela prvotním vývoji, pokud k nim máte nějaké nápady/připomínky prosím ozvěte se. Najdete je ve složce /app/plugins/. Aktivace v configu, například pomocí plugins: PasswordPlugin:{password:123} a na příslušené stránce nastavit metu: .password=yes 


Autor
=====
Pavel Zbytovský (c) 2011-2012
mail/jabber: zbytovsky@gmail.com
www.npress.info


