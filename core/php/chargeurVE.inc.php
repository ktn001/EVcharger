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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
/*
 *
 * Fichier d’inclusion si vous avez plusieurs fichiers de class ou 3rdParty à inclure
 * 
 */

require_once __DIR__ . '/../class/chargeurVEException.class.php';
require_once __DIR__ . '/../class/type.class.php';

$dir = __DIR__ . '/../class/account';
if ($dh = opendir($dir)){
    while (($file = readdir($dh)) !== false){
	    if (substr_compare($file, ".class.php",-10,10) === 0) {
        	require_once  $dir . '/' . $file;
	    }
    }
    closedir($dh);
}
