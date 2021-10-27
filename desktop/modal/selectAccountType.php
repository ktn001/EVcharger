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

<div id="mod_selectAccountType">
  <form class="form-vertical">
    <div class="form-group">
      <label class="control-label">{{Type d'account}}:</label>
      <div>
        <select>
        </select>
      </div>
    </div>
  </form>
</div>

<script>
for (i in accountTypes) {
	option = '<option value="' + i + '">' + accountTypes[i].label + '</option>';
	$('#mod_selectAccountType select').append(option);
}

function mod_selectAccountType(action) {
	if (action = 'result') {
		selected = $('#mod_selectAccountType select').value();
		return accountTypes[selected].type;
	}
}
</script>
