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

class model {

    /*     * ***********************Methodes static************************** */

	public static function all($onlyEnabled = true ) {
		$modelsFile = __DIR__ . "/../config/models.ini";
		$models = parse_ini_file($modelsFile,true);
		if ($models == false) {
			$msg = sprintf(__('Erreur lors de la lecture de %s',__FILE__),$modelsFile);
			log::add("EVcharger","error",$msg);
			throw new Exception($msg);
		}
		$result = array();
		foreach ($models as $modelName => $config){
			$config['label'] = translate::exec($config['label'],__FILE__);
			$model = config::byKey('model::' . $modelName, 'EVcharger');
			if (is_array($model)){
				$model = array_merge($config, $model);
			} else {
				$model = $config;
				$model['enabled'] = 0;
			}
			if ($onlyEnabled == false or $model['enabled'] == 1) {
				$result[$modelName] = $model;
			}
		}
		return $result;
	}

	public static function labels($onlyEnabled = true) {
		$labels = array();
		foreach (model::all($onlyEnabled) as $modelName => $model) {
			$labels[$modelName] = $model['label'];
		}
		return $labels;
	}

	public static function byName($modelName ) {
		return self::all()[$modelName];
	}

	public static function allUsed() {
		$used = array();
		foreach (EVcharger_account::byType('EVcharger_account_%') as $account) {
			$used[$account->getModel()] = 1;
		}
		return array_keys($used);
	}

	public static function images($model, $objet) {
		$images = array();
		$path = realpath(__DIR__ . '/../../desktop/img/' . $model);
		if ($dir = opendir($path)){
			while (($fileName = readdir($dir)) !== false){
				if (preg_match('/^' . $objet . '.*\.png$/', $fileName)){
					$images[] = strchr($path.'/'.$fileName, '/plugins/');
				}
			}
		}
		if (count($images) == 0){
			$images[] = strchr(realpath(__DIR__.'/../../desktop/img/'.$objet.'.png'),'/plugins/');
		}
		sort ($images);
		return $images;
	}

	public static function commands($model, $requiredOnly = false) {

		$parameters = array(
			'calcul',
			'display::graphStep',
			'displayName',
			'group',
			'name',
			'order',
			'required',
			'rounding',
			'subType',
			'template',
			'type',
			'unite',
			'value',
			'visible'
		);

		/*
		 *  Lecture des fichiers de définition des commandes
		 */
		$configPath = __DIR__ . '/../config';
		$configFile = 'cmd.config.ini';

		$globalConfigs = parse_ini_file($configPath . "/" . $configFile,true, INI_SCANNER_RAW);
		$modelConfigs = parse_ini_file($configPath.'/'.$model.'/'.$configFile,true, INI_SCANNER_RAW);

		$sections = array();
		foreach (array_keys($globalConfigs) as $section) {
			$sections[$section] = array();
		}
		foreach (array_keys($modelConfigs) as $section) {
			$sections[$section] = array();
		}

		foreach (array_keys($sections) as $section) {
			if (array_key_exists($section,$globalConfigs)) {
				$sections[$section] = $globalConfigs[$section];
				if (array_key_exists($section,$modelConfigs)) {
					foreach ($parameters as $parameter) {
						if (array_key_exists($parameter,$modelConfigs[$section])) {
							$sections[$section][$parameter] = $modelConfigs[$section][$parameter];
						}
					}
				}
			} else {
				$sections[$section] = $modelConfigs[$section];
			}
		}

		$groupConfigs = array();
		$cmdConfigs = array();
		foreach (array_keys($sections) as $section) {
			if (strpos($section, 'group:') === 0) {
				$group = substr($section,6);
				$groupConfigs[$group] = $sections[$section];
			} else {
				$cmdConfigs[$section] = $sections[$section];
			}
		}

		foreach (array_keys($cmdConfigs) as $cmd) {
			if (array_key_exists('group',$cmdConfigs[$cmd])) {
				$group = $cmdConfigs[$cmd]['group'];
				if (! array_key_exists($group,$groupConfigs)) {
					throw new Exception (sprintf(__("Le groupe %s utilisé dans la définition de la commande %s est introuvable.",__FILE__), $group, $cmd));
				}
				foreach ($parameters as $parameter) {
					if (array_key_exists($parameter,$groupConfigs[$group])) {
						if (! array_key_exists($parameter,$cmdConfigs[$cmd])){
							$cmdConfigs[$cmd][$parameter] = $groupConfigs[$group][$parameter];
						}
					}
				}
			}
		}

		foreach (array_keys($cmdConfig) as $cmd) {
			if (! array_key_exists('required',$cmdConfigs[$cmd])) {
				throw new Exception (sprintf(__("Le paramètre 'required' n'est pas défini pour la commande %s!",__FILE__),$cmd));
			}
			if ($cmdConfigs[$cmd]['required'] == 'no') {
				unset ($cmdConfigs[$cmd]);
			} elseif ($cmdConfigs[$cmd]['required'] == 'optional' and ! $requiredOnly) {
				unset ($cmdConfigs[$cmd]);
			} 
			if ($cmdConfigs[$cmd]['required'] != 'yes') {
				throw new Exception (sprintf(__("Le paramètre 'required' a une valeur non reconnue (%s) pour la commande %s!",__FILE__),$cmd['required'],$cmd));
			}
		}
		foreach (array_keys($cmdConfig) as $cmd) {
			if (! array_key_exists('name',$cmdConfig[$cmd])) {
				throw new Exception (sprintf(__("Le nom n'est pas défini pour la commande %s!",__FILE__),$cmd));
			}
			if (! array_key_exists('order',$cmdConfig[$cmd])) {
				throw new Exception (sprintf(__("Le classement n'est pas défini pour la commande %s!",__FILE__),$cmd));
			}
			if (! array_key_exists('subType',$cmdConfig[$cmd])) {
				throw new Exception (sprintf(__("Le sous-type n'est pas défini pour la commande %s!",__FILE__),$cmd));
			}
			if (! array_key_exists('type',$cmdConfig[$cmd])) {
				throw new Exception (sprintf(__("Le type n'est pas défini pour la commande %s!",__FILE__),$cmd));
			}
		}
		return $cmdConfigs;
	}

	private static function getConfigs($model) {
		return parse_ini_file(__DIR__ . '/../config/' . $model . '/config.ini' ,true);
	}

	public static function getIdentifiantCharger($model) {
		return model::getConfigs($model)['charger']['identifiant'];
	}

    /*     * **********************Getteur Setteur*************************** */

}
