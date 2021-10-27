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
		$type = init('type');
		$id = init('id');
		if ($type == ''){
			throw new Exception(__("Le type d'account n'est pas indiqué",__FILE__));
		}
		if ($id == '') {
			$classe = $type . 'Account';
			$account = new $classe();
		} else {
			$account = account::byId(init('id'));
		}
		if (!is_object($account)) {
			throw new Exception(__('Account inconnu: ',__FILE__) . init(id));
		}
		if ($account->getType() != $type) {
			throw new Exception(__("Le type de l'account n'est pas ",__FILE__) . '"' . $type . '" (' . $account->getType() . ')');
		}
		ajax::success(utils::o2a($account));
	}

	if (init('action') == 'save') {
		$data = json_decode(init('account'),true);
		if ($data['type'] == ''){
			throw new Exception(__("Le type d'account n'est pas indiqué",__FILE__));
		}
		$classe = $data['type'] . 'Account';
		if ($data['id'] == '') {
			$account = new $classe();
		} else {
			$account = account::byId($data['id']);
		}
		log::add("chargeurVE","info","avant a2o " . print_r($account,true));
		utils::a2o($account,$data);
		log::add("chargeurVE","info","apres a2o " . print_r($account,true));
		$account->save();
		ajax::success();
	}

	if (init('action') == 'displayCards') {
		$cards = array ();
		foreach (account::all() as $account) {
			$data = array(
				'enabled' => $account->getIsEnable(),
				'id' => $account->getId(),
				'type' => $account->getType(),
				'humanName' => $account->getHumanName(true,true),
				'image' => 'plugins/chargeurVE/desktop/img/account.png',
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

