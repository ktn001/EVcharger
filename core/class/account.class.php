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
	protected $enabled;
	protected $image;
	protected $modified = array();

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
		$account =  unserialize (config::byKey('account::' . $id, 'chargeurVE' ));
		$account->resetModified();
		return $account;
	}

	/*
	 * Recherche d'accounts selon le type
	 */
	public static function byType ($type) {
		$configs = config::searchKey('account::', self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$account = unserialize ($config['value']);
			$account->resetModified();
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
			$account->resetModified();
			if ($account->getType() == $type and $account->getName() == $name) {
				$accounts[] = $account;
			}
		}
		return $accounts;
	}

	/*
	 * Retourne une liste contenant tous les accounts
	 */
	public static function all ( $enabled=false ) {
		$configs = config::searchKey("account::", self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$account = unserialize($config['value']);
			$account->resetModified();
			if ( ! $enabled or $account->isEnabled()){
				$accounts[] = $account;
			}
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
	 * Wrapper pour les logs
	 */
	protected function log ($level, $message){
		log::add('chargeurVE',$level,'[' . get_class($this) . '][' . $this->name . '] ' . $message);
	}

	/*
	 * Retient qu'une propriété a été modififée mais pas sauvegardée
	 */
	protected function setModified($name){
		if (! in_array($name, $this->modified)) {
			log::add("chargeurVE","debug", $this->getHumanName() . " " . $name . __(" est modifié.",__FILE__));
			$this->modified[] = $name;
		}
	}

	/*
	 * Indique si une propriérét a été modifiée depuis la derinère sauvegarde
	 */
	protected function isModified($name){
		if (is_array($name)){
			foreach ($name as $n){
				if ($this->isModified($n)){
					return true;
				}
			}
			return false;
		}
		return in_array($name, $this->modified);
	}

	/*
	 * Remet à zéro la liste de propriététs modifiées
	 */
	protected function resetModified(){
		$this->modified = array();
	}

	/*
	 * Enregistrement de l'account
	 */
	public function save($options = null) {
		if (trim($this->name) == "") {
			throw new Exception (__("Le nom n'est pas défini!",__FILE__));
		}
		if (method_exists($this, 'preSave')) {
			$this->preSave($options);
		}
		$accounts = self::byTypeAndName($this->getType(),$this->name);
		if (count($accounts) > 1) {
			throw new Exception (__("Il exsite plusieurs compte de ce type nmmés ",__FILE__) . $this->name);
		}
		$onInsert = false;
		if (!isset($this->id) or $this->id == '' ) {
			if ($accounts) {
				throw new Exception (__("Il y a déjà un compte de ce type nommé ",__FILE__) . $this->name);
			}
			$onInsert = true;
			$this->id = self::nextId();
			if (method_exists($this, 'preInsert')) {
				$this->preInsert($options);
			}
		} else {
			if ( ! $accounts ) {
				throw new Exception (__("La version précédente est introuvable",__FILE__));
			}
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
		if ($this->isModified('enabled')) {
			if ($this->enabled){
				$this->startDeamondThread();
			} else {
				$this->stopDeamondThread();
			}
		}
		$this->resetModified();
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
		$this->log('debug','send2deamond: ' . print_r($message,true));
		if (chargeurVE::deamon_info()['state'] != 'ok'){
			$this->log('error', __("Le démon n'est pas démarré!",__FILE__));
			throw new Exception(__("Le démon n'est pas démarré!",__FILE__));
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

	/** enabled **/
	public function getEnabled() {
		if ($this->enabled == ''){
			return 0;
		}
		return $this->enabled;
	}

	public function isEnabled() {
		if ($this->getEnabled() == 0) {
			return false;
		}
		return true;
	}

	public function setEnabled($_enabled) {
		if ($_enabled != $this->enabled) {
			$this->setModified('enabled');
		}
		$this->enabled = $_enabled;
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
		if ($_image != $this->image){
			$this->setModified('image');
		}
		$this->image = $_image;
		return $this;
	}

	/** name **/
	public function getName() {
		return $this->name;
	}

	public function setName($_name) {
		if ($_name != $this->name){
			$this->setModified('name');
		}
		$this->name = $_name;
		return $this;
	}

	/** type **/
	public function getType() {
		return $this->type;
	}

}
