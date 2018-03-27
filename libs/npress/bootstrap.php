<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

// Inject the session_id during Uploadify request by flash  (TODO (ask) better?)
if(isset($_POST['uploadify_session'])){
	session_id($_POST['uploadify_session']);
	$_COOKIE['PHPSESSID'] = $_POST['uploadify_session'];
}

// Fix the document root on virtual hosts (BUG in Apache)
if(isset($_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME'])){
	$pos = strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME']);
	if($pos !== false)
		$_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['SCRIPT_FILENAME'], 0, $pos);
}

$_SERVER['SCRIPT_NAME'] = preg_replace('~/index\.php$~', '', $_SERVER['SCRIPT_NAME']);


// Load Nette Framework
require LIBS_DIR . '/nette/loader.php';
define("NPRESS", "<span title='2018/03/27'>v1.2</span>");


// Configure application
$configurator = new Configurator;
$configurator->addParameters(array(
		'npDir' => LIBS_DIR . '/npress',
		'appDir' => APP_DIR,  //autodetect uses directory with this bootstrap file
		));

// Enable Nette Debugger for error visualisation & logging
//$configurator->setProductionMode(FALSE);
$configurator->enableDebugger(APP_DIR . '/log');
function barDump($x){Debugger::barDump($x);}

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(APP_DIR . '/temp');
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();

// Create Dependency Injection container from config.neon files
$configurator->addConfig(LIBS_DIR . '/npress/config.neon');
$configurator->addConfig(APP_DIR . '/config.neon');
$configurator->addConfig(WWW_DIR . '/data/config.neon');
if (file_exists(WWW_DIR . '/data/config.local.neon'))
    $configurator->addConfig(WWW_DIR . '/data/config.local.neon');
$container = $configurator->createContainer();


// Connect to the database
dibi::connect($container->params['database']);



// Setup router
$flag = isset($_SERVER['HTTPS']) ? Route::SECURED : false;
if (isset($_SERVER['HTTPS'])) Route::$defaultFlags = Route::SECURED;

$container->router[] = $adminRouter = new RouteList('Admin');
$adminRouter[] = new Route('admin/<presenter>/<action>[/<id_page>]', 'Admin:default', $flag);

$container->router[] = $frontRouter = new RouteList('Front');
$frontRouter[] = new Route('data/thumbs/<id>[.<opts>].png', 'Files:preview', $flag);
$frontRouter[] = new Route('files[/<action>][/<id>]', 'Files:default', $flag);
$frontRouter[] = new Route('index.php', 'Pages:default', Route::ONE_WAY);
$frontRouter[] = new PagesRouter;
$frontRouter[] = new RedirectRouter;
$frontRouter[] = new Route('<presenter>[/<action>]/<id_page>', array( //default route
        'presenter' => 'Pages',
        'action' => 'default',
        'id_page' => 1, //TODO default page from config (but matched only when '/' page missing)
    ), $flag);


// Include app specific bootstrap.php
if(file_exists(APP_DIR . '/bootstrap.php'))
				require_once APP_DIR . '/bootstrap.php';


// Configure and run the application!
if(!defined("DONT_RUN_NPRESS_APP"))
    $container->application->run();
