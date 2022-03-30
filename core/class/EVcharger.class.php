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

	public static function byType($_eqType_name, $onlyEnable = false) {
		if (strpos($_eqType_name, '%') === false) {
			return parent::byType($_eqType_name, $onlyEnable);
		}
		$values = array(
			'eqType_name' => $_eqType_name,
		);
		$sql =  'SELECT DISTINCT eqType_name';
		$sql .= '   FROM eqLogic';
		$sql .= '   WHERE eqType_name like :eqType_name';
		$eqTypes = DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL);
		$eqLogics = array ();
		foreach ($eqTypes[0] as $eqType) {
			 $eqLogics = array_merge($eqLogics,parent::byType($eqType, $onlyEnable));
		}
		return $eqLogics;
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

	public function getLinkToConfiguration() {
		if (isset($_SESSION['user']) && is_object($_SESSION['user']) && !isConnect('admin')) {
			return '#';
		}
		return 'index.php?v=d&p=EVcharger&m=EVcharger&id=' . $this->getId();
	}

}

class EVchargerCmd extends cmd {

	public function dontRemoveCmd() {
		if ($this->getLogicalId() == "refresh") {
			return true;
		}
		return false;
	}

}

require_once __DIR__  . '/EVcharger_account.class.php';
require_once __DIR__  . '/EVcharger_charger.class.php';
require_once __DIR__  . '/EVcharger_vehicle.class.php';
