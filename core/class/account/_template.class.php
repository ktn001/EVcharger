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

class _templateAccount extends account {
    /*     * *************************Attributs****************************** */

	/* ###
	 * ### Mettre ici le nom à afficher pour ce type de compte
	 * ### */
	public static $typeLabel = "Template";
	
	/* ### Le fichier image pour ce type d'account ### 
	 * ###
	 * ### Cet attrubut peurt être omis s'il l'image par défaut doit être utilisée
	 * ### Le nom du fichier image ne doit pat contenir de "/"
	 * ### Le fichier doit se trouver dans le répertoire "destktop/img" du plugin
	 * ### */

	/* ###
	 * ### Ajouter ici les attributs spécifiques aux instances de ce type de compte
	 * ### */ 

    /*     * *********************Méthodes d'instance************************* */

	function __construct() {
		parent::__construct();
		
		/* ###
		 * ### ajouter ici les initialisations d'attributs spécifiques
		 * ### */
	}
 
	/* ###
	 * ### Function optionnelle appelée avant la sauvegarde de l'account
	 * ### 
	 * ### public function preSave() {
	 * ### }
	 * ### */ 
	
	/* ###
	 * ### Function optionnelle appelée avant la sauvegarde d'un nouvel account
	 * ### 
	 * ### public function preInsert() {
	 * ### }
	 * ### */ 
	
	/* ###
	 * ### Function optionnelle appelée avant la sauvegarde d'une mise a jour
	 * ### d'un account existant
	 * ### 
	 * ### public function preUpdate() {
	 * ### }
	 * ### */ 

	/* ###
	 * ### Function optionnelle appelée après la sauvegarde de l'account
	 * ### 
	 * ### public function postSave() {
	 * ### }
	 * ### */ 
	
	/* ###
	 * ### Function optionnelle appelée après la sauvegarde d'un nouvel account
	 * ### 
	 * ### public function postInsert() {
	 * ### }
	 * ### */ 
	
	/* ###
	 * ### Function optionnelle appelée après la sauvegarde d'une mise a jour
	 * ### d'un account existant
	 * ### 
	 * ### public function postUpdate() {
	 * ### }
	 * ### */ 
	
	/* ###
	 * ### Function optionnelle appelée avant la suppression de l'account
	 * ### 
	 * ### public function preRemove() {
	 * ### }
	 * ### */ 
	
	/* ###
	 * ### Function optionnelle appelée après la suppression de l'account
	 * ### 
	 * ### public function postRemove() {
	 * ### }
	 * ### */ 
	
    /*     * **********************Getteur Setteur*************************** */
	
	/* ###
	 * ### Ajouter ici les fonctions get* et set* pour les attributs
	 * ### spécifiques de ce type de compte
	 * ### */ 

}
