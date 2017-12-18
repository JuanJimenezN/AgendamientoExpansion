<?php
/**
 * Created by PhpStorm.
 * User: rorozco
 * Date: 27/12/16
 * Time: 02:23 PM
 */
/*
 * Otro cambio más Nuevo cambio para ver si por fin funciona
 */
/**
 * Definicion de la ruta raiz
 **/
$Raiz = dirname(dirname(__file__));

/**
 * nueva cosa en esta vaina
 * Inclusiones al archivo para implementar metodos y estandares
 */
include_once $Raiz . '/include/Packeges.php';

/**
 * Inclusiones al archivo para implementar metodos de Gestion Reclamacion
 */
require_once "Clases/SetupEstadoReclamacion.class.php";
require_once($Raiz . "/include/ConfgGral.conf.php");
require_once($Raiz . "/include/ConfigMVC.conf.php");
/**
 * validacion de acceso
 */
$Permisos = AtenticacionUsuario();
ValidaRol('CGVMMRPE');

/**
 * Iniciacion del requerimiento.
 */
$_class = new SetupEstadoReclamacion();
$_class->_check_var();
?>
