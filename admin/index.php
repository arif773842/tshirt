<?php
/**
 * @author tshirtecommerce - www.tshirtecommerce.com
 * @date: 2015-01-10
 * 
 * @copyright  Copyright (C) 2015 tshirtecommerce.com. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 *
 */

define('ROOT', dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);
ini_set("memory_limit", -1);

include_once dirname(ROOT) .DS. 'includes' .DS. 'config.php';

include_once ROOT .DS. 'includes' .DS. 'functions.php';
include_once ROOT .DS. 'includes' .DS. 'controllers.php';

$platform = 'wordpress';
$file = dirname(ROOT).DS.'version.json';
$json = json_decode(file_get_contents($file), true);
if (isset($json['platforms'])) {
	$platform = $json['platforms'];
}

if ($platform == 'wordpress' && file_exists(ROOT .DS. 'session.php')) {
	include_once ROOT .DS. 'session.php';
	ini_set('session.save_handler', 'files');
} elseif ($platform == 'opencart' && file_exists(dirname(ROOT).DS.'oc_session.php')) {
	include_once dirname(ROOT).DS.'oc_session.php';
}

$controller = new Controllers();
$controller->load();
?>