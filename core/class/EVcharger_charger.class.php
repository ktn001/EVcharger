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

class EVcharger_charger extends EVcharger {
    /*     * ************************* Attributs ****************************** */

    /*     * *********************** Methode static *************************** */

	public static function byAccountId($accountId) {
		return self::byTypeAndSearchConfiguration(__CLASS__,'"accountId":"'.$accountId.'"');
	}

	public static function byModelAndIdentifiant($model, $identifiant) {
		$identKey = model::getIdentifiantCharger($model);
		$searchConf = sprintf('"%s":"%s"',$identKey,$identifiant);
		$chargers = array();
		foreach (self::byTypeAndSearchConfiguration(__CLASS__,$searchConf) as $charger){
			if ($charger->getConfiguration('model') == $model){
				$chargers[] = $charger;
			}
		}
		return $chargers;

	}

    /*     * *********************Méthodes d'instance************************* */

    // Création/mise à jour des commande prédéfinies
	public function updateCmds($options = array()) {
		$createOnly = false;
		if (array_key_exists('createOnly', $options)) {
			$createOnly = $options['createOnly'];
		}
		$updateOnly = false;
		if (array_key_exists('updateOnly', $options)) {
			$updateOnly = $options['updateOnly'];
		}
		$ids = array();
		log::add("EVcharger","debug",sprintf(__("%s: (re)création des commandes",__FILE__),$this->getHumanName()));
		foreach (model::commands($this->getConfiguration('model')) as $logicalId => $config) {
			log::add("EVcharger","debug","XXXX " . $logicalId);
			$cmd = (__CLASS__ . "Cmd")::byEqLogicIdAndLogicalId($this->getId(),$logicalId);
			if (!is_object($cmd)){
				if ($updateOnly) {
					continue;
				}
				log::add("EVcharger","debug","  " . sprintf(__("Création de la commande %s",__FILE__), $logicalId));
				$cmd = new EVcharger_chargerCMD();
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId($logicalId);
				if ($createOnly and array_key_exists('order',$config)) {
					foreach (cmd::byEqLogicId($this->getId()) as $otherCmd) {
						if ($otherCmd->getOrder() >= $config['order']) {
							$otherCmd->setOrder($otherCmd->getOrder()+1);
							$otherCmd->save();
						}
					}
					if ($cmd->getOrder() != $config['order']){
						log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'order'",__FILE__), $logicalId));
						$cmd->setOrder($config['order']);
					}
				}
			} elseif ($createOnly) {
				continue;
			}
				
			if ($cmd->getConfiguration('destination') != $config['destination']) {
				log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'destination'",__FILE__), $logicalId));
				$cmd->setConfiguration('destination',$config['destination']);
			}
			if (array_key_exists('display::graphStep', $config)) {
				if ($cmd->getDisplay('graphStep') != $config['display::graphStep']) {
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'display::graphStep'",__FILE__), $logicalId));
					$cmd->setDisplay('graphStep', $config['display::graphStep']);
				}
			}
			if (array_key_exists('displayName', $config)) {
				if ($cmd->getDisplay('showNameOndashboard') !=  $config['displayName']){
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'display::NameOndashboard'",__FILE__), $logicalId));
					$cmd->setDisplay('showNameOndashboard', $config['displayName']);
				}
				if ($cmd->getDisplay('showNameOnmobile') !=  $config['displayName']){
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'display::NameOnmobile'",__FILE__), $logicalId));
					$cmd->setDisplay('showNameOndashboard', $config['displayName']);
				}
			}
			if ($cmd->getName() != $config['name']){
				log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'name'",__FILE__), $logicalId));
				$cmd->setName($config['name']);
			}
			if ($cmd->getOrder() != $config['order']){
				log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'order'",__FILE__), $logicalId));
				$cmd->setOrder($config['order']);
			}
			if ($cmd->getConfiguration('required') != $config['required']) {
				log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'required'",__FILE__), $logicalId));
				$cmd->setConfiguration('required',$config['required']);
			}
			if (array_key_exists('rounding', $config)) {
				if ($cmd->getConfiguration('historizeRound') != $config['rounding']) {
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'roundig'",__FILE__), $logicalId));
					$cmd->setConfiguration('historizeRound', $config['rounding']);
				}
			}
			if ($cmd->getConfiguration('source') != $config['source']) {
				log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'source'",__FILE__), $logicalId));
				$cmd->setConfiguration('source',$config['source']);
			}
			if ($cmd->getSubType() != $config['subType']) {
				log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'subType'",__FILE__), $logicalId));
				$cmd->setSubType($config['subType']);
			}
			if (array_key_exists('template', $config)) {
				if ($cmd->getTemplate('dashboard') != $config['template']) {
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'template::dashboard'",__FILE__), $logicalId));
					$cmd->setTemplate('dashboard',$config['template']);
				}
				if ($cmd->getTemplate('mobile') != $config['template']) {
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'template::mobile'",__FILE__), $logicalId));
					$cmd->setTemplate('mobile',$config['template']);
				}
			}
			if ($cmd->getType() != $config['type']) {
				log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'type'",__FILE__), $logicalId));
				$cmd->setType($config['type']);
			}
			if (array_key_exists('unite', $config)) {
				if ($cmd->getUnite() != $config['unite']) {
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'unite'",__FILE__), $logicalId));
					$cmd->setUnite($config['unite']);
				}
			}
			if (array_key_exists('visible', $config)) {
				if ($cmd->getIsVisible() != $config['visible']) {
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'visible'",__FILE__), $logicalId));
					$cmd->setIsVisible($config['visible']);
				}
			}

			$cmd->save();
		}
		foreach (model::commands($this->getConfiguration('model')) as $logicalId => $config) {
			$cmd = EVchargerCmd::byEqLogicIdAndLogicalId($this->getId(),$logicalId);
			$needSave = false;
			if (array_key_exists('calcul',$config)){
				$calcul = $config['calcul'];
				if (!is_object($cmd)){
					log::add("EVcharger","error",(sprintf(__("Commande avec logicalIs=%s introuvable",__FILE__),$logicalId)));
					continue;
				}
				preg_match_all('/#(.+?)#/',$calcul,$matches);
				foreach ($matches[1] as $logicalId) {
					$id = cmd::byEqLogicIdAndLogicalId($this->getId(), $logicalId)->getId();
					$calcul = str_replace('#' . $logicalId . '#', '#' . $id . '#', $calcul);
				}
				if ($cmd->getConfiguration('calcul') !=  $calcul) {
					log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'calcul'",__FILE__), $logicalId));
					$cmd->setConfiguration('calcul', $calcul);
					$needSave = true;
				}
			}
			if (array_key_exists('value',$config)){
				if (!is_object($cmd)){
					log::add("EVcharger","error",(sprintf(__("Commande avec logicalId = %s introuvable",__FILE__),$logicalId)));
					continue;
				}
				$cmdValue = cmd::byEqLogicIdAndLogicalId($this->getId(), $config['value']);
				if (! $cmdValue) {
					log::add("EVcharger","error",sprintf(__("La commande '%s' pour la valeur de '%s' est introuvable",__FILE__),$config['value'],$cmd->getLogicalId()));
				} else {
					$value = $cmdValue->getId();
					if ($cmd->getValue() != $value) {
						log::add("EVcharger","debug","  " . sprintf(__("%s: Mise à jour de 'value'",__FILE__), $logicalId));
						$cmd->setValue($value);
						$needSave = true;
					}
				}
			}
			if ($needSave) {
				$cmd->save();
			}
		}
	}

    // Fonction exécutée automatiquement avant la sauvegarde de l'équipement
	public function preSave() {
		if ($this->getIsEnable()) {
			if ($this->getAccountId() == '') {
				throw new Exception (__("Le compte n'est pas défini",__FILE__));
			}
		}
		$accountId = $this->getAccountId();
		if ($accountId != '') {
			$account = EVcharger_account::byId($accountId);
			if (! is_a($account, "EVcharger_account")) {
				throw new Exception (sprintf(__("L'account %s est introuvable!",__FILE__), $accountId));
			}
		}

	}
    // Fonction exécutée automatiquement avant la création de l'équipement
	public function preInsert() {
		$this->setConfiguration('image',model::images($this->getConfiguration('model'),'charger')[0]);
	}

    // Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert() {
		$this->updateCmds(false);
	}

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
	public function postSave() {
		//if ($this->getIsEnable()){
		//	$this->startListener();
		//} else {
		//	$this->stopListener();
		//}
	}

	// Fonction exécutée automatiquement après la sauvegarde de l'eqLogid ET des commandes si sauvegarde lancée via un AJAX
	public function postAjax() {
		if ($this->getAccountId() == '') {
			return;
		}
		$cmd_refresh = EVchargerCmd::byEqLogicIdAndLogicalId($this->getId(),'refresh');
		if (!is_object($cmd_refresh)) {
			return;
		}
		$cmd_refresh->execute();
		return;
	}

	public function getPathImg() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			$image = "/plugins/EVcharger/plugin_info/EVcharger_icon.png";
		}
		return $image;
	}

	public function getAccount() {
		return EVcharger_account::byId($this->getAccountId());
	}

	public function getIdentifiant() {
		$model = $this->getConfiguration('model');
		$configName = model::getIdentifiantCharger($model);
		return $this->getConfiguration($configName);
	}

	public function startListener() {
		if (! $this->getIsEnable()) {
			return;
		}
		$message = array(
			'cmd' => 'start_charger_listener',
			'chargerId' => $this->id,
			'identifiant' => $this->getIdentifiant()
		);
		EVcharger_account::byId($this->getAccountId())->send2Deamon($message);
	}

	public function stopListener() {
		$message = array(
			'cmd' => 'stop_charger_listener',
			'chargerId' => $this->id,
			'identifiant' => $this->getIdentifiant()
		);
		if ($this->getAccountId()) {
			EVcharger_account::byId($this->getAccountId())->send2Deamon($message);
		}
	}

    /*     * **********************Getteur Setteur*************************** */

	public function getAccountId() {
		return $this->getConfiguration('accountId');
	}

	public function setAccountId($_accountId§) {
		$this->setConfiguration('accountId',$_accountId);
		return $this;
	}

	public function getImage() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			return "/plugins/EVcharger/plugin_info/EVcharger_icon.png";
		}
		return $image;
	}

	public function setImage($_image) {
		$this->setConfiguration('image',$_image);
		return $this;
	}
}

class EVcharger_chargerCmd extends EVchargerCmd  {
    /*     * *************************Attributs****************************** */

    /*
	public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		if ($this->getConfiguration('required') == 'yes') {
			return true;
		}
		return false;
	}

	public function preSave() {
		if ($this->getLogicalId() == 'refresh') {
                        return;
                }
		if ($this->getType() == 'info') {
			$calcul = $this->getConfiguration('calcul');
			if (strpos($calcul, '#' . $this->getId() . '#') !== false) {
				throw new Exception(__('Vous ne pouvez appeler la commande elle-même (boucle infinie) sur',__FILE__) . ' : '.$this->getName());
			}
			$added_value = [];
			preg_match_all("/#([0-9]+)#/", $calcul, $matches);
			$value = '';
			foreach ($matches[1] as $cmd_id) {
				$cmd = self::byId($cmd_id);
				if (is_object($cmd) && $cmd->getType() == 'info') {
					if(isset($added_value[$cmd_id])) {
						continue;
					}
					$value .= '#' . $cmd_id . '#';
					$added_value[$cmd_id] = $cmd_id;
				}
			}
			preg_match_all("/variable\((.*?)\)/",$calcul, $matches);
			foreach ($matches[1] as $variable) {
				if(isset($added_value['#variable(' . $variable . ')#'])){
					continue;
				}
				$value .= '#variable(' . $variable . ')#';
				$added_value['#variable(' . $variable . ')#'] = '#variable(' . $variable . ')#';
			}
			$this->setValue($value);
		}
	}

	public function postSave() {
		if ($this->getType() == 'info' && $this->getConfiguration('calcul') != '') {
			$this->event($this->execute());
		}
	}

    // Exécution d'une commande
	public function execute($_options = array()) {
		log::add("EVcharger","debug","Execute : " . $this->getLogicalId());
		switch ($this->getType()) {
		case 'info':
			$calcul = $this->getConfiguration('calcul');
			if ($calcul) {
				return jeedom::evaluateExpression($calcul);
			}
			break;
		case 'action':
			$this->getEqLogic()->getAccount()->execute($this);
		}
	}

    /*     * **********************Getteur Setteur*************************** */
}
