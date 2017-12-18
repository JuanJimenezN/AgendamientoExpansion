<?php
$Raiz = str_replace($_SERVER['PHP_SELF'],'',str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']));
$RootUrl="http://".$_SERVER['HTTP_HOST'];
include_once($Raiz."/include/ConfgGral.conf.php");
include_once(TEMPLATES."Template.class.php");
include_once(INCLUDES."Ajax.class.php");
include_once(INCLUDES."funciones.inc.php");
include_once($Raiz."/Informes/AdmonInformes/AdmonInformes.Class.php");
include_once($Raiz."/Informes/AdmonInformes/GeneraInformes.Class.php");
include_once($Raiz."/Checksession.php");
#imprimir($_POST);
$Permisos = AtenticacionUsuario();
//imprimir($Permisos);   // IMPRIMI EL USUARIO Y LOS ROLES ASOCIADOS A ESTOS INFORMES
if (!sizeof($_POST)){
ValidaRol('MEAI01');
$TemplateO = new Template(0);
$TemplateO->V_Encabezado("...:::INFORMES:::...", "".$Permisos["NOMBRE"]."");
$TemplateO->Estilo(array('http://'.$_SERVER['HTTP_HOST'].'/Template/Estilos/style.css','http://'.$_SERVER['HTTP_HOST'].'/Calendar/calendar.css'));
$TemplateO->Encabezado();
$TemplateO->Prmts_Body();
$TemplateO->Encabezado_Banner(1);
$TemplateO->Cuerpo(0);
$FechaI=$FechaF ="";
$Informes = new AdmonInformes();
?>
<form id="form1" name="form1" enctype="multipart/form-data" method="post" action="../AdmonInformes/DownloadFile.php">
<script type='text/javascript' src='<?php echo $RootUrl; ?>/Informes/AdmonInformes/Funciones.js'></script>
<script type='text/javascript' src='../../Calendar/DiasEspeciales.php'></script>
<script type='text/javascript' language='javascript' src='../../Calendar/calendar.js'></script>

<div id='Principal' name='Principal' align="center">
<table  border="0" align="center" cellpadding="4" cellspacing="4">
	<tr><br>
		<td align='center' class='Banner' colspan="4">
		<div id='Titulo' name='Titulo' align="center">
		<b>MODULO DE INFORMES</b>
		</div>
		</td>
	</tr>
	<tr>
	<td><td />
	</tr>
	<tr id='Select' name='Select'>
		<td><b>SELECCIONAR <br>INFORME: </b></td>
		<td><select name="SInforme" id="SInforme" class="MultipleW" onchange="Informe(this.value)">
			<option value="0">Seleccione un informe</option>
				<?php
				if (ValRolInterno('INN01')){
					echo '<option value="INN01">Zonificacion Nodo x Nivel</option>';
				}
				if (ValRolInterno('INRM01')){
					echo '<option value="INRM01">Informe de MT / Mo Real</option>';
				}
				if (ValRolInterno('LWM01')){
					echo '<option value="LWM01">Log Consultas Digitacion</option>';
				}
				if (ValRolInterno('MEIO04')){
					echo '<option value="MEIO04">Informe Operacion Dia a Dia</option>';
				}
                if (ValRolInterno('INDD02')) {
                    echo '<option value="INDD02">Informe Operacion Dia a Dia Franjas</option>';
                }
				if (ValRolInterno('MEIN10')){
					echo '<option value="MEIN10">Consolidado Actividades </option>';
				}
				
				if (ValRolInterno('MEIO05')){
					echo '<option value="MEIO05">Informe Operacion Soporte</option>';
				}
				
				if (ValRolInterno('ITTF01')){
					echo '<option value="ITTF01">Informe TT Facturados</option>';
				}
				
				if (ValRolInterno('INFI05')){
					echo '<option value="INFI05">Informe Financiera</option>';
				}
				
				if (ValRolInterno('INMO01')){
					echo '<option value="INMO01">Informe Mega Oferta</option>';
				}
				
				if (ValRolInterno('MEIN11')){
					echo '<option value="MEIN11">Consolidado PreActividad</option>';
				}
				
				if (ValRolInterno('DIOK01')){
					echo '<option value="DIOK01">Informe Ot<>OK</option>';
				}
				
				if (ValRolInterno('DIOK02')){
					echo '<option value="DIOK02">Informe Llamadas<>OK</option>';
				}
				
				if (ValRolInterno('DIOK03')){
					echo '<option value="DIOK03">Observaciones en OT<>OK</option>';
				}
				
				if (ValRolInterno('ININ03')){
					echo '<option value="ININ03">Informe Inventario</option>';
				}
				
				if (ValRolInterno('CICL01')){
					echo '<option value="CICL01">Informe Ciclo Cierre</option>';
				}
				
				if (ValRolInterno('MEIC04')){
					echo '<option value="MEIC04">Informe De Capacidad</option>';
				}
				
				if (ValRolInterno('VTPC01')){
					echo '<option value="VTPC01">Informe Ventas PC</option>';
				}
				
				if (ValRolInterno('INMO03')){
					echo '<option value="INMO03">Informe Moviles</option>';
				}
				
				if (ValRolInterno('MEIC08')){
					echo '<option value="MEIC08">Informe Capacidades No Agendada</option>';
				}
				if (ValRolInterno('CUM01')){
					echo '<option value="CUM01">Cargue Unidades Masivo</option>';
				}

				if (ValRolInterno('INLL01')){
					echo '<option value="INLL01">Informe Llamadas Abiertas</option>';
				}
                if (ValRolInterno('LOGFR')){
					echo '<option value="LOGFR">Log Fraude</option>';
				}                
				if (ValRolInterno('INCOPE')){
					echo '<option value="INCOPE">Informe Cierres de Operacion</option>';
				}
                if (ValRolInterno('TCK01')){
					echo '<option value="TCK01">Informe Cierres de Operacion</option>';
				}
				if (ValRolInterno('TCK02')){
					 echo '<option value="TCK02">Reincidencia de Llamadas del Cliente</option>';
				}
				if (ValRolInterno('TCK03')){
					 echo '<option value="TCK03">Tickets Asociados a Masivos</option>';
				}
				if (ValRolInterno('TCK04')){
					 echo '<option value="TCK04">Tickets Con Mto en sitio</option>';
				}
				if (ValRolInterno('TCK05')){
					 echo '<option value="TCK05">Tickets Escalados a  NOC</option>';
				}
				if (ValRolInterno('TCK06')){
					echo '<option value="TCK06">Seguimiento Tiempos PQR</option>';
				}
				if (ValRolInterno('TCK07')){
					echo '<option value="TCK07">Detalle de Notas Ticket</option>';
				}
				if (ValRolInterno('TCK08')){
					 echo '<option value="TCK08">IVR</option>';
				}
				if (ValRolInterno('TCK09')){
					 echo '<option value="TCK09">informe de consolidacion ticket - pqrs </option>';
				}
				if (ValRolInterno('TCK10')){
					echo '<option value="TCK10">Seguimiento Tiempos PQR Operacion</option>';
				}
				if (ValRolInterno('TCK11')){
					echo '<option value="TCK11">Informe de Avances PQR</option>';
				}
				if (ValRolInterno('CONDAM')){
					echo '<option value="CONDAM">Informe datacredito</option>';
				}
				/**
				*	@autor: <jlopezch@everis.com>. 
				*	Se agrega la condicion para el  nuevo reporte: Reporte Capacidades.  	
				*   @version 1.0
				*/
				if (ValRolInterno('CONCAP')){
					echo '<option value="CONCAP">Informe Capacidades Ope</option>';
				}
				/**
				*	@autor: <jlopezch@everis.com>. 
				*	Se agrega la condicion para el  nuevo reporte: Reporte Capacidades.  	
				*   @version 1.0
				*/
				if (ValRolInterno('CONRAN')){
					echo '<option value="CONRAN">Informe Rangos Ope</option>';
				}
				/**
				*	@autor: <jlopezch@everis.com>. 
				*	Se agrega la condicion para el  nuevo reporte: Reporte Aliados.  	
				*   @version 1.0
				*/
				if (ValRolInterno('CONALI')){
					echo '<option value="CONALI">Informe Aliados Ope</option>';
				}
				if (ValRolInterno('CORP01')){
					echo '<option value="CORP01">Informe Corporativo</option>';
				}
				if (ValRolInterno('CORP02')){
					echo '<option value="CORP02">Informe Status OT Corporativo</option>';
				}
				if (ValRolInterno('CORP03')){
					echo '<option value="CORP03">Informe Correcciones Interventoria Corporativo</option>';
				}
				if (ValRolInterno('CORP04')){
					echo '<option value="CORP04">Informe Soporte Operacion Corporativo</option>';
				}
				if (ValRolInterno('CORP05')){
					echo '<option value="CORP05">Informe Serializados (CPEs) Corporativo</option>';
				}
				if (ValRolInterno('CORP06')){
					echo '<option value="CORP06">Informe Cuenta a Cuenta Corporativo</option>';
				}
				if (ValRolInterno('CORP07')){
					echo '<option value="CORP07">Informe Cuenta a Cuenta No Exitosa Corporativo</option>';
				}
				if (ValRolInterno('CORP08')){
					echo '<option value="CORP08">Informe Financiero de Actividades MO Corporativo</option>';
				}
				if (ValRolInterno('CORP09')){
					echo '<option value="CORP09">Informe Reporte MO Corporativo</option>';
				}
				if (ValRolInterno('EQUIRE')){
					echo '<option value="EQUIRE">Informe DTH</option>';
				}
				if (ValRolInterno('TECVIS')){
					echo '<option value="TECVIS">Informe Tecnico - Visita</option>';
				}
				if (ValRolInterno('SAP6')){
					echo '<option value="SAP6">Informe SAP-6</option>';
				}
				if (ValRolInterno('CORP10')){
					echo '<option value="CORP10">Informe Items de Facturacion</option>';
				}
				if (ValRolInterno('CRTL')){
					echo '<option value="CRTL">Informe Items Controles</option>';
				}
				if (ValRolInterno('CORP11')){
					echo '<option value="CORP11">Informe No Serializados (CPEs) Corporativo</option>';
				}
				if (ValRolInterno('OPSACT')){
					echo '<option value="OPSACT">Consolidado Actividades OPS</option>';
				}
				if (ValRolInterno('OPSSAP')){
					echo '<option value="OPSSAP">Informe SAP-6 OPS</option>';
				}
                /**
                 *	@autor: rorozco
                 *	Se agrega la condicion para el  nuevo reporte: Modulo de Reclamacion.
                 *   @version 1.0
                 */
                if (ValRolInterno('CGVMMRR')){
                    echo '<option value="CGVMMRR">Modulo de Reclamacion</option>';
                }
				?>  
	    	</select></td>
	</tr>
		<tr name="Fechas" id="Fechas" style="display:none">
			<td width="27%" style="text-align:right!important;padding-right:3em"><b>FECHAS:</b></td>
			<td width="73%">
			<table width="250px" >
			 <tr>
				<td><input name="FechaIni" id="FechaIni"  readonly="1" class="so" type="Boxh" value="<?php echo $FechaI;?>"></td>
				<td><input name="FechaFin" id="FechaFin"  readonly="1" class="so" type="Boxh" onChange="return ValidaFechas()"  value="<?php echo $FechaF;?>">
			 </tr>
			 <tr>
			   <td align="center"><b>INICIAL</b>
					<script type="text/javascript">			
			 		 generarCalendario("FechaIni", "", "", "");
		    		</script>
		      </td>
		      <td align="center"><b>FINAL</b>
			   <script type="text/javascript">
				  generarCalendario("FechaFin", "", "", "");
			   </script>
		      </td>
			 </tr>
			</table>
			</td>
		</tr>
	<tr>
	  <td colspan="2">
		<div align="center" id="Cuerpo" name="Cuerpo"></div>
	  </td>
	</tr> 
	<tr>
	   <td></td>
	</tr> 
	
</table>
</div>
</form>
<?php
$TemplateO->Pie_Pagina();
}elseif (isset($_POST) && $_POST['Opcion'] == 'GenerarInforme' && isset($_POST['SInforme'])){
	$Genera = new GeneraInformes();
	$Informes = new AdmonInformes();
	
	#SI SE QUIERE QUE LA CONSULTA SE VEA EN PANTALLA
	
	if ($_POST['SInforme'] == "INN01"){
		ValRolInterno('INN01');
		$Informe= $Genera->NodoxNivel($_POST['FechaIni'],$_POST['FechaFin']);
		$title= array('CIUDAD','NODO');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>"
		.$_POST['FechaIni']."  ".$_POST['FechaFin']."<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
		
	}elseif ($_POST['SInforme'] == "INRM01"){
		ValRolInterno('INRM01');
		$Informe= $Genera->MaterialReal($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Area'],$_POST['Aliado']);
		$title= array('AREA','ALIADO','CUENTA','OT','MOVIL','ACTIVIDAD','MATERIAL','TIPO','CANTIDAD');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>
		   <b>ALIADO ".$_POST['Aliado']."</b>
		   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."
		<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
		
	}elseif ($_POST['SInforme'] == "LWM01"){
		ValRolInterno('LWM01');
		$Informe= $Genera->LogConsultasDigitac($_POST['FechaIni'],$_POST['FechaFin']);
		$title= array('USUARIO','FECHA','CONSULTA','TIPO_CONSULTA','RESPUESTA','ORIGEN');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>		
		   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."
		<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
		
	}elseif ($_POST['SInforme'] == "MEIO05"){
		ValRolInterno('MEIO05');
		$ciudad = (isset($_POST['Ciudad'])? $_POST['Ciudad']: '');
	    $carpeta = (isset($_POST['Carpeta'])? $_POST['Carpeta']: '');
		$Informe= $Genera->OperacionSoporte($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$ciudad,$carpeta);
		$title= array ('CUENTA','CLASEOT','OT','NODO','VENDEDOR','AGENDADO_POR','FECHA_AGENDO','MOVIL','HORA_LLEGADA','HORA_SALIDA','TIEMPO_EN_MINUTOS','REPORTA_DEMORA','RESULTADO','DESCRIPCION','USUARIO_CONFIRMA','CIUDAD','FECHA_CIERRE','ALIADO');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>			
		   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."  ".$_POST['Aliado']." ".$ciudad." ".$carpeta."
		<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
		
		}elseif ($_POST['SInforme'] == "MEIO04"){
		ValRolInterno('MEIO04');
		$ciudad = (isset($_POST['Ciudad'])? $_POST['Ciudad']: '');
	    $carpeta = (isset($_POST['Carpeta'])? $_POST['Carpeta']: '');
		$Informe= $Genera->OperacionDiaDia($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$ciudad,$carpeta);
		$title= array ('CARPETA','COMUNIDAD','ALIADOS','DIAAGENDA','CAPACIDAD','AGENDADO','VISITAS_OK','VISITAS_CON_RAZON','VISITAS_LIBRES');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>			
		   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."  ".$_POST['Aliado']." ".$ciudad." ".$carpeta."
		<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
		
		}elseif ($_POST['SInforme'] == "INFI05"){
		ValRolInterno('INFI05');
		$Informe= $Genera->Financiera($_POST['FechaIni'],$_POST['FechaFin']);
		$title= array('ACTIVIDAD','CUENTA','CODIGO','DESCRIPCION','NODO','TIPO','CANTIDAD','TOTAL_MO','TOTAL_MT','TOTAL_ACTIVIDAD');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>		
		   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."
		<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
	}elseif ($_POST['SInforme'] == "INMO01"){
		ValRolInterno('INMO01');
		$Informe= $Genera->LogMegaOfertas($_POST['FechaIni'],$_POST['FechaFin']);
		$title= array('CUENTA','TARIFA_ACT','SERVICIO_ACT','VALOR_ACT','TARIFA_NEW','SERVICIO_NEW','VALOR_NEW',
			'OFERTA','GESTION','USUARIO','FECHA','FECHAGEST');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>		
		   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."
		<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
	}elseif ($_POST['SInforme'] == "CUM01"){
		ValRolInterno('CUM01');
		$Informe= $Genera->InformeCargueUnidades($_POST['FechaIni'],$_POST['FechaFin'],$_POST['numero'],$_POST['calle'], $_POST['apartamento'],$_POST['Ciudad'],$_POST['division'],$_POST['zip'],$_POST['respuesta'], $_POST['newnumero'],$_POST['newcalle'],$_POST['newapartamento'],$_POST['newciudad'],$_POST['newdivision'],$_POST['newciudad']);
		$title= array('ID_LOG','USUARIO','NUMERO', 'CALLE', 'APARTAMENTO', 'CIUDAD','DIVISION','NEWNUMERO', 'NEWCALLE','NEWAPARTAMENTO','NEWCIUDAD','NEWDIVISION','NEWZIP','RESPUESTA','FECHA');
		echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>		
		   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."
		<br></td></tr>";
		#Imprimir($Informe);
		$Informes->MostrarTabla($title,$title,$Informe);
	}	
	elseif ($_POST['SInforme'] == "INLL01"){

			ValRolInterno('INLL01');
 			$Informe= $Genera->InformeLlamadasAbiertas($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$POST_['Carpeta'],$_POST['Aliado']);

			$title= array('LLAMADA_RR','ESTADO_RR','FECHA_RR','CUENTA','ORDEN','PROGRAMACION','FECHA_AGENDO','CODNODO','NODO','ESTRUCTURA','ESTADO','RESULTADO','DIAAGENDA', 'CARPETA','ALIADO','REGIONAL');
			echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>		
			   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."<br></td></tr>";
		$Informes->MostrarTabla($title,$title,$Informe);
		}
        elseif ($_POST['SInforme'] == "LOGFR"){

			ValRolInterno('LOGFR');
 			$Informe= $Genera->LogFraudes($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Regional'],$POST_['Carpeta'],$_POST['Aliado']); 

			$title= array('LLAMADA_RR','ESTADO_RR','FECHA_RR','CUENTA','ORDEN','PROGRAMACION','FECHA_AGENDO','CODNODO','NODO','ESTRUCTURA','ESTADO','RESULTADO','DIAAGENDA', 'CARPETA','ALIADO','REGIONAL');
			//$title= array('ALIADO','REGIONAL','CIUDAD','FECHA_AGENDO','CARPETA','CUENTA','ORDEN','CAMPO_MOD','VALOR','FECHA_MOD','USUARIO','PERFIL_USU','ALIADO_USU');
            echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>		
			   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."<br></td></tr>";
		$Informes->MostrarTabla($title,$title,$Informe);
		}
	elseif ($_POST['SInforme'] == "INCOPE"){

			ValRolInterno('INCOPE');
 			$Informe= $Genera->InformeCierresOperacion($_POST['FechaIni'],$_POST['FechaFin'],$_POST['Aliado'],$_POST['Regional'],$_POST['Ciudad'],$POST_['Carpeta']);

			$title= array('ALIADO','DIAAGENDA','REGIONAL','COMUNIDAD','CARPETA','CUENTA','ORDEN','ESTADO','FRANJA','RESULTADO','DESCRIPCION_RESULTADO','CIERRE_DE_OP','USUARIO_CIERRE_DE_OP','FECHA_CIERRE_DE_OP','CIERRE_ESP','USUARIO_CIERRE_ESP','FECHA_CIERRE_ESP');
			echo "<tr><br><td align='center' class='txt1' bgcolor='#FAFAFA'><b>".$_POST['Titulo']."<b><br>		
			   <br>".$_POST['FechaIni']."  ".$_POST['FechaFin']."<br></td></tr>";
		$Informes->MostrarTabla($title,$title,$Informe);
	}	
	echo "<br><br>";
	echo "<br>";
	echo '<a href="#" onClick="document.location.reload()"><U>VOLVER</U></a>';
	
}
?>

<script type="text/javascript">
function ValidaFechas(){
	var fecha = get('FechaIni');
	var fechaf =get('FechaFin');
	consulta_fechas(fecha,fechaf);
}

function Generar(){
	var Titulo = get('STitulo');
	var SInforme = get('SInforme');
	var ptr = 'Opcion=GenerarInforme&Titulo='+Titulo.value+'&SInforme='+SInforme.value;
	if (document.getElementById('GeneraFechas')){
		var FechaIni = get('FechaIni');
		var FechaFin =get('FechaFin');
		if(!consulta_fechas(FechaIni,FechaFin)){
			return false;
		}
		ptr += '&FechaIni='+FechaIni.value+'&FechaFin='+ FechaFin.value
	}
	if (document.getElementById('Aliado')){
		var len = document.getElementById('Aliado').length;
		var numero = 0;
		for (var j = 0; j < len; j++) 
		{
		   if(document.getElementById('Aliado').options[j].selected == true){
			   ;
			   if(numero == 0){
				  var ali = "'"+document.getElementById('Aliado').options[j].value+"'";
				  numero++;
			   }
			   else
			   {
				  ali =  "'"+document.getElementById('Aliado').options[j].value + "',"+ ali ;
			   }	 
		   } 
		}
		ptr += '&Aliado='+ali;
	}
	if (document.getElementById('Area')){
		var Area = get('Area');
		if(!ValidaCampo(Area)){
			return false;
		}
		ptr +='&Area='+Area.value;
	}
	if (document.getElementById('Carpeta')){
		var len = document.getElementById('Carpeta').length;
		var numero = 0;
		for (var j = 0; j < len; j++) 
		{
		   if(document.getElementById('Carpeta').options[j].selected == true){
			   ;
			   if(numero == 0){
				  var car = "'"+document.getElementById('Carpeta').options[j].value+"'";
				  numero++;
			   }
			   else
			   {
				  car =  "'"+document.getElementById('Carpeta').options[j].value + "',"+ car ;
			   }	 
		   } 
		}
		ptr += '&Carpeta='+car;
			
	}
	if (document.getElementById('Ciudad')){
		var len = document.getElementById('Ciudad').length;
		var numero = 0;
		for (var j = 0; j < len; j++) 
		{
		   if(document.getElementById('Ciudad').options[j].selected == true){
			   ;
			   if(numero == 0){
				  var ciu = "'"+document.getElementById('Ciudad').options[j].value+"'";
				  numero++;
			   }
			   else
			   {
				   ciu = "'"+document.getElementById('Ciudad').options[j].value + "',"+ ciu ;
			   }	 
		   } 
		}
		ptr += '&Ciudad='+ciu;
		
	}
	if (document.getElementById('newciudad')){
		var len = document.getElementById('newciudad').length;
		var numero = 0;
		for (var j = 0; j < len; j++) 
		{
		   if(document.getElementById('newciudad').options[j].selected == true){
			   ;
			   if(numero == 0){
				  var ciu = "'"+document.getElementById('newciudad').options[j].value+"'";
				  numero++;
			   }
			   else
			   {
				   ciu = "'"+document.getElementById('newciudad').options[j].value + "',"+ ciu ;
			   }	 
		   } 
		}
		ptr += '&ciudad='+ciu;
		
	}
	if (document.getElementById('Archivo')){
		var Archivo = get('Archivo');
		if (Archivo.value == 1){
			var url='InterfazInformes.php';
			clearDiv('Principal');
			FAjax(url,'Principal',ptr,'POST');
		}else{
			document.form1.submit();
		}
	}else{
		document.form1.submit();
	}
	
	return true;

}

function Informe(Rol){
	if(Rol != 0){
		var url='<?php echo $RootUrl; ?>/Informes/AdmonInformes/RolInformes.php';
		var ptr = 'Opcion=InterfazInforme&Rol='+Rol;
		clearDiv('Titulo');
		Hide('Select');
		ValidaParametros()
		clearDiv('Cuerpo');
		FAjax(url,'Cuerpo',ptr,'POST');
	}
	
}
function ValidaParametros(){
	if (!document.getElementById('GeneraFechas') && !document.getElementById('STitulo')){
		setTimeout('ValidaParametros();',500);
		return 0;
	} else {
		var Titulo = document.getElementById('STitulo');
		var GeneraFechas = document.getElementById('GeneraFechas');
		document.getElementById('Titulo').innerHTML = '<b>'+Titulo.value+'<b>';
		if (GeneraFechas.value== "Generar"){
			Show('Fechas');
		}else{
			Hide('Fechas');
		}
		return 0;
	}
}

</script>