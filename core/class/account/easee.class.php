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
require_once __DIR__ . '/../../../../../core/php/core.inc.php';
require_once __DIR__ . '/../account.class.php';

class easeeAccount extends account {
    /*     * *************************Attributs****************************** */
    
	private $token;
	protected $image;
	protected $login;
	protected $url;

	public static function paramsToEdit() {
		return array(
			'login' => 1,
			'url' => 1,
		);
	}

	public static function cronHourly() {
		foreach (self::byModel('easee') as $account) {
			$account->renewApiToken();
			$account->save();
		}
	}

    /*     * *********************Méthodes d'instance************************* */

	function __construct () {
		parent::__construct();
		$this->url = "https://api.easee.cloud";
	}

	private function getMapping() {
		$mappingFile = __DIR__ . '/../../config/easee/mapping.ini';
		$mapping = parse_ini_file($mappingFile, true);
		if ($mapping == false) {
			$msg = sprintf(__('Erreur lors de la lecture de %s',__FILE__),$mappingFile);
			log::add("EVcharger","error",$msg);
		}
		return $mapping['API'];
	}

	private function getTransforms() {
		$transfomsFile = __DIR__ . '/../../config/easee/transforms.ini';
		$transfoms = parse_ini_file($transfomsFile, true);
		if ($transfoms == false) {
			$msg = sprintf(__('Erreur lors de la lecture de %s',__FILE__),$transfomsFile);
			log::add("EVcharger","error",$msg);
		}
		return $transfoms;
	}

	private function sendRequest($path, $data = '' ) {
		if (is_array($data)) {
			$data = json_encode($data);
		}
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $this->getUrl() . $path,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $data == "" ? 'GET' : 'POST',
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer " . $this->token['accessToken'],
				"Accept: application/json",
				"Content-Type: application/*+json"
			],
			CURLOPT_POSTFIELDS => $data,
		]);
		$reponse = curl_exec($curl);
		$httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			$this->log("error", "CURL Error : " . $err);
			throw new Exception($err);
		}
		if (substr($httpCode,0,1) != '2') {
			$this->log("debug", "Requête: path:". $path);
			$this->log("debug", "         data:". $data);
			$this->log("error", "Code retour http: " . $httpCode);
			$title = $reponse;
			$matches = array();
			if (preg_match('/<title>(.*?)<\/title>/',$reponse, $matches)) {
				$title = $matches[1];
			}
			$this->log("error", $title);
			throw new Exception ($title);
		}
		return json_decode($reponse, true);
	}

	private function renewApiToken() {
		if ($this->IsEnabled() == 0) {
			return;
		}
		$data = array(
			'accessToken' => $this->token['accessToken'],
			'refreshToken' => $this->token['refreshToken']
		);
		$response = $this->sendRequest('/api/accounts/refresh_token', $data);
		$this->setToken($response);
		$this->log("info", __('token renouvelé.',__FILE__));
	}
 
	private function retreiveToken($password = '') {
		$data = array(
			'userName' => $this->getLogin(),
			'password' => $password
		);
		$response = $this->sendRequest('/api/accounts/token', $data);
		$this->setToken($response);
	}

	private function deleteApiToken() {
		$this->token = null;
	}

	public function preSave($options) {
		$password = null;
		if (is_array($options) and array_key_exists('password',$options)){
			$password = $options['password'];
		}
		$this->setLogin(trim ($this->getLogin()));
		if ($this->getLogin() == "") {
			throw new Exception (__("le login n'est pas défini!",__FILE__));
		}
		$this->setUrl(trim ($this->getUrl()));
		if ($this->getUrl() == "") {
			throw new Exception (__("l'url n'est pas définie!",__FILE__));
		}

		if ($this->IsEnabled() == 1 and $password == null ) {
			if (! is_array($this->token)) {
				$this->log('debug', __("Un nouveau token doit être créé.",__FILE__));
				throw new Exception  (__("Un nouveau token doit être créé.",__FILE__),1);
			}
			if (time() > $this->token['expiresAt']){
				$this->log('debug', __("Le token a expiré.",__FILE__));
				throw new Exception  (__("Le token a expiré.",__FILE__),1);
			}
			$old = self::byId($this->getId());
			if (! is_object($old)) {
				$this->log('debug', __("Nouveau compte",__FILE__));
				throw new Exception  (__("Nouveau compte",__FILE__),1);
			}
			if ($this->getLogin() != $old->getLogin()) {
				$this->log('debug', __("Le login a changé",__FILE__));
				throw new Exception  (__("Le login a changé",__FILE__),1);
			}
			if ($this->getUrl() != $old->getUrl()) {
				$this->log('debug', __("L'URL a changé",__FILE__));
				throw new Exception  (__("L'URL a changé",__FILE__),1);
			}
		} elseif ($this->IsEnabled() == 1 ) {
			$this->retreiveToken($password);
		} else {
			$this->deleteApiToken();
		}
	}

	public function postSave($options) {
		if ($this->isModified(array('token'))){
			if ( ! $this->isModified('enabled')){
				$message['cmd'] = 'newToken';
				$message['token'] = $this->token['accessToken'];
				$this->send2Deamon($message);
			}
		}
	}

	public function msgToStartDeamonThread() {
		$message = array(
			'cmd' => 'start',
			'url' => $this->url,
			'token' => $this->token['accessToken'],
		);
		return $message;
	}

	public function execute_cable_lock($cmd) {
		$serial =  $cmd->getEqLogic()->getConfiguration("serial");
		$path = '/api/chargers/'. $serial .'/commands/lock_state';
		$data = array ( 'state' => 'true');
		$response = $this->sendRequest($path, $data);
	}

	public function execute_cable_unlock($cmd) {
		$serial =  $cmd->getEqLogic()->getConfiguration("serial");
		$path = '/api/chargers/'. $serial .'/commands/lock_state';
		$data = array ( 'state' => 'false');
		$response = $this->sendRequest($path, $data);
	}

	public function execute_refresh($cmd) {
		$chargeurId = $cmd->getEqLogic()->getId();
		$chargeur = EVcharger::byId($chargeurId);
		$serial =  $cmd->getEqLogic()->getConfiguration("serial");
		$path = '/api/chargers/'.$serial.'/state';
		$response = $this->sendRequest($path);
		$mapping = $this->getMapping();
		$transforms = $this->getTransforms();
		foreach (array_keys($response) as $key){
			log::add('EVcharger','debug',$key);
			if ( ! array_key_exists($key,$mapping)){
				continue;
			}
			foreach (explode(',',$mapping[$key]) as $logicalId){
				log::add('EVcharger','debug',"  " . $logicalId);
				$value = $response[$key];
				if (array_key_exists($logicalId, $transforms)) {
					log::add('EVcharger','debug',print_r($transforms,true));
					log::add('EVcharger','debug',print_r($value,true));
					$value = $transforms[$logicalId][$value];
				}
				$chargeur->checkAndUpdateCmd($logicalId,$value);
			}
		}

	}

    /*     * **********************Getteur Setteur*************************** */

	/* login */
	public function setLogin($_login) {
		if ($_login != $this->login) {
			$this->setModified('login');
		}
		$this->login = $_login;
		return $this;
	}

	public function getLogin() {
		return $this->login;
	}

	/* url */
	public function setUrl($_url) {
		if ($_url != $this->url) {
			$this->setModified('url');
		}
		$this->url = $_url;
		return $this;
	}

	public function getUrl() {
		return $this->url;
	}

	/* ApiToken */

	public function setToken($_token) {
		$this->log("debug",print_r($_token,true));
		if ($_token['accessToken'] != $this->token['accessToken']) {
			$this->setModified('token');
		}
		$this->token = array(
			'accessToken' => $_token['accessToken'],
			'expiresAt' => time() + $_token['expiresIn'],
			'refreshToken' => $_token['refreshToken'],
		);
	}
}
