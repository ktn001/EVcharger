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
    
	public static $typeLabel = "Easee";

	protected $login;
	protected $password;
	protected $url;

    /*     * *********************Méthodes d'instance************************* */

	function __construct () {
		parent::__construct();
		$this->url = "https://easee.cloud";
	}

	function save () {
		if (trim($this->login) == "") {
			throw new Exception (__("le login n'est pas défini!",__FILE__));
		}
		$this->login = trim ($this->login);
		if (trim($this->password) == "") {
			throw new Exception (__("le password n'est pas défini!",__FILE__));
		}
		$this->password = trim ($this->password);
		if (trim($this->url) == "") {
			throw new Exception (__("l'url n'est pas définie!",__FILE__));
		}
		$this->url = trim ($this->url);
		parent::save();
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

	/* password */
	public function setPassword($password) {
		$this->password = $password;
		return $this;
	}

	public function getPassword() {
		return $this->password;
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
