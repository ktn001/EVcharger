<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

class EVcharger_account extends EVcharger {

	protected static $_haveDeamon = false;

	public static function byModel($_model){
		$eqType_name = "EVcharger_account_" . $_model;
		return self::byType($eqType_name);
	}

	public function getImage() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			$image = "/plugins/EVcharger/desktop/img/account.png";
		}
		return $image;
	}

	/*
	 * Démarre le thread du démon pour chaque account actif
	 */
	public static function startAllDeamonThread(){
		foreach (EVcharger::byType("EVcharger_account_%",true) as $account) {
			$account->startDeamonThread();
		}
	}

	/*
	 * Envoi d'un message au deamon
	 */
	public function send2Deamon($message) {
		if ($this->getIsEnable() and $this::$_haveDeamon){
			if (is_array($message)) {
				$message = json_encode($message);
			}
			if (EVcharger::deamon_info()['state'] != 'ok'){
				log::add('EVcharger','debug',__("Le démon n'est pas démarré!",__FILE__));
				return;
			}
			$params['apikey'] = jeedom::getApiKey('EVcharger');
			$params['model'] = $this->getModel();
			$params['id'] = $this->getId();
			$params['message'] = $message;
			$payLoad = json_encode($params);
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket,'127.0.0.1',(int)config::byKey('deamon::port','EVcharger'));
			socket_write($socket, $payLoad, strlen($payLoad));
			socket_close($socket);
		}
	}

	/*
	 * Lancement d'un thread du deamon pour l'account
	 */
	public function startDeamonThread() {
		if ($this->getIsEnable() and $this::$_haveDeamon){
			log::add("EVcharger","info",$this->getHumanName() . ": " . __("Lancement du thread",__FILE__));
			$message = array('cmd' => 'start');
			if (method_exists($this,'msgToStartDeamonThread')){
				$message = $this->msgToStartDeamonThread();
			}
			$this->send2Deamon($message);
		}
	}

	/*
	 * Arrêt du thread dédié au compte
	 */
	public function stopDeamonThread() {
		foreach (EVcharger_charger::byAccountId($this->Id()) as $charger){
			if ($charger->getIsEnable()) {
				$message = array(
					'cmd' => 'stop',
					'charger' => $charger->getIdentifiant(),
				);
				$this->send2Deamon($message);
			}
		}
		$message = array('cmd' => 'stop_account');
		$this->sen2Deamon($message);
	}

	protected function getMapping() {
		$mappingFile = __DIR__ . '/../../core/config/' . $this->getModel() . '/mapping.ini';
		if (! file_exists($mappingFile)) {
			return false;
		}
		$mapping = parse_ini_file($mappingFile,true);
		if ($mapping == false) {
			throw new Exception (sprintf(__('Erreur lors de la lecture de %s',__FILE__),$mappingFile));
		}
		return $mapping['API'];
	}

	protected function getTransforms() {
		$transformsFile = __DIR__ . '/../../core/config/' . $this->getModel() . '/transforms.ini';
		if (! file_exists($transformsFile)) {
			return false;
		}
		$transforms = parse_ini_file($transformsFile,true);
		if ($transforms == false) {
			throw new Exception (sprintf(__('Erreur lors de la lecture de %s',__FILE__),$transformsFile));
		}
		return $transforms;
	}

	public function execute ($charger_cmd) {
		try {
			log::add("EVcharger","debug","┌─" . sprintf(__("%s: execution de %s",__FILE__), $this->getHumanName() , $charger_cmd->getLogicalId())); 
			if (! is_a($charger_cmd, "EVcharger_chargerCmd")){
				throw new Exception (sprintf(__("| La commande %s n'est pas une commande de type %s",__FILE__),$charger_cmd->getId(), "EVcharger_chargerCmd"));
			}
			$method = 'execute_' . $charger_cmd->getLogicalId();
			if ( ! method_exists($this, $method)){
				throw new Exception (sprintf(__("| %s: pas de méthode < %s::%s >",__FILE__),$this->getHumanName(), get_class($this), $method));
			}
			$this->$method($charger_cmd);
			log::add("EVcharger","debug","└─" . __("OK",__FILE__));
			return;
		} catch (Exception $e) {
			log::add("EVcharger","error",$e->getMessage());
			log::add("EVcharger","debug","└─" . __("ERROR",__FILE__));
			return;
		}
	}

	public function getModel() {
		return $this->getConfiguration('model');
	}
}

class EVcharger_accountCmd extends EVchargerCmd  {

}

require_once __DIR__ . '/EVcharger_account_easee.class.php';
