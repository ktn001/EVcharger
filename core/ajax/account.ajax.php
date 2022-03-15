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

	if (init('action') == 'save') {
		try {
			$data = json_decode(init('account'),true);
			log::add("EVcharger",'debug',"Ajax: save: " . init('account'));
			$options = json_decode(init('options'),true);
			if ($data['model'] == ''){
				throw new Exception(__("Le modèle de compte n'est pas indiqué",__FILE__));
			}
			if ($data['id'] == '') {
				$classe = $data['model'] . 'Account';
				$account = new $classe();
			} else {
				$account = account::byId($data['id']);
			}
			utils::a2o($account,$data);
			$account->save($options);
			ajax::success();
		} catch (Exception $e) {
			if ($e->getCode() == 1) {
				$response['account'] = init('account');
				$response['message'] = $e->getMessage();
				ajax::error(json_encode($response), $e->getCode());
			} else {
				ajax::error(displayException($e), $e->getCode());
			}
		}
	}

	if (init('action') == 'remove') {
		$id = init('accountId');
		$account = account::byId($id);
		$account->remove();
		ajax::success();
	}

	if (init('action') == 'displayCards') {
		$cards = array ();
		foreach (account::all() as $account) {
			$data = array(
				'enabled' => $account->IsEnabled(),
				'id' => $account->getId(),
				'model' => $account->getModel(),
				'humanName' => $account->getHumanName(true,true),
				'image' => $account->getImage(),
			);
			$cards[] = $data;
		}
		ajax::success(json_encode($cards));
	}

	if (init('action') == 'getAccountToSelect') {
		$result = array();
		foreach (account::byModel(init('model')) as $account) {
			$data = array(
				'id' => $account->getId(),
				'value' => $account->getHumanName(),
			);
			$result[] = $data;
		}
		ajax::success(json_encode($result));
	}

	if (init('action') == 'byIdToEdit'){
		$model = init('model');
		$id = init('id');
		if ($model == ''){
			throw new Exception(__("Le modèle de compte n'est pas indiqué",__FILE__));
		}
		if ($id == '') {
			$classe = $model . 'Account';
			$account = new $classe();
		} else {
			$account = account::byId($id);
		}
		if (!is_object($account)) {
			throw new Exception(__('Compte inconnu: ',__FILE__) . $id);
		}
		if ($account->getModel() != $model) {
			throw new Exception(sprintf(__("Le modèle du compte n'est pas %s (%s)",__FILE__), $model, $account->getModel()));
		}
		
		$result['account'] = utils::o2a($account);
		$result['params'] = ($model . "Account")::paramsToEdit();
		$result['images'] = model::images($model, 'account');
		ajax::success(json_encode($result));
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));

	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}
