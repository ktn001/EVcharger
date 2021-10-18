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

class account {
    /*     * *************************Attributs****************************** */

	private static $accountsFile = __DIR__ . "/../../data/accounts.json";

	private $_id;
	private $_login;
	private $_name;
	private $_url;
    
    /*     * ***********************Methode static*************************** */

	/*
	 * Retourne le prochain Id disponible
	 */
	private static function nextId() {
		$id = config::byKey ('nextId','easee',0,true);
		config::save('nextId',$id+1,'easee');
		return($id);
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
			log::add('easee','error', $e->getMessage());
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

	/*
	 * Création d'un account à partir de données fournies
	 */
	public static function initialise ($data) {
		$account = new account;

		// _id
		if ( array_key_exists("id",$data)) {
			if (empty($data['id'])) {
				$account->setId(self::nextId());
			} else {
				$account->setId($data['id']);
			}
		} else {
			$account->setId(self::nextId());
		}

		// _login
		if ( array_key_exists("login",$data)) {
			$account->setLogin($data['login']);
		}

		// _name
		if ( array_key_exists("name",$data)) {
			$account->setName($data['name']);
		}

		// _url
		if ( array_key_exists("url",$data)) {
			$account->setUrl($data['url']);
		}

		return $account;
	}

    /*     * *********************Méthodes d'instance************************* */
    	public function __construct() {
		$this->checkFile();
		$this->_id = -1;
		$this->_login = "";
		$this->_name = "";
		$this->_url = "";
	}

	public function getDatas() {
		log::add("easee","debug","id: " . $this->_id);
		log::add("easee","debug","login: " . $this->_login);
		log::add("easee","debug","name: " . $this->_name);
		log::add("easee","debug","url: " . $this->_url);
		$data = array(
			'id'    => $this->_id,
			'login' => $this->_login,
			'name'  => $this->_name,
			'url'   => $this->_url
		);
		return $data;
	}

    /*     * **********************Getteur Setteur*************************** */

	public function setId($id) {
		$this->_id = $id;
	}

	public function getId() {
		return $this->_id;
	}

	public function setLogin($login) {
		$this->_login = $login;
	}

	public function getLogin() {
		return $this->_login;
	}

	public function setName($name) {
		$this->_name = $name;
	}

	public function getName() {
		return $this->_name;
	}

	public function setUrl($url) {
		$this->_url = $url;
		log::add("easee","debug","id: " . $this->_id);
		log::add("easee","debug","login: " . $this->_login);
		log::add("easee","debug","name: " . $this->_name);
		log::add("easee","debug","url: " . $this->_url);
		log::add("easee","debug","urlx: " . $url);
	}

	public function getUrl() {
		return $this->_url;
	}
}
