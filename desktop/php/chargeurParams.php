<?php
require_once __DIR__ . '/../../../../core/php/core.inc.php';
$type = $_GET['type'];
log::add("chargeurVE","debug",sprintf(__('Requête du code de saisie des params pour "%s"',__FILE__), $type));
$file = realpath (__DIR__ . '/../../core/config/' . $type . '/chargeur_params.php');
log::add("chargeurVE","debug",$file);
if (file_exists($file)) {
	ob_start();
	require_once $file;
	echo translate::exec(ob_get_clean(), $file);
}
?>
