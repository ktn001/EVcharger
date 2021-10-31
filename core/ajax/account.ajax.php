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

	if (init('action') == 'byId') {
		$accountType = init('accountType');
		$id = init('id');
		if ($accountType == ''){
			throw new Exception(__("Le type de compte n'est pas indiqué",__FILE__));
		}
		if ($id == '') {
			$classe = $accountType . 'Account';
			$account = new $classe();
		} else {
			$account = account::byId(init('id'));
		}
		if (!is_object($account)) {
			throw new Exception(__('Compte inconnu: ',__FILE__) . init(id));
		}
		if ($account->getType() != $accountType) {
			throw new Exception(__("Le type du compte n'est pas ",__FILE__) . '"' . $accountType . '" (' . $account->getType() . ')');
		}
		ajax::success(utils::o2a($account));
	}

	if (init('action') == 'save') {
		$data = json_decode(init('account'),true);
		if ($data['accountType'] == ''){
			throw new Exception(__("Le type de compte n'est pas indiqué",__FILE__));
		}
		$classe = $data['accountType'] . 'Account';
		if ($data['id'] == '') {
			$account = new $classe();
		} else {
			$account = account::byId($data['id']);
		}
		utils::a2o($account,$data);
		$account->save();
		ajax::success();
	}

	if (init('action') == 'remove') {
		$data = json_decode(init('account'),true);
		if ($data['id'] == '') {
			throw new Exception(__("L'id du compte n'est pas défini",__FILE__));
		}
		$account = account::byId($data['id']);
		utils::a2o($account,$data);
		$account->remove();
		ajax::success();
	}

	if (init('action') == 'displayCards') {
		$cards = array ();
		foreach (account::all() as $account) {
			$data = array(
				'isEnable' => $account->getIsEnable(),
				'id' => $account->getId(),
				'accountType' => $account->getType(),
				'humanName' => $account->getHumanName(true,true),
				'image' => $account->getImage(),
			);
			$cards[] = $data;
		}
		ajax::success(json_encode($cards));
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));

	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}

