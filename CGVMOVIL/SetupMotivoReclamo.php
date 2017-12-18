<?php
/**
 * archivo iniciacion de SetupMotivoReclamo
 * @author: rorozco
 * fecha: 04/11/16
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
require_once "Clases/SetupMotivoReclamo.class.php";
require_once($Raiz . "/include/ConfgGral.conf.php");
require_once($Raiz . "/include/ConfigMVC.conf.php");
/**
 * validacion de acceso
 */
$Permisos = AtenticacionUsuario();
ValidaRol('CGVMMRPM');

/**
 * Iniciacion del requerimiento.
 */
$_class = new SetupMotivoReclamo();
$_class->_check_var();
?>