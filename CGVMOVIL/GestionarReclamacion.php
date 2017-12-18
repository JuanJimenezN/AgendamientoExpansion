<?php
/**
 * archivo iniciacion de Gestionar Reclamacion
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
require_once "Clases/GestionarReclamacion.class.php";
require_once($Raiz . "/include/ConfgGral.conf.php");
require_once($Raiz . "/include/ConfigMVC.conf.php");
require_once($Raiz . "/include/client_ws.class.php");
/**
 * validacion de acceso
 */
$Permisos = AtenticacionUsuario();
ValidaRol('CGVMMRG');

/**
 * Iniciacion del requerimiento.
 */
$_class = new GestionarReclamacion();
$_class->_check_var();
?>