<?php
/**
 * archivo iniciacion de SetupTipoReclamo
 * @author: rorozco
 * fecha: 31/10/16
 * Time: 08:41 AM
 */

/**
 * Definicion de la ruta raiz
 **/
$Raiz = dirname(dirname(__file__));

/**
 * Inclusiones al archivo para implementar metodos y estandares
 */
include_once $Raiz . '/include/Packeges.php';

/**
 * Inclusiones al archivo para implementar metodos de Gestion Reclamacion
 */
require_once "Clases/SetupTipoReclamo.class.php";
require_once($Raiz . "/include/ConfgGral.conf.php");
require_once($Raiz . "/include/ConfigMVC.conf.php");
/**
 * validacion de acceso
 */
$Permisos = AtenticacionUsuario();
ValidaRol('CGVMMRPT');

/**
 * Iniciacion del requerimiento.
 */
$_class = new SetupTipoReclamo();
$_class->_check_var();
/**
 *Se agrega un nuevo comentario para el Jenkins
 *Se agrega un segundo comentario para el Jenkins
*/
?>

