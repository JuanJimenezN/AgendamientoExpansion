<?php
$Raiz = str_replace($_SERVER['PHP_SELF'],'',str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']));
include_once($Raiz."/Informes/AdmonInformes/GeneraInformes.Class.php");
include_once(INCLUDES."multiServer.conf.php");
include_once($Raiz."/Checksession.php");

# Incluya las siguientes librerias para generar archivos XLS
include("genera.xls.php");
$Permisos = AtenticacionUsuario();
/**
*Se crea esta funcionalidad ya que el cliente requiere descargar el informe unificado
*TODOS los que seleccione el usuario en uno solo para DetalleOPS
*/

//Se añade flujo para generacion de informe modulo de reclamacion
if(isset($_POST['SInforme']) && $_POST['SInforme'] == 'CGVMMRR'){

    $file = 'Reclamaciones'.EXT;
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$file\"\n");

    $Genera = new GeneraInformes();

    $Informe= $Genera->informeReclamacion($_POST['FechaIni'],$_POST['FechaFin']);

    $title= 'CUENTA;ORDEN DE TRABAJO;SERVICIO;VENDEDOR;TIPO RECLAMACION;MOTIVO RECLAMACION;ESTADO;FECHA INGRESO;HORA INGRESO;FECHA SOLUCION;HORA SOLUCION;USUARIO MODULO DE GESTION';
    echo $title."\r\n";
    imprimirInforme($Informe);

}elseif(isset($_POST['Opcion']) && $_POST['Opcion'] == 'Actividad_Consulta'){
	$Genera = new GeneraInformes();
		$sqlIN='';
		foreach ($_POST['dataTrabajos'] as $key => $value) {
			//print_r($value);
			$sqlIN .=$value['Estado']."','";
			}
		$sqlIN=substr($sqlIN, 0,-3)	;
		$Informe[]= $Genera->DetalladoOPS($_POST['fechai'],$_POST['fechaf'],$_POST['Area'],$_POST['Division'],$sqlIN,$_POST['Aliado'],'','   DISTINCT(CTIPOTRABAJO(FO.ID_TT)) AS TT   ');
			
		
		print returnCheck($Informe,'<b>NOMBRE ACTIVIDAD:</b>').'<div align="center" id="Response_Archivo"></div>';
		unset($sqlIN,$Informe,$_POST);
}

if(isset($_GET['Opcion']) && $_GET['Opcion'] == 'DetalleOPS_unificada'){
	
		$Name='';
		//Va retornando los valores
		$Informe='';
		set_time_limit(0);
		//header("Content-type: application/octet-stream");
		//header("Content-Disposition:  filename=\"'InformeOPS_Agrupados'".EXT."\";");
		// $_GET['Estado'] $_GET['Name'] salen de la logica ahora vienen cada una dentro de un elemento del array  $_GET['data']
		$Genera = new GeneraInformes();
		
	$tituloCabecera=array(
		0=>'DIVISION:',
		1=>$_GET['Division'],
		2=>'AREA',
		3=>$_GET['Area'],
		4=>'DESDE ',
		4=>$_GET['fechai'],
		4=>'HASTA',
		4=>$_GET['fechaf']
		);
	/*INCLUIMOS LA FUNCIONLIDAD GENERANDO EXCEL CON PHP, PERMITE MANEJAR LAS ENTRAÑAS DE EXCEL*/
	$generaExcel=new generaExcel(2);
	#digo desde que fila empezare
	$generaExcel->numeroFIla=1;
	#creamos un set de este atributo para el titulo opcional
	$generaExcel->dataTitulos=$tituloCabecera;
	#seteamos los titulos 
	$generaExcel->setTitulos();
	#titulos de las filas
	$generaExcel->numeroFIla=3;
	#envio el arreglo que contiene mis titulos
	$generaExcel->dataTitulos=array(
								0=>'AREA',
								1=>'NODO',
								2=>'OT',
								3=>'CUENTA',
								4=>'CIUDAD',5=>'DIAAGENDA',6=>'MOVIL',
								7=>'ALIADO',
								8=>'NOMBRE ALIADO',
								9=>'TIPO TRABAJO',
								10=>'ACTIVIDAD',
								11=>'NOMBRE ACTIVIDAD',
								12=>'CANTIDAD',
								13=>'FECHA',
								14=>'PROGRAMACION',
								15=>'TIPO'
								);
	#Generamos el arhivo con sus particularidades
	$generaExcel->setArchivo();
	#definir la pestaña del libro
	$generaExcel->setPestanaLibro(0,'Informe_Agrupados','A3:P3');	
	#setear los titulos
	$generaExcel->setTitulos();






	/*DEBEMOS HACER LA REASIGNACION DEL KEY 0 AL ULTIMO O NOS PODRIA RETORNAR ERROR EN ALUNAS VERSIONES PHP*/
		$_GET['dataInformes'][]=$_GET['dataInformes'][0];
		$_GET['dataInformes'][0]='';

	
	/*******************************/
		foreach ($_GET['dataTrabajos'] as $key => $value) {
			//REVISAR ESTA CONSULTA ESTE METODO NO RETORNA NADA
			//Consulta la informacion
			@$Informes= $Genera->DetalladoOPS($_GET['fechai'],$_GET['fechaf'],$_GET['Area'],$_GET['Division'],$value['Estado'],$_GET['Aliado'],'oRDER BY ACTIVIDAD ASC');
		
			//Enviamos los registro a depurar

			@$Informe=EliminaElemntoArray($Informes,$_GET['dataInformes']);
			//echo'<pre> Eliminados';print_r($Informe);
			//Escribimos contenido de las filas
			
			#dar valor a las filas

			@$generaExcel->setDataFilas($Informe);	
			


			#Escribir($Informe);
			unset($Informes,$Informe);
		}

		@$generaExcel->setPestanaLibro(0,'Informe_Agrupados_repetidos','A3:N3');	
		@$generaExcel->generaArchivo(false,'../../ArchiveContainer/cargue/informeAgrupados.xls');	
		
		print '../../ArchiveContainer/cargue/informeAgrupados.xls';

	#	echo 'Fin '.takeTimeFlag();
}
// Inicio Agregado OB Delaware 07/07/2015 Nuevo Reporte de Alertas
if(isset($_GET['Opcion']) && $_GET['Opcion'] == 'DetalleOPS_unificada_alertas'){
	
		$Name='';
		//Va retornando los valores
		$Informe='';
		set_time_limit(0);
		ini_set("memory_limit", "-1"); 
		ini_set('max_execution_time', 6000);

		$Genera = new GeneraInformes();
	
	/*DEBEMOS HACER LA REASIGNACION DEL KEY 0 AL ULTIMO O NOS PODRIA RETORNAR ERROR EN ALUNAS VERSIONES PHP*/
		$_GET['dataInformes'][]=$_GET['dataInformes'][0];
		$_GET['dataInformes'][0]='';

	
	/*******************************/
			//REVISAR ESTA CONSULTA ESTE METODO NO RETORNA NADA
			//Consulta la informacion
			@$Informes= $Genera->DetalladoOPSAlertas($_GET['fechai'],$_GET['fechaf'],$_GET['Area'],$_GET['Division'],$_GET['Aliado'],'oRDER BY ACTIVIDAD ASC');
		
			//Enviamos los registro a depurar

			@$Informe=EliminaElemntoArray($Informes,$_GET['dataInformes']);
			
			#dar valor a las filas

			@$generaExcel->setDataFilas($Informe);	
			

			#Escribir($Informe);
			unset($Informes,$Informe);

}

// Fin Agregado OB Delaware 07/07/2015

if (isset($_POST['Opcion']) && $_POST['Opcion'] == 'DetalleOPS' && isset($_POST['Area'])&& isset($_POST['Division']) 
		 && isset($_POST['fechai'])  && isset($_POST['fechaf']) && isset($_POST['Name']) && isset($_POST['Estado'])){
ValidaRol('FOPS01');
	$file = 'InformeOPS'.EXT;
	set_time_limit(0);
	header("Content-type: application/octet-stream");
	header("Content-Disposition:  filename=\"".$file."\";");

	$Genera = new GeneraInformes();
	$Informe= $Genera->DetalladoOPS($_POST['fechai'],$_POST['fechaf'],$_POST['Area'],$_POST['Division'],$_POST['Estado'],$_POST['Aliado']);
	echo "ORDENES EN ESTADO: ".$_POST['Name']."
DIVISION ".$_POST['Division']." AREA ".$_POST['Area']."
".$_POST['fechai']."  ".$_POST['fechaf']."
";

	echo 'AREA;NODO;OT;CUENTA;CIUDAD;DIAAGENDA;MOVIL;ALIADO;NOMBRE ALIADO;TIPO TRABAJO;ACTIVIDAD;NOMBRE ACTIVIDAD;CANTIDAD;FECHA
';
	Escribir($Informe);


// Fin Agregado OB Delaware 07/07/2015
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'GenerarInformeMarcaciones' && isset($_POST['FechaFin'])
		&& isset($_POST['FechaIni'])&& isset($_POST['Aliado']) ){
	#ob_start();
	#ob_end_clean();
	$file = 'Marcaciones'.EXT;
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"$file\"\n");
	#header("Content-Disposition:  filename=\"".$file."\";");
	$Genera = new GeneraInformes();
	set_time_limit(0);
	$Informe= $Genera->MarcacionOPS($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado']);		
	$title= 'IDALIADO;ALIADO NOMBRE;DIAAGENDA;AREA;MOVIL;MOVIL NOMBRE;OT;CUENTA;RAZON;OBSERVACION;USUARIO;FECHA_MARC
';
	echo $title;
	Escribir($Informe,';');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'GenerarInforme' && isset($_POST['STitulo'])){
ValidaRol($_POST['SInforme']);
	$Genera = new GeneraInformes();
	if ($_POST['SInforme']== "INRM01" ){
		
		$title= 'AREA;ALIADO;ALIADO NOMBRE;CUENTA;OT;MOVIL;MOVIL NOMBRE;ACTIVIDAD;ACTIVIDAD NOMBRE;MATERIAL;TIPO;CANTIDAD
';
		set_time_limit(0);
		$Informe= $Genera->MaterialReal($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Area'],$_POST['Aliado']);
		$file = 'MaterialesR'.EXT;
		
	}elseif ($_POST['SInforme']== "INN01" ){
		
		$title= 'CIUDAD;NODO
';
		set_time_limit(0);
		$Informe= $Genera->NodoxNivel($_POST['FechaIni'],$_POST['FechaFin']);
		$file = 'NodoxNivel'.EXT;
	
	}elseif ($_POST['SInforme']== "LWM01" ){
		
		$title= 'USUARIO;FECHA;CONSULTA;TIPO_CONSULTA;RESPUESTA;ORIGEN
';
		set_time_limit(0);
		$Informe= $Genera->LogConsultasDigitac($_POST['FechaIni'],$_POST['FechaFin']);
		$file = 'LogConsultasDigitacion'.EXT;
		
	}elseif ($_POST['SInforme']== "MEIO04" ){
		
		$title= 'CARPETA;COMUNIDAD;ALIADOS;DIAAGENDA;CAPACIDAD;VISITAS_PROGRAMADAS;VISITAS_OK;VISITAS_CON_RAZON;VISITASLIBRES_NOABIERTAS;VISITAS_LIBRES;UTILIZACION;EFECTIVIDAD;INEFECTIVIDAD
';
		set_time_limit(0);
		$Informe= $Genera->OperacionDiaDia($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'],$_POST['Carpeta']);   //nombre de la funcion
		$file = 'Informe_Dia_Dia'.EXT;      // como quiere que se  llame el archivo
		
		
	}elseif ($_POST['SInforme']== "INDD02" ){
		
		$title= 'CARPETA;COMUNIDAD;ALIADOS;DIAAGENDA;FRANJA;CAPACIDAD;VISITAS_PROGRAMADAS;VISITAS_OK;VISITAS_CON_RAZON;VISITASLIBRES_NOABIERTAS;VISITAS_LIBRES
';
		set_time_limit(0);
		$Informe= $Genera->OperacionDiaDiaFranjas($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'],$_POST['Carpeta']);   //nombre de la funcion
		$file = 'Informe_Dia_Dia_Franjas'.EXT;      // como quiere que se  llame el archivo
		
		
	}elseif ($_POST['SInforme']== "MEIN10" ){
		
		$title= 'DIAAGENDA;CCOSTOS;NODO;AREA;IDALIADO;ALIADO;ID_ACTIVIDAD;CODIGO ACTIVIDAD;DESCRIPCION_ACTIVIDAD;CANTIDAD;TOTAL_MO;CODIGO SAP;BODEGA SAP
';
		set_time_limit(0);
		$Informe= $Genera->ConsolidadoActividades($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Area'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_Actividades_All'.EXT;      // como quiere que se  llame el archivo
		
		
	}elseif ($_POST['SInforme']== "MEIO05" ){
		
		$title= 'CARPETA;CUENTA;PROGRAMACION;ORDEN_O_LLAMADA;NODO;VENDEDOR;AGENDADO_POR;FECHA_AGENDO;MOVIL;HORA_LLEGADA;HORA_SALIDA;TIEMPO_EN_MINUTOS;REPORTA_DEMORA;RESULTADO;DESCRIPCION;USUARIO_CONFIRMA;COD_CIUDAD;FECHA_CIERRE;ALIADO
';
		set_time_limit(0);
		$Informe= $Genera->OperacionSoporte($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'],$_POST['Carpeta']);  //nombre de la funcion
		$file = 'Informe_Operacion_Soporte'.EXT;      // como quiere que se  llame el archivo
	
	}elseif ($_POST['SInforme']== "INFI05" ){
		
		$title= 'ACTIVIDAD;CUENTA;CODIGO;DESCRIPCION;NODO;TIPO;CANTIDAD;TOTAL_MO;TOTAL_MT;TOTAL_ACTIVIDAD;TYPE_USER;TIPO_USUARIO
';
		set_time_limit(0);
		$Informe= $Genera->Financiera($_POST['FechaIni'],$_POST['FechaFin']);
		$file = 'Informe_Financiera'.EXT;
		
		
}elseif ($_POST['SInforme'] == "INMO01"){


		ValRolInterno('INMO01');
		$title= 'CUENTA;TARIFA_ACT;SERVICIO_ACT;VALOR_ACT;TARIFA_NEW;SERVICIO_NEW;VALOR_NEW;OFERTA;GESTION;USUARIO;FECHA;FECHAGEST
';
		set_time_limit(0);
		$Informe= $Genera->LogMegaOfertas($_POST['FechaIni'],$_POST['FechaFin']);

		$file = 'Informe_MegaOferta'.EXT;		
		
		
	}elseif ($_POST['SInforme']== "MEIN11" ){
		
		$title= 'DIAAGENDA;CCOSTOS;NODO;AREA;IDALIADO;ALIADO;ID_ACTIVIDAD;CODIGO ACTIVIDAD;DESCRIPCION_ACTIVIDAD;CANTIDAD;TOTAL_MO;CODIGO SAP;BODEGA SAP
';
		set_time_limit(0);
		$Informe= $Genera->ConsolidadoPreActividad($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Area'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_PreActividad'.EXT;      // como quiere que se  llame el archivo
	
	
	
		}elseif ($_POST['SInforme']== "DIOK01" ){
		
		$title= 'Nº|NIT ALIADO|ALIADO|BODEGA SAP|COD SAP MATERIAL|NOMBRE MATERIAL|TIPO|FABRICANTE|SERIAL|CUENTA|OT|CANTIDAD|NODO|PEP|INSTALADO|TIPO NUEVO/ANTIGUO|TIPO TRABAJO FACTURADO|DIVISION|COMUNIDAD|FECHA|NODO-SERIAL-OT-CUENTA|DIVISION-AREA-ZONA-DISTRITO-UNIDAD|RAZON_CIERRE
';
		set_time_limit(0);
		$Informe= $Genera->OtPintadasRazon($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_Ot<>OK'.EXT;      // como quiere que se  llame el archivo
	
	
       }elseif ($_POST['SInforme']== "DIOK02" ){
		
		$title= 'Nº|NIT ALIADO|ALIADO|BODEGA SAP|COD SAP MATERIAL|NOMBRE MATERIAL|TIPO|FABRICANTE|SERIAL|CUENTA|OT|CANTIDAD|NODO|PEP|INSTALADO|TIPO NUEVO/ANTIGUO|TIPO TRABAJO FACTURADO|DIVISION|COMUNIDAD|FECHA|NODO-SERIAL-OT-CUENTA|DIVISION-AREA-ZONA-DISTRITO-UNIDAD|RAZON_CIERRE
';
		set_time_limit(0);
		$Informe= $Genera->LlPintadasRazon($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_LLamadas<>OK'.EXT;      // como quiere que se  llame el archivo
	
		}elseif ($_POST['SInforme']== "DIOK03" ){
		
		$title= 'Nº|OT|DIA AGENDA|CALENDARIO|TIPO TRABAJO|COD RESULTADO|OBSERVACION|ALIADO|MOVI|CIUDAD|NODO
';
		set_time_limit(0);
		$Informe= $Genera->ObservacionesOT($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Observaciones_Ot'.EXT;      // como quiere que se  llame el archivo
		
	
	     }elseif ($_POST['SInforme']== "ININ03" ){
		
		$title= 'NOMBRE_MATERIAL;TIPO;FABRICANTE;SERIAL_MAC;DIVISION;COMUNIDAD;CUENTA;OT;MAC_ADRESS;ESTADO_ACTUAL;ESTADO_ANTERIOR;FECHA_ULTIMA_OT
';
		set_time_limit(0);
		$Informe= $Genera->InformeInventarios($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_Inventario'.EXT;      // como quiere que se  llame el archivo
	
	
	}elseif ($_POST['SInforme']== "CICL01" ){
		
		$title= 'CUENTA;SUSCRIPTOR;DIRECCION;TELEFONO1;TELEFONO2;DIVI-AREA-ZONA-DISTRI-UNID;NODO;ALIADOS;CIUDAD;ESTADO_AGENDA;FECHA_CREACION;FECHA_AGENDO;HORA_LLEGADA;HORA_SALIDA;MOVIL;ORDEN_O_LLAMADA;REPORTA_DEMORA;RESULTADO_VISITA;TIPO_AGENDA;TIPO_RED;CARPETA;USUARIO_AGENDO;USUARIO_CERRO;CODIGO_CAUSA;DESCRIPCION_CAUSA;TIPO_CLIENTE_CAT;TIPO_CLIENTE;TOTAL_SERVICIOS;VOZ;INTERNET;TV;OPERACIONES;REVISTA;ALIANZAS INGRESOS A TERCEROS;ALIANZAS INGRESO PROPIO;DATOS;OTROS QUE GENERAN INGRESO;OTROS QUE NO GENERAN INGRESO
';
		set_time_limit(0);
		$Informe= $Genera->InformeCierreCiclo($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Aliado'],$_POST['Carpeta']);   //nombre de la funcion
		$file = 'Informe_CierreCiclo'.EXT;      // como quiere que se  llame el archivo
	}
	
elseif ($_POST['SInforme']== "MEIC04" ){
		
		$title= 'FECHA;CIUDAD;ID ALIADO;NOMBRE_ALIADO;TIPO_TRABAJO;FRANJA_HORA;CAPACIDAD;AGENDADOS;CAPACIDAD_DISPONIBLE'. PHP_EOL;
		
		set_time_limit(0);
		$Informe= $Genera->InformeCapacidad($_POST['Carpeta'],$_POST['RangoFechas'],$_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_Capacidad'.EXT;      // como quiere que se  llame el archivo
	}
	
	elseif ($_POST['SInforme']== "VTPC01" ){
		
		$title= 'Nº;NIT ALIADO;BODEGA SAP;COD SAP MATERIAL;NOMBRE MATERIAL;TIPO;FABRICANTE;SERIAL;CUENTA;OT;CANTIDAD;NODO;PEP;INSTALADO;CLASE VALORACION;TIPO TRABAJO FACTURADO;DIVISION;COMUNIDAD;FECHA;RUBRO;DIVISION-AREA-ZONA-DISTRITO-UNIDAD
';
		set_time_limit(0);
		$Informe= $Genera->InformeVentasPc($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_VentasPC'.EXT;      // como quiere que se  llame el archivo
	}
	
	
	elseif ($_POST['SInforme']== "INMO03" ){
		
		/**
		* @autor John Camilo Trilleros Restrepo
		* @uses  Fecha Modificacion: 2013/04/16 Dexon: 661583, Se cambio el orden de las columnas.
		*/		
		
		$title= 'ALIADO;REGIONAL;ID_MOVIL;DESCRIPCION_MOVIL;NOMBRE_TECNICO;CEDULA_TECNICO;COD_SAP_TECNICO;CELULAR_TECNICO;TIPO VEHICULO;PLACA;ELITE;ESTADO_MÓVIL;CIUDAD;NOMBRE_USU_MOD;CEDULA_USU_MOD;TELEFONO_USUARIO;ID_RADIO;TIPO_RED
';
		set_time_limit(0);
		$Informe= $Genera->InformeMoviles($_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_Moviles'.EXT;      // como quiere que se  llame el archivo
	}
	
	
	elseif ($_POST['SInforme']== "MEIC08" ){

	$title= 'LLAMADA_RR;ESTADO_RR;FECHA_RR;CUENTA_RR;DIRECCION;CUENTA;ORDEN DE TRABAJO;PROGRAMACION;ESTADO AGENDA;FECHA_AGENDO;CODIGO NODO;NODO;ESTRUCTURA;RESULTADO;DIA AGENDA;CARPETA;ALIADOS;REGIONAL
 ';
		
		$title= 'ORDEN_TRABAJO;CUENTA;TIPOS_TRABAJO;NOMBRE_ALIADO;MOVIL;RESULTADO;FECHA_HORA_LLEGADA;FECHA_HORA_SALIDA;REGIONAL;CIUDAD;FRANJA_ ESCOGIDA;FECHA_AGENDA_ESCOGIDA;FRANJA_MAS_CERCANA;FECHA_AGENDA_MAS_CERCANA;CANTIDAD_DE_CUPOS_FRANJA_MAS_CERCANA;SUSCRIPTOR_MANIFESTO_NECECITAR_CAPACIDAD_ANTES_DE_LA_PRIMERA_FECHA_Y_FRANJA_DISPONIBLE?(SI/NO); FECHA_AGENDA_USUARIO; ESTADO_AGENDA;NODO;USUARIO_AGENDO
 ';
        if(ValRolInterno('SLF001')):
	$title= 'ORDEN_TRABAJO;CUENTA;TIPOS_TRABAJO;NOMBRE_ALIADO;MOVIL;RESULTADO;FECHA_HORA_LLEGADA;FECHA_HORA_SALIDA;REGIONAL;CIUDAD;FRANJA_ ESCOGIDA;FECHA_AGENDA_ESCOGIDA;FRANJA_MAS_CERCANA;FECHA_AGENDA_MAS_CERCANA;CANTIDAD_DE_CUPOS_FRANJA_MAS_CERCANA;SUSCRIPTOR_MANIFESTO_NECECITAR_CAPACIDAD_ANTES_DE_LA_PRIMERA_FECHA_Y_FRANJA_DISPONIBLE?(SI/NO); FECHA_AGENDA_USUARIO; ESTADO_AGENDA;NODO;USUARIO_AGENDO;SELF;SERV;APROVISIONAMIENTO
 ';
        endif;
		set_time_limit(0);
		@$Informe= $Genera->InformeCapacidades($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Ciudad'],$_POST['Aliado'],$_POST['Carpeta']);   //nombre de la funcion
		$file = 'Informe_Capacidades_No_Agendada'.EXT;      // como quiere que se  llame el archivo
	}
	
	elseif($_POST['SInforme']== "CUM01" ){
		set_time_limit(0);
		$title='ID_LOG;USUARIO;NUMERO;CALLE;APARTAMENTO;CIUDAD;DIVISION;NEWNUMERO;NEWCALLE;NEWAPARTAMENTO;NEWCIUDAD;NEWDIVISION;NEWZIP;RESPUESTA;FECHA';
		$Informe= $Genera->InformeCargueUnidades($_POST['FechaIni'],$_POST['FechaFin'],$_POST['numero'],$_POST['calle'], $_POST['apartamento'],$_POST['Ciudad'],$_POST['division'],$_POST['zip'],$_POST['respuesta'], $_POST['newnumero'],$_POST['newcalle'],$_POST['newapartamento'],$_POST['newciudad'],$_POST['newdivision'],$_POST['newciudad']);
		$file = 'Informe_Cargue_Unidades'.EXT;      // como quiere que se  llame el archivo
	}


	elseif ($_POST['SInforme']== "INLL01" ){
			
	$title= 'LLAMADA_RR;ESTADO_RR;FECHA_RR;CUENTA_RR;DIRECCION;CUENTA;ORDEN DE TRABAJO;PROGRAMACION;ESTADO AGENDA;FECHA_AGENDO;CODIGO NODO;NODO;ESTRUCTURA;RESULTADO;DIA AGENDA;CARPETA;ALIADOS;REGIONAL
 ';
 
	set_time_limit(0);
	$Informe= $Genera->InformeLlamadasAbiertas($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Aliado'],$_POST['Carpeta']);
	$file = 'Informe_Llamadas_Abiertas'.EXT;

		
	}elseif ($_POST['SInforme']== "LOGFR" ){
			
	$title= 'ALIADO;REGIONAL;CIUDAD;FECHADEAGENDA_DE_OT;TIPO_TRABAJO;CUENTA;ORDEN_DE_TRABAJO;CAMPO_MODIFICADO;VALOR;FECHA_HORA_MOD;USUARIO;NOMBRE_USUARIO;PERFIL_DE_USUARIO;ALIADO_USUARIO;ACCION
 ';
 
	set_time_limit(0);
	$Informe= $Genera->LogFraudes($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Aliado'],$_POST['Carpeta']);

	$file = 'Informe_Log_Fraudes'.EXT;

			
	}
	
	elseif($_POST['SInforme']== "INCOPE" ){
		set_time_limit(0);
		$title='ALIADO;DIAAGENDA;REGIONAL;COMUNIDAD;CARPETA;CUENTA;ORDEN;ESTADO;FRANJA;RESULTADO;DESCRIPCION_RESULTADO;CIERRE_DE_OP;USUARIO_CIERRE_DE_OP;FECHA_CIERRE_DE_OP;CIERRE_ESP;USUARIO_CIERRE_ESP;FECHA_CIERRE_ESP
';
		$Informe= $Genera->InformeCierresOperacion($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Regional'],$_POST['Ciudad'],$_POST['Carpeta']);
		$file = 'Informe_Cierres_Operacion'.EXT;      // como quiere que se  llame el archivo
	}
	elseif ($_POST['SInforme']== "TCK01" ){
	
	$title= 'Nº|NIT ALIADO|ALIADO|CUENTA|OT|NODO|FECHA_AGENDO|CIUDAD|NUM_ALIADO_Y_NOMBRE_ALIADO|REGIONAL|PROGRAMACION|AREA|CUENTA|AVISOSAP|HORA_ENTRADA|HORA_SALIDA|CIERRE_TICKET | CODIGO_SEG
';
	set_time_limit(0);
	$Informe= $Genera->LlPintadasRazonTicket($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Ciudad'],$_POST['Aliado']);   //nombre de la funcion
	$file = 'Informe_Ticket'.EXT;      // como quiere que se  llame el archivo

	}
	elseif ($_POST['SInforme']== "TCK02" ){
	
	$title= 'No. DE TICKET|CUENTA|TIPO ClIENTE|ESTADO DEL TICKET|SERVICIO AFECTADO|USUARIO QUE COLOCA NOTAS|AREA DEL USUARIO QUE COLOCÓ NOTAS|FECHA DE INSERCIÓN DE NOTAS|HORA DE INSERCIÓN DE NOTAS|TIPIFICACION DE NOTA:|MARCACIÓN EQUIVALENTE A LA TIPIFICACIÓN|NOTAS COLOCADAS ASOCIADAS A LA TIPIFICACIÓN | CODIGO_SEG
';
	set_time_limit(0);
	$Informe= $Genera->Reincidencia($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Informe_Reincidencia'.EXT;      // como quiere que se  llame el archivo

	}
        elseif ($_POST['SInforme']== "TCK03" ){
	
	$title= 'No. DE TICKET;CUENTA;TIPO CLIENTE;ESTADO DEL TICKET;SERVICIO AFECTADO;USUARIO QUE CREO EL TICKET;AREA DEL USUARIO QUE CREO EL TICKET;FECHA DE CREACIÓN DE TICKET;HORA DE CREACIÓN DE TICKET;SINTOMA;AVISO;FECHA DE CIERRE DE TICKET;HORA DE CIERRE DE TICKET;USUARIO RESPONSABLE; AREA RESPONSABLE;NOTA;PROCEDENCIA;CODIGO_SEG;tiempo_solucion;FECHA_SOLUCION;CAUSA NIVEL 1; CAUSA NIVEL 2;CAUSA NIVEL 3
            ';
	set_time_limit(0);
	$Informe= $Genera->Masivos($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Informe_Masivos'.EXT;      // como quiere que se  llame el archivo

	}
        elseif ($_POST['SInforme']== "TCK04" ){
	
	$title= 'No. de Ticket;Cuenta;Ciudad;Dirección;Telefono;NODO;Estado del Ticket;Servicio Afectado;Fecha de Creación de Ticket;Hora de Creación de Ticket;Fecha de Agenda;Aliado que atiende MTO;Hora llegada Agenda;Hora Salida Agenda;No. Movil;Reporta Demora;Area responsable;Area Usuario que cerro el ticket;Resultado Visita;Notas de cierre;Procedencia;ESTADO_LLS;CODIGO_SEG;Causa Nivel1;Causa Nivel2;Causa Nivel3
            ';
	set_time_limit(0);
	$Informe= $Genera->Mtositio($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Informe_Mtositio'.EXT;      // como quiere que se  llame el archivo

	}
        
        elseif ($_POST['SInforme']== "TCK05" ){
	
	$title= 'No. de Ticket| Cuenta| Ciudad| Dirección| Teléfono| NODO| Estado del Ticket| Servicio Afectado| Fecha de Creación de Ticket| Hora de Creación Ticket| Síntoma| Aviso Relacionado| Fecha de Escalamiento a NOC| Hora de Escalamiento a NOC| Usuario que escalo| Area que escalo| Notas de escalamiento| Area Responsable que recibe el escalamiento | CODIGO_SEG | tiempo_solucion | FECHA_SOLUCION
';
	set_time_limit(0);
	$Informe= $Genera->Noc($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Informe_NOC'.EXT;      // como quiere que se  llame el archivo

	}
         elseif ($_POST['SInforme']== "TCK06" ){
             
 $title= ' No. de Ticket;FECHA_SOLUCION;No. de PQR;Cuenta;Nombre Cliente;Segmento Cliente;TIPO PQR;Estado del PQR;Medio de Ingreso;Servicio Afectado;Area Responsable;Fecha de Creación de Ticket;Hora de Creación Ticket;Usuario que creo Ticket;Síntoma;Marcación equivalente al Sintoma;Aviso Relacionado;Fecha de Cierre de Ticket;Hora de Cierre de Ticket;Usuario que cerro Ticket;Area Usuario que cerro el ticket;Notas de cierre;Procedencia;CUN 1;CUN 2;Tiempo de Vida Incidente (horas) ;Tiempo de Vida Incidente (horas decimales) ;Tiempo de Vida Incidente (dias);CORREO ;NODO ;DIVISION ;COMUNIDAD ;CODIGO_SEG ;tiempo_solucion ;area_solucion;Causa Nivel 1;Causa Nivel 2;Causa Nivel3
';
	set_time_limit(0);
	$Informe= $Genera->PQR($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Informe_Tiempos_PQR'.EXT;      // como quiere que se  llame el archivo
}
 elseif ($_POST['SInforme']== "TCK07" ){
	
	$title= 'No. de Ticket;No. de PQR;Cuenta;Nombre Cliente;Segmento Cliente;TIPO PQR; Estado del PQR; Medio de Ingreso;Servicio Afectado;Area Responsable;Fecha de Creación de Ticket;Hora de Creación Ticket;Usuario que creo Ticket;Aviso Relacionado;Usuario que coloca Notas;Area del Usuario que colocó notas;Fecha de Inserción de Notas; Hora de Inserción de Notas; Tipificación de NOTA;Marcación Equivalente a la Tipificación ;Notas colocadas asociadas a la tipificación;CODIGO_SEG
';
	set_time_limit(0);
	$Informe= $Genera->NotasPQR($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Detalle_notas_PQR'.EXT;      // como quiere que se  llame el archivo
}
 elseif ($_POST['SInforme']== "TCK08" ){
	
     

     
	$title= 'No. De Cuenta | Nombre del Cliente |Teléfono 1 | Teléfono 2 | Tipo de Cliente | Comunidad | División | Ciudad | Cuenta Matriz | Código de Causa o Marcación  | Notas de Cierre | Fecha de Creación de Ticket | Usuario de creación | No. De Ticket| No. CUN 1| No. CUN 2| Aviso SAP | nivel1 | nivel2 | nivel3 | CODIGO_SEG
            ';
	set_time_limit(0);
        if (isset($_POST['correo'])){
        $correo=$_POST['correo'];
    }
    else{
        $correo='-1';
    }
        
	$Informe= $Genera->OtrosIvr($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$correo, $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Detalle_Ivr_PQR'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "TCK09" ){
	
	$title= 'ID AGENDA; CUENTA ;TICKET;NUM PQR;ESTADO AGENDAMIENTO;ESTADO PQR EN RR ;DESC ESTADO DE PQR EN RR;ESTADO LLS EN RR;FECHA AGENDA;CODIGO_SEG;ALIADO;CALENDARIO
            ';
	set_time_limit(0);
	$Informe= $Genera->RR_PQR($_POST['FechaIni'],$_POST['FechaFin'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'consolidacion_ticket_pqrs'.EXT;      // como quiere que se  llame el archivo
}
elseif ($_POST['SInforme']== "TCK10" ){
             
 $title= ' No. de Ticket;No. de PQR;Cuenta;Nombre Cliente;Segmento Cliente;TIPO PQR;Estado Ticket;Medio de Ingreso;Servicio Afectado;Area Responsable;Fecha de Creación de Ticket;Hora de Creación Ticket;Usuario que creo Ticket;Síntoma;Marcación equivalente al Sintoma;Aviso Relacionado;Fecha de Cierre de Ticket;Hora de Cierre de Ticket;Usuario que cerro Ticket;Area Usuario que cerro el ticket;Notas de cierre;Procedencia;CUN 1;CUN 2;Tiempo de Vida Incidente (horas);Tiempo de Vida Incidente (decimales);Tiempo de Vida Incidente (dias) ;CORREO ;NODO ;SEGMENTO;NOMBRE_NODO ;NOMBRE_CIUDAD ;NOMBRE_REGIONAL;NOMBRE_ZONA;NOMBRE_AREA ;NOMBRE_DISTRITO ;CIUDAD ;REGIONAL;ZONA ;AREA;DISTRITO ;CODIGO_SINTOMA ;HORA_AGENDA;FRANJA_CERCANA ;FECHA_CERCANA;FRANJA_ESCOGIDA;FECHA_ESCOGIDA ;ESTADO_AGENDA_V ;DIVISION;Causa Nivel 1;Causa Nivel 2;Causa Nivel3
';
	set_time_limit(0);
	$Informe= $Genera->PQR_OPE($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks'], $_POST['PQRA']);   //nombre de la funcion
	$file = 'Informe_Tiempos_PQR'.EXT;      // como quiere que se  llame el archivo
}
elseif ($_POST['SInforme']== "TCK11" ){           
 $title= ' No. de Ticket |	No. de PQR |	Cuenta |	Nombre Cliente |	Segmento Cliente |	 TIPO PQR |	Estado del PQR |	Medio de Ingreso |	Servicio Afectado |	Fecha de Contacto |	Hora de Contacto  |	Usuario que creo el contacto |	Síntoma	 | Tipificación equivalente del contacto  |	Area del Usuario que creo contacto |	Notas de contacto |	Procedencia |	Tiempo de Vida Incidente ( días) | estado_lls | CODIGO_SEG
';
	set_time_limit(0);
	$Informe= $Genera->NOTAS_PQR($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'], $_POST['pyme'], $_POST['oks']);   //nombre de la funcion
	$file = 'Informe_Tiempos_PQR'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CONDAM" ){           
 $title= ' CEDULA |	APELLIDO |	USUARIO_QUE_CONSULTA |	OPCION |	FECHA_CONSULTA |	TIPO_IDENTIFICACION |	Estado del PQR |	NOMBRES |	PRIMER_APELLIDO |	SEGUNDO_APELLIDO |	SOCRE  |	PUNTAJE |	TIPO_RESPUESTA	 | CLASIFICACION  |	CIUDAD 
';
	set_time_limit(0);
	$Informe= $Genera->datacredito($_POST['FechaIni'],$_POST['FechaFin']);   //nombre de la funcion
	$file = 'Informe_Datacredito'.EXT;      // como quiere que se  llame el archivo
}
/**
*	@autor: <jlopezch@everis.com>. 
*	Se agrega la condicion con las cabeceras que genera el  nuevo reporte: Reporte Capacidades.  	
*   @version 1.0
*/
elseif ($_POST['SInforme']== "CONCAP" ){           
	$title= '  FRANJA;CAPACIDAD;FECHA;IDUSUARIO;NOMBRE;ALIADO;CIUDAD;REGIONAL;PERIFL;TIPO_TRABAJO;IP;FECHA_INGRESO;FECHA_MODIFICACION
';
	set_time_limit(0);
	$Informe= $Genera->ConsultasCapacidades($_POST['FechaIni'],$_POST['FechaFin'], $_POST['Aliado'], $_POST['Regional'],$_POST['Ciudad']);   //nombre de la funcion
	$file = 'Informe_Capacidades_nuevo'.EXT;      // como quiere que se  llame el archivo
}
/**
*	@autor: <jlopezch@everis.com>. 
*	Se agrega la condicion con las cabeceras que genera el  nuevo reporte: Reporte Capacidades.  	
*   @version 1.0
*/
elseif ($_POST['SInforme']== "CONRAN" ){           
	$title= '  ID_RANGO_HORA;HORAS;ID_RANGO_FECHA;ID_USUARIO;NOMBRES;PERFIL;CEDULA;FECHA_INGRESO;FECHA_MODIFICACION;HORA_MODIFICACION;TIPO_TRABAJO;CIUDAD;REGIONAL;IP
';
	set_time_limit(0);
	$Informe= $Genera->ConsultasRangos($_POST['FechaIni'],$_POST['FechaFin'], $_POST['Regional'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_Rangos_nuevo'.EXT;      // como quiere que se  llame el archivo
}

/**
*	@autor: <jlopezch@everis.com>. 
*	Se agrega la condicion con las cabeceras que genera el  nuevo reporte: Reporte Aliados.  	
*   @version 1.0
*/
elseif ($_POST['SInforme']== "CONALI" ){           
 $title= 'ID_USUARIO;NOMBRES;PERFIL;CEDULA;FECHA_INGRESO;HORA_INGRESO;FECHA_MODIFICACION;HORA_MODIFICACION;CODIGO_NODOS;NODOS;ESTADO;TIPO_TRABAJO;CIUDAD;REGIONAL;ALIADO;IP
 ';
	set_time_limit(0);
	$Informe= $Genera->ConsultasAliados($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_Aliados_nuevo'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP01" ){           
  $title= 'IDAGENDA;DIAAGENDA;INCIDENTE;TIPO_TRABAJO;ALIADO;ESTADO;PROGRAMACION;CODRESULTADO;IDUSUARIO;NOMBRE_USUARIO;FECHA_AGENDO;RANGO_HORA;SUSCRIPTOR;CODCIUDAD;OBSERVACIONES_EVENTO;HORA_ENTRADA_TECNICO;HORA_SALIDA_TECNICO;NOTAS;DIRECCION;TELEFONO;DIRECION2;CLIENTE;VISITAS;CUENTA;NOMBRE_TECNICO;CANTIDAD_AGENDAMIENTOS;INCIDENTEPADRE;ASIGNACION_ONIX;NOMBRE_MOVIL;ALIADOASO;SERVICIO;
 ';
	set_time_limit(0);
	$Informe= $Genera->InformeCorporativo($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_Corporativo'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP02" ){           
  $title= 'OTP;OTH;ESTATUS;ALIADO;TECNICO;INTERVENTOR;CIUDAD;CLIENTE;ESTADO_CIERRE;INTERVENTORIA
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeStatusOT($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Status_OT'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP03" ){           
  $title= 'ID;NIT_ALIADO;ALIADO;COD_SAP;COD_ACTIVIDAD;ACTIVIDAD;FECHA_AGENDAMIENTO;OTP;OTH;CeCo;PEP;ID_SDS;NOMBRE_SDS;TIPO_CLIENTE;GRAFO;CLIENTE_ESPECIAL;NOMBRE_CLIENTE;CIUDAD;DIRECCION;SEDE;TECNICO;CC;CLASE;USUARIO;FECHA_FACTURA;CANTIDAD;VALOR_MO;VALOR_MATERIAL;TOTAL;PROGRAMACION;VISITAS;COMUNIDAD
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeCorInterventoria($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Correcciones_Interventoria'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP04" ){           
  $title= 'NIT_ALIADO;ALIADO;COD_SAP;COD_ACTIVIDAD;ACTIVIDAD;DIA_AGENDA;TIPO_TRABAJO;FECHA_AGENDAMIENTO;OTP;OTH;Ceco;ID_SDS;PEP;NOMBRE_SDS;SEDE;TIPO_CLIENTE;ID_CLIENTE;NOMBRE_CLIENTE;CIUDAD;DIRECCION;MOVIL;TECNICO;CC;USUARIO;FECHA_FACTURA;CANTIDAD;REPROGRAMADA;ESTADO_VISITA;COMUNIDAD
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeSopOperacion($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Soporte_Operacion'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP05" ){           
  $title= 'ID;NIT_ALIADO;ALIADO;CENTRO;BODEGA_SAP;COD_SAP_MATERIAL;NOMBRE_MATERIAL;TIPO;FABRICANTE;SERIAL;OTH;OTP;CANTIDAD;ID_SDS;PEP;INSTALADO;CLASE_VALORACION;TIPO_TRABAJO;DIVISION;COMUNIDAD;FECHA_INICIAL_AGENDA;SDS_SERIAL_OTP_OTH;NOMBRE_CLIENTE;FECHA_CIERRE
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeSerializadosCPEs($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Serializados_cpes'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP06" ){           
  $title= 'C;COD_ACTIVIDAD;DESCRIPCION_ACTIVIDAD;COD_TRABAJO2;DESCRIPCION_TRABAJO2;FECHA_CIERRE;NIT_ALIADO;ALIADO;MOVIL;TECNICO;CC;CUENTA;T_USER;OTH;OTP;CIUDAD;ID_SDS;PEP;CeCo;DIRECCION;CLASE;USUARIO;FACTURADO;CANTIDAD;VALOR_MO;VALOR_MATERIAL;TOTAL;ESTADOOT;COD_SAP;TARIFA;ESTRATO;COMUNIDAD;CATEGORIA_PRECIO;NOMBRE_CLIENTE;SEDE;INTERVENTOR
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeCuentaCuenta($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Cuenta_Cuenta'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP07" ){           
  $title= 'ID;CONTRATO_MARCO;NIT_ALIADO;ALIADO;COD_SAP;COD_ACTIVIDAD;ACTIVIDAD;FECHA_AGENDAMIENTO;OTP;OTH;CeCo;PEP;ID_SDS;NOMBRE_SDS;TIPO_CLIENTE;COD_PROYECTO;GRAFO;CLIENTE_ESPECIAL;NOMBRE_CLIENTE;CIUDAD;DIRECCION;SEDE;TECNICO;CC;CLASE;USUARIO;FECHA_FACTURA;CANTIDAD;VALOR_MO;VALOR_MATERIAL;TOTAL;PROGRAMACION;ESTADOOT;COMUNIDAD
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeCuentaNoExitosa($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Cuenta_Cuenta_NO_EXITOSA'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP08" ){           
  $title= 'FECHA_CIERRE;C_COSTOS;ID_SDS;ID_ALIADO;ALIADO;ID_ACTIVIDAD;COD_ACTIVIDAD;DESCRIP_ACTIVIDAD;CANTIDAD;VALOR_MO;COD_SAP;BODEGA_SAP
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeFinancieroActividadesMO($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Financiero_Actividades_MO'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CORP09" ){           
	$title = '';
	set_time_limit(0);
	$Informe= $Genera->InformeReporteMO($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_Reporte_MO'.EXT;      // como quiere que se  llame el archivo
}



elseif ($_POST['SInforme']== "EQUIRE" ){           
 $title= 'IDAGENDA;IDORDEN_DE_TRABAJO;NODO;CUENTA;CIUDAD;REGIONAL;TIPO_TRABAJO;NOMBRE_ALIADO;CODRESULTADO;PROGRAMACION;DIAAGENDA;FECHA_AGENDO;CALENDARIO;BODEGASAP;ESTADO_AGENDAMIENTO;CEDULA_TECNICO;CODIGO_DISTRIBUIDOR;AREA;CODIGO_ACTIVIDAD;DESCRIPCION_ACTIVIDAD;CANTIDAD;CODMATERIAL_SAP;UNIDAD;DESCRIPCION;DESCRIPCIONMAT;PROMEDIO;TOTAL_MT;SERIAL1;SERAIL2;TIPO;FABRICANTE
 ';
	set_time_limit(0);
	$Informe= $Genera->ConsultaEquipoRecogido($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_facturacion_DTH'.EXT;  // como quiere que se  llame el archivo
}



elseif ($_POST['SInforme']== "TECVIS" ){           
 $title= 'IDAGENDA;CUENTA;IDORDEN_DE_TRABAJO;DIAAGENDA;FECHA_AGENDO;FECHA_FRANJA;HORA_FRANJA;TIPO_TRABAJO;SUSCRIPTOR;DIRECCION;CIUDAD;NODO;TELEFONO1;TELEFONO2;TECNICO;IDENTIFICACION;COD_UNIFORME;CODRESULTADO;PROGRAMCION;CALENDARIO
 ';
	set_time_limit(0);
	$Informe= $Genera->TecnicoVisita($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_Tecnico_Visitas'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "SAP6" ){           
 $title= 'GRUPO_DE_COMPRA;FILENAME;PAIS;SOCIEDAD;OPERACION;CODTRABAJO;DESCRIPCION_TRABAJO;CODTRABAJO2;DESCRIPCION_TRABAJO2;DIAAGENDA;NIT_PROVEEDOR;NOMBRE_PROVEEDOR;IDMOVIL;CEDULA;NOMBRE_TECNICO;CUENTA;TIPO_USER;ORDEN;REGIONAL;CODCIUDAD;ID_SITIO;RED_NODO;CANTIDAD;CECO;ESTRATO
 ';
 $title='';	
	set_time_limit(0);
	$Informe= $Genera->SAP($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_SAP_6'.EXT;      // como quiere que se  llame el archivo
}
elseif ($_POST['SInforme']== "CORP10" ){           
 $title= 'CIUDAD;DISPONIBILIDAD;CONSECUTIVO;OTPADRE;OTHIJA;VISITA;CLIENTE;PRODUCTO;FECHA_MAX_ECPC;ITEM_FACTURACION;PRIORIDAD;DISPONIBLIDAD_FECHA;BLOQUE_DISPONIBLIDAD;DISPONIBILIDAD_ESTRUCTURA;ESTADO_FIN_GESTION;PNC_COD_RES;FECHA_CREACION;FECHA_MODIFICACION;DILIGENCIA_FORMULARIO;ESTADO_ITEM
 ';
	set_time_limit(0);
	$Informe= $Genera->ConsultaItemFacturacionCor($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_Item_facturacion'.EXT;      // como quiere que se  llame el archivo
}

elseif ($_POST['SInforme']== "CRTL" ){           
 $title= 'WHACCT;SUDCDE;SUCCDE;PHWOÑ;FECHACREOT;FECHAFINOT;PHITMC;PHMANC;PHIDCD;PHITMD;PHSERÑ;SUTYPE;SURSCP;CAM1;TARIFA;WHNODE;WHSTAT;WHDLRC;PHTOT$;PHGVRS;PHGVBY;WHCRTU;PHSTAT;WHTYPE;CANTIDAD;IDALIADO;NOMBRE_ALIADO;IDALIADO_CAV_VISOR;CAV_VISOR;
 ';
	set_time_limit(0);
	$Informe= $Genera->ConsultaControles($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_Controles'.EXT;      // como quiere que se  llame el archivo
}
elseif ($_POST['SInforme']== "CORP11" ){           
  $title= 'ID;NIT_ALIADO;ALIADO;CENTRO;BODEGA_SAP;COD_SAP_MATERIAL;NOMBRE_MATERIAL;TIPO;FABRICANTE;SERIAL;OTH;OTP;CANTIDAD;ID_SDS;PEP;INSTALADO;CLASE_VALORACION;TIPO_TRABAJO;DIVISION;COMUNIDAD;FECHA_INICIAL_AGENDA;SDS_OTP_OTH;NOMBRE_CLIENTE;FECHA_CIERRE
  ';
	set_time_limit(0);
	$Informe= $Genera->InformeNoSerializadosCPEs($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad']/*, $_POST['Carpeta']*/);   //nombre de la funcion
	$file = 'Informe_No_Serializados_cpes'.EXT;      // como quiere que se  llame el archivo
}
elseif ($_POST['SInforme']== "OPSSAP" ){           
    $title='';
	set_time_limit(0);
	$Informe= $Genera->SAP_OPS($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Ciudad'], $_POST['Carpeta']);   //nombre de la funcion
	$file = 'Informe_SAP_6_OPS'.EXT; 
}
elseif ($_POST['SInforme']== "OPSACT" ){
		$title= 'IDAGENDA;DIAAGENDA;CCOSTOS;NODO;AREA;IDALIADO;ALIADO;ORDEN;ID_ACTIVIDAD;CODIGO ACTIVIDAD;DESCRIPCION_ACTIVIDAD;CANTIDAD;TOTAL_MO;CODIGO SAP;BODEGA SAP;MARCACION
';
		set_time_limit(0);
		$Informe= $Genera->ConsolidadoActividadesOPS($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Area'],$_POST['Aliado']);   //nombre de la funcion
		$file = 'Informe_Actividades_All_OPS'.EXT;      // como quiere que se  llame el archivo	
}

        //	elseif ($_POST['SInforme']== "TCK01" ){
//	
//	$title= 'No. DE TICKET|CUENTA|TIPO ClIENTE|ESTADO DEL TICKET|SERVICIO AFECTADO|USUARIO QUE COLOCA NOTAS|AREA DEL USUARIO QUE COLOCÓ NOTAS|FECHA DE INSERCIÓN DE NOTAS|MARCACIÓN EQUIVALENTE A LA TIPIFICACIÓN
//';
//	set_time_limit(0);
//	$Informe= $Genera->Asoc_Mas($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado']);   //nombre de la funcion
//	$file = 'Asociados_Masivos'.EXT;      // como quiere que se  llame el archivo
//
//	}
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"$file\"\n");
			

	echo $title;
	Escribir($Informe,';');
	
}
/**
Creo la siguiente funcion para que nos retorne un select con informacion que requiera construyendlo a partir delos $datos recibidos por parametro
*/
function returnCheck($datos,$titulo='',$filas=3){
	
	$html='<table>
		<tr>
			<th>'.$titulo.'</th>
			<th colspan="'.($filas*2).'"><Input data-accion="activa" type="button" onclick="habilitar_checl()" value="Seleccionar Todos" name="all_check" id="all_check"></th>
		</tr>';
	$page=0;
	$html.='<tr>';
	foreach ($datos as  $value) {

		foreach ($value as $key => $val) {

			$page++;
			//$valor=explode(';',$val['ACTIVIDAD']);
			$html.='<td>'.$val['TT'].':</td><td><Input type="checkbox" value="'.$val['TT'].'" name="'.$val['TT'].'"></td>';
			if($page==$filas){
				$html.="</tr>";
				$page=0;
			}
		}
	}
		if($page!=0){
			$html.='</tr>';
		}

	$html.= '<tr>
				<td colspan="'.($filas*1).'"><div align="center" id="button"><br><input type="button" value="Genere Informe" onclick="generaInformes()"><br></div></td>';
	if( ValRolInterno('USP023') ){
	$html.= '	<td colspan="'.($filas*2).'"><div align="center" id="button"><br><input type="button" value="Genere Informe Alertas" onclick="generaInformesAlertas()"><br></div></td>';
	}	
	$html.= '</tr>';
	
	$html.='</table>';
	$html.='<br>';
	
	unset($datos,$titulo,$valor);
	
	return $html;
}

function EliminaElemntoArray($Agujas,$pajar){
	//REccorre el arreglo en busca de los valores no aceptados por el usuario
	$retorno=array();
	$comodin='';
	$cantidad=0;
	foreach ($Agujas as $Indice => $Valor){
		//Divide para buscar
		//$valActi=explode(';',$Valor['ACTIVIDAD']);
		//Buscar el valor en el Pajar, si NO esta
		//Eliminara todo el KEY del array
		//echo '<br>'.$valActi[0].'<br>';

		if(array_search(trim($Valor['TT']), $pajar)){
			
			$retorno[]=$Agujas[$Indice];
			
		}
	}
	//Retornamos el arreglo "Depurado"
	return $retorno;
}



function Escribir($Informe,$separador = ';'){
	foreach ($Informe as $Indice => $Valor){
			if(is_array($Valor) && sizeof($Valor)){
				echo str_replace('
','', implode($separador,$Valor));
				
			}else {
					echo str_replace('
','',$Valor);
			} 
		  echo "
";
	}
}

/**
 * DownloadFile::imprimirInforme()
 * Metodo que escribe contenido informe reclamacion
 * @access public
 * @version 1.0
 * @author rorozco
 **/
function imprimirInforme($informe){

    $fila = "";
    if(empty($informe)){
        echo "No existen datos!";
    }else{

        foreach ($informe as $Indice => $Valor){

            if(is_array($Valor) && sizeof($Valor)){

                $fila = implode(';', array_values($Valor));

                echo $fila. "\r\n";

            }

        }
    }

}

