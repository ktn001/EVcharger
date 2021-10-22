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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<div>
  <form class="form-vertical">
    <div class="form-group">
      <label class="control-label">{{Type d'account}}:</label>
      <div id="mod_selectAccountType"></div>
    </div>
  </form>
</div>

<script>
$.ajax({
  type: 'POST',
  url: 'plugins/chargeurVE/core/ajax/chargeurVE.ajax.php',
  data: {
    action: 'getAccountTypeLabels'
  },
  dataType: 'json',
  global: false,
  error: function (request, status, error) {
    handleAjaxError(request, status, error);
  },
  success: function (data) {
    if (data.state != 'ok') {
      $('#div_alert').showAlert({message: date.state, level: 'danger'});
      return;
    }
    types = json_decode(data.result);
    select = '<select id="selectAccountType">';
    for (type in types) {
      select += '<option value=' + type + '>' + types[type] + '</option>';
    }
    select += '</select>';
    $('#mod_selectAccountType').empty().append(select);
  }
});
</script>
