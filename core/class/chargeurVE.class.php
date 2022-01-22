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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/account.class.php';

class chargeurVE extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

	public static function byAccountId($accountId) {
		return self::byTypeAndSearchConfiguration('chargeurVE','"accountId":"'.$accountId.'"');
	}

	public static function byTypeAndIdentifiant($type, $identifiant) {
		$identKey = type::getIdentifiantChargeur($type);
		$searchConf = sprintf('"%s":"%s"',$identKey,$identifiant);
		$chargeurs = array();
		foreach (chargeurVE::byTypeAndSearchConfiguration('chargeurVE',$searchConf) as $chargeur){
			if ($chargeur->getConfiguration('type') == $type){
				$chargeurs[] = $chargeur;
			}
		}
		return $chargeurs;

	}

    /*     * **********************Gestion du daemon************************* */

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
		$cmd = 'python3 ' . $path . '/chargeurVEd.py';
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
		$cmd .= ' --socketport ' . config::byKey('daemon::port', __CLASS__); // port
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/chargeurVE/core/php/jeechargeurVE.php';
		$cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
		$cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		log::add(__CLASS__, 'info', 'Lancement démon');
		log::add(__CLASS__, "info", $cmd . ' >> ' . log::getPathToLog('chargeurVE_daemon') . ' 2>&1 &');
		$result = exec($cmd . ' >> ' . log::getPathToLog('chargeurVE_daemon.out') . ' 2>&1 &');
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

    /*     * ************************Les crons**************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {
	}
     */

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
	public static function cron5() {
		account::cronHourly();
	}
     */

    /*
     * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
	public static function cron10() {
	}
     */

    /*
     * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
	public static function cron15() {
	}
     */

    /*
     * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
	public static function cron30() {
	}
     */

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
     */
	public static function cronHourly() {
		account::cronHourly();
	}

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDaily() {
	}
     */


    /*     * *********************Méthodes d'instance************************* */

    // Création/mise à jour des commande prédéfinies
	public function UpdateCmds($mandatoryOnly = true) {
		$ids = array();
		foreach (type::commands($this->getConfiguration('type'),$mandatoryOnly) as $logicalId => $config) {
			log::add("chargeurVE","debug","logicalID : " . $logicalId);
			$cmd = chargeurVECmd::byEqLogicIdAndLogicalId($this->getId(),$logicalId);
			if (!is_object($cmd)){
				$cmd = new chargeurVECMD();
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName(__($config['name'],__FILE__));
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
			$cmd->save();
			$ids[$logicalId] = $cmd->getId();
		}
		foreach (type::commands($this->getConfiguration('type'),$mandatoryOnly) as $logicalId => $config) {
			log::add("chargeurVE","debug","logicalID : " . $logicalId);
			if (array_key_exists('value',$config)){
				log::add("chargeurVE","debug","  value : " . $config['value']);
				log::add("chargeurVE","debug","     id : " . $ids[$config['value']]);
				$cmd = chargeurVECmd::byEqLogicIdAndLogicalId($this->getId(),$logicalId);
				if (!is_object($cmd)){
					log::add("chargeurVE","error",(sprintf(__("Commande avec logicalIs=%s introuvable",__FILE__),$logicalId)));
					continue;
				}
				$cmd->setValue($ids[$config['value']]);
				$cmd->save();
			}
		}
	}

    // Fonction exécutée automatiquement avant la création de l'équipement
	public function preInsert() {
		$this->setConfiguration('image',type::images($this->getConfiguration('type'),'chargeur')[0]);
	}

    // Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert() {
		$this->UpdateCmds(false);
	}

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
	public function preUpdate() {
	}

    // Fonction exécutée automatiquement après la mise à jour de l'équipement
	public function postUpdate() {
	}

    // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
	public function preSave() {
	}

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
	public function postSave() {
		if ($this->getIsEnable()){
			$this->startListener();
		} else {
			$this->stopListener();
		}
	}

    // Fonction exécutée automatiquement avant la suppression de l'équipement
	public function preRemove() {
	}

    // Fonction exécutée automatiquement après la suppression de l'équipement
	public function postRemove() {
	}

	public function getPathImg() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			return "/plugins/chargeurVE/plugin_info/chargeurVE_icon.png";
		}
		return $image;
	}

	public function getAccount() {
		return account::byId($this->getAccountId());
	}

	public function getIdentifiant() {
		$type = $this->getConfiguration('type');
		$configName = type::getIdentifiantChargeur($type);
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
			return "/plugins/chargeurVE/plugin_info/chargeurVE_icon.png";
		}
		return $image;
	}

	public function setImage($_image) {
		$this->setConfiguration('image',$_image);
		return $this;
	}
}

class chargeurVECmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
	public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    // Exécution d'une commande
	public function execute($_options = array()) {
		$this->getEqLogic()->getAccount()->execute($this);
	}

    /*     * **********************Getteur Setteur*************************** */
}


