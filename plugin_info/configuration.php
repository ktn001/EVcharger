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
include_file('core', 'chargeurVE', 'class', 'chargeurVE');
$chargeurVETypes = chargeurVE::types();
$defaultTagColor = config::getDefaultConfiguration('chargeurVE')['chargeurVE']['defaultTagColor'];
$defaultTextTagColor = config::getDefaultConfiguration('chargeurVE')['chargeurVE']['defaultTextTagColor'];
?>

<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <div class='col-sm-6'>
        <legend>{{Démon}}:</legend>
        <label class="col-sm-2 control-label">{{Port}}</label>
	<input class="configKey form-control col-sm-4" data-l1key="daemon::port" placeholder="<?php echo config::getDefaultConfiguration('chargeurVE')['chargeurVE']['daemon::port'] ?>"></input>
      </div>
      <div class='col-sm-6'>
        <legend>{{Les types de chargeurs}}:</legend>
        <table class='table table-bordered'>
          <thead>
            <tr>
              <th>{{Type}}</th>
              <th style='text-align:center'>{{Activer}}</th>
              <th style="text-align:center">{{Couleur du tag}}</th>
              <th style="text-align:center">{{Couleur du texte du tag}}</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($chargeurVETypes as $type) {
	      $config = config::byKey('chargeurVE::' . $type['type'],'chargeurVE');
	      if ($config == '') {
		      $cfg['tagColor'] = $defaultTagColor;
		      $cfg['tagTextColor'] = $defaultTextTagColor;
		      config::save('chargeurVE::' . $type['type'],$cfg,'chargeurVE');
	      }
              echo '<tr>';
	      echo '<td>' . $type['label'] . '</td>';
	      echo '<td style="text-align:center"><input class="configKey" type="checkbox" data-l1key="chargeurVE::' . $type['type'] . '" data-l2key="enable"/></td>';
              echo '<td style="text-align:center"><input class="configKey" type="color" data-l1key="chargeurVE::' . $type['type'] . '" data-l2key="tagColor"/></td>';
              echo '<td style="text-align:center"><input class="configKey" type="color" data-l1key="chargeurVE::' . $type['type'] . '" data-l2key="tagTextColor"/></td>';
              echo '</tr>';
	    }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </fieldset>
</form>
