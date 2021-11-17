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
		try{
			$dirPath = __DIR__ . "/../../data";
			$dir = opendir($dirPath);
		} catch (Exceptionn $e) {
			log::add('chargeurVE','error',$e->getMessage() . ' File: ' . $e->getFile . ' Line: ' . $e->getLine());
		}
		$types = array();
		while ($file = readdir($dir)){
			if (substr_compare($file, '.type.ini', -9) == 0){
				$type = parse_ini_file ($dirPath . '/' . $file);
				if ($type === false) {
					$message = __(sprintf('Erreur lors de la lecture de %s', $file),__FILE__);
					log::add('chargeurVE','error',$message);
					throw new Exception ($message);
				}	
				$type['label'] = translate::exec($type['label'],__FILE__);
				$type = array_merge($type, config::byKey('type::' . $type['type'],'chargeurVE'));
				if (($onlyEnabled == false) or ($type['enable'] == 1)) {
					$types[$type['type']] = $type;
				}
			}
		}
		closedir($dir);
		return $types;
	}

	public static function types() {
		try{
			$dir = opendir("../../data");
		} catch (Exceptionn $e) {
			log::add('chargeurVE','error',$e->getMessage() . ' File: ' . $e->getFile . ' Line: ' . $e->getLine());
		}
		$types = array();
		while ($file = readdir($dir)){
			if (substr_compare($file, '.type.ini', -9) == 0){
				$types[] = substr_replace($file,"",-9);
			}
		}
		closedir($dir);
		return $types;
	}

	public static function labels ( $onlyEnabled = true) {
		$labels = array();
		foreach (type::all($onlyEnabled) as $type) {
			$labels[$type['type']] = $type['label'];
		}
		return $labels;
	}


    /*     * **********************Getteur Setteur*************************** */

}
