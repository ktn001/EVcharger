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
    
  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */
    
    /*     * ***********************Methode static*************************** */

    /*
     * Info sur le daemon
     */
    public static function deamon_info() {
        $return = array();
        $return['log'] = __CLASS__;
        $return['state'] = 'nok';
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
            } else {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    /*
     * Lancement de deamon
     */
    public static function deamon_start() {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/bin'); // répertoire du démon
        $cmd = 'python3 ' . $path . '/chargeurVEd.py'; 
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --socketport ' . config::byKey('deamon::port', __CLASS__); // port
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/template/core/php/jeechargeurVE.php';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid'; 
        log::add(__CLASS__, 'info', 'Lancement démon');
        $result = exec($cmd . ' >> ' . log::getPathToLog('chargeurVE_daemon') . ' 2>&1 &');
        $i = 0;
        while ($i < 20) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($deamon_info['state'] != 'ok') {
            log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
            return false;
        }
        message::removeAll(__CLASS__, 'unableStartDeamon');
        return true;
    }

    public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid'; // ne pas modifier
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        system::kill('chargeurVEd.py'); // nom du démon à modifier
        sleep(1);
    }
    
    public static function dependancy_install() {
        # log::remove(__CLASS__ . '_update');
	return array(
	    'script' => dirname(__FILE__) . '/../../resources/bin/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency',
	    'log' => log::getPathToLog(__CLASS__ . '_update')
	);
    }

    public static function dependancy_info(){
    	$return = array();
	$return ['log'] = log::getPathToLog(__CLASS__ . '_update');
	$return ['progress_file'] = jeedom::getTmpFolder(__CLASS__) . 'dependency';
	if (file_exists(jeedom::getTmpFolder(__CLASS__) . 'dependency')) {
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

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
	public static function cron() {
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] != 'ok') {
			throw new Exception("Le démon n'est pas démarré");
		}
		$params['apikey'] = jeedom::getApiKey(__CLASS__);
		$params['message'] = "Ceci est un message";
		$payLoad = json_encode($params);
		log::add(__CLASS__,"info",$payLoad);
		log::add(__CLASS__,"info",config::byKey('deamon::port', __CLASS__));
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket,'127.0.0.1', config::byKey('deamon::port', __CLASS__));
		socket_write($socket, $payLoad, strlen($payLoad));
		socket_close($socket);
	}
//		log::add("chargeurVE","debug","Lancement du Cron");
//		$curl = curl_init();
//
//			 // 'userName' => '+41797491023'
//		$data = array (
//			 'userName' => 'christian@ockolly.ch',
//			 'password' => 'EMobile&1e'
//		);
//		
//		$post_data = json_encode($data);
//
//		curl_setopt_array($curl, [
//			CURLOPT_URL => "https://api.easee.cloud/api/accounts/token",
//			CURLOPT_RETURNTRANSFER => true,
//			CURLOPT_ENCODING => "",
//			CURLOPT_MAXREDIRS => 10,
//			CURLOPT_TIMEOUT => 30,
//			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//			CURLOPT_CUSTOMREQUEST => "POST",
//			CURLOPT_HTTPHEADER => [
//				"Accept: application/json",
//				"Content-Type: application/*+json"
//			],
//			CURLOPT_POSTFIELDS => $post_data,
//		]);
//		$response = curl_exec($curl);
//		$err = curl_error($curl);
//
//		curl_close($curl);
//
//		if ($err) {
//			log::add("chargeurVE","debug", "cURL Error #:" . $err);
//		} else {
//			log::add("chargeurVE","debug", "XXX " . $response);
//		}
//		log::add("chargeurVE","debug","Fin du Cron");
//    }

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
      public static function cron5() {
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
      public static function cronHourly() {
      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {
      }
     */



    /*     * *********************Méthodes d'instance************************* */
    
 // Fonction exécutée automatiquement avant la création de l'équipement 
    public function preInsert() {
        
    }

 // Fonction exécutée automatiquement après la création de l'équipement 
    public function postInsert() {
        
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
        
    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement 
    public function preRemove() {
        
    }

 // Fonction exécutée automatiquement après la suppression de l'équipement 
    public function postRemove() {
        
    }

    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    public function getPathImg () {
	$image = $this->getConfiguration('image');
	if ($image == '') {
	    return "plugins/chargeurVE/plugin_info/chargeurVE_icon.png";
	}
	return "plugins/chargeurVE/desktop/img/" . $image;
    }

    /*     * **********************Getteur Setteur*************************** */
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
        
     }

    /*     * **********************Getteur Setteur*************************** */
}


