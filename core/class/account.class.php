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
require_once __DIR__ . '/../php/chargeurVE.inc.php';

class account {
    /*     * *************************Attributs****************************** */

	protected static $plugin_id = "chargeurVE";

	protected $type;
	protected $name = "";
	protected $id;
	protected $isEnable;
	protected $image;

    /*     * ***********************Methodes static************************** */

	/*
	 * Retourne le prochain Id disponible
	 */
	private static function nextId() {
		$id = config::byKey ('accountId::next',self::$plugin_id,1,true);
		config::save('accountId::next',$id+1,self::$plugin_id);
		return($id);
	}

	/*
	 * retourne l'account dont l'id est donné en argument
	 */
	public static function byId($id) {
		return unserialize (config::byKey('account::' . $id, 'chargeurVE' ));
	}

	/*
	 * Recherche d'accounts selon le type
	 */
	public static function byType ($type) {
		$configs = config::searchKey('account::', self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$account = unserialize ($config['value']);
			if ($account->getType() == $type) {
				$accounts[] = $account;
			}
		}
		return $accounts;
	}

	/*
	 * Recherche les objets qui ont le type et le nom donnés en argument
	 */
	public static function byTypeAndName ($type, $name) {
		$configs = config::searchKey('account::', self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$account = unserialize ($config['value']);
			if ($account->getType() == $type and $account->getName() == $name) {
				$accounts[] = $account;
			}
		}
		return $accounts;
	}

	/*
	 * Retounre une liste contenant tous les accounts
	 */
	public static function all ( $sortBy="type") {
		$configs = config::searchKey("account::", self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$accounts[] = unserialize($config['value']);
		}
		return $accounts;
	}

	/*
	 * Retourne la liste des paramêtres éditables pour la construction
	 * du modal d'édition
	 */
	public static function paramsToEdit() {
		return array(
			'login' => 0,
			'password' => 0,
			'url' => 0,
		);
	}

	/*
	 * Retourne la liste des images d'account pour le type de plugin
	 */
	public static function images($type) {
		$images = array();
		$path = realpath(__DIR__ . "/../../desktop/img/". $type);
		if($dir = opendir($path)) {
			while (($fileName = readdir($dir)) !== false){
				if (preg_match('/^account.*\.png$/',$fileName)){
					$images[] = '/plugins/chargeurVE/desktop/img/' . $type . '/' . $fileName;
				}
			}
			closedir($dir);
		}
		if (count($images) == 0){
			$images[] = "/plugins/chargeurVE/desktop/img/account.png";
		}
		return $images;
	}

	public static function cronHourly() {
		easeeAccount::cronHourly();
	}

    /*     * *********************Methodes d'instance************************ */

	/*
	 * Constructeur
	 */
	public function __construct() {
		$this->type = substr_replace(get_class($this),'',-7);
		$this->image = self::images($this->type)[0];
	}

	/*
	 * Enregistrement de l'account
	 */
	public function save($options = null) {
		if (trim($this->name) == "") {
			throw new Exception (__("Le nom n'est pas défini!",__FILE__));
		}
		$accounts = self::byTypeAndName($this->getType(),$this->name);
		$onInsert = false;
		if (method_exists($this, 'preSave')) {
			$this->preSave($options);
		}
		if (!isset($this->id) or $this->id == '' ) {
			if ($accounts) {
				throw new Exception (__("Il y a déjà un compte de ce type nommé ",__FILE__) . $this->name);
			}
			if (method_exists($this, 'preInsert')) {
				$this->preInsert($options);
			}
			$onInsert = true;
			$this->id = self::nextId();
		} else {
			if (method_exists($this, 'preUpdate')) {
				$this->preUpdate($options);
			}
		}
		config::save('account::' . $this->id, serialize($this), self::$plugin_id);
		if ($onInsert) {
			if (method_exists($this, 'postInsert')) {
				$this->postInsert($options);
			}
		} else {
			if (method_exists($this, 'postUpdate')) {
				$this->postUpdate($options);
			}
		}
		if (method_exists($this, 'postsave')) {
			$this->postSave($options);
		}
		if ($this->isEnable){
			$this->startDeamondThread();
		} else {
			$this->stopDeamondThread();
		}
	}

	/*
	 * suppression de l'account
	 */
	public function remove() {
		if (method_exists($this, 'preRemove')) {
			$this->preRemove();
		}
		config::remove('account::' . $this->id, self::$plugin_id);
		if (config::byKey('account::' . $this->id, self::$plugin_id) != '') {
			throw new Exception (__("L'account n'a pas pu être supprimé!",__FILE__));
		}
		if (method_exists($this, 'postRemove')) {
			$this->postRemove();
		}
	}

	public function send2deamond($message) {
		log::add('chargeurVE','debug','send2deamond: ' . $message);
		$deamonInfo = chargeurVE::deamon_info();
		if ($deamonInfo['state'] != 'ok'){
			throw new Exception("Le démon n'est pas démarré!");
		}
		$params['apikey'] = jeedom::getApiKey('chargeurVE');
		$params['type'] = $this->getType();
		$params['id'] = $this->getId();
		$params['message'] = $message;
		$payLoad = json_encode($params);
		$socket = socket_create(AF_INET, SOCK_STREAM,0);
		socket_connect($socket,'127.0.0.1', config::byKey('daemon::port','chargeurVE'));
		socket_write($socket, $payLoad, strlen($payLoad));
		socket_close($socket);
	}

	public function startDeamondThread() {
		$message['cmd'] = 'start';
		$this->send2Deamond($message);
	}

	public function stopDeamondThread() {
		$message['cmd'] = 'stop';
		$this->send2Deamond($message);
	}

	public function getHumanName($_tag = false, $_prettify = false) {
		$name = '';
		$type = type::byName($this->getType());
		if ($_tag) {
			if ($_prettify) {
				if ($type['customColor'] == 1) {
					$name .= '<span class="label" style="background-color:' . $type['tagColor'] . ';color:' . $type['tagTextColor'] . '">' . $type['label'] . '</span>';
				} else {
					$name .= '<span class="label labelObjectHuman">' . $type['label'] . '</span>';
				}
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

	/** image **/
	public function getImage() {
		if ($this->image == "") {
			return "plugins/" . self::$plugin_id . "/desktop/img/account.png";
		}
		return $this->image;
	}

	public function setImage($_image) {
		$this->image = $_image;
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

	/** type **/
	public function getType() {
		return $this->type;
	}

}
