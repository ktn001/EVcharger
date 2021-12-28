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

class type {
    /*     * ***********************Methodes static************************** */
	public static function all($onlyEnabled = true ) {
		$typesFile = __DIR__ . "/../config/types.ini";
		$types = parse_ini_file($typesFile,true);
		if ($types == false) {
			$msg = sprintf(__('Erreur lors de la lecture de %s',__FILE__),$typesFile);
			log::add("chargeurVE",error,$message);
			throw new Exception($message);
		}
		$result = array();
		foreach ($types as $typeName => $config){
			$config['label'] = translate::exec($config['label'],__FILE__);
			$type = config::byKey('type::' . $typeName, 'chargeurVE');
			if (is_array($type)){
				$type = array_merge($config, $type);
			} else {
				$type = $config;
				$type['enabled'] = 0;
			}
			if ($onlyEnabled == false or $type['enabled'] == 1) {
				$result[$typeName] = $type;
			}
		}
		return $result;
	}

	public static function types() {
		$typesFile = __DIR__ . "/../config/types.ini";
		$types = parse_ini_file($typesFile,true);
		return array_keys($types);
	}

	public static function labels($onlyEnabled = true) {
		$labels = array();
		foreach (type::all($onlyEnabled) as $typeName => $type) {
			$labels[$typeName] = $type['label'];
		}
		return $labels;
	}

	public static function byName($typeName ) {
		return self::all()[$typeName];
	}

	public static function allUsed() {
		$used = array();
		foreach (account::all() as $account) {
			$used[$account->getType()] = 1;
		}
		return array_keys($used);
	}

	public static function images($type, $objet) {
		$images = array();
		$path = realpath(__DIR__ . '/../../desktop/img/' . $type);
		if ($dir = opendir($path)){
			log::add("chargeurVE","debug","path : " . $path);
			log::add("chargeurVE","debug","objet : " . $objet);
			while (($fileName = readdir($dir)) !== false){
				log::add("chargeurVE","debug","file : " . $fileName);
				if (preg_match('/^' . $objet . '.*\.png$/', $fileName)){
					$images[] = strchr($path.'/'.$fileName, '/plugins/');
				}
			}
		}
		if (count($images) == 0){
			$images[] = strchr(realpath(__DIR__.'/../../desktop/img/'.$objet.'.png'),'/plugins/');
		}
		return $images;
	}

	public static function commands($type, $mandatoryOnly = false) {
		$configPath = __DIR__ . '/../config';
		$configFile = 'cmd.config.ini';
		$defaultCommands = parse_ini_file($configPath . "/" . $configFile,true);
		$typeCommands = parse_ini_file($configPath.'/'.$type.'/'.$configFile,true);
		$return = array();
		foreach ($defaultCommands as $logicalId => $cmdConfig) {
			if (array_key_exists($logicalId, $typeCommands)) {
				$cmdConfig = array_merge($cmdConfig, $typeCommands[$logicalId]);
			}
			if ( ! $mandatoryOnly or $cmdConfig['mandatory']) {
				$return[$logicalId] = $cmdConfig;
			}
		}
		return $return;
	}

    /*     * **********************Getteur Setteur*************************** */

}
