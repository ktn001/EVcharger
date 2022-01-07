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
	require_once __DIR__ . '/../class/account.class.php';

	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	if (init('action') == 'typeLabels') {
		ajax::success(json_encode(type::labels()));
	}

	if (init('action') == 'images') {
		$type = init('type');
		if ($type == '') {
			throw new Exception(__("Le type de chargeur n'est pas indiqué",__FILE__));
		}
		ajax::success(json_encode(type::images($type, 'chargeur')));
	}

	if (init('action') == 'chargeurParamsHtml') {
		$type = init('type');
		if ($type == '') {
			throw new Exception(__("Le type de chargeur n'est pas indiqué",__FILE__));
		}
		$file = realpath (__DIR__.'/../config/'.$type.'/chargeur_params.php');
		if (file_exists($file)) {
			log::add("chargeurVE","debug",$file);
			ob_start();
			require_once $file;
			$content = translate::exec(ob_get_clean(), $file);
			ajax::success($content);
		}
		ajax::success();
	}

	throw new Exception(__("Aucune méthode correspondante à : ", __FILE__) . init('action'));

	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}

