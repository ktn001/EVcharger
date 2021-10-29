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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../account.class.php';

class templateAccount extends account {
    /*     * *************************Attributs****************************** */

	/* ### Mettre ici le nom à afficher pour ce type de compte ### */
	public static $typeLabel = "Template";
	
	/* ### Le fichier image pour ce type d'account ### */
	/* ###
	   ### Cet attrubut peurt être omis s'il l'image par défaut doit être utilisée
	   ### Le nom du fichier image ne doit pat contenir de "/"
	   ### Le fichier doit se trouver dans le répertoire "destktop/img" du plugin
	   ### */
	// public static $image = "account_template.png";
	
	/* ### Ajouter ici les attributs spécifiques aux instances de ce type de compte ### */ 

    /*     * *********************Méthodes d'instance************************* */

	function __construct() {
		parent::__construct();
		
		/* ### ajouter ici les initialisations d'attributs spécifiques ### */
	}
 
	function save () {
		/* ### Vérification des valeurs des attributs spécifiques de ce type de compte.
		   ### Une exception doit être levée si l'accompte ne doit pas être enregistré
		   ### */
		parent::save();
	}
	
    /*     * **********************Getteur Setteur*************************** */

	/* ### Ajouter ici les fonctions get* et set* pour les attributs spécifiques de ce type de compte ### */ 

}
