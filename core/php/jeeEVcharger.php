<?php
try {
	require_once __DIR__ . '/../../../../core/php/core.inc.php';

	function process_deamon_message($message) {
		if ($message['info'] == 'started'){
			account::startAllDeamon();
			die();
		}
	}

	function process_account_message($message) {
		if ($message['info'] == 'thread_started'){
			$accountId = $message['account_id'];
			foreach(EVcharger::byAccountId($accountId) as $charger){
				$charger->startListener();
			}
		}
	}

	function process_chargeur_message($message) {
		if ($message['info'] == 'closed'){
			log::add('EVcharger','info','[' . $message['model'] . '][' . $message['chargeur'] . __('Connection du démon fermée',__FILE__));
		}
	}

	function process_cmd_message($message) {
		if (!array_key_exists('chargeur',$message)) {
			log::add('EVcharger','error',__("Message du demon de modèle <cmd> mais sans identifiant de chargeur!",__FILE__));
		}
		if (!array_key_exists('model',$message)) {
			log::add('EVcharger','error',__("Message du demon de modèle <cmd> mais sans modèle de chargeur!",__FILE__));
		}
		if (!array_key_exists('logicalId',$message)) {
			log::add('EVcharger','error',__("Message du demon de modèle <cmd> mais sans <logicalId>!",__FILE__));
		}
		foreach (EVcharger::byModelAndIdentifiant($message['model'],$message['chargeur']) as $chargeur){
			$chargeur->checkAndUpdateCmd($message['logicalId'],$message['value']);
		}
	}

	if (!jeedom::apiAccess(init('apikey'), 'EVcharger')) {
		echo __('Vous n\'êtes pas autorisé à effectuer cette action', __FILE__);
		die();
	}
	if (init('test') != '') {
		echo 'ok';
		die();
	}

	$message = json_decode(file_get_contents("php://input"), true);
	if (!is_array($message)) {
		die();
	}
	log::add("EVcharger","debug","Message reçu du démon: " . print_r($message,true));

	if (!array_key_exists('object',$message)){
		log::error('EVcharger','error','Message reçu du deamon sans champ "object"');
		die();
	}
	switch ($message['object']) {
	case 'deamon':
		process_deamon_message($message);
		break;
	case 'account':
		process_account_message($message);
		break;
	case 'chargeur':
		process_chargeur_message($message);
		break;
	case 'cmd':
		process_cmd_message($message);
		break;
	}


} catch (Exception $e) {
	log::add('EVcharger','error', displayException($e));
}

?>
