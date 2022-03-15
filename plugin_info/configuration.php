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
$defaultTagColor = config::getDefaultConfiguration('EVcharger')['EVcharger']['defaultTagColor'];
$defaultTextTagColor = config::getDefaultConfiguration('EVcharger')['EVcharger']['defaultTextTagColor'];
$defaultPort = config::getDefaultConfiguration('EVcharger')['EVcharger']['daemon::port'];
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
        <input class="configKey form-control col-sm-4" data-l1key="daemon::port" placeholder="<?php echo $defaultPort ?>"/>
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
        <table class='table table-bordered'>
          <thead>
            <tr>
              <th>{{Type}}</th>
              <th style='text-align:center'>{{Activer}}</th>
              <th style='text-align:center'>{{Couleurs personnalisées}}</th>
              <th style='text-align:center'>{{Couleur du tag}}</th>
              <th style='text-align:center'>{{Couleur du texte du tag}}</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach (model::all(false) as $modelName => $model) {
              if ($modelName[0] == '_') {
                continue;
              }
              $config = config::byKey('model::' . $modelName,'EVcharger');
              if ($config == '') {
                $cfg['tagColor'] = $defaultTagColor;
                $cfg['tagTextColor'] = $defaultTextTagColor;
                config::save('model::' . $modelName,$cfg,'EVcharger');
              }
              echo '<tr>';
              echo '<td>' . $model['label'] . '</td>';
              echo '<td style="text-align:center"><input class="configKey" type="checkbox" data-l1key="model::' . $modelName . '" data-l2key="enabled"/></td>';
              echo '<td style="text-align:center"><input class="configKey" type="checkbox" data-l1key="model::' . $modelName . '" data-l2key="customColor"/></td>';
              echo '<td style="text-align:center"><input class="configKey" type="color" data-l1key="model::' . $modelName . '" data-l2key="tagColor"/></td>';
              echo '<td style="text-align:center"><input class="configKey" type="color" data-l1key="model::' . $modelName . '" data-l2key="tagTextColor"/></td>';
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </fieldset>
</form>

<script>
$(".configKey[data-l1key^='model::'][data-l2key='enabled']").on('change',function(){
	if ($(this).value() == 1) {
		return;
	}
	model = $(this).attr('data-l1key').slice(6);
	if (usedTypes.indexOf(model) != -1) {
		$(this).value(1);
		bootbox.alert({title: "{{Désactivation impossible.}}", message: "{{Il existe au moins un compte pour ce modèle.}}"});
	}

});
</script>
