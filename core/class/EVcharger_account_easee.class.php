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

	public function decrypt() {
		$this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
	}

	public function encrypt() {
		$this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
	}

	private function sendRequest($path, $data = '' ) {
		log::add("EVcharger","info","┌─Easee: envoi d'une requête au cloud");
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
			log::add("EVCharger","error", "CURL Error : " . $err);
			throw new Exception($err);
		}
		if (substr($httpCode,0,1) != '2') {
			log::add("EVCharger","debug", "| Requête: path:". $path);
			log::add("EVCharger","debug", "|          data:". $data);
			log::add("EVCharger","error", "| Code retour http: " . $httpCode);
			$title = $reponse;
			$matches = array();
			if (preg_match('/<title>(.*?)<\/title>/',$reponse, $matches)) {
				$title = $matches[1];
			}
			log::add("EVCharger","error", "└─" . $title);
			throw new Exception ($title);
		}
		log::add("EVCharger","info", "└─Requête envoyée");
		return json_decode($reponse, true);
	}

}

class EVcharger_account_easeeCmd extends EVcharger_accountCmd  {

}
