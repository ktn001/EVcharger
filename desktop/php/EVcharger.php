<?php
if (!isConnect('admin')) {
     throw new Exception('{{401 - Accès non autorisé}}');
}
//  Déclaration des variables obligatoires
$plugin = plugin::byId('EVcharger');
$chargers = EVcharger_charger::byType('EVcharger_charger');
$vehicles = EVcharger_vehicle::byType('EVcharger_vehicle');
$accounts = account::all();

log::add("EVcharger","info",print_r($chargers,true));
// Déclaration de variables pour javasctipt
sendVarToJS('eqType', $plugin->getId());
sendVarToJS('chargerType', $plugin->getId() . "_charger");
sendVarToJS('vehicleType', $plugin->getId() . "_vehicle");
sendVarToJs('confirmDelete',config::byKey('confirmDelete','EVcharger'));
sendVarToJS('modelLabels',model::labels());
?>

<div class="row row-overflow">
    <!-- ======================== -->
    <!-- Page d'accueil du plugin -->
    <!-- ======================== -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">

	<!-- Boutons de gestion du plugin -->
	<!-- ============================ -->
	<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
	<div class="eqLogicThumbnailContainer">
	    <div class="cursor accountAction logoPrimary" data-action="add">
		<i class="fas fa-user-plus"></i>
		<br>
		<span>{{Ajouter un compte}}</span>
	    </div>
	    <div class="cursor chargerAction logoPrimary" data-action="add">
		<i class="fas fa-charging-station"></i>
		<br>
		<span>{{Ajouter un chargeur}}</span>
	    </div>
	    <div class="cursor vehicleAction logoPrimary" data-action="add">
		<i class="fas fa-car"></i>
		<br>
		<span>{{Ajouter un véhicule}}</span>
	    </div>
	    <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
		<i class="fas fa-wrench"></i>
		<br>
		<span>{{Configuration}}</span>
	    </div>
	</div> <!-- Boutons de gestion du plugin -->

	<!-- Les accounts -->
	<!-- ============ -->
	<legend><i class="fas fa-user"></i> {{Mes comptes}}</legend>
	<!-- Champs de recherche des accounts -->
	<div class="input-group">
	    <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchAccount"/>
	    <div class="input-group-btn">
		<a id="bt_resetSearchAccount" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
	    </div>
	</div> <!-- Champs de recherche des accounts -->
	<!-- Liste des accounts -->
	<div id="accounts-div" class="eqLogicThumbnailContainer">
	</div> <!-- Liste des accounts -->
	<!-- Les accounts -->

	<!-- Les chargeurs et véhicules -->
	<!-- ========================== -->
	<legend><i class="fas fa-charging-station"></i><i class="fas fa-car"></i> {{Mes chargeurs et véhicules}}</legend>
	<!-- Champ de recherche des chargeurs -->
	<div class="input-group">
	    <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
	    <div class="input-group-btn">
		<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
	    </div>
	</div> <!-- Champ de recherche des chargeurs -->
	<!-- Liste des chargeurs -->
	<div class="eqLogicThumbnailContainer">
	    <?php
	    foreach ($chargers as $charger) {
		$opacity = ($charger->getIsEnable()) ? '' : 'disableCard';
		echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $charger->getId() . '" data-eqLogic_type="EVcharger_charger" data-eqLogic_model="' . $charger->getconfiguration('model') . '">';
		echo '<img src="' . $charger->getPathImg() . '" style="width:unset !important"/>';
		echo '<br>';
		echo '<span class="name">' . $charger->getHumanName(true, true) . '</span>';
		echo '</div>';
	    }
	    foreach ($vehicles as $vehicle) {
		echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $vehicle->getId() . '" data-eqLogic_type="EVcharger_vehicle">';
		echo '<img src="' . $vehicle->getPathImg() . '"/>';
		echo '<br>';
		echo '<span class="name">' . $vehicle->getHumanName(true, true) . '</span>';
		echo '</div>';
	    }
	    ?>
	</div> <!-- Liste des chargeurs -->

    </div> <!-- Page d'accueil du plugin -->

    <!-- ================================================= -->
    <!-- Pages de configuration des chargeurs et véhicules -->
    <!-- ================================================= -->
    <div class="col-xs-12 eqLogic EVcharger_charger EVcharger_vehicle" style="display: none;">

	<!-- barre de gestion des chargeurs et véhicules -->
	<!-- =========================================== -->
	<div class="input-group pull-right" style="display:inline-flex;">
	    <span class="input-group-btn">
		<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
		<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
		</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
		</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
		</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
		</a>
	    </span>
	</div> <!-- barre de gestion du chargeur -->

	<!-- Les onglets des chargeurs et véhicules -->
	<!-- ====================================== -->
	<ul class="nav nav-tabs" role="tablist">
	    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
	    <li role="presentation" class="tab-EVcharger_charger active"><a href="#chargertab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-charging-station"></i><span class="hidden-xs"> {{Chargeur}}</span></a></li>
	    <li role="presentation" class="tab-EVcharger_vehicle"><a href="#vehicletab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-car"></i><span class="hidden-xs"> {{Véhicule}}</span></a></li>
	    <li role="presentation"><a href="#chargercommandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i><span class="hidden-xs"> {{Commandes}}</span></a></li>
	</ul>

	<div class="tab-content">
	    <!-- Tab de configuration d'un chargeur -->
	    <!-- ================================== -->
	    <div role="tabpanel" class="tab-pane tab-EVcharger_charger" id="chargertab">
		<!-- Paramètres généraux de l'équipement -->
		<form class="form-horizontal">
		    <fieldset>

			<!-- Partie gauche de l'onglet "Equipements" -->
			<div class="col-lg-6">
			    <legend><i class="fas fa-wrench"></i> {{Général}}</legend>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Nom du chargeur}}</label>
				<div class="col-sm-7">
				    <input type="text" class="EVcharger_chargerAttr form-control" data-l1key="id" style="display : none;"/>
				    <input type="text" class="EVcharger_chargerAttr form-control" data-l1key="configuration" data-l2key="model" style="display : none;"/>
				    <input type="text" class="EVcharger_chargerAttr form-control" data-l1key="name" placeholder="{{Nom du chargeur}}"/>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label" >{{Objet parent}}</label>
				<div class="col-sm-7">
				    <select id="sel_object" class="EVcharger_chargerAttr form-control" data-l1key="object_id">
					<option value="">{{Aucun}}</option>
					<?php
					$options = '';
					foreach ((jeeObject::buildTree(null, false)) as $object) {
					    $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
					}
					echo $options;
					?>
				    </select>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Catégorie}}</label>
				<div class="col-sm-7">
				    <?php
				    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
					echo '<label class="checkbox-inline">';
					echo '<input type="checkbox" class="EVcharger_chargerAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
					echo '</label>';
				    }
				    ?>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Options}}</label>
				<div class="col-sm-7">
				    <label class="checkbox-inline"><input type="checkbox" class="EVcharger_chargerAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
				    <label class="checkbox-inline"><input type="checkbox" class="EVcharger_chargerAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
				</div>
			    </div>
			    <br>

			    <legend><i class="fas fa-cogs"></i> {{Paramètres}}</legend>
			    <div class='form-group'>
				<label class="col-sm-3 control-label">{{Coordonnées GPS}}</label>
				<div class="col-sm-7">
				   <i class="far fa-comment"></i>
				   <i>{{Pour obtenir les coordonnées GPS, vous pouvez utiliser ce <a href="https://www.torop.net/coordonnees-gps.php" target="_blank">site.</a>}}</i>
				</div>
			    </div>
			    <div class="col-sm-3"></div>
			    <div class="col-sm-7">
				<div class='form-group' style="margin-right:-24px">
				    <label class="col-sm-3">{{Latitude}}:</label>
			   	    <div class="col-sm-9">
					<input type="text" class="EVcharger_chargerAttr form-control" data-l1key="configuration" data-l2key="latitude" placeholder="{{Latitude}}"/>
				    </div>
				</div>
				<div class='form-group' style="margin-right:-24px">
				    <label class="col-sm-3">{{Longitude}}:</label>
				    <div class="col-sm-9">
					<input type="text" class="EVcharger_chargerAttr form-control" data-l1key="configuration" data-l2key="longitude" placeholder="{{Longitude}}"/>
				    </div>
				</div>
			    </div>
			    <div class='form-group'>
				<label class="col-sm-3 control-label">{{Compte}}</label>
				<div class="col-sm-7">
				    <select id="selectAccount" class="EVcharger_chargerAttr" data-l1key="configuration" data-l2key="accountId">
				    </select>
				</div>
			    </div>
			    <div id="ChargerSpecificsParams">
			    </div>
			</div> <!-- Partie gauche de l'onglet "Equipements" -->

			<!-- Partie droite de l'onglet "Équipement" -->
			<div class="col-lg-6">
			    <!-- Informations des chargeurs -->
			    <legend><i class="fas fa-info"></i> {{Informations}}</legend>
			    <div class="form-group">
				<div class="text-center">
				    <img name="icon_visu" style="max-width:160px;"/>
				    <select id="selectChargerImg" class="EVcharger_chargerAttr" data-l1key="configuration" data-l2key="image">
				    </select>
				</div>
			    </div>
			</div> <!-- Partie droite de l'onglet "Équipement" -->

		    </fieldset>
		</form>
	    </div> <!-- Tab de configuration d'un chargeur -->

	    <!-- Tab de configuration d'un véhicule -->
	    <!-- ================================== -->
	    <div role="tabpanel" class="tab-pane tab-EVcharger_vehicle" id="vehicletab">
		<!-- Paramètres généraux de l'équipement -->
		<form class="form-horizontal">
		    <fieldset>

			<!-- Partie gauche de l'onglet "Equipements" -->
			<div class="col-lg-6">
			    <legend><i class="fas fa-wrench"></i> {{Général}}</legend>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Nom du chargeur}}</label>
				<div class="col-sm-7">
				    <input type="text" class="EVcharger_vehicleAttr form-control" data-l1key="id" style="display : none;"/>
				    <input type="text" class="EVcharger_vehicleAttr form-control" data-l1key="configuration" data-l2key="model" style="display : none;"/>
				    <input type="text" class="EVcharger_vehicleAttr form-control" data-l1key="name" placeholder="{{Nom du chargeur}}"/>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label" >{{Objet parent}}</label>
				<div class="col-sm-7">
				    <select id="sel_object" class="EVcharger_vehicleAttr form-control" data-l1key="object_id">
					<option value="">{{Aucun}}</option>
					<?php
					$options = '';
					foreach ((jeeObject::buildTree(null, false)) as $object) {
					    $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
					}
					echo $options;
					?>
				    </select>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Catégorie}}</label>
				<div class="col-sm-7">
				    <?php
				    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
					echo '<label class="checkbox-inline">';
					echo '<input type="checkbox" class="EVcharger_vehicleAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
					echo '</label>';
				    }
				    ?>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Options}}</label>
				<div class="col-sm-7">
				    <label class="checkbox-inline"><input type="checkbox" class="EVcharger_vehicleAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
				    <label class="checkbox-inline"><input type="checkbox" class="EVcharger_vehicleAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
				</div>
			    </div>
			</div> <!-- Partie gauche de l'onglet "Equipements" -->

			<!-- Partie droite de l'onglet "Équipement" -->
			<div class="col-lg-6">
			    <legend><i class="fas fa-info"></i> {{Informations}}</legend>
			    <div class="form-group">
				<div class="text-center">
				    <img name="icon_visu" style="max-width:160px;"/>
				    <select id="selectChargerImg" class="EVcharger_vehicleAttr" data-l1key="configuration" data-l2key="image">
				    </select>
				</div>
			    </div>
			</div> <!-- Partie droite de l'onglet "Équipement" -->

		    </fieldset>
		</form>
	    </div> <!-- Tab de configuration d'un véhicule -->

	    <!-- Onglet des commandes du chargeur -->
	    <!-- ================================ -->
	    <div role="tabpanel" class="tab-pane" id="chargercommandtab">
		<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
		<a class="btn btn-default btn-sm pull-right cmdAction" data-action="actualize" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Mettre à jour les commande par défaut}}</a>
		<br/><br/>
		<div class="table-responsive">
		    <table id="table_cmd" class="table table-bordered table-condensed">
			<thead>
			    <tr>
				<th class="hidden-xs" style="min-width:50px;width:70px"> ID</th>
				<th style="min-width:200px;width:240px">{{Nom}}</br>Logical_Id</th>
				<th style="min-width:200px;width:240px">{{Icône}}</br>{{valeur retour}}</th>
				<th style="width:130px">{{Type}}</br>{{Sous-type}}</th>
				<th>{{Valeur}}</th>
				<th style="min-width:260px;width:310px">{{Options}}</th>
				<th style="min-width:80px;width:200px">{{Action}}</th>
			    </tr>
			</thead>
			<tbody>
			</tbody>
		    </table>
		</div>
	    </div> <!-- Onglet des commandes du chargeur -->

	</div> <!-- Les onglets des chargeurs -->
    </div> <!-- Page de configuration d'un chargeur -->

</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'EVcharger', 'js', 'EVcharger');?>
<?php include_file('desktop', 'EVcharger', 'css', 'EVcharger');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
