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

<!-- ### Ajouter les champ de saisie pour les attributs spécifiques au type de compte
     ###   * Chaque champ de saisie d'un attribut doit avoir la classe "accountAttr"
     ###   * Chaque champ de saisie doit avoir un attribut *data-l1key* dont la valeur doit être le nom de l'attribut du type de ccompte
     ### -->

<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
	<span class="accountAttr" data-l1key="accountType" style="display:none"></span>
      <label class="control-label col-sm-2">Id:</label>
      <span class="accountAttr col-sm-2" data-l1key="id">123</span>
    </div>
    <div class="form-group">
      <label class="control-label col-sm-2">{{Nom}}:</label>
      <input class="accountAttr col-sm-9" type="text" data-l1key="name" placeholder="{{Nom}}"></input>
    </div>
  </fieldset>
</form>
