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
		foreach (self::byType('easee') as $account) {
			$account->renewApiToken();
			$message['action'] = "newKey";
			$message['key'] = $account->token['token'];
			$account->send2deamond($message);
		}
	}

    /*     * *********************Méthodes d'instance************************* */

	function __construct () {
		parent::__construct();
		$this->url = "https://api.easee.cloud";
	}

	private function sendRequest($path, $data) {
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
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => [
				"Accept: application/json",
				"Content-Type: application/*+json"
			],
			CURLOPT_POSTFIELDS => $data,
		]);
		$reponse = json_decode(curl_exec($curl),true);
		$httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			$this->log("error", "CURL Error : " . $err);
			throw new Exception($err);
		}
		if ($httpCode != '200') {
			$this->log("warning", $httpCode . ": " . $reponse['title']);
			throw new Exception ($reponse['title']);
		}
		return $reponse;
	}

	private function renewApiToken() {
		if ($this->IsEnabled() == 0) {
			return;
		}
		$data = array(
			'accessToken' => $this->token['token'],
			'refreshToken' => $this->token['refreshToken']
		);
		$reponse = $this->sendRequest('/api/accounts/refresh_token', $data);
		$this->token = array(
			'token' => $reponse['accessToken'],
			'expiresAt' => time() + $reponse['expiresIn'],
			'refreshToken' => $reponse['refreshToken'],
		);
		$this->log("info","Account " . $this->getHumanName(false) . ": " . __('token renouvelé.',__FILE__));
		$this->save();
	}
 
	private function setApiToken($password = '') {
		$data = array(
			'userName' => $this->getLogin(),
			'password' => $password
		);
		$reponse = $this->sendRequest('/api/accounts/token', $data);
		$this->token = array(
			'token' => $reponse['accessToken'],
			'expiresAt' => time() + $reponse['expiresIn'],
			'refreshToken' => $reponse['refreshToken'],
		);
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
				$this->log('chargeurVE','debug', __("Un nouveau token doit être créé.",__FILE__));
				throw new Exception  (__("Un nouveau token doit être créé.",__FILE__),1);
			}
			if (time() > $this->token['expiresAt']){
				$this->log('chargeurVE','debug', __("Le token a expiré.",__FILE__));
				throw new Exception  (__("Le token a expiré.",__FILE__),1);
			}
			$old = self::byId($this->getId());
			if (! is_object($old)) {
				$this->log('chargeurVE','debug', __("Nouveau compte",__FILE__));
				throw new Exception  (__("Nouveau compte",__FILE__),1);
			}
			if ($this->getLogin() != $old->getLogin()) {
				$this->log('chargeurVE','debug', __("Le login a changé",__FILE__));
				throw new Exception  (__("Le login a changé",__FILE__),1);
			}
			if ($this->getUrl() != $old->getUrl()) {
				$this->log('chargeurVE','debug', __("L'URL a changé",__FILE__));
				throw new Exception  (__("L'URL a changé",__FILE__),1);
			}
		} elseif ($this->IsEnabled() == 1 ) {
			$this->setApiToken($password);
		} else {
			$this->deleteApiToken();
		}

	}

	public function startDeamondThread() {
		$message['cmd'] = 'start';
		$message['token'] = $this->token['token'];
		$this->send2Deamond($message);
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
}
