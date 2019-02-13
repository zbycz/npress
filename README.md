# nPress - opensource cms

nPress je systém pro správu obsahu postavený na Nette Frameworku z roku 2012. Demo na [npress.zby.cz](http://npress.zby.cz) (od 7.12.2015 zrušena původní doména npress.info).

**Proč používat:**

- úprava strukturu ve více jazycích
- dobrá práce s přílohami
- rozšiření pomocí Nette Latte šablon (složka theme a meta `.template`, `.sectionTemplate`)
- běží 2010-2016 cca na 10 webech k plné spokojenosti majitelů (z nekomerečních třeba [openstreetmap.cz](http://openstreetmap.cz), [blanik.info](http://blanik.info) či [smetanovokvarteto.cz](http://smetanovokvarteto.cz))

**Proč nepoužívat:**

- staré nette
- kdo neumí Latte šablony, moc to nerozšíří
- vývoj nepokračuje

# Instalace

1. Stáhněte obsah repozitáře nebo distribuční verzi
2. Připravte MySQL databázi a naimportujte soubor `/data/init.sql`
   - V distribuční verzi se též nachází testovací data. Naleznete je
     v `/data/npdemo.sql` a `/data/files/`.
3. Soubor `/data/config.neon.sample` zkopírujte na `/data/config.neon`

- upravte připojení k databázi
- nastavte heslo do administrace a případně další možnosti
  - ověřte, že soubor `/data/config.neon` není dostupný z webu

4. Nastavte oprávnění pro zápis do složek `/data/files/`, `/data/thumbs/`, `/app/log/` a `/app/temp/`
5. Pokud spouštíte aplikaci v podsložce, v `.htaccess` zakomentujte `RewriteBase`
6. Administrace je na adrese `<webova-cesta-k-npress>/admin/`
7. Enjoy!

pozn. kdykoliv něco nefunguje, co by fakt mělo, možná stačí smazat `/app/temp/cache`

Systém je stále v beta verzi, takže jistě obsahuje množství chyb. Bugy, prosím, hlašte na adrese https://github.com/zbycz/npress/issues Stejně tak budu rád za jakékoliv pull requesty či nápady. Pokud systém někde použijete, budu rád, když mi dáte vědět.

# Požadavky

- PHP 5.2 a vyšší
- požadavky Nette: http://doc.nette.org/cs/requirements
- Apache + mod_rewrite
- MySQL databáze

# Další featury

- Nepovinná složka `/theme/` může obsahovat šablony pro vlastní vzhled. Výchozí layout potom bude `@layout.latte` a stránku s metou: `.template=neco` zobrazí pomocí šablony `/theme/neco.latte`.

- Stránku lze změnit v kategorii článků pomocí mety: `.category=yes` Následně se v menu přestanou podstránky zobrazovat a nabídne se rozhraní pro správu článků. Do textu kategorie je nutno přidat například makro `#-subpagesblog-<id_page>-#`

- Makra najdete v `/app/components/NpMacros.php`, zatím nejsou content aware a v nejbližší době je očekává refactoring.

- Pluginy jsou zatím ve zcela prvotním vývoji, pokud k nim máte nějaké nápady/připomínky prosím ozvěte se. Najdete je ve složce /app/plugins/. Aktivace v configu, například pomocí `plugins: PasswordPlugin:{password:123}` a na příslušené stránce nastavit metu: `.password=yes`

# Autor a licence

(c) 2011-2012 Pavel Zbytovský [zby.cz](http://zby.cz)
Pod licencí MIT - volně šiřte, prodávejte, zachovejte uvedení autora.

# Dokumentace (ehh)

Pluginy se registrují v configu pomocí sekce parameters.plugins. Každý plugin si pak registruje události ve svém statickém poli $events, při vyvolání (trigger) události je pak různým způsobem zavolána metoda pluginu přesně s názvem $event() a to na všech Pluginech, které ji registrují.dý

Vrací true, když každý spuštěný Plugin vrátil true.

- `Presenter#triggerEvent($event, [$args])` - Plugin je připojen jako komponenta presenteru `$presenter[PluginName]`, metoda zavolána na ni
- `Presenter#triggerStaticEvent($event, [$args])` - metoda zavolána staticky jako `PluginName::{$event}()`
- `Presenter#triggerEvent_filter($event, $filter)` - metoda je volána postupně na všechny registrované a `$filter` je postupně předáván, nakonec vrácen.
