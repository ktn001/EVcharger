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
require_once __DIR__ . '/../php/EVcharger.inc.php';

class account {
    /*     * *************************Attributs****************************** */

	protected static $plugin_id = "EVcharger";

	protected $model;
	protected $name = "";
	protected $id;
	protected $enabled;
	protected $image;
	protected $modified = array();

    /*     * ***********************Methodes static************************** */

    /**
     * Retourne le prochain Id disponible
     */
	private static function nextId() {
		$id = config::byKey ('accountId::next',self::$plugin_id,1,true);
		config::save('accountId::next',$id+1,self::$plugin_id);
		return($id);
	}

    /**
     * retourne l'account dont l'id est donné en argument
     */
	public static function byId($id) {
		$account =  unserialize (config::byKey('account::' . $id, 'EVcharger' ));
		$account->resetModified();
		return $account;
	}

    /**
     * Recherche d'accounts selon le modèle
     */
	public static function byModel ($model) {
		$configs = config::searchKey('account::', self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$account = unserialize ($config['value']);
			$account->resetModified();
			if ($account->getModel() == $model) {
				$accounts[] = $account;
			}
		}
		return $accounts;
	}

    /**
     * Recherche les objets qui ont le modèle et le nom donnés en argument
     */
	public static function byModelAndName ($model, $name) {
		$configs = config::searchKey('account::', self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$account = unserialize ($config['value']);
			$account->resetModified();
			if ($account->getModel() == $model and $account->getName() == $name) {
				$accounts[] = $account;
			}
		}
		return $accounts;
	}

    /**
     * Retourne une liste contenant tous les accounts
     */
	public static function all ( $enabled=false ) {
		$configs = config::searchKey("account::", self::$plugin_id);
		$accounts = array();
		foreach ($configs as $config) {
			$account = unserialize($config['value']);
			if ( ! is_a($account, 'account')) {
				continue;
			}
			$account->resetModified();
			if ( ! $enabled or $account->isEnabled()){
				$accounts[] = $account;
			}
		}
		return $accounts;
	}

    /**
     * Retourne la liste des paramètres éditables pour la construction
     * du modal d'édition
     */
	public static function paramsToEdit() {
		return array(
			'login' => 0,
			'password' => 0,
			'url' => 0,
		);
	}

    /**
     * Lancement de threads du deamon pour cahque account actif
     */
	public static function startAllDeamon(){
		foreach (account::all() as $account) {
			$account->startDeamonThread();
		}
	}

    /**
     * Method appelée chaque heure via le cron du plugin
     */
	public static function cronHourly() {
		easeeAccount::cronHourly();
	}

    /*     * *********************Methodes d'instance************************ */

    /**
     * Constructeur
     */
	public function __construct() {
		$this->model = substr_replace(get_class($this),'',-7);
		$this->image = model::images($this->model,'account')[0];
	}

    /**
     * Wrapper pour les logs
     */
	protected function log($level, $message){
		log::add('EVcharger',$level,'[' . get_class($this) . '][' . $this->name . '] ' . $message);
	}

    /**
     * Retient qu'une propriété a été modififée mais pas sauvegardée
     */
	protected function setModified($name){
		if (! in_array($name, $this->modified)) {
			log::add("EVcharger","debug", $this->getHumanName() . " " . $name . __(" est modifié.",__FILE__));
			$this->modified[] = $name;
		}
	}

    /**
     * Indique si une propriéré a été modifiée depuis la derinère sauvegarde
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

    /**
     * Remet à zéro la liste de propriétés modifiées
     */
	protected function resetModified(){
		$this->modified = array();
	}

    /**
     * Enregistrement de l'account
     */
	public function save($options = null) {
		if (trim($this->name) == "") {
			throw new Exception (__("Le nom n'est pas défini!",__FILE__));
		}
		if (method_exists($this, 'preSave')) {
			$this->preSave($options);
		}
		$accounts = self::byModelAndName($this->getModel(),$this->name);
		if (count($accounts) > 1) {
			throw new Exception (__("Il exsite plusieurs compte de ce modèle nmmés ",__FILE__) . $this->name);
		}
		$onInsert = false;
		if (!isset($this->id) or $this->id == '' ) {
			if ($accounts) {
				throw new Exception (__("Il y a déjà un compte de ce modèle nommé ",__FILE__) . $this->name);
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
				$this->startDeamonThread();
			} else {
				$this->stopDeamonThread();
			}
		}
		$this->resetModified();
	}

    /**
     * suppression de l'account
     */
	public function remove() {
		if (EVcharger::byAccountId($this->id)) {
			throw new Exception (__("Au moins un chargeur est liée à l'account",__FILE__));
		}
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

    /**
     * Envoi d'un message au démon
     */
	public function send2Deamon($message) {
		$this->log('debug','send2Deamon: ' . print_r($message,true));
		if (EVcharger::deamon_info()['state'] != 'ok'){
			$this->log('warning', __("Le démon n'est pas démarré!",__FILE__));
			return;
		}
		$params['apikey'] = jeedom::getApiKey('EVcharger');
		$params['model'] = $this->getModel();
		$params['id'] = $this->getId();
		$params['message'] = $message;
		$payLoad = json_encode($params);
		$socket = socket_create(AF_INET, SOCK_STREAM,0);
		socket_connect($socket,'127.0.0.1', config::byKey('daemon::port','EVcharger'));
		socket_write($socket, $payLoad, strlen($payLoad));
		socket_close($socket);
	}

    /**
     * lancement d'un thread de démon pour l'account
     */
	public function startDeamonThread() {
		if ($this->isEnabled()){
			$message = array('cmd' => 'start');
			if (method_exists($this,'msgToStartDeamonThread')){
				$message = $this->msgToStartDeamonThread();
			}
			$this->send2Deamon($message);
		}
	}

    /**
     * Arrêt du thread dédié à l'account 
     */
	public function stopDeamonThread() {
		foreach (EVcharger::byAccountId($this->getId()) as $chargeur) {
			if ($chargeur->getIsEnable()) {
				$message = array(
					'cmd' => 'stop',
					'chargeur' => $chargeur->getIdentifiant(),
				);
				$this->send2Deamon($message);
			}
		}
		$message = array('cmd' => 'stop_account');
		$this->send2Deamon($message);
	}

    /**
     * Nom de l'account à afficher dans l'interface utilisateur
     */
	public function getHumanName($_tag = false, $_prettify = false) {
		$name = '';
		$model = model::byName($this->getModel());
		if ($_tag) {
			if ($_prettify) {
				if ($model['customColor'] == 1) {
					$name .= '<span class="label" style="background-color:' . $model['tagColor'] . ';color:' . $model['tagTextColor'] . '">' . $model['label'] . '</span>';
				} else {
					$name .= '<span class="label labelObjectHuman">' . $model['label'] . '</span>';
				}
			} else {
				$name .= $this->getModel();
			}
		} else {
			$name .= '['.$this->getModel().']';
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

    /*
     * Execution d'une commande
     */
	public function execute ($cmd){
		if (! is_a($cmd, 'EVchargerCMD')){
			$this->log('error', sprintf(__("La commande n'est pas de la class %s",__FILE__),"EVchargerCMD"));
			return;
		}
		$this->log("debug", __("Exécution de ",__FILE__) . $cmd->getLogicalId());
		$method = 'execute_' . $cmd->getLogicalId();
		if ( ! method_exists($this, $method)){
			$this->log('error', sprintf(__("La méthode %s n'existe pas dans la classe %s",__FILE__),$method, get_class($this)));
			return;
		}
		$this->$method($cmd);
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

    /** modèle **/
	public function getModel() {
		return $this->model;
	}

}
