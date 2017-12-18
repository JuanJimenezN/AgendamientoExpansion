<?php
$Raiz = str_replace($_SERVER['PHP_SELF'],'',str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']));
include_once($Raiz."/include/ConfgGral.conf.php");
include_once($Raiz."/Checksession.php");
include_once(INCLUDES."Ajax.class.php");
include_once(INCLUDES."funciones.inc.php");
include_once($Raiz."/Informes/AdmonInformes/AdmonInformes.Class.php");
$Permisos = AtenticacionUsuario();
echo "<script type='text/javascript' src='../../Informes/AdmonInformes/Funciones.js'></script>";
echo "<script type='text/javascript' language='javascript' src='../../Calendar/calendar.js'></script>";
echo "<script type='text/javascript' src='../../Calendar/DiasEspeciales.php'></script>";


if (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'INN01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME ZONIFICACION NODO x NIVEL";
	Generar($Titulo,'Fecha');	
	Fin('Archivo','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'INRM01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME DE MATERIALES REAL";
	Generar($Titulo,'Fecha');	
	Area();
	Aliado();
	Fin('Archivo','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'LWM01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME LOGS CONSULTAS DIGITACION";
	Generar($Titulo,'Fecha');	
	Fin('Archivo','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'MEIO04'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME OPERACION DIA A DIA <br><br><b>(Para Seleccionar Mas De Una Opcion Mantener La Tecla Ctrl Presionada.)</b>";
	Generar($Titulo,'Fecha');
	Ciudad(5);
	Aliado(5);
	Carpeta(5);
	Fin('Archivo','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' &&
isset($_POST['Rol']) && $_POST['Rol'] == 'INDD02') {
    ValidaRol($_POST['Rol']);
    $Titulo = "INFORME OPERACION DIA A DIA FRANJAS<br><br><b>(Para Seleccionar Mas De Una Opcion Mantener La Tecla Ctrl Presionada.)</b>";
    Generar($Titulo, 'Fecha');
    Ciudad(5);
    Aliado(5);
    Carpeta(5);
    Fin('', 'Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'MEIN10'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME ACTIVIDADES";
	Generar($Titulo,'Fecha');
	Area();
	Aliado();
	Fin('','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'MEIO05'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME OPERACION SOPORTE <br><br><b>(Para Seleccionar Mas De Una Opcion Mantener La Tecla Ctrl Presionada.)</b>";
	Generar($Titulo,'Fecha');
	Ciudad(5);
	Aliado(5);
	Carpeta(5);
	Fin('Archivo','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'INFI05'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME FINANCIERA";
	Generar($Titulo,'Fecha');	
	Fin('Archivo','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'INMO01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME MEGAOFERTA";
    Generar($Titulo,'Fecha');	
	Fin('Archivo','Volver');	
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'MEIN11'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME PREACTIVIDADES";
	Generar($Titulo,'Fecha');
	Area();
	Aliado();
	Fin('','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'DIOK01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME OT <> OK";
	Generar($Titulo,'Fecha');
	Regional();
	Ciudad();
	Aliado();
	Fin('Archivo','Volver');


}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'DIOK02'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME LLAMADAS <> OK";
	Generar($Titulo,'Fecha');
	Regional();
	Ciudad();
	Aliado();
	Fin('Archivo','Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'DIOK03'){
	ValidaRol($_POST['Rol']);
	$Titulo= "OBSERVACIONES EN OT <> OK";
	Generar($Titulo,'Fecha');
	Regional();
	Ciudad();
	Aliado();
	Fin('Archivo','Volver');
		
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'ININ03'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME INVENTARIO <br><b>(Filtrar 10 Dias Maximo)</b>";
	Generar($Titulo,'Fecha');
	Regional();
	Ciudad('',0 );
	Aliado('',0 );
	Fin('Archivo','Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CICL01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME CIERRE CICLO <br><b>(Filtrar 10 Dias Maximo)</b>";
	Generar($Titulo,'Fecha');
	Regional();
	Aliado();
	Carpeta();
	Fin('Archivo','Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'MEIC04'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe de Capacidad";
	Generar($Titulo);
	Ciudad('',0,'ConsultaRangoFechas()');
	Aliado('','',0);
	Carpeta('',0,'ConsultaRangoFechas()');
	RangoFechas();
	Fin('','Volver');
	
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'VTPC01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Ventas PC";
	Generar($Titulo,'Fecha');
	Regional('',"");
	Ciudad('');
	Aliado('',"");
	Fin('','Volver');
	
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'INMO03'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Moviles";
	Generar($Titulo);
	Ciudad('');
	Aliado('',"");
	Fin('','Volver');
	
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'MEIC08'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Capacidades No Agendada";
	Generar($Titulo,'Fecha');
	Ciudad(5);
	Aliado(5);
	Carpeta(5);
	Fin('','Volver');
	
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CUM01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Cargue Unidades Masivo";
	Generar($Titulo,'Fecha');
	Ciudad();
	newCiudad();
	consultaUnidad();
	//Estado(true);
	Fin('','Volver');
}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'INLL01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME LLAMADAS ABIERTAS<br>Maximo 5 Dias<br><b>(Para Seleccionar Mas De Una Opcion Mantener La Tecla Ctrl Presionada.)</b>";
    Generar($Titulo,'Fecha');
    newRegional(5);
	Carpeta(5);
	Aliado(5);	
	Fin('','Volver');
	
}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'LOGFR'){
	ValidaRol($_POST['Rol']);
	$Titulo= "LOG FRAUDES<br><b>(Para Seleccionar Mas De Una Opcion Mantener La Tecla Ctrl Presionada.)</b>";
    Generar($Titulo,'Fecha');
    newRegional(5);
	Carpeta(5);
	Aliado(5);	
	Fin('','Volver');
	
}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'INCOPE'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME CIERRES DE OPERACION<br>Maximo 5 Dias<br><b>(Para Seleccionar Mas De Una Opcion Mantener La Tecla Ctrl Presionada.)</b>";
    Generar($Titulo,'Fecha');
	Aliado(5);
    newRegional(5);
	Ciudad(5);
	Carpeta(5);	
	Fin('','Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME LLAMADAS TICKETS";
	Generar($Titulo,'Fecha');
	Regional();
	Ciudad();
	Aliado();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK02'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Reincidencia de Llamadas del Cliente";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroOK();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK03'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Tickets Asociados a Masivos ";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroOK();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK04'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Tickets Con Mto en sitio ";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroOK();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK05'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Tickets Escalados a  NOC";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroOK();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK06'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Seguimiento Tiempos PQR";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroOK();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK07'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Detalle de Notas Ticket";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroOK();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK08'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Otros Casos IVR";
	Generar($Titulo,'Fecha');
	Aliado();
        Correos();
		filtroPyme();
		filtroOK();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK09'){
	ValidaRol($_POST['Rol']);
	$Titulo= "informe de consolidacion ticket - pqrs";
	Generar($Titulo,'Fecha');
		filtroPyme();
		filtroOK();
	Fin('Archivo','Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK10'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Seguimiento Tiempos PQR Operacion";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroPQR();
	filtroOK();
	Fin('Archivo','Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TCK11'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe de Avances PQR";
	Generar($Titulo,'Fecha');
	Aliado();
	filtroPyme();
	filtroOK();
	Fin('Archivo','Volver');

}elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CONDAM'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe datacredito";
	Generar($Titulo,'Fecha');
	Fin('Archivo','Volver');

}			
/**
*	@autor: <jlopezch@everis.com> 
*	Se agrega la condicion  para el nuevo reporte: Reporte Capacidades.  	
*   @version 1.0
*/
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CONCAP'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Capacidades";
	Generar($Titulo,'Fecha');
	Aliado();
	Regional();
	Ciudad();
	Fin('Archivo','Volver');

}
/**
*	@autor: <jlopezch@everis.com> 
*	Se agrega la condicion  para el nuevo reporte: Reporte Rangos.  	
*   @version 1.0
*/
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CONRAN'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Rangos OPE";
	Generar($Titulo,'Fecha');
	Carpeta();
	Regional();
	Ciudad();
	Fin('Archivo','Volver');

}
/**
*	@autor: <jlopezch@everis.com>. 
*	Se agrega la condicion  para el nuevo reporte: Reporte Capacidades.  	
*   @version 1.0
*/
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CONALI'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Aliados";
	Generar($Titulo,'Fecha');
	Carpeta();
	Regional();
	Ciudad();
	Fin('Archivo','Volver');

}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP01'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Corporativo";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Carpeta();
	Fin('Archivo','Volver');

}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'EQUIRE'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe facturacion DTH";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Carpeta();
	Fin('Archivo','Volver');

}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP02'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Status OT";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP03'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Correcciones Interventoria";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}


elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP04'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Soporte Operacion";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP05'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Serializados (CPEs)";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP06'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Cuenta a Cuenta";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP07'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Cuenta a Cuenta No Exitosa";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP08'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Financiero de Actividades MO";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP09'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Reporte MO";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}



elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'TECVIS'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Tecnico Visita";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Carpeta();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'SAP6'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe SAP-6";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Carpeta();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP10'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Items Facturacion";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Carpeta();
	Fin('Archivo','Volver');

}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CRTL'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe Controles";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Carpeta();
	Fin('Archivo','Volver');

}

elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CORP11'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe No Serializados (CPEs)";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Fin('Archivo','Volver');
}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'OPSSAP'){
	ValidaRol($_POST['Rol']);
	$Titulo= "Informe SAP-6 OPS";
	Generar($Titulo,'Fecha');
	Aliado();
	Ciudad();
	Carpeta();
	Fin('Archivo','Volver');

}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'OPSACT'){
	ValidaRol($_POST['Rol']);
	$Titulo= "INFORME ACTIVIDADES OPS";
	Generar($Titulo,'Fecha');
	Area();
	Aliado();
	Fin('','Volver');
	
}
elseif (isset($_POST['Opcion']) && $_POST['Opcion'] == 'InterfazInforme' && isset($_POST['Rol']) && $_POST['Rol'] == 'CGVMMRR'){
    ValidaRol($_POST['Rol']);
    $Titulo= "INFORME MODULO DE RECLAMACION";
    Generar($Titulo,'Fecha');

    Fin('','Volver');

}


function Generar($Titulo,$Fecha = ""){
	/*Genera el titulo, una tabla de cuerpo y el campo fecha de ser necesario*/
	global $Permisos;
	?>
	
<table style="border: none;padding-left: 1em;padding-right: 1em" width="100%" >
	
	<input type="hidden" name="STitulo" id="STitulo" value="<?php echo $Titulo; ?>">
	<input type="hidden" name="idusuario" id="idusuario" value="<?php echo $Permisos['IDUSUARIO']; ?>">
	<?php
	if ($Fecha != "" ){
		echo '<input type="hidden" name="GeneraFechas" id="GeneraFechas" value="Generar">';
	}	
}
function Fin($ATipo="",$Volver =""){
	if ($ATipo !=""){
		/*Genera tipo de reporte, boton volver y final de los parametros*/
		echo '<tr><td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>TIPO DE <br>ARCHIVO: </b></td>';
		echo '<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
			  <select name="Archivo" id="Archivo" class="MultipleW" style="width: 250">';
		//echo '<option value="1">.text/html</option>';
		echo '<option value="2" selected="selected">.file</option>';
		echo '</select></td></tr>';
	}
		echo '<tr><td></tr></td>';
		echo '<tr><td colspan="2" align="center"><input type="button" class="botonesformas" onclick="Generar()"; value="Generar Informe"></td> </tr>';
	if ($Volver !=""){
		echo '<tr><td colspan="2" align="center"><a href="#" onClick="document.location.reload()">VOLVER</a></td> </tr>';
	}
	echo '<input type="hidden" name="Opcion" id="Opcion" value="GenerarInforme">';
	echo '</table>';
}

function Aliado($size = '',$Permiso = '',$todos= 1){
    $Informes = new AdmonInformes();
	$size = ($size != '' ? 'size=\""'.$size.'\""' : $size);
	$Multiple = ($size != '' ? ' Multiple ' : '');
	//$filtro = (implode("ALIADOS","Aliado"));    //PARA RESTRINGUIR POR USUARIO ALIADO
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em">
			<b>SELECCIONAR <br> ALIADO: </b>
		</td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
			<select <?php echo $Multiple;?> class="MultipleW" name="Aliado[]" id="Aliado"
			style='width: 250;' <?php echo $size;?>>
			<?php
			    if ($todos == 1 ){
					?> <option value='TODOS' selected="selected">TODOS LOS ALIADOS</option><?php
		    	}
				$Aliado = $Informes->ConsultaAliados('SELECT');
				imprimir($Aliado);
			    /*if ($Permiso == ''? '',$filtro));
				$Aliado = $Informes->ConsultaAliados('SELECT');   //PARA RESTRINGUIR POR USUARIO ALIADO
				imprimir($Aliado);*/
			?>  
    		</select>
   		</td>
</tr>
<?php
}

function Area(){
	$Informes = new AdmonInformes();
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>
		AREA: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em"><select name="Area" id="Area" class="MultipleW" style='width: 250'>
			<option value='TODOS'>TODAS LAS AREAS</option>
			<?php
				$Area = $Informes->ConsultaAreas('SELECT');
				imprimir($Area);
			?>  
    	</select></td>
	</tr>
<?php
}

function Correos(){
	$Informes = new AdmonInformes();
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>
		Correo: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em"> 
                    <input type="radio" name="correo" id="inpsin" value="1">Sin envio de Correo<br>
<input type="radio" name="correo" id="inpcon" value="0">Con envio de Correo<br></td>
	</tr>
<?php
}

function Ciudad($size = '', $todos= 1,$onchange=''){
	$Informes = new AdmonInformes();
	$size = ($size != '' ? 'size=\""'.$size.'\""' : $size);
	$Multiple = ($size != '' ? ' Multiple ' : '');
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>
		CIUDADES: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
		<div id='divCiudades'>
		<select <?php echo $Multiple;?> class="MultipleW" name="Ciudad[]" id="Ciudad" style='width: 250;' <?php echo $size;?> <?php echo (!empty($onchange)?'onChange="'.$onchange.'"':'');?>>
<?php
		if ($todos == 1 ){
			?> <option value='TODOS' selected="selected">TODAS LAS CIUDADES</option><?php
		}
		else 
		{
			?> <option value='' selected="selected">SELECCIONE LA CIUDAD</option><?php
		}
				$Ciudad = $Informes->ConsultaCiudad('SELECT');
				imprimir($Ciudad);
			?>  
    	</select></div>
		</td>
	</tr>
<?php
}
function newCiudad($size = '', $todos= 1,$onchange=''){
	$Informes = new AdmonInformes();
	$size = ($size != '' ? 'size=\""'.$size.'\""' : $size);
	$Multiple = ($size != '' ? ' Multiple ' : '');
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>
		NEW CIUDAD: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
		<div id='divCiudades'>
		<select <?php echo $Multiple;?> class="MultipleW" name="newciudad[]" id="newciudad" style='width: 250;' <?php echo $size;?> <?php echo (!empty($onchange)?'onChange="'.$onchange.'"':'');?>>
<?php
		if ($todos == 1 ){
			?> <option value='TODOS' selected="selected">TODAS LAS CIUDADES</option><?php
		}
		else 
		{
			?> <option value='' selected="selected">SELECCIONE LA CIUDAD</option><?php
		}
				$Ciudad = $Informes->ConsultaCiudad('SELECT');
				imprimir($Ciudad);
			?>  
    	</select></div>
		</td>
	</tr>
<?php
}

function Regional($size = '',$accion = '',$todos =1, $Restringuir = 'N'){
	$Informes = new AdmonInformes();
	$size = ($size != '' ? 'size=\""'.$size.'\""' : $size);
	$Multiple = ($size != '' ? ' Multiple ' : '');
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>
		REGIONALES: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
		<div id='divRegionales'><select <?php echo $Multiple;?> class="MultipleW"
			name="Regional" id="Regional" style='width: 250;'
			<?php echo $size; echo $accion;?> onchange="ConsultaCiudades()">
<?php
			if ($todos == 1 || ($Restringuir != 'N' && ValRolInterno ('REIN03'))){
				?> <option value='TODOS' selected="selected">TODAS LAS REGIONALES</option><?php
		    }
				$Regional = $Informes->ConsultaRegional('SELECT','',$Restringuir);
				imprimir($Regional);
			?>  
    	</select></div>
		</td>
	</tr>
<?php
}

function newRegional($size = '',$accion = '',$todos =1, $Restringuir = 'N'){
	$Informes = new AdmonInformes();
	$size = ($size != '' ? 'size=\""'.$size.'\""' : $size);
	$Multiple = ($size != '' ? ' Multiple ' : '');
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>
		REGIONALES: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
		<div id='divRegionales'><select <?php echo $Multiple;?> class="MultipleW"
			name="Regional[]" id="Regional" style='width: 250;'
			<?php echo $size; echo $accion;?> >
<?php
			if ($todos == 1 || ($Restringuir != 'N' && ValRolInterno ('REIN03'))){
				?> <option value='TODOS' selected="selected">TODAS LAS REGIONALES</option><?php
		    }
				$Regional = $Informes->ConsultaRegional('SELECT','',$Restringuir);
				imprimir($Regional);
			?>  
    	</select></div>
		</td>
	</tr>
<?php
}


function Carpeta($size = '',$todos =1,$onchange=''){
	$Informes = new AdmonInformes();
	$size = ($size != '' ? 'size=\""'.$size.'\""' : $size);
	$Multiple = ($size != '' ? ' Multiple ' : '');
?> 
<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>
		CARPETAS: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
		<div id='divCarpeta'><select <?php echo $Multiple;?> name="Carpeta[]" class="MultipleW" id="Carpeta" style='width: 250;' <?php echo $size;?> <?php echo (!empty($onchange)?'onChange="'.$onchange.'"':'');?>>
			<?php if ($todos == 1 )
			{
			?><option value='TODOS' selected="selected">TODAS LAS CARPETAS</option><?php
			}
			else
			{
				?><option value=''>Seleccione la carpeta</option><?php
			}
				$Carpeta = $Informes->ConsultaCarpeta('SELECT');
				imprimir($Carpeta);
			?>  
    	</select></div>
	
	</tr>
<?php
}

function RangoFechas($size = ''){
	$Informes = new AdmonInformes();
	$size = ($size != '' ? 'size=\""'.$size.'\""' : $size);
?> 
<tr>
		<td align="right" width="30%" style=" padding-left: 1em;padding-right: 1em"><b>SELECCIONAR <br>RANGO: </b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em">
		<div id='divRangoFechas'>
		<select name="RangoFechas" class="MultipleW" id="RangoFechas" style='width: 250;' <?php echo $size;?>>
			<option value="" selected="selected">Seleccione un rango</option>
			<?php
				$RangoFechas = $Informes->ConsultaRangoFechas('SELECT');
				imprimir($RangoFechas);
			?>  
    	</select></div>
	
	</tr>
<?php
}

function estado($valor){
?>
	<tr>
	<td>Estado</td>
		<td>
			<select name="estado">
				<option value = "0">Todos</option>
				<option value = "P">Pendiente</option>
				<option value = "Pr">Proceso</option>
				<option value = "T">Terminado</option>
				<option value = "C">Cancelado</option>
			</select>
		</td>
	</tr>
<?php
}

function consultaUnidad(){
?>
	<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>NUMERO</b></td>
		<td align="left" width="35%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "numero" size="15"></td>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>NEW NUMERO</b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "newnumero" size="15"></td>
	</tr>
	<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>CALLE</b></td>
		<td align="left" width="35%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "calle" size="15"></td>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>NEW CALLE</b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "newcalle" size="15"></td>
	</tr>
	<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>APARTAMENTO</b></td>
		<td align="left" width="35%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "apartamento" size="15"></td>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>NEW APARTAMENTO</b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "newapartamento" size="15"></td>
	</tr>
	<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>DIVISION</b></td>
		<td align="left" width="35%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "division" size="15"></td>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em"><b>NEW DIVISION</b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em"><input type="text" name = "newdivision" size="15"></td>
	</tr>
	<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em" ><b>ZIP</b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em" ><input type="text" name = "zip" size="15"></td>
	</tr>
	<tr>
		<td align="right" width="30%" style="padding-left: 1em;padding-right: 1em" ><b>RESPUESTA</b></td>
		<td align="left" width="70%" style="padding-left: 1em;padding-right: 1em" >
			<select name="respuesta">
				<option value = "0">OK</option>
				<option value = "e">Error</option>

			</select>
		</td>
	</tr>
	
<?php
}
function filtroPyme(){
?>
	<tr>
		<td align="right" width="30%">
			<b>SELECCIONAR <br> FILTRO PYME: </b>
		</td>
		<td width="70%" style="padding-left: 1em;padding-right: 1em" >
			<select name="pyme">
				<option value = "0">Todos</option>
				<option value = "1">PYME</option>
				<option value = "2">RESIDENCIAL</option>
			</select>
		</td>
</tr>
<?php
}

function filtroOK(){
?>
	<tr>
		<td align="right" width="30%">
			<b>SELECCIONAR <br> ABIERTOS O CERRADOS: </b>
		</td>
		<td width="70%" style="padding-left: 1em;padding-right: 1em" >
			<select name="oks">
				<option value = "0">TODOS</option>
				<option value = "1">ABIERTOS</option>
				<option value = "2">CERRADOS</option>
			</select>
		</td>
</tr>
<?php
}
?>
<?php
function filtroPQR(){
?>
	<tr>
		<td align="right" width="30%">
			<b>PQR <br> ASOCIADA: </b>
		</td>
		<td width="70%" style="padding-left: 1em;padding-right: 1em" >
			<select name="PQRA">
				<option value = "SI">SI</option>
				<option value = "NO">TODOS</option>
			</select>
		</td>
</tr>
<?php
}
?>

