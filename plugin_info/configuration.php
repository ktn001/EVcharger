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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
include_file('core', 'EVcharger', 'class', 'EVcharger');
sendVarToJS('usedTypes',model::allUsed());
sendVarToJS('defaultTagColor', config::getDefaultConfiguration('EVcharger')['EVcharger']['defaultTagColor']);
sendVarToJS('defaultTagTextColor', config::getDefaultConfiguration('EVcharger')['EVcharger']['defaultTextTagColor']);
sendVarToJS('defaultPort', config::getDefaultConfiguration('EVcharger')['EVcharger']['daemon::port']);
?>

<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <div class='col-sm-6'>

        <legend class='col-sm-12'><i class="fas fa-university"></i> {{Démon}}:</legend>
        <label class="col-sm-2 control-label">
          {{Port}}
          <sup><i class="fas fa-question-circle" title="{{Redémarrer le démon en cas de modification}}"></i></sup>
        </label>
        <input class="configKey form-control col-sm-4" data-l1key="deamon::port" placeholder="<?php echo $defaultPort ?>"/>
        <legend class='col-sm-12'><i class="fas fa-laptop"></i> {{Interface}}</legend>
        <label class="col-sm-2 control-label">{{Confirme}}</label>
        <label class='col-sm-10'>
          <input class="configKey" type="checkbox" data-l1key="confirmDelete"/>
          {{Suppressions}}
          <sup><i class="fas fa-question-circle" title="{{Demande de confirmation en cas de suppression d'un élément}}"></i></sup>
        </label>
      </div>
      <div class='col-sm-6'>
        <legend><i class="fas fa-charging-station"></i> {{Les modèles de chargeurs}}:</legend>
        <table id='models' class='table table-bordered'>
          <thead>
            <tr>
              <th>{{Type}}</th>
              <th style='text-align:center'>{{Activer}}</th>
              <th style='text-align:center'>{{Couleurs personnalisées}}</th>
              <th style='text-align:center'>{{Couleur du tag}}</th>
              <th style='text-align:center'>{{Couleur du texte du tag}}</th>
              <th style='text-align:center'>{{options}}</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </fieldset>
</form>

<script>

$.ajax({
  type: 'POST',
  url: 'plugins/EVcharger/core/ajax/EVcharger.ajax.php',
  data: {
    action: 'models',
  },
  dataType: 'json',
  global: false,
  error: function (request, status, error) {
    handleAjaxError(request, status, error);
  },
  success: function (data) {
    if (data.state != 'ok') {
      $('#div_alert').showAlert({message: data.result, level: 'danger'});
      return;
    }
    $('.model').remove();
    for (var _model of data.result) {
	    console.log(_model);
      tr = '<tr class="model" data-modelId="' + _model.modelId + '">';
      tr += '<td><input class="modelAttr" data-l1key="label" disabled></input><input class="modelAttr", data-l1key="modelId" style="display:none"</td>';
      tr += '<td style="text-align:center"><input class="modelAttr" type="checkbox" data-l1key="configuration" data-l2key="enabled"/></td>';
      tr += '<td style="text-align:center"><input class="modelAttr" type="checkbox" data-l1key="configuration" data-l2key="customColor"/></td>';
      tr += '<td style="text-align:center"><input class="modelAttr" type="color" data-l1key="configuration" data-l2key="tagColor" value="' + defaultTagColor + '"/></td>';
      tr += '<td style="text-align:center"><input class="modelAttr" type="color" data-l1key="configuration" data-l2key="tagTextColor" value="' + defaultTagTextColor + '"/></td>';
      if (_model.haveModalOptions) {
        tr += '<td style="text-align:center"><a class="btn btn-default btn-xs" action="configModel"><i class="fas fa-cogs"</i></a></td>';
      } else {
        tr += '<td></td>';
      }
      tr += '</tr>';
      $('table#models tbody').append(tr);
      $('table#models tbody tr').last().setValues(_model,'.modelAttr');
    }

  }
});

$(".configKey[data-l1key^='model::'][data-l2key='enabled']").on('change',function(){
  if ($(this).value() == 1) {
    return;
  }
  modelId = $(this).closest('tr').data('modelId');
  if (usedTypes.indexOf(modelId) != -1) {
    $(this).value(1);
    bootbox.alert({title: "{{Désactivation impossible.}}", message: "{{Il existe au moins un compte pour ce modèle.}}"});
  }

});

$('table#models tbody').delegate('.btn[action=configModel]','click',function(){
  modelId = $(this).closest('tr').data('modelId');
  modelLabel = $(this).closest('tr').find('[data-l1key=label]').value();
  contName = 'modContainer_Config_' + modelId;
  contId = '#' + contName;
  if ($(contId).length == 0) {
    $('body').append('<div id="' + contName + '"></div>');
    $.ajaxSetup({async: false});
    $(contId).load('index.php?v=d&plugin=EVcharger&modal=' + modelId + '/config');
    $.ajaxSetup({async: true});
    $('#' + contName).dialog({
      closeText: '',
      autoOpen: false,
      modal: true,
      height: 200,
      width: 400
    });
  }
  $(contId).dialog({title: '{{Modèle}}' + ': ' + modelLabel});
  $(contId).dialog('option', 'buttons', {
    "{{Annuler}}": function() {
      $(this).dialog("close");
    },
    "{{Valider}}": function() {
      configs = json_encode($(contId).getValues('.configKey'));
      $.ajax({
        type: 'POST',
        url: '/plugins/EVcharger/core/ajax/EVcharger.ajax.php',
        data: {
          action: 'saveModels',
          configs : configs
        },
        dataType : 'json',
        global: false,
        error: function (request, status, error) {
          handleAjaxError(request, status, error);
        },
        success: function (data) {
          if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'});
            return;
          }
        }

      })
      configs = $(contId).getValues('.configKey');
      $(this).dialog("close");
    }
  })
  $(contId).dialog('open');
});

function EVcharger_postSaveConfiguration () {
  models = $('table#models tbody').find('.model').getValues('.modelAttr');
  console.log(models);
  $.ajax({
    type: 'POST',
    url: '/plugins/EVcharger/core/ajax/EVcharger.ajax.php',
    data: {
      action: 'saveModels',
      models : json_encode(models)
    },
    dataType : 'json',
    global: false,
    error: function (request, status, error) {
      handleAjaxError(request, status, error);
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({message: data.result, level: 'danger'});
        return;
      }
    }
  })
}
</script>
