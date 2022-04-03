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

class EVcharger_account extends EVcharger {

	public static function byModel($_model){
		$eqType_name = "EVcharger_account_" . $_model;
		return self::byType($eqType_name);
	}

	public function getImage() {
		$image = $this->getConfiguration('image');
		if ($image == '') {
			$image = "/plugins/EVcharger/desktop/img/account.png";
		}
		return $image;
	}

	public function execute ($charger_cmd) {
		try {
			log::add("EVcharger","debug","┌─" . sprintf(__("%s: execution de %s",__FILE__), $this->getHumanName() , $charger_cmd->getLogicalId())); 
			if (! is_a($charger_cmd, "EVcharger_chargerCmd")){
				throw new Exception (sprintf(__("La commande %s n'est pas une commande de type %s",__FILE__),$charger_cmd->getId(), "EVcharger_chargerCmd"));
			}
			$method = 'execute_' . $charger_cmd->getLogicalId();
			if ( ! method_exists($this, $method)){
				throw new Exception (sprintf(__("%s: pas de méthode < %s >",__FILE__),$this->getHumanName(), $method));
			}
			$this->$method($charger_cmd);
			log::add("EVcharger","debug","└─" . __("FIN",__FILE__));
			return;
		} catch (Exception $e) {
			log::add("EVcharger","error",$e->getMessage());
			log::add("EVcharger","debug","└─" . __("ERROR",__FILE__));
			return;
		}
	}
}

class EVcharger_accountCmd extends EVchargerCmd  {

}

require_once __DIR__ . '/EVcharger_account_easee.class.php';
