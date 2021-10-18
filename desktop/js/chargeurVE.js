
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


/*
 * Permet la réorganisation des commandes dans l'équipement et des accounts
 */
$('#table_cmd').sortable({axis: 'y', cursor: 'move', items: '.cmd', placeholder: 'ui-state-highlight', tolerance: 'intersect', forcePlaceholderSize: true});
$('#table_account').sortable({axis: 'y', cursor: 'move', items: '.account', placeholder: 'ui-state-highlight', tolerance: 'intersect', forcePlaceholderSize: true});
$('#table_cmd').on('sortupdate',function(event,ui){
	modifyWithoutSave = true;
});
$('#table_account').on('sortupdate',function(event,ui){
	$('#bt_saveAccounts').removeClass('disabled');
	modifyWithoutSave = true;
});

/*
 * Ajout d'un account
 */
$('.accountAction[data-action=add]').off('click').on('click',function() {
	addAccount();
	modifyWithoutSave = true;
	$('#bt_saveAccounts').removeClass('disabled');
});

/*
 * Sauvegarde des accounts
 */
$('#bt_saveAccounts').on('click',function() {
	accounts = json_encode($(this).closest('table').find('tr.account').getValues('.accountAttr'));
	$.ajax({
		type: 'POST',
		url: 'plugins/chargeurVE/core/ajax/chargeurVE.ajax.php',
		data: {
			action: 'saveAccounts',
			accounts: accounts
		},
		dataType : 'json',
		global:false,
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.state, level: 'danger'});
				return;
			}
			console.log(json_decode(data.result));
			$('#table_account tbody').empty();
			accounts =  json_decode(data.result);
			for (i in json_decode(data.result)){
				console.log(accounts[i]);
				addAccount(accounts[i]);
			}
			$('#bt_saveAccounts').addClass('disabled');
			modifyWithoutSave = false;
		}
	});
});

/*
 *
 */
$('#table_account').on('change','.accountAttr',function() {
	$('#bt_saveAccounts').removeClass('disabled');
	modifyWithoutSave = true;
});

/*
 * Fonction permettant l'affichage des accounts
 */
function addAccount(_account) {
	if(!isset(_account)) {
		var _account = {};
	}
	var tr = '<tr class="account" data-account_id="' + init(_account.id) + '">';
	tr += '<td>';
	tr += '<span class="accountAttr" data-l1key="id"></span>';
	tr += '</td>';
	tr += '<td>';
	tr += '<input class="accountAttr form-control input-sm" data-l1key="name" placeholder="{{Nom du compte}}">';
	tr += '</td>';
	tr += '<td>';
	tr += '<input class="accountAttr tooltips form-control input-sm" data-l1key="login" placeholder="{{login}}" title="{{N° de tel. ou email du compte Easee}}"/>';
	tr += '</td>';
	tr += '<td>';
	tr += '<input class="accountAttr form-control input-sm" data-l1key="url" value="https://api.easee.cloud"/>';
	tr += '</td>';
	tr += '<td>';
	tr += '<span class="accountKeyStatus label label-danger">KO</span>';
	tr += '<a class="btn btn-success btn-xs accountAction" data-action="getApiKey" style="position:relative;top:-1px;margin-left:5px">{{Renouveler}}</a>';
	tr += '</td>';
	tr += '<td>';
	tr += '</td>';
	tr += '</tr>';
	$('#table_account tbody').append(tr);
	var tr = $('#table_account tbody tr').last();
	tr.setValues(_account, '.accountAttr');
	if (init(_account.id) == "") {
		tr.find('.accountAction[data-action=getApiKey]').addClass('disabled');
	}
}

/*
* Fonction permettant l'affichage des commandes dans l'équipement
*/
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td style="min-width:50px;width:70px;">';
	tr += '<span class="cmdAttr" data-l1key="id"></span>';
	tr += '</td>';
	tr += '<td style="min-width:300px;width:350px;">';
	tr += '<div class="row">';
	tr += '<div class="col-xs-7">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
	tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
	tr += '<option value="">{{Aucune}}</option>';
	tr += '</select>';
	tr += '</div>';
	tr += '<div class="col-xs-5">';
	tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> {{Icône}}</a>';
	tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
	tr += '</div>';
	tr += '</div>';
	tr += '</td>';
	tr += '<td>';
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
	tr += '</td>';
	tr += '<td style="min-width:120px;width:140px;">';
	tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></div> ';
	tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></div> ';
	tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></div>';
	tr += '</td>';
	tr += '<td style="min-width:180px;">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min.}}" title="{{Min.}}" style="width:30%;display:inline-block;"/> ';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max.}}" title="{{Max.}}" style="width:30%;display:inline-block;"/> ';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;"/>';
	tr += '</td>';
	tr += '<td>';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
	}
	tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
	tr += '</tr>';
	$('#table_cmd tbody').append(tr);
	var tr = $('#table_cmd tbody tr').last();
	jeedom.eqLogic.builSelectCmd({
		id:  $('.eqLogicAttr[data-l1key=id]').value(),
		filter: {type: 'info'},
		error: function (error) {
			$('#div_alert').showAlert({message: error.message, level: 'danger'});
		},
		success: function (result) {
			tr.find('.cmdAttr[data-l1key=value]').append(result);
			tr.setValues(_cmd, '.cmdAttr');
			jeedom.cmd.changeType(tr, init(_cmd.subType));
		}
	});
}

$.ajax({
	type: 'POST',
	url: 'plugins/chargeurVE/core/ajax/chargeurVE.ajax.php',
	data: {
		action: 'getAccounts',
	},
	dataType : 'json',
	global:false,
	error: function (request, status, error) {
		handleAjaxError(request, status, error);
	},
	success: function (data) {
		if (data.state != 'ok') {
			$('#div_alert').showAlert({message: data.state, level: 'danger'});
			return;
		}
		$('#table_account tbody').empty();
		accounts =  json_decode(data.result);
		for (i in json_decode(data.result)){
			console.log(accounts[i]);
			addAccount(accounts[i]);
		}
		$('#bt_saveAccounts').addClass('disabled');
	}
});
