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

/*
 * Inclusion des classes héritières
 */
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

	protected static $plugin_id = "chargeurVE";
	public static $typeLabel = "";

	protected $type;
	protected $name = "";
	protected $id;
	protected $isEnable;
    
    /*     * ***********************Methodes static************************** */

	/*
	 * Retourne le prochain Id disponible
	 */
	private static function nextId() {
		$id = config::byKey ('accountId::next',self::$plugin_id,1,true);
		config::save('accountId::next',$id+1,self::$plugin_id);
		return($id);
	}

	public static function byId($id) {
		return unserialize (config::byKey('account::' . $id, self::$plugin_id));
	}

	public static function byTypeAndName ($type, $name) {
		log::add("chargeurVE","info","type: " . $type . "   nom: " . $name);
		$configs = config::searchKey('account::', self::$plugin_id);
		$accounts = array();
		log::add("chargeurVE","info","configs : " . print_r($configs,true));
		foreach ($configs as $config) {
			log::add("chargeurVE","info","config : " . print_r($config['value'],true));
			$account = unserialize ($config['value']);
			log::add("chargeurVE","  info","type: " . $account->getType() . "   nom: " . $account->getName());
			if ($account->getType() == $type and $account->getName() == $name) {
				$accounts[] = $account;
			}
		}
		log::add("chargeurVE","info","CC " . print_r($accounts,true));
		switch (count($accounts)) {
		case 0:
			return NULL;
			break;
		case 1:
			return $accounts[0];
		default:
			return $accounts;
		}
	}

	public static function all () {
		$configs = config::searchKey("account::", self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$accounts[] = unserialize($config['value']);
		}
		return $accounts;
	}

	public static function types() {
		$dir = __DIR__ . '/account';
		$types = array();
		try {
			$dh = opendir($dir);
			while (($file = readdir($dh)) !== false) {
				if (substr_compare($file, '.class.php', -10, 10) === 0) {
					$type = substr_replace($file,'',-10);
					$accountClass = $type . 'Account';
					$account = new $accountClass();
					$label = $account->getTypeLabel();
					$types[] = array('type' => $type, "label" => $label); 
				}
			}
		} catch (Exception $e) {
			log::add('chargeurVE','error', $e->getMessage());
			return false;
		}
		return $types;
	}

    /*     * *********************Methodes d'instance************************ */

	public function __construct() {
		$this->type = substr_replace(get_class($this),'',-7);
	}

	public function save() {
		if (trim($this->name) == "") {
			throw new Exception (__("Le nom n'est pas défini!",__FILE__));
		}
		$accounts = self::byTypeAndName($this->getType(),$this->name);
		log::add("chargeurVE","info","XX " . print_r($accounts,true));
		if (!isset($this->_id) or $this->id == '' ) {
			if ($accounts) {
				throw new Exception (__("Il y a déjà un compte nommé ",__FILE__) . $this->name);
			}
			$this->_id = self::nextId();
		}
		config::save('account::' . $this->_id, serialize($this), self::$plugin_id);
	}

	public function getHumanName($_tag = false, $_prettify = false) {
		$name = '';
		if ($_tag) {
			if ($_prettify) {
				$name .= '<span class="label labelObjectHuman">' . $this->getType() . '</span>';
			} else {
				$name .= $this->getType();
			}
		} else {
			$name .= '['.$this->getType().']'; 
		}
		if ($_prettify) {
			$name .= '<br/><strong>';
		}
		if ($_tag) {
			$name .= ' ' . $this->getName();
		} else {
			$name .= '[' . $this->getName() . ']';
		}
		if ($_prettify) {
			$name .= '</strong>';
		}
		return $name;
	}

	public function getType() {
		return substr_replace(get_class($this),'',-7);
	}

    /*     * **********************Getteur Setteur*************************** */

	/** id **/
	public function getId() {
		return $this->id;
	}

	public function setId($_id) {
		$this->id = $_id;
		return $this;
	}

	/** isEnable **/
	public function getIsEnable() {
		if ($this->isEnable == '' || !is_numeric($this->isEnable)){
			return 0;
		}
		return $this->isEnable;
	}

	public function setIsEnable($_isEnable) {
		$this->isEnable = $_isEnable;
		return $this;
	}

	/** name **/
	public function getName() {
		return $this->name;
	}

	public function setName($_name) {
		$this->name = $_name;
		return $this;
	}

	/** get type Label **/
	public function getTypeLabel() {
		if ($this::$typeLabel == "") {
			return get_class($this);
		} else {
			return $this::$typeLabel;
		}
	}

}
