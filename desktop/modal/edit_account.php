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

<form class='form-horizontal'>
  <fieldset>
    <div id='accountParameters' class='col-sm-6'>
      <div class='form-group'>
        <span class='accountAttr' data-l1key='model' style='display:none'></span>
        <label class='control-label col-sm-3'>Id:</label>
        <span class='accountAttr col-sm-2' data-l1key='id'</span>
      </div>
      <div class='form-group'>
        <label class='control-label col-sm-3'>{{Nom}}:</label>
        <input class='accountAttr col-sm-9' type='text' data-l1key='name' placeholder='{{Nom}}'></input>
      </div>
      <div class='dynamic'>
      </div>
      <div class='form-group'>
        <label class='control-label col-sm-3'>{{Options}}:</label>
        <div class='col-sm-9'>
	  <label class='checkbox-inline'>
            <input class='accountAttr' type='checkbox' data-l1key='enabled'>{{Activer}}</input>
          </label>
        </div>
      </div>
    </div>
    <div class='col-sm-6'>
      <div class='text-center'>
        <img id='accountImage' class='text-center' tyle='max-width:160px'><img>
      </div>
      <select id='selectAccountImage' class='accountAttr' data-l1key='image' ></select>
    </div>
  </fieldset>
</form>

<script>

$('#selectAccountImage').change(function() {
	$('#accountImage').attr('src', $(this).value())
});

mod_editAccount = {
	build : function (params, images) {
		$('#accountParameters .dynamic').empty();
		if (params.login == 1) {
			champ = "<div class='form-group'>"
			      + "<label class='control-label col-sm-3'>{{Login}}:</label>"
			      + "<input class='accountAttr col-sm-9' type='text' data-l1key='login' placeholder='{{Login}}'></input>"
			      + "</div>";
			$('#accountParameters .dynamic').append(champ);
		}
		if (params.password == 1) {
			champ = "<div class='form-group'>"
			      + "<label class='control-label col-sm-3'>{{PAssword}}:</label>"
			      + "<input class='accountAttr col-sm-9' type='password' data-l1key='password' placeholder='{{Password}}'></input>"
			      + "</div>";
			$('#accountParameters .dynamic').append(champ);
		}
		if (params.url == 1) {
			champ = "<div class='form-group'>"
			      + "<label class='control-label col-sm-3'>{{URL}}:</label>"
			      + "<input class='accountAttr col-sm-9' type='text' data-l1key='url' placeholder='{{URL}}'></input>"
			      + "</div>";
			$('#accountParameters .dynamic').append(champ);
		}
		$('#selectAccountImage').empty();
		for (image of images) {
			console.log(image)
			splitPath = image.split('/').reverse();
			if (splitPath[1] != 'img') {
				display = splitPath[1] + "/" + splitPath[0];
			} else {
				display = splitPath[0];
			}
			option = '<option value="' + image + '">' + display + '</option>';
			$('#selectAccountImage').append(option);
		}
	}
}

</script>
