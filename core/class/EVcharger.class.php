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

/* * *************************** Includes ********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/account.class.php';

class EVcharger extends eqLogic {
    /*     * ************************* Attributs ****************************** */

    /*     * *********************** Methode static *************************** */

	public static function byAccountId($accountId) {
		return self::byTypeAndSearchConfiguration('EVcharger','"accountId":"'.$accountId.'"');
	}

	public static function byModelAndIdentifiant($model, $identifiant) {
		$identKey = model::getIdentifiantCharger($model);
		$searchConf = sprintf('"%s":"%s"',$identKey,$identifiant);
		$chargers = array();
		foreach (EVcharger::byTypeAndSearchConfiguration('EVcharger',$searchConf) as $charger){
			if ($charger->getConfiguration('model') == $model){
				$chargers[] = $charger;
			}
		}
		return $chargers;

	}

    /*     * ********************** Gestion du daemon ************************* */

    /*
     * Info sur le daemon
     */
	public static function deamon_info() {
		$return = array();
		$return['log'] = __CLASS__;
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		if (file_exists($pid_file)) {
			if (posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
			}
		}
		$return['launchable'] = 'ok';
		return $return;
	}

    /*
     * Lancement de daemon
     */
	public static function deamon_start() {
		self::deamon_stop();
		$daemon_info = self::deamon_info();
		if ($daemon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}

		$path = realpath(dirname(__FILE__) . '/../../resources/bin'); // répertoire du démon
		$cmd = 'python3 ' . $path . '/EVchargerd.py';
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
		$cmd .= ' --socketport ' . config::byKey('daemon::port', __CLASS__); // port
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/EVcharger/core/php/jeeEVcharger.php';
		$cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
		$cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		log::add(__CLASS__, 'info', 'Lancement démon');
		log::add(__CLASS__, "info", $cmd . ' >> ' . log::getPathToLog('EVcharger_daemon') . ' 2>&1 &');
		$result = exec($cmd . ' >> ' . log::getPathToLog('EVcharger_daemon.out') . ' 2>&1 &');
		$i = 0;
		while ($i < 20) {
			$daemon_info = self::deamon_info();
			if ($daemon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($daemon_info['state'] != 'ok') {
			log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
			return false;
		}
		message::removeAll(__CLASS__, 'unableStartDeamon');
		return true;
	}

    /*
     * Arret de daemon
     */
	public static function deamon_stop() {
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			log::add(__CLASS__, 'info', __('kill process: ',__FILE__) . $pid);
			system::kill($pid, false);
			foreach (range(0,15) as $i){
				if (self::deamon_info()['state'] == 'nok'){
					break;
				}
				sleep(1);
			}
			return;
		}
	}

    /*
     * Installation des dépendances
     */
	public static function dependancy_install() {
		# log::remove(__CLASS__ . '_update');
		return array(
			'script' => dirname(__FILE__) . '/../../resources/bin/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance',
			'log' => log::getPathToLog(__CLASS__ . '_update')
		);
	}

    /*
     * Etat de dépendances
     */
	public static function dependancy_info(){
		$return = array();
		$return ['log'] = log::getPathToLog(__CLASS__ . '_update');
		$return ['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
		if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependance')) {
			$return['state'] = 'in_progress';
		} else {
			if (exec(system::getCmdSudo() . system::get('cmd_check') . '-Ec python3\-requests') < 1) {
				$return['state'] = 'nok';
			} else {
				$return['state'] = 'ok';
			}
		}
		return $return;
	}

    /*     * ************************ Les widgets **************************** */

    /*
     * template pour les widget
     */
	public static function templateWidget() {
		$return = array(
			'action' => array(
				'other' => array(
					'cable_lock' => array(
						'template' => 'cable_lock',
						'replace' => array(
							'#_icon_on_#' => '<i class=\'icon_green icon jeedom-lock-ferme\'><i>',
							'#_icon_off_#' => '<i class=\'icon_orange icon jeedom-lock-ouvert\'><i>'
						)
					)
				)
			),
			'info' => array(
				'string' => array(
					'etat' => array(
						'template' => 'etat',
						'replace' => array(
							'#texte_1#' =>  '{{Débranché}}',
							'#texte_2#' =>  '{{En attente}}',
							'#texte_3#' =>  '{{Recharge}}',
							'#texte_4#' =>  '{{Terminé}}',
							'#texte_5#' =>  '{{Erreur}}',
							'#texte_6#' =>  '{{Prêt}}'
						)
					)
				)
			)
		);
		return $return;
	}

    /*     * ************************ Les crons **************************** */

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
     */
	public static function cronHourly() {
		account::cronHourly();
	}

    /*     * *********************Méthodes d'instance************************* */

    // Création/mise à jour des commande prédéfinies
	public function UpdateCmds($mandatoryOnly = false) {
		$ids = array();
		foreach (model::commands($this->getConfiguration('model'),$mandatoryOnly) as $logicalId => $config) {
			$cmd = EVchargerCmd::byEqLogicIdAndLogicalId($this->getId(),$logicalId);
			if (!is_object($cmd)){
				$cmd = new EVchargerCMD();
				$cmd->setName(__($config['name'],__FILE__));
			}
			$cmd->setConfiguration('mandatory',$config['mandatory']);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId($logicalId);
			$cmd->setType($config['type']);
			$cmd->setSubType($config['subType']);
			$cmd->setOrder($config['order']);
			if (array_key_exists('template', $config)) {
				$cmd->setTemplate('dashboard',$config['template']);
				$cmd->setTemplate('mobile',$config['template']);
			}
			if (array_key_exists('visible', $config)) {
				$cmd->setIsVisible($config['visible']);
			}
			if (array_key_exists('displayName', $config)) {
				$cmd->setDisplay('showNameOndashboard', $config['displayName']);
				$cmd->setDisplay('showNameOnmobile', $config['displayName']);
			}
			if (array_key_exists('unite', $config)) {
				$cmd->setUnite($config['unite']);
			}
			if (array_key_exists('display::graphStep', $config)) {
				$cmd->setDisplay('graphStep', $config['display::graphStep']);
			}
			if (array_key_exists('rounding', $config)) {
				$cmd->setConfiguration('historizeRound', $config['rounding']);
			}
			$cmd->save();
		}
		foreach (model::commands($this->getConfiguration('model'),$mandatoryOnly) as $logicalId => $config) {
			if (array_key_exists('value',$config)){
				$cmd = EVchargerCmd::byEqLogicIdAndLogicalId($this->getId(),$logicalId);
				if (!is_object($cmd)){
					log::add("EVcharger","error",(sprintf(__("Commande avec logicalId = %s introuvable",__FILE__),$logicalId)));
					continue;
				}
				$value = cmd::byEqLogicIdAndLogicalId($this->getId(), $config['value'])->getId();
				$cmd->setValue($value);
				$cmd->save();
			}
			if (array_key_exists('calcul',$config)){
				$calcul = $config['calcul'];
				$cmd = EVchargerCmd::byEqLogicIdAndLogicalId($this->getId(),$logicalId);
				if (!is_object($cmd)){
					log::add("EVcharger","error",(sprintf(__("Commande avec logicalIs=%s introuvable",__FILE__),$logicalId)));
					continue;
				}
				preg_match_all('/#(.+?)#/',$calcul,$matches);
				foreach ($matches[1] as $logicalId) {
					$id = cmd::byEqLogicIdAndLogicalId($this->getId(), $logicalId)->getId();
					$calcul = str_replace('#' . $logicalId . '#', '#' . $id . '#', $calcul);
				}
				$cmd->setConfiguration('calcul', $calcul);
				$cmd->save();
			}
		}
	}

    // Fonction exécutée automatiquement avant la création de l'équipement
	public function preInsert() {
		$this->setConfiguration('image',model::images($this->getConfiguration('model'),'charger')[0]);
	}

    // Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert() {
		$this->UpdateCmds(false);
	}

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
	public function postSave() {
		if ($this->getIsEnable()){
			$this->startListener();
		} else {
			$this->stopListener();
		}
	}

	// Fonction exécutée automatiquement après la sauvegarde de l'eqLogid ET des commandes si sauvegarde lancée via un AJAX
	public function postAjax() {
		if ($this->getAccountId() == '') {
			return;
		}
		$cmd_refresh = EVchargerCmd::byEqLogicIdAndLogicalId($this->getId(),'refresh');
		if (!is_object($cmd_refresh)) {
			return;
		}
		$cmd_refresh->execute();
		return;
	}

	public function getPathImg() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			return "/plugins/EVcharger/plugin_info/EVcharger_icon.png";
		}
		return $image;
	}

	public function getAccount() {
		return account::byId($this->getAccountId());
	}

	public function getIdentifiant() {
		$model = $this->getConfiguration('model');
		$configName = model::getIdentifiantCharger($model);
		return $this->getConfiguration($configName);
	}

	public function startListener() {
		if (! $this->getIsEnable()) {
			return;
		}
		$message = array(
			'cmd' => 'start_charger_listener',
			'chargerId' => $this->id,
			'identifiant' => $this->getIdentifiant()
		);
		account::byId($this->getAccountId())->send2Deamon($message);
	}

	public function stopListener() {
		$message = array(
			'cmd' => 'stop_charger_listener',
			'chargerId' => $this->id,
			'identifiant' => $this->getIdentifiant()
		);
		if ($this->getAccountId()) {
			account::byId($this->getAccountId())->send2Deamon($message);
		}
	}

    /*     * **********************Getteur Setteur*************************** */

	public function getAccountId() {
		return $this->getConfiguration('accountId');
	}

	public function setAccountId($_accountId§) {
		$this->setConfiguration('accountId',$_accountId);
		return $this;
	}

	public function getImage() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			return "/plugins/EVcharger/plugin_info/EVcharger_icon.png";
		}
		return $image;
	}

	public function setImage($_image) {
		$this->setConfiguration('image',$_image);
		return $this;
	}
}

class EVchargerCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
	public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		if ($this->getLogicalId() == "refresh") {
			return true;
		}
		return false;
	}

	public function preSave() {
		if ($this->getLogicalId() == 'refresh') {
                        return;
                }
		if ($this->getType() == 'info') {
			$calcul = $this->getConfiguration('calcul');
			if (strpos($calcul, '#' . $this->getId() . '#') !== false) {
				throw new Exception(__('Vous ne pouvez appeler la commande elle-même (boucle infinie) sur',__FILE__) . ' : '.$this->getName());
			}
			$added_value = [];
			preg_match_all("/#([0-9]+)#/", $calcul, $matches);
			$value = '';
			foreach ($matches[1] as $cmd_id) {
				$cmd = self::byId($cmd_id);
				if (is_object($cmd) && $cmd->getType() == 'info') {
					if(isset($added_value[$cmd_id])) {
						continue;
					}
					$value .= '#' . $cmd_id . '#';
					$added_value[$cmd_id] = $cmd_id;
				}
			}
			preg_match_all("/variable\((.*?)\)/",$calcul, $matches);
			foreach ($matches[1] as $variable) {
				if(isset($added_value['#variable(' . $variable . ')#'])){
					continue;
				}
				$value .= '#variable(' . $variable . ')#';
				$added_value['#variable(' . $variable . ')#'] = '#variable(' . $variable . ')#';
			}
			$this->setValue($value);
		}
	}

	public function postSave() {
		if ($this->getType() == 'info' && $this->getConfiguration('calcul') != '') {
			$this->event($this->execute());
		}
	}

    // Exécution d'une commande
	public function execute($_options = array()) {
		log::add("EVcharger","debug","Execute : " . $this->getLogicalId());
		switch ($this->getType()) {
		case 'info':
			$calcul = $this->getConfiguration('calcul');
			if ($calcul) {
				return jeedom::evaluateExpression($calcul);
			}
			break;
		case 'action':
			$this->getEqLogic()->getAccount()->execute($this);
		}
	}

    /*     * **********************Getteur Setteur*************************** */
}
