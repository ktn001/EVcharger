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

class EVcharger_vehicle extends EVcharger {

	public static function types() {
		$types = array();
		$path = __DIR__ . '/../../desktop/img/vehicle';
		if ($dir = opendir($path)){
			while (($fileName = readdir($dir)) !== false){
				if (preg_match('/^([^_]+)\.png$/',$fileName,$matches)){
					$types[] = $matches[1];
				}
			}
		}
		return $types;
	}

    // Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert() {
		$cmd = (__CLASS__ . "Cmd")::byEqLogicIdAndLogicalId($this->getId(),'latitude');
		if (!is_object($cmd)){
			$cmd = new EVchargerCMD();
			$cmd->setName(__($config['name'],__FILE__));
		}
	}

	public function getImage() {
		$type = $this->getConfiguration('type');
		$image = "/plugins/EVcharger/desktop/img/vehicle/" . $type . ".png";
		if (! file_exists('/var/www/html' . $image)) {
			return "/plugins/EVcharger/desktop/img/vehicle/compact.png";
		}
		return $image;
	}

    /*     * **********************Getteur Setteur*************************** */

	public function setType($_type) {
		$this->setConfiguration('type',$_type);
		return $this;
	}
}

class EVcharger_vehicleCmd extends EVchargerCmd  {
}
