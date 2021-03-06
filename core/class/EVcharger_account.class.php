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

	protected static $_haveDaemon = false;

	public static function byModel($_modelId, $_onlyEnable = false){
		$eqType_name = "EVcharger_account_" . $_modelId;
		return self::byType($eqType_name, $_onlyEnable);
	}

	public static function do_cron($frequency) {
		foreach (model::all(true) as $model){
			$modelId = $model->getId();
			$class='EVcharger_account_' . $modelId;
			$method= "_cron" . $frequency;
			if (method_exists($class,$method)) {
				$class::$method();
			}
		}
	}

	public function postSave() {
		if (! $this->getIsEnable()){
			$chargers = EVcharger_charger::byAccountId($this->getId());
			foreach ($chargers as $charger) {
				$charger->setIsEnable(0);
				$charger->save();
			}
		}
	}

	/*
	 * Démarre le thread du démon pour chaque account actif
	 */
	public static function startAllDaemonThread(){
		foreach (EVcharger::byType("EVcharger_account_%",true) as $account) {
			$account->startDaemonThread();
		}
	}

	/*
	 * Envoi d'un message au daemon
	 */
	public function send2Daemon($message) {
		if ($this->getIsEnable() and $this::$_haveDaemon){
			if (is_array($message)) {
				$message = json_encode($message);
			}
			if (EVcharger::daemon_info()['state'] != 'ok'){
				log::add('EVcharger','debug',__("Le démon n'est pas démarré!",__FILE__));
				return;
			}
			$params['apikey'] = jeedom::getApiKey('EVcharger');
			$params['modelId'] = $this->getModelId();
			$params['id'] = $this->getId();
			$params['message'] = $message;
			log::add("EVcharger","debug",__("Envoi de message au daemon: ",__FILE__) . print_r($params,true));
			$payLoad = json_encode($params);
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket,'127.0.0.1',(int)config::byKey('daemon::port','EVcharger'));
			socket_write($socket, $payLoad, strlen($payLoad));
			socket_close($socket);
		}
	}

	/*
	 * Lancement d'un thread du daemon pour l'account
	 */
	public function startDaemonThread() {
		if ($this->getIsEnable() and $this::$_haveDaemon){
			log::add("EVcharger","info","Account " . $this->getHumanName() . ": " . __("Lancement du thread",__FILE__));
			$message = array('cmd' => 'start_account');
			if (method_exists($this,'msgToStartDaemonThread')){
				$message = $this->msgToStartDaemonThread();
			}
			$this->send2Daemon($message);
		}
	}

	/*
	 * Après démarrage du thread de l'account
	 */
	public function daemonThreadStarted() {
		log::add("EVcharger","info","Account " . $this->getHumanName() . ": " . __("Le thread est démarré",__FILE__));
		foreach (EVcharger_charger::byAccountId($this->getId()) as $charger) {
			$charger->startDaemonThread();
		}
	}

	/*
	 * Arrêt du thread dédié au compte
	 */
	public function stopDaemonThread() {
		foreach (EVcharger_charger::byAccountId($this->getId()) as $charger){
			if ($charger->getIsEnable()) {
				$message = array(
					'cmd' => 'stop',
					'charger' => $charger->getIdentifiant(),
				);
				$this->send2Daemon($message);
			}
		}
		$message = array('cmd' => 'stop_account');
		$this->send2Daemon($message);
	}

	public function getImage() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			$image = "/plugins/EVcharger/desktop/img/account.png";
		}
		return $image;
	}

	protected function getMapping() {
		$mappingFile = __DIR__ . '/../../core/config/' . $this->getModelId() . '/mapping.ini';
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
		$transformsFile = __DIR__ . '/../../core/config/' . $this->getModelId() . '/transforms.ini';
		if (! file_exists($transformsFile)) {
			return false;
		}
		$transforms = parse_ini_file($transformsFile,true);
		if ($transforms == false) {
			throw new Exception (sprintf(__('Erreur lors de la lecture de %s',__FILE__),$transformsFile));
		}
		return $transforms;
	}

	public function execute ($cmd_charger) {
		try {
			log::add("EVcharger","debug","┌─" . sprintf(__("%s: execution de %s",__FILE__), $this->getHumanName() , $cmd_charger->getLogicalId()));
			if (! is_a($cmd_charger, "EVcharger_chargerCmd")){
				throw new Exception (sprintf(__("| La commande %s n'est pas une commande de type %s",__FILE__),$cmd_charger->getId(), "EVcharger_chargerCmd"));
			}
			if ($cmd_charger->getConfiguration('destination') == 'charger') {
				$method = 'execute_' . $cmd_charger->getLogicalId();
				if ( ! method_exists($this, $method)){
					throw new Exception ("| " . sprintf(__("%s: pas de méthode < %s::%s >",__FILE__),$this->getHumanName(), get_class($this), $method));
				}
				$this->$method($cmd_charger);
				log::add("EVcharger","debug","└─" . __("OK",__FILE__));
				return;
			} else if ($cmd_charger->getConfiguration('destination') == 'cmd') {
				log::add("EVcharger","debug","| " . __("Transfert vers une CMD",__FILE__));
				$cmds = explode('&&', $cmd_charger->getConfiguration('destId'));
				if (is_array($cmds)) {
					foreach ($cmds as $cmd_id) {
						$cmd = cmd::byId(str_replace('#', '', $cmd_id));
						if (is_object($cmd)) {
							$cmd->execCmd();
						}
					}
					return;
				} else {
					$cmd = cmd::byId(str_replace('#', '', $cmd_id));
					$cmd->execCmd();
				}
			} else {
				throw new Exception (sprintf(__("La destination de la commande %s est inconnue!",__FILE__),$cmd_charger->getLogicalId()));
			}
		} catch (Exception $e) {
			log::add("EVcharger","error",$e->getMessage());
			log::add("EVcharger","debug","└─" . __("ERROR",__FILE__));
			return;
		}
	}

	public function getModelId() {
		return $this->getConfiguration('modelId');
	}

	public function getModel() {
		return model::byId($this->getModelId());
	}
}

class EVcharger_accountCmd extends EVchargerCmd  {

}

require_once __DIR__ . '/EVcharger_account_easee.class.php';
require_once __DIR__ . '/EVcharger_account_virtual.class.php';
