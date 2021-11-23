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
require_once dirname(__FILE__) . '/../../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../account.class.php';

class easeeAccount extends account {
    /*     * *************************Attributs****************************** */
    
	//public static $typeLabel = "Easee";
	protected $image;

	protected $login;
	protected $url;

	public static function paramsToEdit() {
		return array(
			'login' => 1,
			'url' => 1,
		);
	}

    /*     * *********************Méthodes d'instance************************* */

	function __construct () {
		parent::__construct();
		$this->url = "https://api.easee.cloud";
	}

	private function setApiToken($save = true) {
		$curl = curl_init();
		$data = array(
			'userName' => $this->getLogin(),
			'password' => $this->getPassword()
		);
		$post_data = json_encode($data);
		curl_setopt_array($curl, [
			CURLOPT_URL => $this->getUrl() . '/api/accounts/token',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => [
				"Accept: application/json",
				"Content-Type: application/*+json"
			],
			CURLOPT_POSTFIELDS => $post_data,
		]);
		$reponse = json_decode(curl_exec($curl),true);
		$httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			log::add("chargeurVE","error", "CURL Error : " . $err);
			throw new Exception($err);
		} else {
			if ($httpCode != '200') {
				throw new Exception ($httpCode . ": " . $reponse['title']);
			}
			if ($save) {
				if (!is_numeric($this->id)) { 
					throw new Exception (__("l'id est incorrect",__FILE__));
				}
				$accessToken = $reponse['accessToken'];
				$expiresAt = time() + $reponse['expiresIn'];
				$refreshToken = $reponse['refreshToken'];
				$config = array(
					'token' => $reponse['accessToken'],
					'expiresAt' => $expiresAt,
					'refreshToken' => $reponse['refreshToken'],
				);
				config::save('easeeToken::' . $this->id, json_encode($config), self::$plugin_id);
			}
		}
	}

	private function deleteApiToken() {
		config::remove('easeeToken::' . $this->id, self::$plugin_id);
	}

	private	function getApiToken() {
		if ($this->id == ''){
			return null;
		}
		return config::byKey('easeeToken::' . $this->id,'chargeurVE');
	}

	public function needPasswordToSave() {
		if ($this->getIsEnable() == 0) {
			return false;
		}
		$token = $this->getApiToken();
		if (! is_array($token)) {
			log::add('chargeurVE','debug',__CLASS__ . '::Presave: ' . __("Un nouveau token doit être créé.",__FILE__));
			return true;
		}
		if (time() > $token['expiresAt']){
			log::add('chargeurVE','debug',__CLASS__ . '::Presave: ' . __("Le token a expiré.",__FILE__));
			return true;
		}
		$old = self::byId($this->getId());
		if (! is_object($old)) {
			log::add('chargeurVE','debug',__CLASS__ . '::Presave: ' . __("Nouveau compte",__FILE__));
			return true;
		}
		if ($this->getLogin() != $actuel->getLogin()) {
			log::add('chargeurVE','debug',__CLASS__ . '::Presave: ' . __("Le login a changé",__FILE__));
			return true;
		}
		if ($this->getUrl() != $actuel->getUrl()) {
			log::add('chargeurVE','debug',__CLASS__ . '::Presave: ' . __("L'URL a changé",__FILE__));
			return true;
		}
		return false;
	}

	public function preSave() {
		$this->setLogin(trim ($this->getLogin()));
		if ($this->getLogin() == "") {
			throw new Exception (__("le login n'est pas défini!",__FILE__));
		}
		$this->setUrl(trim ($this->getUrl()));
		if ($this->getUrl() == "") {
			throw new Exception (__("l'url n'est pas définie!",__FILE__));
		}
	}

	public function preInsert() {
		if ($this->isEnable) {
			// $key = $this->setApiToken(false);
		}
	}

	public function preUpdate() {
		if ($this->isEnable) {
			// $key = $this->setApiToken();
		}
	}
		
	public function postInsert() {
		if ($this->isEnable) {
			// $key = $this->setApiToken();
		}
	}

	public function postSave() {
		if (!$this->isEnable) {
			// $this->deleteApiToken();
		}
	}

	public function postRemove() {
		// $this->deleteApiToken();
	}

    /*     * **********************Getteur Setteur*************************** */

	/* login */
	public function setLogin($login) {
		$this->login = $login;
		return $this;
	}

	public function getLogin() {
		return $this->login;
	}

	/* url */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	public function getUrl() {
		return $this->url;
	}
}
