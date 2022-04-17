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

try {
	require_once __DIR__ . '/../../../../core/php/core.inc.php';
	require_once __DIR__ . '/../php/EVcharger.inc.php';

	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	if (init('action') == 'modelLabels') {
		ajax::success(json_encode(model::labels()));
	}

	if (init('action') == 'images') {
		$model = init('model');
		if ($model == '') {
			throw new Exception(__("Le modèle de chargeur n'est pas indiqué",__FILE__));
		}
		ajax::success(json_encode(model::images($model, 'charger')));
	}

	if (init('action') == 'ParamsHtml') {
		$model = init('model');
		if ($model == '') {
			throw new Exception(__("Le modèle de chargeur n'est pas indiqué",__FILE__));
		}
		$object = init('object');
		if ($object == '') {
			throw new Exception(__("L'objet n'est pas indiqué",__FILE__));
		}
		$file = realpath (__DIR__.'/../../desktop/php/'.$model.'/' . $object . '_params.inc.php');
		if (file_exists($file)) {
			ob_start();
			require_once $file;
			$content = translate::exec(ob_get_clean(), $file);
			ajax::success($content);
		}
		ajax::success();
	}

	if (init('action') == 'createCmds' || init('action') == 'updateCmds')  {
		$id = init('id');
		if ($id == ''){
			throw new Exception(__("L'Id du chargeur n'est pas indiqué",__FILE__));
		}
		$charger = EVcharger::byId($id);
		if (!is_object($charger)){
			throw new Exception(sprintf(__("Chargeur %s introuvable.",__FILE__),$id));
			ajax::error();
		}
		try {
			$option = array();
			if (init('action') == 'createCmds') {
				$options["createOnly"] = true;
			} elseif (init('action') == 'updateCmds')  {
				$options["updateOnly"] = true;
			}
			$charger->updateCmds($options);
			ajax::success();
		} catch (Exception $e){
			ajax::error(displayException($e), $e->getCode());
		}
	}

	throw new Exception(__("Aucune méthode correspondante à : ", __FILE__) . init('action'));

	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}

