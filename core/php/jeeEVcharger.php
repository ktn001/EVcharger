<?php
try {
	require_once __DIR__ . '/../../../../core/php/core.inc.php';
	require_once __DIR__ . '/../class/EVcharger.class.php';
	require_once __DIR__ . '/../php/EVcharger.inc.php';

	function process_deamon_message($message) {
		if ($message['info'] == 'started'){
			EVcharger_account::startAllDeamonThread();
		}
	}

	function process_account_message($message) {
		if ($message['info'] == 'thread_started'){
			$accountId = $message['account_id'];
			foreach(EVcharger_charger::byAccountId($accountId) as $charger){
				$charger->startListener();
			}
		}
	}

	function process_charger_message($message) {
		if ($message['info'] == 'closed'){
			log::add('EVcharger','info','[jeeEVcharger] [' . $message['model'] . '][' . $message['charger'] . __('Connection du démon fermée',__FILE__));
		}
	}

	function process_cmd_message($message) {
		if (!array_key_exists('charger',$message)) {
			log::add('EVcharger','error',"[jeeEVcharger] " .  __("Message du demon de modèle <cmd> mais sans identifiant de chargeur!",__FILE__));
		}
		if (!array_key_exists('model',$message)) {
			log::add('EVcharger','error',"[jeeEVcharger] " .  __("Message du demon de modèle <cmd> mais sans modèle de chargeur!",__FILE__));
		}
		if (!array_key_exists('logicalId',$message)) {
			log::add('EVcharger','error',"[jeeEVcharger] " . __("Message du demon de modèle <cmd> mais sans <logicalId>!",__FILE__));
		}
		foreach (EVcharger_charger::byModelAndIdentifiant($message['model'],$message['charger']) as $charger){
			log::add("EVcharger","debug",__("Traitement pour :",__FILE__) . $charger->getHumanName());
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
		log::error('EVcharger','error','[jeeEVcharger] Message reçu du deamon sans champ "object"');
		die();
	}
	switch ($message['object']) {
	case 'deamon':
		process_deamon_message($message);
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
