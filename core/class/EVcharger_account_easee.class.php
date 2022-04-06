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


class EVcharger_account_easee extends EVcharger_account {

	protected static $haveDeamon = true;
	public function decrypt() {
		$this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
	}

	public function encrypt() {
		$this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
	}

	private function sendRequest($path, $data = '', $token='' ) {
		log::add("EVcharger","info",__("Easee: envoi d'une requête au cloud", __FILE__));
		if (! $token) {
			$token = $this->getToken();
		}
		$header = [
			'Authorization: Bearer ' . $token,
			"Accept: application/json",
			"Content-Type: application/*+json"
		];
		if (is_array($data)) {
			if (array_key_exists('userName',$data) and array_key_exists('password',$data)) {
				$header = [
					"Accept: application/json",
					"Content-Type: application/*+json"
				];
			}
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
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_POSTFIELDS => $data,
		]);
		$reponse = curl_exec($curl);
		$httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			log::add("EVcharger","error", "CURL Error : " . $err);
			throw new Exception($err);
		}
		log::add("EVcharger","debug", "  " . __("Requête: URL :",__FILE__) . $this->getUrl() . $path);
		$data = json_decode($data,true);
		if (array_key_exists('password',$data)) {
			$data['password'] = "**********";
		}
		$data = json_encode($data);
		log::add("EVcharger","debug", "           " . __("data:",__FILE__) . $data);
		if (substr($httpCode,0,1) != '2') {
			//$reponse = json_decode($reponse,true);
			$msg= sprintf(__("Code retour http: %s - %s",__FILE__) , $httpCode, $reponse);
			log::add("EVcharger","error", $msg);
			throw new Exception ($msg);
		}
		log::add("EVcharger","debug", "  " . __("Code retour http: ",__FILE__) . $httpCode);
		log::add("EVcharger","info", "Requête envoyée");
		return json_decode($reponse, true);
	}

	private function msgToStartDeamonThread() {
		$message = array(
			'cmd' => 'start',
			'url' => $this->url,
			'token' => $this->token['accessToken']
		);
		return $message;
	}

	public function postSave() {
		$this->getToken();
	}

	private function getToken () {
		$token = $this->getCache('token');
		$getNew = false;
		$changed = false;
		if ($token == '') {
			$getNew = true;
		} else {
			$token = json_decode($token,true);
			if ($token['expiresAt'] < time() ) {
				$getNew = true;
			} else if (($token['expiresAt'] - 12*3600) < time() ) {
				$data = array(
					'accessToken' => $token['accessToken'],
					'refreshToken' => $token['refreshToken']
				);
				$token = $this->sendRequest('/api/accounts/refresh_token', $data, $token['accessToken']);
				$token['expiresAt'] = time() + $token['expiresIn'];
				$this->setCache('token',json_encode($token));
				$changed = true;
			}
		}
		if ($getNew) {
			$data = array(
				'userName' => $this->getConfiguration('login'),
				'password' => $this->getConfiguration('password')
			);
			$token = $this->sendRequest('/api/accounts/token', $data, "X");
			$token['expiresAt'] = time() + $token['expiresIn'];
			$this->setCache('token',json_encode($token));
			$changed = true;
		}
		return $token['accessToken'];
	}

	public function setUrl($_url) {
		return $this->setConfiguration('url',$_url);
	}

	public function getUrl() {
		return $this->getConfiguration('url');
	}
}
class EVcharger_account_easeeCmd extends EVcharger_accountCmd  {

}
