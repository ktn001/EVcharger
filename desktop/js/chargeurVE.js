
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
$('#table_cmd').on('sortupdate',function(event,ui){
    modifyWithoutSave = true;
});

/*
 * Chargement des acountDisplayCards
 */
function loadAccountCards() {
    $.ajax({
	type: 'POST',
	url: 'plugins/chargeurVE/core/ajax/account.ajax.php',
	data: {
	    action: 'displayCards',
	},
	dataType : 'json',
	global:false,
	error: function (request, status, error) {
	    handleAjaxError(request, status, error);
	},
	success: function (data) {
	    if (data.state != 'ok') {
		$('#div_alert').showAlert({message: data.result, level: 'danger'});
		return;
	    }
	    accounts = json_decode(data.result);
	    $('.accountThumbnailContainer').empty();
	    for (account of accounts) {
		opacity = (account.isEnable == 1) ? '' : 'disableCard';
		html = '<div class="accountDisplayCard cursor ' + opacity + '" data-account_id="' + account.id + '" data-account_type="' + account.accountType + '">';
		html += '<img src="' + account.image + '"/>';
		html += '<br/>';
		html += '<span class="name">' + account.humanName + '</span>';
		html += '</div>';
		$('.accountThumbnailContainer').append(html);
		$('.accountThumbnailContainer').packery('reloadItems').packery();
	    }
	}
    });
}

/*
 * Chargement initial des accounts
 */
$('.accountThumbnailContainer').packery();
loadAccountCards();

/*
 * Edition d'un account
 */
function editAccount (accountType ,accountId = '') {
    if (accountType === undefined) {
	$('#div_alert').showAlert({message: "{{Type de compte pas défini!}}", level: 'danger'});
	return;
    }
    for (a of accountTypes) {
	if (a.accountType == accountType){
	    accountType_label = a.label;
	    break;
	}
    }

    mod_url = 'index.php?v=d&plugin=chargeurVE&modal=edit_' + accountType + 'Account';
    mod_id = 'mod_EditAccountType' + accountType;
    if ($('#' + mod_id).length == 0){
	$('body').append('<div id="' + mod_id + '" title="{{Compte de type:}} ' + accountType_label + '"/>');
	$('#' + mod_id).dialog({
	    closeText: '',
	    autoOpen: false,
	    modal: true,
	    height:300,
	    width:680
	});
	jQuery.ajaxSetup({async: false});
	$('#' + mod_id).load(mod_url);
	jQuery.ajaxSetup({async: true});
    }
    $.ajax({
	type: 'POST',
	url: 'plugins/chargeurVE/core/ajax/account.ajax.php',
	data: {
	    action: 'byId',
	    id: accountId,
	    accountType: accountType
	},
	dataType : 'json',
	global:false,
	error: function (request, status, error) {
	    handleAjaxError(request, status, error);
	},
	success: function (data) {
	    if (data.state != 'ok') {
		$('#div_alert').showAlert({message: data.result, level: 'danger'});
		return;
	    }
	    $('#' + mod_id + ' .accountAttr').value('');
	    $('#' + mod_id).setValues(data.result,'.accountAttr');
	}
    });
    buttons = []
    buttons.push( {
	text: "{{Annuler}}",
	click: function() {
	    $(this).dialog("close");
	}
    });
    if ( accountId != '') {
	buttons.push( {
	    text: "{{Supprimer}}",
	    click: function() {
		account =  json_encode($('#' + mod_id).getValues('.accountAttr')[0]);
		$.ajax({
		    type: 'POST',
		    url: 'plugins/chargeurVE/core/ajax/account.ajax.php',
		    data: {
			action: 'remove',
			account: account
		    },
		    dataType: 'json',
		    global: false,
		    error: function (request, status, error) {
			handleAjaxError(request, status, error);
			loadAccountCards();
		    },
		    success: function (data) {
			if (data.state != 'ok') {
			    $('#div_alert').showAlert({message: data.result, level: 'danger'});
			    return;
			}
			$('#' + mod_id).dialog("close");
			loadAccountCards();
		    }
		})
	    }
	});
    };
    buttons.push( {
	text: "{{Sauvegarder}}",
	click: function () {
	    account =  json_encode($('#' + mod_id).getValues('.accountAttr')[0]);
	    $.ajax({
		type: 'POST',
		url: 'plugins/chargeurVE/core/ajax/account.ajax.php',
		data: {
		    action: 'save',
		    account: account
		},
		dataType: 'json',
		global: false,
		error: function (request, status, error) {
		    handleAjaxError(request, status, error);
		    loadAccountCards();
		},
		success: function (data) {
		    if (data.state != 'ok') {
			$('#div_alert').showAlert({message: data.result, level: 'danger'});
			return;
		    }
		    $('#' + mod_id).dialog("close");
		    loadAccountCards();
		}
	    });
	}
    });
    $('#' + mod_id).dialog('option', 'buttons', buttons);
    $('#' + mod_id).dialog('open');
}

/*
 * Action du bouton d'ajout d'un account
 */
$('.accountAction[data-action=add]').off('click').on('click', function() {
    if ($('#mod_selectAccountType').length == 0) {
	$('body').append('<div id="mod_selectAccountType" title="{{Sélection d\'un type de compte}}"/>');
	$("#mod_selectAccountType").dialog({
	    closeText: '',
	    autoOpen: false,
	    modal: true,
	    height:200,
	    width:300
	});
	jQuery.ajaxSetup({async: false});
	$('#mod_selectAccountType').load('index.php?v=d&plugin=chargeurVE&modal=selectAccountType');
	jQuery.ajaxSetup({async: true});
    }
    $('#mod_selectAccountType').dialog('option', 'buttons', {
	"{{Annuler}}": function () {
	    $(this).dialog("close");
	},
	"{{Valider}}": function () {
	    $(this).dialog("close");
	    editAccount(selectAccountType('result'));
	}
    });
    $('#mod_selectAccountType').dialog('open');
});

/*
 * Action click sur account Display card
 */
$('.accountThumbnailContainer').off('click').on('click','.accountDisplayCard', function () {
    account_id = $(this).attr("data-account_id");
    account_type = $(this).attr("data-account_type");
    editAccount(account_type, account_id);
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
		$('#div_alert').showAlert({message: data.result, level: 'danger'});
		return;
	    }
	    $('#table_account tbody').empty();
	    accounts =  json_decode(data.result);
	    for (i in json_decode(data.result)){
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
 * Action du bouton d'ajout d'un chargeur
 */
$('.chargeurAction[data-action=add').off('click').on('click',function () {
    if ($('#modContainer_chargeurNameAndType').length == 0) {
	$('body').append('<div id="modContainer_chargeurNameAndType" title="{{Nouveau chargeur:}}"/>');
	jQuery.ajaxSetup({async: false});
	$('#modContainer_chargeurNameAndType').load('index.php?v=d&plugin=chargeurVE&modal=chargeurNameAndType');
	jQuery.ajaxSetup({async: true});
	$("#mod_chargeurNameAndType").dialog({
	    closeText: '',
	    autoOpen: false,
	    modal: true,
	    height:200,
	    width:400
	});
    }
    $('#mod_chargeurNameAndType').dialog('option', 'buttons', {
	"{{Annuler}}": function () {
	    $(this).dialog("close");
	},
	"{{Valider}}": function () {
	    chargeurs = mod_chargeurNameAndType('result');
	    if ( chargeurs[0].name != '') {
		$(this).dialog("close");
		jeedom.eqLogic.save({
		    type: eqType,
		    eqLogics: chargeurs,
		    error: function(error) {
			$('#div_alert').showAlert({message: error.message, level: 'danger'});
		    },
		    success: function(_data) {
			var vars = getUrlVars();
			var url = 'index.php?';
			for (var i in vars) {
			    if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
				url += i + '=' + vars[i].replace('#', '') + '&';
			    }
			}
			modifyWithoutSave = false;
			url += 'id=' + _data.id + '&saveSuccessFull=1';
			loadPage(url);
		    }
		})
	    }
	}
    });
    $('#mod_chargeurNameAndType').dialog('open');
});

$('#selectChargeurImg').on('change',function(){
    console.log("XXX " + $(this).value());
    $('[name=icon_visu]').attr('src', "plugins/chargeurVE/desktop/img/" + $(this).value());
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

/*
 * Chargement de la liste des choix des accounts
 */
function loadSelectAccount() {
    $.ajax({
	type: 'POST',
	url: 'plugins/chargeurVE/core/ajax/account.ajax.php',
	data: {
	    action: 'getAccountToSelect',
	    accountType: $('.eqLogicAttr[data-l1key=configuration][data-l2key=type]').value(),
	},
	dataType : 'json',
	global:false,
	error: function (request, status, error) {
	    handleAjaxError(request, status, error);
	},
	success: function (data) {
	    if (data.state != 'ok') {
		$('#div_alert').showAlert({message: data.result, level: 'danger'});
		return;
	    }
    	    selectedId = $('#selectAccount select').value();
    	    $('#selectAccount').empty();
	    datas = json_decode(data.result,true);
	    content = '<select class="eqLogicAttr" data-l1key="configuration" data-l2key="account">';
	    for (data of datas) {
		selected = data.id == selectedId ? "selected" : "";
		content += '<option value="' + data.id + '" ' + selected + '>' + data.value + '</option>';
	    }
	    content += '</select>';
    	    $('#selectAccount').append(content);
	}
    });
}

function loadSelectImg() {
    chargeurType = $('.eqLogicAttr[data-l1key=configuration][data-l2key=type]').value();
    selectedImg = $('#selectChargeurImg').value();
    content = "";
    for (defs of chargeursDefs) {
	if (defs.type == chargeurType) {
	    for (img of defs.images) {
		selected =  img == selectedImg ? "selected" : "";
		content += '<option value="' + img + '" ' + selected + '>' + img + '</option>';
	    }
	}
    }
    $('#selectChargeurImg').empty().append(content).trigger('change');
}

function printEqLogic (data) {
    if ($('.eqLogicAttr[data-l1key=logicalId]').value() == "chargeur"){
	$('.carParams').remove();
	loadSelectAccount();
	loadSelectImg();
    }
    if ($('.eqLogicAttr[data-l1key=logicalId]').value() == "car"){
	$('.chargeurParams').remove();
    }

}
