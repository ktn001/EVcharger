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

<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="control-label col-sm-2">Id:</label>
      <span class="accounAttr col-sm-2" data-l1key="id">123</span>
    </div>
    <div class="form-group">
      <label class="control-label col-sm-2">{{Nom}}:</label>
      <input class="acounAttr col-sm-9" type="text" data-l1key="name" placeholder="{{Nom}}"></input>
    </div>
    <div class="form-group">
      <label class="control-label col-sm-2">{{Login}}:</label>
      <input class="acounAttr col-sm-9" type="text" data-l1key="login" placeholder="{{Login}}"></input>
    </div>
    <div class="form-group">
      <label class="control-label col-sm-2">{{Password}}:</label>
      <input class="acounAttr col-sm-9" type="password" data-l1key="password" placeholder="{{Password}}"></input>
    </div>
    <div class="form-group">
      <label class="control-label col-sm-2">{{URL}}:</label>
      <input class="acounAttr col-sm-9" type="text" data-l1key="url" placeholder="{{URL}}"></input>
    </div>
  </fieldset>
</form>

