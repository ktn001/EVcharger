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
$dir = __DIR__ . '/../class/account';
if ($dh = opendir($dir)){
    while (($file = readdir($dh)) !== false){
	    if (substr_compare($file, ".class.php",-10,10) === 0) {
        	require_once  $dir . '/' . $file;
	    }
    }
    closedir($dh);
}

class account {
    /*     * *************************Attributs****************************** */

	private static $accountsFile = __DIR__ . "/../../data/accounts.json";

	protected $_id;
	public $_TypeLabel;

    
    /*     * ***********************Methode static*************************** */


	/*
	 * Retourne le prochain Id disponible
	 */
	private static function nextId() {
		$id = config::byKey ('nextId','chargeurVE',0,true);
		config::save('nextId',$id+1,'chargeurVE');
		return($id);
	}

	/*
	 * Création d'une instance à partir de données
	 */
	public static function fromData ($data) {
		log::add("chargeurVE", "debug", $data);
		if (gettype($data) == "string") {
			$data = json_decode($data,true)[0];
		}
		log::add("chargeurVE", "debug", print_r($data,true));
		if (!array_key_exists('type', $data)) {
			throw new Exception("Type d'account non défini");
		}
		$classe = $data['type'] . "Account";
		log::add("chargeurVE","debug",$classe);
		$account = new $classe();
	}

	public static function getTypeLabels() {
		$dir = __DIR__ . '/account';
		$labels = array();
		try {
			$dh = opendir($dir);
			while (($file = readdir($dh)) !== false) {
				if (substr_compare($file, '.class.php', -10, 10) === 0) {
					$account = substr_replace($file,'',-10);
					$accountClass = $account . 'Account';
					$acc = new $accountClass();
					$label = $acc->getTypeLabel();
					$labels[$account] = $label;
				}
			}
		} catch (Exception $e) {
			log::add('chargeurVE','error', $e->getMessage());
			return false;
		}
		return $labels;
	}
	
	/*
	 * Retoune les infos pour l'affichage des thumbnails
	 */
	public static function ThumbnailsInfos() {
		self::checkFile();
	}

	/*
	 * Création du répertoire et fichier json s'ils n'existent pas encore
	 */
	private static function checkFile () {
		$dataDir = dirname(self::$accountsFile);
		try {
			if ( !is_dir($dataDir)) {
				mkdir($dataDir,0775);
			}
			if ( !file_exists(self::$accountsFile)) {
				touch(self::$accountsFile);
			}
			chmod(self::$accountsFile,0664);
		} catch (Exception $e) {
			log::add('chargeurVE','error', $e->getMessage());
			return false;
		}
		return true;
	}

	/*
	 * Sauvegarde de tous les accounts qui sont founis dans le json passé en argument.
	 *
	 * Les accounts existants seront tous supprimés
	 */
	public static function saveAll ($accounts) {
		self::checkFile();
		$accounts = json_decode($accounts,true);
		$accounts2Save = array();
		foreach ($accounts as $account) {
			$a = self::initialise($account);
			$accounts2Save[] = $a->getDatas();
		}
		file_put_contents(self::$accountsFile,json_encode($accounts2Save,JSON_PRETTY_PRINT));
	}

	/*
	 * retourne la config de tous les accounts sous la forme d'un json
	 */
	public static function getAll () {
		return file_get_contents(self::$accountsFile);
	}

    /*     * *********************Méthodes d'instance************************* */

	public function save() {
	}

    /*     * **********************Getteur Setteur*************************** */

	public function getTypeLabel() {
		if ($this->_typeLabel == "") {
			return get_class($this);
		} else {
			return $this->_typeLabel;
		}
	}

}
