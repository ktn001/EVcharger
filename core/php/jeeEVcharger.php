<?php
try {
	require_once __DIR__ . '/../../../../core/php/core.inc.php';
	require_once __DIR__ . '/../class/EVcharger.class.php';
	require_once __DIR__ . '/../php/EVcharger.inc.php';

	function process_daemon_message($message) {
		if ($message['info'] == 'started'){
			EVcharger_account::startAllDaemonThread();
		}
	}

	function process_account_message($message) {
		if ($message['info'] == 'thread_started'){
			$account = EVcharger_account::byId($message['account_id']);
			if (is_object($account)) {
				$account->daemonThreadStarted();
			} else {
				log::add("EVcharger","error",sprintf(__("L'account %s est introuvable",__FILE__),$message['account_id']));
			}
		}
	}

	function process_charger_message($message) {
		if ($message['info'] == 'closed'){
			log::add('EVcharger','info','[jeeEVcharger] [' . $message['modelId'] . '][' . $message['charger'] . __('Connection du démon fermée',__FILE__));
		}
	}

	function process_cmd_message($message) {
		if (!array_key_exists('charger',$message)) {
			log::add('EVcharger','error',"[jeeEVcharger] " .  __("Message du demon de modèle <cmd> mais sans identifiant de chargeur!",__FILE__));
		}
		if (!array_key_exists('modelId',$message)) {
			log::add('EVcharger','error',"[jeeEVcharger] " .  __("Message du demon de modèle <cmd> mais sans modèle de chargeur!",__FILE__));
		}
		if (!array_key_exists('logicalId',$message)) {
			log::add('EVcharger','error',"[jeeEVcharger] " . __("Message du demon de modèle <cmd> mais sans <logicalId>!",__FILE__));
		}
		foreach (EVcharger_charger::byModelAndIdentifiant($message['modelId'],$message['charger']) as $charger){
			$charger->checkAndUpdateCmd($message['logicalId'],$message['value']);
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
	log::add("EVcharger","debug","[jeeEVcharger] Message reçu du démon: " . print_r($message,true));

	if (!array_key_exists('object',$message)){
		log::error('EVcharger','error','[jeeEVcharger] Message reçu du daemon sans champ "object"');
		die();
	}
	switch ($message['object']) {
	case 'daemon':
		process_daemon_message($message);
		break;
	case 'account':
		process_account_message($message);
		break;
	case 'charger':
		process_charger_message($message);
		break;
	case 'cmd':
		process_cmd_message($message);
		break;
	}


} catch (Exception $e) {
	log::add('EVcharger','error', "[jeeEVcharger] " . displayException($e));
}

?>
