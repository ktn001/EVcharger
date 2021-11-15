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

<div id="selectAccountType">
  <form class="form-horizontal">
    <fieldset>
      <label class="control-label">{{Type d'account}}:</label>
      <select class="toto">
      </select>
    </fieldset>
  </form>
</div>

<script>
function selectAccountType_actualizeTypes() {
    $.ajax({
        type: 'POST',
        url: 'plugins/chargeurVE/core/ajax/chargeurVE.ajax.php',
        data: {
            action: 'chargeurTypes',
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
            types = json_decode(data.result);
            $('#selectAccountType select').empty();
            for (type of types) {
                option = '<option value="' + type.type + '">' + type.label + '</option>';
		console.log(option);
                $('#selectAccountType select').append(option);
            }
        },
    });
}

function selectAccountType(action) {
    if (action = 'result') {
        return $('#mod_selectAccountType select').value();
    }
}

$('#selectAccountType').parent().closest('div').dialog({
    focus: function (event, ui) {
        selectAccountType_actualizeTypes();
    }
})

</script>
