<?php /** @author Herik Giovanny Cardozo Bustos @version 2014.01.03 @package Configuracion **/ //include_once( 'Configuracion.php'); $Raiz =  Configuracion::getBase(); /** EndConfiguracion **/ ?><?php
#12924522253921||ACTUALIZACION||80234702||10.244.143.19:8091||192.168.15.213||201012151730253921#
define('VALIDA_NIVELES','N');#Valida niveles [S/N]
define('NGZONA', '12');
define('NRZONA', '3');
define('IDNG_RES', '3'); #VALIDA EL IDNG PARA RESIDENCIAL
define('MATERIALES', '0'); #VALIDA MUESTRA MATERIALES 0 NO VEMOS MATERIALES 1 LOS VEMOS.
define('RESTRINGEINV', 1);#Activa o desactiva restriccion de inventarios
define('AUTENTICAAD','N');#Flag Realiza la Validacion de Usuario en el Directori Activo [S/N]
define('SESSIONES','N');#Flag Realiza la Validacion de Usuario Por Variables de Sesion [S/N]
define('COOKIES','S'); #Flag Realiza Validacion de Usuarios por Cokies [S/N]
define('GENERALOG','N');#Flag Habilita la Generacion de log [S/N]
define('PCMLAGENDA',1);#Flag Habilita el uso de WS en agendamiento prueba[1/0]
define('VALIDACADICIONALES',7);
define('TIMESESSION',10);#Minutos de duracion de la sesion de sistema
define('PENALIDAD_INTENTOS', 10);//minutios de penalizacion por exeder los 5 intentos fallidos de logeo
define('MAIL_SISTEMA', 'xx@telmex.com'); //mail que va a enviar los correos de alertas del sistema
define('SESSIONTIME',0);#Activa o desactiva el el limite de sesion por tiempo
define('LOGSCRIPT',0);#Activa o desactiva el log por scripts en el sistema.
define('LOGCONSULTAS',FALSE);#Activa o desactiva el log por scripts en el sistema.
define('DBSDEBUGS','ORACLE|INTRAWAY|AS400');#Cadena que contiene los nombres de las bases a las que se les hace debug, cada base va separada por caracter pipe
define('LOGAUDITORIA',false);
define('LOGAUDITORIAPATH','E:/LogSesiones/');
define('PERFILCG0','<PERFILCGO><PERFIL>81</PERFIL></PERFILCGO>');#ID DE PERFIL SEPARADO POR [#] EJ. 81#82#134
define('RED_EXTERNA','EXT');#id en la base de datos de la red externa
define('RED_RESIDENCIAL','RES');#id en la base de datos de la red residencial
define('RED_CORPORATIVA','COR');#id en la base de datos de la red corporativa
//constantes para agendamiento de moviles
define('UNIDAD_TIEMPO_MINIMA',15);
define('CANTIDAD_DIAS',2);
define('INVVIGENCIA',0);#Valida vigencia de inventario por ciudad - tegnologia
define('VALIDAxCEDULA',TRUE);#para validar por cedula poner true
//Constantes para redireccionar a gerencia produccion y a gerencia pruebas
define('GERENCIAPRO', 'gerencia.cable.net.co');
//define('GERENCIAPRU', '10.244.143.19:8888');
define('GERENCIAPRU', '192.168.0.133');
#12924522253921||ACTUALIZACION||80234702||10.244.143.19:8091||192.168.15.213||201012151730253921#
define('BODEGA_SERIALIZADOS', 'A');
define('BODEGA_MATERIALES', 'K');
define('HILOSACTMASUNIDADES', '2');
define('HILOSACTMASUNIDADESFESTIVO', '20');
define('ACTIVA_ENVIO_CORREOS', 'Y'); //ACTIVA O DESACTIVA ENVIO DE CORREO (Y ENVIA CORRERO) (N NO ENVIA CORREO)
define('FTP_ALIADOS_MACHINE','10.244.143.17');
define('FTP_ALIADOS_USER','ftpsapaliados');
define('FTP_ALIADOS_PASSWORD','T3lm3xFTP');
define('WS_COMCEL_ACTIVO', TRUE); //Ejecutar activacion en comcel por WS (true) Ejecutar activacion Manual(False) Sin WS
/**************************************************************************************
 * aqui comienzan las constantes necesarias para facturacion.
 **************************************************************************************/
define('CODIGODIASESPECIALESCAPEX', 'RECAPEX');#defineel codigo con el que se creo la actividad facturable de dias especiales.
define('CODIGODIASESPECIALESOPEX', 'REOPEX');#defineel codigo con el que se creo la actividad facturable de dias especiales.

define('CODIGOHORASNOCTURNASCAPEX', 'NOCAPEX');#defineel codigo con el que se creo la actividad facturable de horas nocturnas.
define('CODIGOHORASNOCTURNASOPEX', 'NOOPEX');#defineel codigo con el que se creo la actividad facturable de horas nocturnas.

define('VALIDARANGOHORA', TRUE);#HABILITA O DESHABILITA LA VALIDACION DEL RANGO HORA.
define('CIERRACOKIEE',TRUE);
DEFINE('IDWS_SAPInfo','73'); # Define el IDWS del webservice SAPInfo en la tabla WebServices de la base de datos.
DEFINE('IDWS_ConsOnix','74'); # Define el IDWS del webservice consultas.asmx (que hace consultas sobre onix) en la tabla WebServices de la base de datos.
IF(!DEFINED('DTH_PROVISIONING')) DEFINE('DTH_PROVISIONING',TRUE);#

DEFINE('URL_DTH','http://10.244.143.195:8011/DthConaxIntg/Core/Proxies/ejecutarIntraway_PS?wsdl');
DEFINE('authKey_DTH','3d171243d43a41605ad58e1e2fbfea25');#
DEFINE('CLIENTE_DTH','22');#

IF(!DEFINED('CTI_PROVISIONING')) DEFINE('CTI_PROVISIONING',FALSE);#
DEFINE('URL_CTI','http://200.118.0.117/IntrawayWS/server.php?wsdl');
DEFINE('authKey_CTI','dfRthglo34ERDFertShkitd');#
DEFINE('CLIENTE_CTI','1'); #

IF(!DEFINED('DAC_PROVISIONING')) DEFINE('DAC_PROVISIONING',FALSE);#
DEFINE('URL_DAC','http://200.118.0.117/IntrawayWS/server.php?wsdl');
DEFINE('authKey_DAC','dfRthglo34ERDFertShkitd');#
DEFINE('CLIENTE_DAC','1'); #

IF(!DEFINED('DAC/CTI_PROVISIONING')) DEFINE('DAC/CTI_PROVISIONING',FALSE);# 
DEFINE('URL_DAC/CTI','http://200.118.0.117/IntrawayWS/server.php?wsdl');
DEFINE('authKey_DAC/CTI','dfRthglo34ERDFertShkitd');#
DEFINE('CLIENTE_DAC/CTI','1');

DEFINE('HOMOLOGACION_TECNOLOGIAS',json_encode(array('DAC'=>array('DAC/CTI'),'CTI'=>array('DAC/CTI'),'DTH'=>array('DTH'),'DAC/CTI'=>array('DAC','CTI') )));

IF(!DEFINED('TV_PROVISIONING')) DEFINE('TV_PROVISIONING',TRUE);############### #
IF(!DEFINED('IW_SEND_CONTROLLER')) DEFINE('IW_SEND_CONTROLLER','TRUE');#
IF(!DEFINED('NDS_PROVISIONING')) DEFINE('NDS_PROVISIONING',FALSE);#

IF(!DEFINED('SELF_PROVISIONING')) DEFINE('SELF_PROVISIONING',FALSE);#

IF(!DEFINED('SELF_TIPO_SERV')) DEFINE('SELF_TIPO_SERV','<0>TL</0><1>IN</1>');#
IF(!DEFINED('TV_TIPO_SERV')) DEFINE('TV_TIPO_SERV','<0>TV</0><1>DG</1><2>DBA</2><3>enDDG</3><4>DBV</4><5>CAR</5>');#
IF(!DEFINED('KEYWS')) DEFINE('KEYWS','94dda7d96c847be15d52485cc6f4706e');#

IF(!DEFINED('NAGRA')) DEFINE('NAGRA',TRUE);############### VAriable para que tv Vaya directamente por RR
IF(!DEFINED('NAGRA_PROVISIONING')) DEFINE('NAGRA_PROVISIONING',TRUE);
IF(!DEFINED('DAC_CTI_PROVISIONING_RR')) DEFINE('DAC_CTI_PROVISIONING_RR',TRUE);
IF(!DEFINED('DEBUG_DB')) DEFINE('DEBUG_DB',FALSE); 

//array('DAC'=>'DAC/CTI','CTI'=>'DAC/CTI','DTH'=>'DTH')
DEFINE('NO_PARING',json_encode(array('DAC'=>array('DAC/CTI'),'CTI'=>array('DAC/CTI'),'DTH'=>array('DTH'),'DAC/CTI'=>array('DAC','CTI') )));
DEFINE('IPPVBaseBalance',40000);
DEFINE('IPPVPurchasesAllowed',5);
DEFINE('IPPVMaxPackCost',10000);

DEFINE('IDWS_PPV','61');//#defineel IDWS del Servicio Web wsPPVService que se encuentra en la tabla WEBSERVICES. 
DEFINE('IDWS_PPV_IW','71');//#defineel IDWS del Servicio Web Interfaz2040WS que se encuentra en la tabla WEBSERVICES. 
DEFINE('IDWS_PPV_MSM','113');//#defineel IDWS del Servicio MsmPPVService que se encuentra en la tabla WEBSERVICES. 

DEFINE('IDPANTALLA_SERIW','2037');//#defineel id_submenu de la pantalla configuracion Intraway que llena los datos sobre la tabla SERV_IWTV.
DEFINE('IDPANTALLA_INVSERV','634');//#defineel id_submenu de la pantalla  que llena los datos sobre la tabla INV_SERV.
DEFINE('IDPANTALLA_PRODTV','100001028');//#defineel id_submenu de la pantalla  que llena los datos sobre la tabla RR_PRODUCTOS_TV.
/**
*
* PROCESS_VALIDATE
*
* Constante para Habilitar o deshabilitar interceptor
* @uses Usado en:
*         <ul>
*             <li>GestionBD.new.class.php</li>
*         </ul>
* @var   : String
* @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
* @fecha : 10/03/2016
**/
define('PROCESS_VALIDATE','FALSE'); 

/**
* Se añade codigo para la utilizacion de variables bind
* en las sentencias SQL.
*
* @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
* @fecha : 21/05/2015
*
**/
define('VAR_BIND','FALSE'); // Habilita o deshabilita la utilizacion de variables bind
define('CARP_BIND',''); // Lista de carpetas en las que se aplicaran las variables bind

/**
 * Se añade nueva constante para variables bind
 * @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
 * @fecha : 13/04/2016
 */
define('EXCEPCION_CARPETAS_BIND','MGW|'); # Lista de carpetas en las que no se aplicaran las variables bind
define('EXCEPCION_ARCHIVOS_BIND','Agendamiento/AJX_CapacidadAgenda.php|Agendamiento/AJX_AgendaMods.php|Interfaces/Capacidad/AJX_CapacidadOperacion.php|Agendamiento/AJX_CapaAgenda.php|Interfaces/Moviles/AJX_Moviles.php|Formulario/Formulariover.php|'); # Lista de archivos en las que no se aplicaran las variables bind

/**
* Se añade codigo para la utilizacion de variables para correo
* en las sentencias SQL.
*
* @autor : Juan Sebastian Mendez  <juan.mendez.ext@Claro.com.co>
* @fecha : 21/05/2015
*
**/
define('SERVERFILECORREO','192.168.18.69');
define('PATHCORREO','E:\\AgendamientoExpansion'); 
define('PATHTEMPLATECORREO','/CGV2/email/proceso_nuevo/');
define('PATHTEMPLATECORREO1','/CGV2/email/img_temp/');
define('SERVERFILEARCHIVO','192.168.18.69');

/**
* Se añade codigo con el fin de unificar las constantes del modulo de gestion
*
* @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
* @fecha : 30/06/2015
*
**/

# Autentica\Autentica.class.php
define('VERIFICACION',20);//TAMAÑO DE LA CLAVE DE VERIFICACION

# Facturacion\Facturacion_Automatica\Funciones_FA.php
define('DIRECCION','<0><ID>1</ID><CODIGO>1</CODIGO><DESCRIPCION>DIRECCION NUEVA</DESCRIPCION></0><1><ID>2</ID><CODIGO>2</CODIGO><DESCRIPCION>DIRECCION ANTERIOR</DESCRIPCION></1>');
define('ASESOR','<0><ID>1</ID><CODIGO>1</CODIGO><DESCRIPCION>9999</DESCRIPCION></0><1><ID>2</ID><CODIGO>2</CODIGO><DESCRIPCION>OTROS</DESCRIPCION></1>');

# Facturacion\Facturacion_Automatica\InformeConsumoMaterialArea\SAPConfig.conf.php
define('DB','AGENDAMIENTO');

# formacion\MVCInformes\config.php
# massUserManagement\config.php
define('TIPO_CONEXION', 'AGENDAMIENTO');

# formularios\controladores\consultaFormulario.php
define('NO','NO');
define('SI','SI');
define('CERO','0');
define('UNO','1');
define('ENCUESTA','R');
define('VALIDACION','O');
//Adicion hitss 12 feb 2016 
define('FILESUCCESS','/formularios/procesar_file.php');
define('FILESACCESS','formularios/controladores/procesos/');

# formularios\tareas_programadas\DinamicFormEmail.php
define('ENVIO_ALERT_ENC_AUX', true);  //Constante  para  inhabilitar  o  habilitar  el envio de alertas  a  encuestados   auxiliares
define('ID_NOTIFICACIONPENDIENTEVALIDAR', 6);  //Guarda  la  id que  corresponde   al  tipo de email  NOTIFICACION RECORDATORIO FORMULARIO PENDIENTE POR VALIDAR

# Interfaces\Citofonia_Virtual\Funcion_cito.php
define( 'ESCAPE', '9' );

# Interfaces\Citofonia_Virtual\mysql_class.php
# Interfaces\Citofonia_Virtual\mysql_class2.php
define('USUARIOBDMYSQL','gestion');
define('CLAVEBDMYSQL','dac64046');

# Interfaces\Homolo_onix\constantes.php
DEFINE('TITULO1', 'HOMOLAGION CRITERIO CIUDADES ONYX');
DEFINE('TIPO_VARIABLE_ED', 'EDITA');

# Interfaces\Usuarios\Keys.conf.php
define('keys','<N>39878191</N><E>7411</E><D>6379691</D>');

# RedMaestra\includeMaestra\definesMaestra.php
define('DISTRITO',0);

# RedMaestra2\constantes\constantes.php
DEFINE('urlExcel', '/RedMaestra2/cargueExcel/');
DEFINE('URL_EXCEL', '/RedMaestra2/cargueExcel/');
DEFINE('SERVIDOR_FTP','192.168.18.18');
DEFINE('USUARIO_FTP','malla_agendamiento');
DEFINE('PASS_FTP','Ag3lim.Ma11');
DEFINE('RUTA_FTP_REFERENCIAS','Referencias/');
DEFINE('RUTA_FTP_ASESORES','Asesores/');
DEFINE('FTP_SERVER_RR','192.168.5.25');
DEFINE('FTP_USER_RR','STORR77502');
DEFINE('FTP_PASS_RR','claro2015');
DEFINE('FTP_ROUTE_RR_REFERENCIAS','/home/redmaestra/referencias/');
DEFINE('FTP_ROUTE_RR_ASESORES','/home/redmaestra/asesores/');
DEFINE('WS_CARGUE_RR','http://192.168.18.215:8088/RedMaestraCargueRRService-web/webresources/CargueRR/');

# Version\Version.conf.php
define('GENERICDESC','Paquete de Actualizacion de Sistema de Gestion TELMEX\n\n');
define('TEXTFILES','txt,asp,php,log,xml,css');
define('VERSIONUPLOAD',false);
define('VERSIONSERVERS','<0>192.168.15.17:8080</0>');
define('VERSIONPASS','64FgelaijASD()');

# include\ActiveDirectory.class.php
define('DN','dc=tvcable,dc=loc');
define('PUERTOLDAP',389);

# include\ConfigIF.ini.php
define('IP_FTP_SAP','172.19.140.2');
define('USER_SAP','legagendmto');
define('PASS_SAP','L05c3dR0s');
define('PATH_SFPT','/trans/CO/fija/mgestion/manoobra/in/');
define('CARPETA_SAP_MT','/trans/CO/fija/mgestion/consumos/in/Modulo/');

# include\ConfigIW.ini.php
define('LOGIW',FALSE);
define('IWACTIVE',true);
define('DEBUGIW',FALSE);

# include\DIDs.class.php
define('URL_VB','http://sandbox.voxbone.com/ws/services/VoxService?wsdl');
define('NEW_URL_VB','http://sandbox.voxbone.com/VoxAPI/services/VoxAPI?WSDL');
define('USER_VB','feliperojas');
define('PASS_VB','theislinar');

# include\multiServer.conf.php
define('MULTISERVERS',
'<COLBTAHAGE04><url>192.168.18.69</url></COLBTAHAGE04>'
.'<DESARROLLO><url>192.168.18.54:8091</url></DESARROLLO>'
.'<CAPACITACION><url>192.168.15.17</url></CAPACITACION>'
.'<ETOM><url>colbtaeln03</url></ETOM>'
.'<ETOMFILE><url>colbtaeln03/ARCHIVOS</url></ETOMFILE>'
);
define('SERVERVTS','COLBTAHAGE04');
define('SERVERJOBS','COLBTAHAGE04');
define('SERVERDOC','COLBTAHAGE04');
define('PATHVTS','/ArchiveContainer/Visitas_Tecnicas/Diseno/');
define('PATHJOBSDOC','/ArchiveContainer/Tareas/');
define('PATHVTSDOC','/ArchiveContainer/Visitas_Tecnicas/documentosvt/');
define('PATHVTSDOCCS','/ArchiveContainer/Visitas_Tecnicas/CambioEstrato/');
define('PATHVTSDOCCSMC','/ArchiveContainer/Maestra_Codigos/');
define('PATHDETALLE','/ArchiveContainer/DetalleDocumentos/uploads/');
define('PATHCARTERA','/ArchiveContainer/Cartera/uploads');
define('PATHENRCSV','/Interfaces/Enrutamiento/uploads/');
define('RUTABOM','/Interfaces/Bom/archivo'); //LISTA DE MATERIALES BOM
define('PATHJOBLOC','E:/AgendamientoExpansion');
define('PATHJOBEXC','E:/AgendamientoExpansion/AgendamientoExpansion/AdministradorTareasProgramadas/');
define('PATHJOBIMPFILE','E:/AgendamientoExpansion.80181157.8091/TareasProgramadas/Administrador/');
define('PATH','/ArchiveContainer/');
define('PATHBOLET','/ArchiveContainer/BoletinOYM/');
define('PATHCARGUE','ArchiveContainer/Cargue/');
define('PATH_ARCH_SGA','/ArchiveContainer/sga/archivos/');
define('PATH_IMAGES_NOTICIAS','/ArchiveContainer/noticias/uploads/');
define('RUTAFOTOSALIADOS','/Aliados/CargueFotos/Imagen');
define('PATH_IMAGES_PERFILES','/ArchiveContainer/aliados/fotos/');#desarrollo
define('PATH_ALIADOS_ESCALAMIENTOS','/ArchiveContainer/aliados/escalamientos/');
define('PATH_HTMLFORM','/ArchiveContainer/htmlform/uploads/');
define('PROTOCOL_SERVER','http://');
define('PATH_ARCHIVOS_CGV','/ArchiveContainer/cgv/');
define('PATHLOGISTICA','/ArchiveContainer/logistica/uploads');
define('PATH_TAREASRR','TareasProgramadas/Importar_TablasRR');//Ruta en donde se va a buscar el archivo **Johana Salcedo
define('PATH_BATTAREASRR','TareasProgramadas/Importar_TablasRR/bat');//Ruta en donde se va a buscar el archivo bat **Johana Salcedo
define('SERVERTAREASACTIVAS','TareasProgramadas/Importar_TablasRR/ejecutarTareasInterface.php'); // SERVIDOR EN LA CUAL SE ENCUENTRA LA TAREA PROGRAMADA QUE EJECUTARA LAS TAREAS PROGRAMADAS ACTIVAS**Johana Salcedo
# include\paths.Gral.php
define('WSACTIVEDIR','https://Conozcamonos.cable.net.co/WbService/2Wbintranet.php?wsdl');
define('WSSERVER','http://192.168.18.122:8080/telmex/PcmlService?wsdl');
define('WSSERVERSAP','http://192.168.18.76:8087/SAPService/ExternalNetkworkInfo?wsdl');//produccion
define('WSDATACREDITO','http://172.24.14.7:8080/dhws/services/DHService?wsdl');
define('WSONYX','http://192.168.18.76:8086/OnyxService/ordering?wsdl'); //desarrollo

# include\RRWS.class2.php
# include\RRWS.class.php
define('RRWSURL','http://serviciorr.co.attla.corp:30100/OutboundProd/services/OutboundWebServicePort/wsdl/TelmexOutbound.wsdl');

# CompatibleLinux.php
define('DEBUG_LINUX',FALSE);
define('DIR_FINAL','Linux');

# Fin de la Unificacion

/** Se adiciona codigo para requerimiento de LogQuirurgico
 * @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
 * @fecha : 17/09/2015
 */
 define('LOGQUIRURGICO',FALSE); # Activar el funcionamiento del LogQuirurgico
 /* Fin de la adicion */
 

//constantes de Actas de Inventario
define('URL_BAT','/ActaInventario/bat/');
define('PATHACTAINVENTARIO','/ArchiveContainer/DataActaInventario/');
define('SERVERTPAI','ActaInventarioTP/ejecutor.php'); // SERVIDOR EN LA CUAL SE ENCUENTRA LA TAREA PROGRAMADA QUE GENERA LOS PDF
define('PATH_ACTINV','/ArchiveContainer/FilesActInventarios/');
define('PATH_LOAD','/ActaInventario/carga.php');


  //FORMACION INFORMES
define('FILESUCCESSFR','/formacion/MVCInformes/controlador/informesAsincrono/');
define('FILESACCESSFR','formularios/controladores/procesos/');
define('PATHFORMACION','/ArchiveContainer/formacion/');
/* Fin adicion informes formacion */
 //Redmaestra
 define('PATHREDMAESTRA','/ArchiveContainer/RedMaestra/');
 define('PATHCARGUEREDMAESTRA','/RedMaestra2/UsuariosSec/clases/cargue_permisos.php');
 define('PATHCARGUECODIGO','/RedMaestra2/CodigoVendedor/clases/cargue_permisos.php');
/* Jeffry Granados */

/*
* se agrega constante USING_SESSION_BD para el Manejo de Maestro Roles Perfiles 
* Define si almacena informacion de session y cookies en BD
* @autor : Luis Figueredo <figueredol@globalhitss.com>
* @fecha : 15/04/2016
*/
DEFINE('USING_SESSION_BD','FALSE'); 

/* Jeffry Granados */
define('COMMAND_DELAY','Delay');
/* fin modificacion */

/* Constantes para carga mensajes */
define('PATHCARGAS','E:\\AgendamientoExpansion\\Interfaces\\DataCredito\\cmclientes\\'); 
define('PATHCARGASCTL','E:\\AgendamientoExpansion\\Interfaces\\DataCredito\\cmclientes\\ctl\\');  
 
/** 
 * Constante para establecer la ruta del archivo de configuracion de la DB
 * @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
 * @fecha : 21/04/2016
 */
define('URL_CONFIG_DB','C:/PHP/configDB.json'); # Ruta de configuracion para la DB

/** Se adiciona codigo para requerimiento de Configuracion
 * Modulo por servidor
 * @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
 * @fecha : 10/09/2016
 */
 define('MODULO_SERVIDOR','FALSE'); # Activar el funcionamiento del Configuracion Modulo por servidor

//CGVMOVIL
define("PATHTEMPLATEPORTALCGVMOVIL","/img/archivos/");
define("SERVERCGVMOVIL","https://myappsclaro.claro.com.co");

/*******************************************************
 *                definicion hpsa                      *
 *                                                     *
 *                                                     *
 */
defined('HPSA') OR define('HPSA',TRUE);
defined('IWPROVISIONING') OR define('IWPROVISIONING',TRUE);
defined('DEBUGHPSP') OR define('DEBUGHPSP',FALSE);

/** contante que habilita (en caso de TRUE) la logica de WTTH **/
define(  'FLAGWTTH' , false );

# Constantes para la activacion de los WS para la actualizacion de datos WFM
# @autor : Julie Sarmiento <sarmientoj@globalhitss.com>
# @fecha : 21/11/2016
define('WS_OFSC','FALSE');
define('WS_SMARTCOLLABORATION','FALSE');
?>