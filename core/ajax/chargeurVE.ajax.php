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

	/*
	 * Liste des type d'accounts
	 */
	if (init('action') == 'getAccountTypeLabels') {
		log::add ('chargeurVE', 'debug', 'AJAX: début de "getAccountLabels"');
		ajax::success(json_encode(account::getTypeLabels()));
	}

	/*
	 * Chargement des accouts
	 */
	if (init('action') == 'getAccounts') {
		log::add ('chargeurVE', 'debug', 'AJAX: début de "getAccounts"');
		try {
			ajax::success(account::getAll());
		} catch (Exception $e) {
			ajax::error();
		}
	}

	/*
	 * Sauvegarde d'un account
	 */
	if (init('action') == 'saveAccount') {
		log::add ('chargeurVE', 'debug', 'AJAX: début de "saveAccount"');
		try {
			$account = account::fromData(init('account'));
			//$account->save();
			ajax::success();
		} catch (Exception $e) {
			ajax::error();
		}
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));

	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}

