<?php
// Version
define('VERSION', '2.3.0.2');

use Cart\Currency;
use Cart\Weight;
use Cart\User;
use Cart\Length;


// Configuration
require_once('../admin/config.php');

if (file_exists('../vqmod/vqmod.php')) {
	require_once('../vqmod/vqmod.php');
	VQMod::bootup();

	require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/cart/currency.php'));
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/cart/user.php'));
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/cart/weight.php'));
	require_once(VQMod::modCheck(DIR_SYSTEM . 'library/cart/length.php'));
}
else {
	require_once(DIR_SYSTEM . 'startup.php');
	require_once(DIR_SYSTEM . 'library/cart/currency.php');
	require_once(DIR_SYSTEM . 'library/cart/user.php');
	require_once(DIR_SYSTEM . 'library/cart/weight.php');
	require_once(DIR_SYSTEM . 'library/cart/length.php');
}

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

// Settings
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting");

foreach ($query->rows as $setting) {
	if (!$setting['serialized']) {
		$config->set($setting['key'], $setting['value']);
	} else {
		$config->set($setting['key'], json_decode($setting['value']));
		
	}
}

// Log
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);

// Error Handler
function error_handler($errno, $errstr, $errfile, $errline) {
	global $config, $log;

	if (0 === error_reporting()) return TRUE;
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			break;
		default:
			$error = 'Unknown';
			break;
	}

	if ($config->get('config_error_display')) {
		echo '<b>' . $error . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
	}

	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	return TRUE;
}

// Error Handler
set_error_handler('error_handler');

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response);

// Session
$registry->set('session', new Session());

// Cache
$registry->set('cache', new Cache('file'));

// Document
$registry->set('document', new Document());

// Language
$languages = array();

$query = $db->query("SELECT * FROM " . DB_PREFIX . "language");

foreach ($query->rows as $result) {
	$languages[$result['code']] = array(
		'language_id'	=> $result['language_id'],
		'name'		=> $result['name'],
		'code'		=> $result['code'],
		'locale'	=> $result['locale'],
		'directory'	=> $result['directory']
	);
}

$config->set('config_language_id', $languages[$config->get('config_admin_language')]['language_id']);

$language = new Language($languages[$config->get('config_admin_language')]['directory']);
//$language->load($languages[$config->get('config_admin_language')]['filename']);
$registry->set('language', $language);

// Currency
$registry->set('currency', new Currency($registry));

// Weight
$registry->set('weight', new Weight($registry));

// Length
$registry->set('length', new Length($registry));

// User
$registry->set('user', new User($registry));

// Event
$event = new Event($registry);
$registry->set('event', $event);

$query = $db->query("SELECT * FROM " . DB_PREFIX . "event");

foreach ($query->rows as $result) {
	$event->register($result['trigger'], new Action ($result['action']));
}

// Front Controller
$controller = new Front($registry);


// Router
if (isset($request->get['mode']) && $request->get['type'] == 'catalog') {

	switch ($request->get['mode']) {
		case 'checkauth':
			$action = new Action('extension/module/myskladoc23/modeCheckauth');
			break;

		case 'init':
			$action = new Action('extension/module/myskladoc23/modeCatalogInit');
			break;

		case 'file':
			$action = new Action('extension/module/myskladoc23/modeFile');
			break;

		case 'import':
			$action = new Action('extension/module/myskladoc23/modeImport');
			break;

		default:
			echo "success\n";
	}

} else if (isset($request->get['mode']) && $request->get['type'] == 'sale') {

	switch ($request->get['mode']) {
		case 'checkauth':
			$action = new Action('extension/module/myskladoc23/modeCheckauth');
			break;

		case 'init':
			$action = new Action('extension/module/myskladoc23/modeSaleInit');
			break;

		case 'query':
			$action = new Action('extension/module/myskladoc23/modeQueryOrders');
			break;

		default:
			echo "success\n";
	}

} else {
	echo "success\n";
	exit;
}

// Dispatch
if (isset($action)) {
	$controller->dispatch($action, new Action('error/not_found'));
}

// Output
$response->output();
?>

