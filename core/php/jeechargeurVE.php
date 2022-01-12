<?php
try {
	require_once __DIR__ . '/../../../../core/php/core.inc.php';

	if (!jeedom::apiAccess(init('apikey'), 'chargeurVE')) {
		echo __('Vous n\'êtes pas autorisé à effectuer cette action', __FILE__);
		die();
	}
	if (init('test') != '') {
		echo 'ok';
		die();
	}
	$result = json_decode(file_get_content("php://input"), true);
	if (!is_array($result)) {
		die();
	}

	log::add("chargeurVE","debug","Message reçu du démon: " . print_r($result,true));

} catch (Exception $e) {
	log::add('chargeurVE','error', displayException($e));
}
