<?php
if (!isConnect('admin')) {
     throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('chargeurVE');
sendVarToJS('eqType', $plugin->getId());
sendVarToJS('typeLabels',type::labels(false));
$eqLogics = eqLogic::byType($plugin->getId());
$accounts = account::all();

$chargeursDefs = chargeurVE::types();
sendVarToJS('chargeursDefs',$chargeursDefs);
?>

<div class="row row-overflow">
    <!-- Page d'accueil du plugin -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">
	<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
	<!-- Boutons de gestion du plugin -->
	<div class="eqLogicThumbnailContainer">
	    <div class="cursor accountAction logoPrimary" data-action="add">
		<i class="fas fa-user-plus"></i>
		<br>
		<span>{{Ajouter un compte}}</span>
	    </div>
	    <div class="cursor chargeurAction logoPrimary" data-action="add">
		<i class="fas fa-charging-station"></i>
		<br>
		<span>{{Ajouter un chargeur}}</span>
	    </div>
	    <div class="cursor carAction logoPrimary" data-action="add">
		<i class="fas fa-car"></i>
		<br>
		<span>{{Ajouter un véhicule}}</span>
	    </div>
	    <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
		<i class="fas fa-wrench"></i>
		<br>
		<span>{{Configuration}}</span>
	    </div>
	</div>

	<!-- Les accounts -->
	<legend><i class="fas fa-user"></i> {{Mes comptes}}</legend>
	<!-- Champs de recherche des accounts -->
	<div class="input-group" style="margin:5px;">
	    <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchAccount"/>
	    <div class="input-group-btn">
		<a id="bt_resetAccountSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
	    </div>
	</div>
	<!-- Liste des accounts -->
	<!-- <div class="accountThumbnailContainer"> -->
	<div class="accountThumbnailContainer">
	</div>

	<!-- Les chargeurs -->
	<legend><i class="fas fa-charging-station"></i> {{Mes chargeurs}}</legend>
	<!-- Champ de recherche des chargeur -->
	<div class="input-group" style="margin:5px;">
	    <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
	    <div class="input-group-btn">
		<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
	    </div>
	</div>
	<!-- Liste des chargeurs  -->
	<div class="eqLogicThumbnailContainer">
	    <?php
	    foreach ($eqLogics as $eqLogic) {
		$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
		echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
		echo '<img src="' . $eqLogic->getPathImg() . '"/>';
		echo '<br>';
		echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
		echo '</div>';
	    }
	    ?>
	</div>
    </div> <!-- Page d'accueil du plugin -->

    <!-- Page de présentation de l'équipement -->
    <div class="col-xs-12 eqLogic" style="display: none;">
	<!-- barre de gestion de l'équipement -->
	<div class="input-group pull-right" style="display:inline-flex;">
	    <span class="input-group-btn">
		<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
		<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
		</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs">  {{Dupliquer}}</span>
		</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
		</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
		</a>
	    </span>
	</div>
	<!-- Onglets -->
	<ul class="nav nav-tabs" role="tablist">
	    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
	    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i><span class="hidden-xs"> {{Équipement}}</span></a></li>
	    <li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i><span class="hidden-xs"> {{Commandes}}</span></a></li>
	</ul>
	<div class="tab-content">
	    <!-- Onglet de configuration de l'équipement -->
	    <div role="tabpanel" class="tab-pane active" id="eqlogictab">
		<!-- Partie gauche de l'onglet "Equipements" -->
		<!-- Paramètres généraux de l'équipement -->
		<form class="form-horizontal">
		    <fieldset>
			<div class="col-lg-6">
			    <legend><i class="fas fa-wrench"></i> {{Général}}</legend>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
				<div class="col-sm-7">
				    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;"/>
				    <input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" style="display : none;"/>
				    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="type" style="display : none;"/>
				    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label" >{{Objet parent}}</label>
				<div class="col-sm-7">
				    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
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
					echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
					echo '</label>';
				    }
				    ?>
				</div>
			    </div>
			    <div class="form-group">
				<label class="col-sm-3 control-label">{{Options}}</label>
				<div class="col-sm-7">
				    <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
				    <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
				</div>
			    </div>
			    <br>

			    <legend><i class="fas fa-cogs"></i> {{Paramètres}}</legend>
			    <!--
			    ---- Paramètres des chargeurs
			    -->
			    <div class='chargeurParams form-group'>
				<label class="col-sm-3 control-label">{{Compte}}</label>
				<div id="selectAccount" class="col-sm-7">
					<select class="eqLogicAttr" data-l1key="configuration" data-l2key="account">
					<?php
					foreach (account::all() as $account){
					    echo '<option value="' . $account->getId() . '">' . $account->getHumanName() . '</option>';
					}
					?>
					</select>
				</div>
			    </div>
			    <!--
			    ---- Paramètres des véhicules
			    -->
			    <div class='carParams form-group'>
				<label class="col-sm-3 control-label">{{param}}</label>
				<div class="col-sm-7 selectAccount">
				    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="param1" placeholder="{{Paramètre n°1}}"/>
				</div>
			    </div>
			</div>

			<!-- Partie droite de l'onglet "Équipement" -->
			<!-- Affiche l'icône du plugin par défaut mais vous pouvez y afficher les informations de votre choix -->
			<div class="col-lg-6">
			    <legend><i class="fas fa-info"></i> {{Informations}}</legend>
			    <!--
			    ---- Informations des chargeurs
			    -->
			    <div class="chargeurParams form-group">
				<div class="text-center">
				    <img name="icon_visu" src="<?= $plugin->getPathImgIcon(); ?>" style="max-width:160px;"/>
				    <select id="selectChargeurImg" class="eqLogicAttr" data-l1key="configuration" data-l2key="image">
				    <?php
					foreach ($chargeursDefs as $def) {
					    foreach ($def['images'] as $image) {
						echo "<option value='" . $image . "'>" . $image . "</option>";
					    }
					}
				    ?>
				    </select>
				</div>
			    </div>
			</div>
		    </fieldset>
		</form>
		<hr>
	    </div><!-- /.tabpanel #eqlogictab-->

	    <!-- Onglet des commandes de l'équipement -->
	    <div role="tabpanel" class="tab-pane" id="commandtab">
		<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
		<br/><br/>
		<div class="table-responsive">
		    <table id="table_cmd" class="table table-bordered table-condensed">
			<thead>
			    <tr>
				<th>{{Id}}</th>
				<th>{{Nom}}</th>
				<th>{{Type}}</th>
				<th>{{Options}}</th>
				<th>{{Paramètres}}</th>
				<th>{{Action}}</th>
			    </tr>
			</thead>
			<tbody>
			</tbody>
		    </table>
		</div>
	    </div><!-- /.tabpanel #commandtab-->

	</div><!-- /.tab-content -->
    </div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'chargeurVE', 'js', 'chargeurVE');?>
<?php include_file('desktop', 'chargeurVE', 'css', 'chargeurVE');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
