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
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methodes static************************** */
	public static function all( $onlyEnabled = true ) {
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

	public static function labels ( $onlyEnabled = true) {
		$labels = array();
		foreach (type::all($onlyEnabled) as $typeName => $type) {
			$labels[$typeName] = $type['label'];
		}
		return $labels;
	}

	public static function byName ( $typeName ) {
		return self::all()[$typeName];
	}

	public static function allUsed () {
		$used = array();
		foreach (account::all() as $account) {
			$used[$account->getType()] = 1;
		}
		return array_keys($used);
	}

    /*     * **********************Getteur Setteur*************************** */

}
