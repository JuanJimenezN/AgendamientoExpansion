<?php
$Raiz = str_replace($_SERVER['PHP_SELF'], '', str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']));
include_once ($Raiz . "/include/ConfgGral.conf.php");
include_once (INCLUDES . "GestionBD.new.class.php");
include_once (INCLUDES . "multiServer.conf.php");
include_once ("File.Class.php");
include_once (INCLUDES . "RR.class.php");
include_once($Raiz ."/Agendamiento/Agenda.class.php");

set_time_limit(0);

class GeneraInformes	
{
    public $data;
    public $dataRR;
    public $Informe;
    public $DBGestion;
    public $DBAS400;

    function EjecutaConsulta($sql, $dbset = 0)
    {
        if (!ValidaConexion(@$this->DBGestion)) {
            if($dbset==0)
			$this->DBGestion = new GestionBD('AGENDAMIENTO');
			else
			$this->DBGestion = new GestionBD('WFMPRD');	
        }
        $this->DBGestion->ConsultaArray($sql);
        $this->data = $this->DBGestion->datos;
        $this->DBGestion->datos = '';
    }

    function EjecutaConsultaRR($sql)
    {
        if (!ValidaConexion(@$this->DBAS400)) {
            $this->DBAS400 = new GestionBD('AS400');
        }
        $this->DBAS400->ConsultaArray($sql);
        //$this->dataRR = array();
        $this->dataRR = $this->DBAS400->datos;
        $this->DBAS400->datos = '';
    }

    /**
     * GenerarInformes::informeReclamacion()
     * Metodo que obtiene la informacion del informe de reclamacion
     * @access public
     * @return array
     * @version 1.0
     * @author rorozco
     **/
    function informeReclamacion($FechaI, $FechaF){

        $reclamacion = <<<OE

          SELECT
                CMR.CUENTA,
                CMR.OT,
                CMR.SERVICIO,
                CMR.VENDEDOR,
                CTR.CODIGO ||' - '|| CTR.DESCRIPCION AS "TIPO RECLAMACION",
                CVM.MOTIVO AS "MOTIVO RECLAMACION",
                CASE
                  WHEN CMR.ESTADO = 'P' THEN 'PENDIENTE'
                  WHEN CMR.ESTADO = 'N' THEN 'RECHAZADO'
                  WHEN CMR.ESTADO = 'S' THEN 'APROBADO'
                END ESTADO,
                TO_CHAR(CMR.FECHA_INGRESO,'dd/mm/yyyy') "FECHA INGRESO",
                TO_CHAR(CMR.FECHA_INGRESO,'HH24:MI:SS') "HORA INGRESO",
                TO_CHAR(CMR.FECHA_GESTION,'dd/mm/yyyy') "FECHA GESTION",
                TO_CHAR(CMR.FECHA_GESTION,'HH24:MI:SS') "HORA GESTION",
                CMR.FECHA_GESTION "FECHA GESTION",
                CMR.USUARIO_SOLUCIONA "USUARIO QUE DA SOLUCION"

                FROM CGVM_RECLAMACION CMR

                INNER JOIN CGVM_TIPO_RECLAMO CTR ON CTR.ID_TIPO_RECLAMO = CMR.TIPO_RECLAMO
                INNER JOIN CGVM_MOTIVO_RECLAMO CVM ON CVM.ID_MOTIVO_RECLAMO = CMR.MOTIVO_RECLAMO

                WHERE
                TRUNC(CMR.FECHA_INGRESO)  BETWEEN TO_DATE ('{$FechaI}', 'yyyy/mm/dd') AND TO_DATE ('{$FechaF}', 'yyyy/mm/dd')
OE;

        $this->EjecutaConsulta($reclamacion);
        return $this->data;

    }

    function InformeOPS($FechaI, $FechaF, $Area, $Division, $Aliado)
    {

        /* Informe de ordenes de prestacion de servicios por fecha de agenda */
        $this->data = "";
        $sql = "SELECT 
				FE.ESTADO,
				FE.DESCRIPCION,
				COUNT(1) AS TOTAL
			FROM FAC_OPS FO 
			INNER JOIN FAP_ESTADOS FE ON FE.ESTADO = FO.ESTADO 
			WHERE FO.DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD') AND 
			TO_DATE('" . $FechaF . "','YYYY/MM/DD') ";
        if ($Area != "TODOS") {
            $sql .= " AND CAREA(CODNODO) IN (" . $Area . ")";
        } elseif ($Division != "TODOS") {
            $sql .= " AND CAREA(CODNODO) IN (SELECT CODIGO FROM RR_AREAS 
							WHERE CODDIVISION = " . $Division . " AND ESTADO = 'A')";
        }
        if ($Aliado != "TODOS") {
            $sql .= " AND IDALIADO IN (" . $Aliado . ")";
        }
        $sql .= " GROUP BY FE.ESTADO,DESCRIPCION";

        $this->EjecutaConsulta($sql);
        return $this->data;
    }

    function DetalladoOPS($FechaI, $FechaF, $Area, $Division, $Estado, $Aliado, $plusSQL='',$campos=" CAREA(A.CODNODO), 
																						FO.CODNODO AS NODO, 
																						A.IDORDEN_DE_TRABAJO AS OT,
																						FO.CUENTA,
																						FO.CODCIUDAD CIUDAD,
																						FO.DIAAGENDA, 
																						CONSULTA_MOVIL(A.IDMOVIL),
																						CONSULTA_ALIADO(FO.IDALIADO,';') AS ALIADO,
																						CTIPOTRABAJO(FO.ID_TT) AS TT,
																						CONSULTA_ACTIVIDAD(PA.ID_TTACTIVIDAD,';')AS ACTIVIDAD,
																						PA.CANTIDAD,
																						FO.FECHA,CASE
																						  WHEN A.PROGRAMACION = 'O'
																						  THEN 'ORDEN'
																						  WHEN A.PROGRAMACION = 'L'
																						  THEN 'LLAMADA'
																						  WHEN A.PROGRAMACION = 'E'
																						  THEN 'EDIFICIO'
																						  ELSE 'OTRAS BASES'
																						END AS PROGRAMACION,
																					   CASE
																						WHEN  TT.NOMBRE_TIPO = 'OTS HIJAS'
																						THEN 'OTS HIJAS'
																						WHEN AG.IDAGENDA IS NOT NULL
																						THEN 'OTS HIJAS EX'
																						ELSE ''
																					  END AS TIPO  ")	
    {

        /* Informe de ordenes de prestacion de servicios por fecha de agenda detallado ()actividad facturada, orden... */
        $this->data = "";
        $sql = "SELECT". 
					$campos
			   ." FROM FAC_OPS FO
					INNER JOIN AGENDA A ON A.IDAGENDA =FO.IDAGENDA
					INNER JOIN GESTIONNEW.TIPO_TRABAJO TT ON TT.ID_TT=A.ID_TT
					LEFT JOIN GESTIONNEW.AG_OT_EXCHANGE AG ON AG.IDAGENDA_HIJA=A.IDAGENDA
					LEFT JOIN PRE_FACTURA PF ON PF.IDAGENDA=A.IDAGENDA
					LEFT JOIN PRE_ACTIVIDAD PA ON PF.ID_FACTURA=PA.ID_FACTURA
				WHERE FO.DIAAGENDA BETWEEN TO_DATE('".trim($FechaI)." 00:00:00','YYYY/MM/DD HH24:MI:SS') AND  TO_DATE('".trim($FechaF)." 23:59:59','YYYY/MM/DD HH24:MI:SS') 
				";
        if ($Area != "TODOS") {
            $sql .= " AND CAREA(FO.CODNODO) IN (" . $Area . ")";
        } elseif ($Division != "TODOS") {
            $sql .= " AND CAREA(FO.CODNODO) IN (SELECT CODIGO FROM RR_AREAS 
							WHERE CODDIVISION = " . $Division . " AND ESTADO = 'A')";
        }
        if ($Aliado != "TODOS") {
            $sql .= " AND FO.IDALIADO IN (" . $Aliado . ")";
        }
        $sql .= "  AND FO.ESTADO IN('" . $Estado . "')";       
        $this->EjecutaConsulta($sql);       
        return $this->data;
    }

	
	// Inicio Agregado OB Delaware 07/07/2015 Nuevo Reporte de Alertas
	
function DetalladoOPSAlertas($FechaI, $FechaF, $Area, $Division, $Aliado, $plusSQL='',$campos=" CAREA(A.CODNODO), 
																						FO.CODNODO AS NODO, 
																						A.IDORDEN_DE_TRABAJO AS OT,
																						FO.CUENTA,
																						FO.CODCIUDAD CIUDAD,
																						FO.DIAAGENDA, 
																						CONSULTA_MOVIL(A.IDMOVIL),
																						CONSULTA_ALIADO(FO.IDALIADO,';') AS ALIADO,
																						CTIPOTRABAJO(FO.ID_TT) AS TT,
																						FAC_TTACTIVIDAD_ACT_COD_FN(PA.ID_TTACTIVIDAD)AS ACTIVIDAD,
																						FAC_TTACTIVIDAD_ACT_DESC_FN(PA.ID_TTACTIVIDAD)AS ACTIVIDAD_DESC,
																						NVL2(PA.CANTIDAD,PA.CANTIDAD, '0') AS CANTIDAD,
																						FO.FECHA,	
																						NVL2(TO_CHAR(PA.ID_TTACTIVIDAD),' ', 'No Tiene Actividad') AS OBSERVA1,
																						NVL2(TO_CHAR(PA.CANTIDAD),' ', 'No Tiene Cantidad') AS OBSERVA2,																							
																						FAC_TTACTIVIDAD_CANTIDAD_FN(TO_CHAR(PA.CANTIDAD),PA.ID_TTACTIVIDAD)AS REVISARCANT,
																						FAC_TTACTIVIDAD_CAMBIOEQUIP_FN(FO.ID_TT,PA.ID_TTACTIVIDAD)AS CAMBIOEQUIP,
																						FAC_TTACTIVIDAD_NFI_NF(FO.CODNODO,PA.ID_TTACTIVIDAD) AS NODONFI,
																						FAC_TTACTIVIDAD_OVERLAP_FN(FO.ID_TT,PA.ID_TTACTIVIDAD) AS OVERLAP,
																						FAC_OPS_CLALIA_FN(FO.IDALIADO) as COMCLA,
																						FAC_OPS_COMCEL_FN (FO.IDALIADO) as COMCEL,
																						FAC_TTACTIVIDAD_NOAPLICA_FN (PA.ID_TTACTIVIDAD) as NOAPLICA
																					")																						
    {

        /* Informe de ordenes de prestacion de servicios por fecha de agenda detallado ()actividad facturada, orden... */
        $this->data = "";
        $Informe = new File();
        $sql = "SELECT". 
					$campos
			   ."FROM AGENDA A   
				 INNER JOIN FAC_OPS FO  ON FO.IDAGENDA = A.IDAGENDA
				 LEFT JOIN PRE_FACTURA PF ON PF.IDAGENDA=A.IDAGENDA LEFT JOIN PRE_ACTIVIDAD PA ON PF.ID_FACTURA=PA.ID_FACTURA 
				WHERE FO.DIAAGENDA BETWEEN TO_DATE('" . $FechaI ."','YYYY/MM/DD') AND  TO_DATE('" . $FechaF ."','YYYY/MM/DD') AND FO.ESTADO IN('L','SA','I')";
        if ($Area != "TODOS") {
            $sql .= " AND CAREA(FO.CODNODO) IN (" . $Area . ")";
        } elseif ($Division != "TODOS") {
            $sql .= " AND CAREA(FO.CODNODO) IN (SELECT CODIGO FROM RR_AREAS 
							WHERE CODDIVISION = " . $Division . " AND ESTADO = 'A')";
        }
        if ($Aliado != "TODOS") {
            $sql .= " AND FO.IDALIADO IN (" . $Aliado . ")";
        } 
        if($plusSQL!=''){
        	$sql.=$plusSQL;
        }
		$fecha = date(dmy);
        $this->EjecutaConsulta($sql);
		unlink('../../ArchiveContainer/InformeOPS_Alertas'.EXT);
        $Informe->ImprimirFile('InformeOPS_Alertas'.EXT,$this->data);
        $Informe->CreateFile('InformeOPS_Alertas'.EXT,$this->data);

        //return $this->data;
    }	
	
	// Fin Agregado OB Delaware 07/07/2015

    function MarcacionOPS($FechaI, $FechaF, $Aliado)
    {

        /*Ordenes marcadas por el area de logistica*/
        $this->data = "";
        #$Informe = new File();
        $sql = "SELECT
					CONSULTA_ALIADO(A.IDALIADO,';') AS IDALIADO,A.DIAAGENDA, CAREA(A.CODNODO) AS AREA, 
					CONSULTA_MOVIL(A.IDMOVIL,';') AS MOVIL,A.IDORDEN_DE_TRABAJO AS OT,A.CUENTA,F.RAZON, F.OBSERVACION, 
					CONSULTA_USUARIO(F.IDUSUARIO) AS USUARIO, F.FECHA AS FECHA_MARC
				FROM AGENDA A 
				INNER JOIN FOPS_MARCADA F ON F.IDAGENDA = A.IDAGENDA AND  A.DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD') AND TO_DATE('" . $FechaF ."','YYYY/MM/DD') 
				WHERE F.ESTADO ='A'";
        if ($Aliado != "TODOS") {
            $sql .= " AND A.IDALIADO IN (" . $Aliado . ")";
        }
        $this->EjecutaConsulta($sql);
        #$Informe->ImprimirFile('InformeOPS'.EXT,$this->data);
        #$Informe->CreateFile('InformeOPS_'.$Division.'_'.$Area.'_'.$Estado.EXT,$this->data);

        return $this->data;
    }


    function NodoxNivel($FechaI, $FechaF)
    {
        /*Zonificacion, nodos que no tienen nivel asignado*/
        $this->data = "";
        $sql = "SELECT  DISTINCT  CODCIUDAD AS CIUDAD, CODNODO AS NODO
    			 FROM AGENDA A
  				  WHERE ESTADO IN ('A', 'V')AND ZONI_MANUAL = 'N'
				     AND A.DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD') AND 
				          TO_DATE('" . $FechaF . "','YYYY/MM/DD')
				    AND  CNIVEL_NODO (CODCIUDAD, IDALIADO, CODNODO, id_tt) NOT IN (1,2)
				ORDER BY   CODCIUDAD, CODNODO";
        $this->EjecutaConsulta($sql);
        return $this->data;
    }

    function MaterialReal($FechaI, $FechaF, $Area, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
        /*Oredenes con material Real*/
        $this->data = "";
        $sql = "SELECT CAREA(A.CODNODO)	AS AREA,
					  CONSULTA_ALIADO(A.IDALIADO, ';') AS ALIADO,
					  CONSULTA_MOVIL(A.IDMOVIL, ';')   AS MOVIL,
					  A.CUENTA,
					  A.IDORDEN_DE_TRABAJO	AS OT,
					  CONSULTA_ACTIVIDAD(FM.ID_ACTIVIDAD, ';') AS ACTIVIDAD,
					  MT.DESCRIPCION	AS MATERIAL,
					  FM.TIPOP,
					  SUM(FM.CANTIDAD) AS CANTIDAD
					FROM AGENDA A
					INNER JOIN FAO_MATERIAL_INS FM ON FM.ID_AGENDA = A.IDAGENDA
					INNER JOIN FAC_MATERIALES MT ON MT.IDMATERIALES = FM.ID_MTR
					WHERE A.ESTADO IN ('A', 'V') 
					  AND A.DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "', 'YYYY/MM/DD') AND TO_DATE('" . $FechaF . "', 'YYYY/MM/DD')";

        if ($Aliado != "TODOS") {
			$sql .= " AND A.IDALIADO  IN (" . $Aliado . ")";
        }
        if ($Area != "TODOS") {
			$sql .= " AND CAREA(A.CODNODO) IN (" . $Area . ")";
        }

        $sql .= " GROUP BY   CAREA(A.CODNODO),A.IDALIADO,A.IDMOVIL,A.CUENTA,A.IDORDEN_DE_TRABAJO,FM.ID_ACTIVIDAD,MT.DESCRIPCION,FM.TIPOP";
        #echo $sql;
        $this->EjecutaConsulta($sql);
        return $this->data;
    }

    function LogConsultasDigitac($FechaI, $FechaF)
    {

        /*Log mascara digitacion*/
        $this->data = "";
        $sql = "SELECT /*+index(MSC_LOG_CONSULTA_MOVIL SOP_LOG_CONSULTA_FECHA)*/ USUARIO, FECHA, CONSULTA, TIPO_CONSULTA, REPLACE(REPLACE(RESPUESTA, '
', ' '), '    ',' ')AS RESPUESTA, ORIGEN FROM INTEGRADOR.MSC_LOG_CONSULTA_MOVIL
    WHERE FECHA BETWEEN TO_DATE('" . $FechaI . ":00:00','YYYY/MM/DD:HH24:MI') 
     AND TO_DATE('" . $FechaF . ":23:59','YYYY/MM/DD:HH24:MI')";

        #echo $sql;
        $this->EjecutaConsulta($sql);
        return $this->data;
    }
    function LogMegaOfertas($FechaI, $FechaF)
    {
        /*Reporte de Ofertas*/
        $this->data = "";
        $sql = "SELECT /*+index(MSC_OFERTA_SUSCRIPTOR MSC_OFERTA_SUSCRIPTOR_IX1)*/ M.CUENTA, M.TARIFA_ACT, M.SERVICIO_ACT,M.VALOR_ACT, M.TARIFA_NEW, M.SERVICIO_NEW, 
				   M.VALOR_NEW, M.OFERTA, M.GESTION,M.USUARIO, M.FECHA, M.FECHAGEST
				FROM INTEGRADOR.MSC_OFERTA_SUSCRIPTOR M
				WHERE FECHA BETWEEN TO_DATE('" . $FechaI . ":00:00','YYYY/MM/DD:HH24:MI') 
				 AND TO_DATE('" . $FechaF . ":23:59','YYYY/MM/DD:HH24:MI')";

        #echo $sql;
        $this->EjecutaConsulta($sql);
        return $this->data;
    }

    /**
    *Limpiar los areglos de tipo de data que no sea numerico
    *
    *
    */
    function limpiarArray($array){
    	if(is_array($array)){
		   foreach($array as $key=>$value){
		       if(!is_numeric($value) ) {
					   unset($array[$key]);
		       }
		       
		   }
		  }
		 return $array;
		 
    }

    function OperacionDiaDia($FechaI, $FechaF, $Aliado, $Ciudad, $Carpeta)  {
        /*CIERRE DE OPERACION  DIA A DIA*/
        //var_dump($_POST);
        //exit();
        /*DEBEMOS CONTROLAR ERRORES QUE OCURREO DERIVADOS DE NO CONTROLAR EL TIPO DE DATO ESPERADO EN LA QUERY
		* Todos aquellos valores que NO sean numericos se eliminaran de este arreglo, antes de que realice el IMPLODE
		* porque este implode va a dar formato al query indicando valores del IN para un Campos Numerico.
		*@AUTOHOR Jose Pinilla - Hitts Colombia
		*Qdate 17/02/2015
        */
       
		
        
	   	 $Carpeta=$this->limpiarArray($Carpeta);
		 $Aliado= $this->limpiarArray($Aliado);
		
		/******/
		
        $Carpeta = implode("','", $Carpeta);
        $Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
       
        $this->data = "";
        $sql="SELECT CTIPOTRABAJO(CA.ID_TT ) AS CARPETA ,
				  CA.CODCIUDAD AS COMUNIDAD,
				  CONSULTA_ALIADO(CA.IDALIADO,'%')AS ALIADOS,
				  CA.FECHA,
				  (SELECT SUM(C.CAPACIDAD)
				  FROM CAPACIDAD_ALIADOS C
				  INNER JOIN RANGO_FECHA F
				  ON (C.ID_RF = F.ID_RF)
				  INNER JOIN CIUDAD_TIPOTRABAJO CT
				  ON (F.ID_CIUDADTT = CT.IDCIUDADTT)
				  WHERE C.FECHA BETWEEN TO_DATE('". $FechaI . "','YYYY/MM/DD:HH24:MI') AND TO_DATE('" . $FechaF . "','YYYY/MM/DD:HH24:MI')
				  AND C.ACTIVO = 'Y'
				  AND C.IDALIADO = CA.IDALIADO
				  AND C.FECHA =CA.FECHA
				  AND C.ID_TT =CA.ID_TT
				  AND CT.CODCIUDAD = CCIUDAD_SECUNDARIA(CA.CODCIUDAD)
				  AND C.CODCIUDAD  = CT.CODCIUDAD
				  ) AS CAPACIDAD,
				  (SELECT COUNT(IDAGENDA)
				  FROM AGENDA G
				  WHERE G.IDALIADO = CA.IDALIADO
				  AND G.DIAAGENDA =CA.FECHA
				  AND G.ID_TT =CA.ID_TT
				  AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD)
				  AND G.ESTADO IN( 'A','V')
				  ) AS AGENDADO,
				  (SELECT COUNT(IDAGENDA)
				  FROM AGENDA G
				  WHERE G.IDALIADO = CA.IDALIADO
				  AND G.DIAAGENDA =CA.FECHA
				  AND G.ID_TT =CA.ID_TT
				  AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD)
				  AND G.ESTADO IN('A','V' )
				  AND G.CODRESULTADO ='OK'
				  ) AS VISITAS_OK,
				  (SELECT COUNT(IDAGENDA)
				  FROM AGENDA G
				  WHERE G.IDALIADO = CA.IDALIADO
				  AND G.DIAAGENDA =CA.FECHA
				  AND G.ID_TT =CA.ID_TT
				  AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD)
				  AND G.ESTADO IN('A','V')
				  AND G.CODRESULTADO NOT IN ('OK')
				  ) AS VISITAS_CON_RAZON,
				  (SELECT COUNT(IDAGENDA)
				  FROM AGENDA G
				  WHERE G.IDALIADO = CA.IDALIADO
				  AND G.DIAAGENDA =CA.FECHA
				  AND G.ID_TT =CA.ID_TT
				  AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD)
				  AND G.ESTADO IN ('A','V')
				  AND G.CODRESULTADO ='0'
				  ) AS VISITAS_SIN_RAZON,
				  (
				  (SELECT SUM(C.CAPACIDAD)
				  FROM CAPACIDAD_ALIADOS C
				  INNER JOIN RANGO_FECHA F
				  ON (F.ID_RF = C.ID_RF)
				  INNER JOIN CIUDAD_TIPOTRABAJO CT
				  ON (CT.IDCIUDADTT = F.ID_CIUDADTT)
				  WHERE C.FECHA BETWEEN TO_DATE('" .$FechaI . "','YYYY/MM/DD:HH24:MI') AND TO_DATE('" . $FechaF . "','YYYY/MM/DD:HH24:MI')
				  AND C.ACTIVO = 'Y'
				  AND C.IDALIADO = CA.IDALIADO
				  AND C.FECHA =CA.FECHA
				  AND C.ID_TT =CA.ID_TT
				  AND CT.CODCIUDAD = CCIUDAD_SECUNDARIA(CA.CODCIUDAD)
				  AND C.CODCIUDAD = CT.CODCIUDAD
				  ) -
				  (SELECT COUNT(IDAGENDA)
				  FROM AGENDA G
				  WHERE G.IDALIADO = CA.IDALIADO
				  AND G.DIAAGENDA =CA.FECHA
				  AND G.ID_TT =CA.ID_TT
				  AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD)
				  AND G.ESTADO IN ('A','V')
				  )) AS VISITAS_LIBRES
				FROM
				  (SELECT /*+index(F,PK_RANGO_FECHA) index(ct,PK_IDCIUDADTT)*/ 
					DISTINCT C.FECHA,
					C.IDALIADO,
					C.CODCIUDAD,
					C.ID_TT
				  FROM CAPACIDAD_ALIADOS C
				  INNER JOIN RANGO_FECHA F
				  ON (F.ID_RF = C.ID_RF)
				  INNER JOIN CIUDAD_TIPOTRABAJO CT
				  ON (CT.IDCIUDADTT = F.ID_CIUDADTT)
				  WHERE C.FECHA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD:HH24:MI') AND TO_DATE('" . $FechaF . "','YYYY/MM/DD:HH24:MI')
				  AND C.ACTIVO      = 'Y'";
 
		if ($Aliado != "TODOS" AND !empty($Aliado) ) {
            $sql .= " AND C.IDALIADO IN (" . $Aliado . ")";
        }
        if ($Ciudad != "TODOS" AND !empty($Ciudad) ) {
            $sql .= " AND CT.CODCIUDAD IN ('" . $Ciudad . "')";
        }

        if ($Carpeta != "TODOS" AND !empty($Carpeta)) {
            $sql .= " AND CT.ID_TT IN ('" . $Carpeta . "')";
        }


        $sql .= " ) CA GROUP BY CA.ID_TT ,CA.CODCIUDAD,CA.IDALIADO,CA.FECHA
		";

        //echo $sql;
        $this->EjecutaConsulta($sql);

        $v2 = array();

        foreach ($this->data as $id => $v1) {
            if ($v1['CAPACIDAD'] == 0) {
                $v1['EFECTIVIDAD'] = '0%';
                $v1['INEFECTIVIDAD'] = '0%';
                $v1['UTILIZACION'] = '0%';
            } else {
                $valor1 = $v1['CAPACIDAD'];
                $valor2 = $v1['AGENDADO'];
                $valor3 = $v1['VISITAS_OK'];
                $porcentaje = round(($valor2 * 100) / $valor1);
                $v1['UTILIZACION'] = $porcentaje . '%';

                $efectividad = round(($valor3 * 100) / $valor1);
                $v1['EFECTIVIDAD'] = $efectividad . '%';

                $inefectividad = 100 - $efectividad;
                $v1['INEFECTIVIDAD'] = $inefectividad . '%';
            }
            $v2[] = $v1;

        }
        $this->data = $v2;

        return $this->data; 
    }

    function OperacionDiaDiaFranjas($FechaI, $FechaF, $Aliado, $Ciudad, $Carpeta)
    {
        
		$rescarpeta = array();
		$resciudad = array();
		$resaliado = array();
		
		if(count($Aliado)>1)
		{
			foreach ($Aliado as $aliado) 
			{
				if (is_numeric($aliado)) 
				{
					$resaliado[] = $aliado; 
				}
			} 	
		}else
		{
			$resaliado = $Aliado;
		}
		
		if(count($Ciudad)>1)
		{
			foreach ($Ciudad as $ciudad) 
			{
				if ($ciudad != "TODOS") 
				{
					$resciudad[] = $ciudad; 
				}
			} 	
		}else
		{
			$resciudad = $Ciudad;
		}
		if(count($Carpeta)>1)
		{
			foreach ($Carpeta as $carpeta) 
			{
				if (is_numeric($carpeta)) 
				{
					$rescarpeta[] = $carpeta; 
				}
			} 	
		}else
		{
			$rescarpeta = $Carpeta;
		}
		
		$resaliado = implode(",", $resaliado);
        $resciudad = implode("','", $resciudad);
		$rescarpeta = implode(",", $rescarpeta);


        $this->data = "";
        $sql="SELECT 
		  DISTINCT CTIPOTRABAJO(CA.ID_TT) AS CARPETA, CA.CODCIUDAD AS COMUNIDAD, CONSULTA_ALIADO(CA.IDALIADO, '%') AS ALIADOS, CA.FECHA, 
		  FN_FRANJAS(CA.ID_RH) AS FRANJA, 
		  (SELECT SUM(C.CAPACIDAD) 
		  FROM CAPACIDAD_ALIADOS C
		  INNER JOIN RANGO_FECHA F 
			ON (F.ID_RF = C.ID_RF) 
		  INNER JOIN CIUDAD_TIPOTRABAJO CT 
			ON (CT.IDCIUDADTT = F.ID_CIUDADTT)    
		  WHERE C.FECHA BETWEEN TO_DATE('" . $FechaI ."', 'YYYY/MM/DD:HH24:MI') AND TO_DATE('" . $FechaF . "', 'YYYY/MM/DD:HH24:MI') 
			 AND C.ACTIVO = 'Y' 
			 AND C.IDALIADO = CA.IDALIADO 
			 AND C.FECHA = CA.FECHA 
			 AND C.ID_TT = CA.ID_TT 
			 AND CT.CODCIUDAD = CCIUDAD_SECUNDARIA(CA.CODCIUDAD) 
			 AND C.CODCIUDAD = CT.CODCIUDAD      
			 AND C.ID_RH = CA.ID_RH) AS CAPACIDAD,           
		  (SELECT COUNT(IDAGENDA) FROM AGENDA G 
		   WHERE G.IDALIADO = CA.IDALIADO 
			 AND G.DIAAGENDA = CA.FECHA 
			 AND G.ID_TT = CA.ID_TT 
			 AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD) 
			 AND (ESTADO IN ('A','V')) 
			 AND G.ID_RH = CA.ID_RH) AS AGENDADO, 
		  (SELECT COUNT(IDAGENDA) 
		   FROM AGENDA G 
		   WHERE G.IDALIADO = CA.IDALIADO 
		   AND G.DIAAGENDA = CA.FECHA 
		   AND G.ID_TT = CA.ID_TT 
		   AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD) 
		   AND ESTADO IN ('A','V')
		   AND CODRESULTADO = 'OK' 
		   AND G.ID_RH = CA.ID_RH) AS VISITAS_OK,           
		  (SELECT COUNT(IDAGENDA) 
		   FROM AGENDA G 
		   WHERE G.IDALIADO = CA.IDALIADO 
		   AND G.DIAAGENDA = CA.FECHA 
		   AND G.ID_TT = CA.ID_TT 
		   AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD) 
		   AND ESTADO IN ('A','V') 
		   AND CODRESULTADO != 'OK'
		   AND G.ID_RH = CA.ID_RH) AS VISITAS_CON_RAZON,          
		  (SELECT COUNT(IDAGENDA) FROM AGENDA G 
		   WHERE G.IDALIADO = CA.IDALIADO 
		   AND G.DIAAGENDA = CA.FECHA 
		   AND G.ID_TT = CA.ID_TT 
		   AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD) 
		   AND ESTADO IN ('A','V') 
		   AND CODRESULTADO = '0' 
		   AND G.ID_RH = CA.ID_RH) AS VISITAS_SIN_RAZON,          
		  ((SELECT SUM(C.CAPACIDAD) 
		   FROM CAPACIDAD_ALIADOS C 
		   INNER JOIN RANGO_FECHA F ON (F.ID_RF = C.ID_RF) 
		   INNER JOIN CIUDAD_TIPOTRABAJO CT ON (CT.IDCIUDADTT = F.ID_CIUDADTT)    
		   WHERE C.FECHA BETWEEN TO_DATE('". $FechaI ."', 'YYYY/MM/DD:HH24:MI') AND TO_DATE('" . $FechaF . "', 'YYYY/MM/DD:HH24:MI') 
		   AND C.ACTIVO = 'Y' 
		   AND C.IDALIADO = CA.IDALIADO 
		   AND C.FECHA = CA.FECHA 
		   AND C.ID_TT = CA.ID_TT 
		   AND CT.CODCIUDAD = CCIUDAD_SECUNDARIA(CA.CODCIUDAD) 
		   AND C.CODCIUDAD = CT.CODCIUDAD 
		   AND C.ID_RH = CA.ID_RH) 
		   - 
		   (SELECT COUNT(IDAGENDA) 
			FROM AGENDA G 
			WHERE G.IDALIADO = CA.IDALIADO 
			AND G.DIAAGENDA = CA.FECHA 
			AND G.ID_TT = CA.ID_TT 
			AND CCIUDAD_SECUNDARIA(CA.CODCIUDAD) = CCIUDAD_SECUNDARIA(G.CODCIUDAD) 
			AND ESTADO IN ('A','V') 
			AND G.ID_RH = CA.ID_RH)) AS VISITAS_LIBRES 
		FROM 
		  (SELECT  /*+index(F,PK_RANGO_FECHA) index(ct,PK_IDCIUDADTT)*/ 
		   DISTINCT C.FECHA,C.IDALIADO,CT.CODCIUDAD,CT.ID_TT,C.ID_RH 
		   FROM CAPACIDAD_ALIADOS C 
		   INNER JOIN RANGO_FECHA F 
			ON (F.ID_RF = C.ID_RF) 
		   INNER JOIN CIUDAD_TIPOTRABAJO CT 
			ON (CT.IDCIUDADTT = F.ID_CIUDADTT)
		   WHERE C.FECHA BETWEEN TO_DATE('" . $FechaI . "', 'YYYY/MM/DD:HH24:MI') AND TO_DATE('" . $FechaF . "', 'YYYY/MM/DD:HH24:MI') 
			AND C.ACTIVO = 'Y'";
			        
		if ($resaliado != "TODOS") {
            $sql .= " AND C.IDALIADO IN (" . $resaliado . ")";
		}
        if ($resciudad != "TODOS") {
            $sql .= " AND CT.CODCIUDAD IN ('" . $resciudad . "')";
        }
        if ($rescarpeta != "TODOS") {
            $sql .= " AND CT.ID_TT IN (" . $rescarpeta . ")";
        }

        $sql .= " ) CA ORDER BY 1,3,4,5 ";

        $this->EjecutaConsulta($sql);

        return $this->data;
    }

    function ConsolidadoActividades($FechaI, $FechaF, $Area, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
        /**/
        $this->data = "";
        $sql = "SELECT DIAAGENDA,CCOSTOS_NEW(ID_TTACTIVIDAD,CODNODO),A.CODNODO AS NODO,
					CAREA(CODNODO) AS AREA ,IDALIADO ,CALIADO(IDALIADO) AS ALIADO,ID_TTACTIVIDAD AS ID,
					CONSULTA_ACTIVIDAD(ID_TTACTIVIDAD,';') AS ACTIVIDAD, SUM(CANTIDAD) AS CANTIDAD , SUM(TOTAL_MO) AS TOTAL_MO,
					CCODSAP_MO(ID_TTACTIVIDAD) AS COD_SAP, BODEGA_SAP(IDALIADO,CODCIUDAD) AS BODEGA_SAP
				FROM (SELECT a.idagenda, diaagenda, codnodo, idaliado, codciudad,facturado
						FROM agenda a LEFT JOIN fac_sol_especial sf
							 ON (a.idagenda = sf.idagenda)
					   WHERE DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD') 
					AND TO_DATE('" . $FechaF . "','YYYY/MM/DD')
				    AND FACTURADO = 'Y'
             AND sf.ID IS NULL) A INNER JOIN 
			 (select max(id_factura) as id_factura,idagenda,idusuario,max(TO_CHAR (fec_registro, 'DD/MM/YYYY')) as fec_registro,estado,total 
				from FAO_FACTURA
				WHERE idagenda IN (SELECT idagenda
									FROM agenda 
									WHERE DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD') 
									AND TO_DATE('" . $FechaF . "','YYYY/MM/DD')
							 AND facturado = 'Y')
				GROUP BY idagenda,idusuario,estado,total) F ON A.IDAGENDA= F.IDAGENDA 
				              INNER JOIN FAO_ACTIVIDAD FA ON F.ID_FACTURA=FA.ID_FACTURA
				WHERE  F.ESTADO ='A'";

        if ($Aliado != "TODOS") {
            $sql .= " AND IDALIADO IN (" . $Aliado . ")";
        }
        if ($Area != "TODOS") {
            $sql .= " AND CAREA(A.CODNODO) IN (" . $Area . ")";
        }

        $sql .= " GROUP BY a.idagenda,DIAAGENDA,CCOSTOS_NEW(ID_TTACTIVIDAD,CODNODO),CAREA(CODNODO),A.CODNODO,IDALIADO,BODEGA_SAP(IDALIADO,CODCIUDAD),ID_TTACTIVIDAD,CONSULTA_TTACTIVIDAD(ID_TTACTIVIDAD)";
        #echo $sql; exit;
        $this->EjecutaConsulta($sql);
        return $this->data;
    }
	
	function ConsolidadoActividadesOPS($FechaI, $FechaF, $Area, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
		$modAgeAnt = array();
		$modAgeNuev = array();
		$ActAnt = array();
		$modAgeNuev = array();
        /**/
        $this->data = "";
        $sql = "SELECT DISTINCT 
						F.IDAGENDA,
						J.DIAAGENDA,
						'' AS CECO,
					    J.CODNODO AS NODO,
						CAREA (J.CODNODO) AS AREA,
						J.IDALIADO,
						CALIADO (J.IDALIADO) AS ALIADO,
						J.IDORDEN_DE_TRABAJO as ORDEN,
						CASE 
						 WHEN 
							OPO.ORDEN IS NOT NULL
							THEN 
											OPACT.ID_ACTIVIDAD
							ELSE 
												A .ID_TTACTIVIDAD
						END AS ID,
						CASE 
						 WHEN 
							OPO.ORDEN IS NOT NULL
							THEN 
											OPO.ACTIVIDAD
							ELSE 
											T .CODIGO
						END AS CODIGO,
						CASE 
						 WHEN 
							OPO.ORDEN IS NOT NULL
							THEN 
											OPACT.NOMBRE_ACTIVIDAD
							ELSE 
											T .DESCRIPCION
						END AS DESCRIPCION,
						
						CASE 
						 WHEN 
							OPO.ORDEN IS NOT NULL
							THEN 
											OPO.CANTIDAD
							ELSE 
											A .CANTIDAD
						END AS CANTIDAD_ACTIVIDAD,
						CASE 
						 WHEN 
							OPO.ORDEN IS NOT NULL
							THEN 
											0
							ELSE 
											A .TOTAL_MO
						END AS TOTAL_MO,
						CASE 
						 WHEN 
							OPO.ORDEN IS NOT NULL
							THEN 
											OPACT.CODIGO_SAP
							ELSE 
											CCODSAP_MO(A.ID_TTACTIVIDAD)
						END AS COD_SAP,
						BODEGA_SAP (J.IDALIADO, J.CODCIUDAD) AS BODEGA_SAP,
						CASE 
						 WHEN 
							OPO.ORDEN IS NOT NULL
							THEN 
											OPO.MARCACION
							ELSE 
											1
						END AS MARCACION
					  FROM PRE_FACTURA F
					  INNER JOIN PRE_ACTIVIDAD A ON A.ID_FACTURA = F.ID_FACTURA
					  INNER JOIN FAC_TTACTIVIDAD T ON T.ID_TTACTIVIDAD = A.ID_TTACTIVIDAD
					  INNER JOIN AGENDA J ON J.IDAGENDA = F.IDAGENDA
					  LEFT JOIN USUARIOS K ON K.ID_USUARIO = F.IDUSUARIO
					  LEFT JOIN OPS.OPS_ACT_ORDEN OPO ON (OPO.ORDEN = J.IDORDEN_DE_TRABAJO AND OPO.ESTADO IN ('A'))
					  LEFT JOIN OPS.OPS_ACTIVIDAD OPACT ON (OPO.ACTIVIDAD = OPACT.CODIGO AND OPACT.ESTADO = 'A')
					  WHERE J.DIAAGENDA BETWEEN TO_DATE ('" . $FechaI . "', 'YYYY/MM/DD')
					  AND TO_DATE ('" . $FechaF . "', 'YYYY/MM/DD')
				";

        if ($Aliado != "TODOS") {
            $sql .= " AND J.IDALIADO IN (" . $Aliado . ")";
        }
        if ($Area != "TODOS") {
            $sql .= " AND CAREA(J.CODNODO) IN (" . $Area . ")";
        }
       
		$this->EjecutaConsulta($sql);
		$modAgeAnt = $this->data;
		
		if($modAgeAnt<>array())
		{
			$ots = '';
			foreach($modAgeAnt as $idAnt => $okAnt)
			{
				$ots.= ',' . $okAnt['ORDEN'] . '';
				$ots1[] = $okAnt['ORDEN'];
			}

			if($ots<>array())
			{
			   $ots = substr($ots, 1, strlen($ots));
			   $qry = getSqlInReverse("O.ORDEN",$ots1);
			}else
			{
			  $qry = "1=1";
			}
		}
		//para wf nuevas actividades
		$sql1 = "SELECT 
				AGE.ID_AGENDA AS IDAGENDA,
				AGE.DIAAGENDA,
				'0' AS CCOSTOS,
				O.CODNODO AS NODO,
				CAREA(O.CODNODO) AS AREA ,
				AGE.IDALIADO,
				CALIADO(AGE.IDALIADO) AS ALIADO,
				O.ORDEN,
				OPA.ID_ACTIVIDAD AS ID,
				OPA.CODIGO AS ACTIVIDAD,
				OPA.NOMBRE_ACTIVIDAD AS NOMBRE_ACTIVIDAD,				
				OPO.CANTIDAD AS CANTIDAD,
				'0' AS TOTAL_MO,
				OPA.CODIGO_SAP AS CODIGO_SAP, 
				'' AS BODEGA_SAP,
				OPO.MARCACION
				FROM MGW_AGENDA AGE
				INNER JOIN MGW_ORDEN O ON O.ID_ORDEN = AGE.ID_ORDEN
				INNER JOIN OPS.OPS_ACT_ORDEN OPO ON (OPO.ORDEN = O.ORDEN AND OPO.ESTADO IN ('A'))
				INNER JOIN OPS.OPS_ACTIVIDAD OPA ON (OPO.ACTIVIDAD = OPA.CODIGO)
				WHERE 
				$qry
				AND AGE.DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD') 
				AND TO_DATE('" . $FechaF . "','YYYY/MM/DD')
				AND AGE.ID_ESTADO_AGENDA > '7'
				";

        if ($Aliado != "TODOS") {
            $sql1 .= " AND AGE.IDALIADO IN (" . $Aliado . ")";
        }
        if ($Area != "TODOS") {
            $sql1 .= " AND CAREA(AGE.CODNODO) IN (" . $Area . ")";
        }
		$this->EjecutaConsulta($sql1, 1);
		$modAgeNuev = $this->data;
		
		$nuevosValores  = array_merge($modAgeAnt, $modAgeNuev);
		return $nuevosValores;
    }

    function OperacionSoporte($FechaI, $FechaF, $Aliado, $Ciudad, $Carpeta)
    {
        /*Informe Operacion Soporte QUERY*/

        $Carpeta = implode("','", $Carpeta);
        $Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);


        $this->data = "";
        $sql = "SELECT CTIPOTRABAJO(A.ID_TT )AS CARPETA,CUENTA,A.PROGRAMACION CLASEOT,A.IDORDEN_DE_TRABAJO AS OT,A.CODNODO AS NODO,VENDOROT AS VENDEDOR,";
        $sql .= "	CONSULTA_USUARIO(A.IDUSUARIO) AS AGENDADO_POR, FECHA_AGENDO, CMOVIL (A.IDMOVIL) AS MOVIL,A.HORA_LLEGADA, ";
        $sql .= "	A.HORA_SALIDA,ROUND((TO_DATE (TO_CHAR (HORA_SALIDA , 'YYYY/MM/DD HH24:MI'),'YYYY/MM/DD HH24:MI') -";
        $sql .= "   TO_DATE(TO_CHAR (HORA_LLEGADA, 'YYYY/MM/DD HH24:MI'),'YYYY/MM/DD HH24:MI'))*1440) AS TIEMPO_EN_MINUTOS,";
        $sql .= "   A.DEMORA AS REPORTA_DEMORA,A.CODRESULTADO AS RESULTADO,R.DESCRIPCION,";
        $sql .= "   CONSULTA_USUARIO(A.USUARIO_CONFIRMA) AS USUARIO_CONFIRMA,";
        $sql .= "   A.CODCIUDAD AS CIUDAD, CLOG_CO (IDAGENDA, 'RESULTADO_VISITA' ) AS FECHA_CIERRE,";
        $sql .= "   CONSULTA_ALIADO(A.IDALIADO,'%') AS ALIADO";
        $sql .= " FROM AGENDA A INNER JOIN RESULTADO_VISITA R ON (A.CODRESULTADO = R.CODRESULTADO) ";
        $sql .= "   WHERE A.DIAAGENDA BETWEEN TO_DATE ('" . $FechaI . "','YYYY/MM/DD') ";
        $sql .= "     AND TO_DATE ('" . $FechaF . "','YYYY/MM/DD') AND IDMOVIL <>0";

        if ($Aliado != "TODOS") {
			$Aliado=str_replace("TODOS,", "", $Aliado);
            $sql .= " AND A.IDALIADO IN (" . $Aliado . ")";
        }
        if ($Ciudad != "TODOS") {
            $sql .= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
        }

        if ($Carpeta != "TODOS") {
            $sql .= " AND A.ID_TT IN ('" . $Carpeta . "')";
        }

        $sql .= " ORDER BY FECHA_AGENDO ASC";

        #echo $sql;
        $this->EjecutaConsulta($sql);
        return $this->data;
    }

    function Financiera($FechaI, $FechaF)
    {
        $this->data = "";
        $sql = "SELECT ID_TTACTIVIDAD AS ACTIVIDAD,AG.CUENTA,FTT.CODIGO,FTT.DESCRIPCION, AG.CODNODO AS NODO,FTT.TIPO,
         			SUM (AC.CANTIDAD) AS CANTIDAD, SUM (TOTAL_MO) AS TOTAL_MO,
         			SUM (TOTAL_MT) AS TOTAL_MT, SUM (AC.TOTAL) AS TOTAL_ACTIVIDAD
    			FROM AGENDA AG INNER JOIN FAO_FACTURA FA ON (AG.IDAGENDA = FA.IDAGENDA)
        			 INNER JOIN FAO_ACTIVIDAD AC ON (FA.ID_FACTURA = AC.ID_FACTURA)
         			 INNER JOIN RR_CIUDADES C ON (AG.CODCIUDAD = C.CODIGO)
        			 INNER JOIN FAC_TTACTIVIDAD FTT ON FTT.ID_TTACTIVIDAD =  AC.ID_TTACTIVIDAD
  			    WHERE AG.DIAAGENDA BETWEEN TO_DATE ('" . $FechaI . "', 'YYYY/MM/DD')
                          AND TO_DATE ('" . $FechaF . "', 'YYYY/MM/DD')
     					  AND AG.FACTURADO = 'Y'";

        $sql .= " GROUP BY AG.CUENTA,ID_TTACTIVIDAD,FTT.CODIGO,FTT.DESCRIPCION, AG.CODNODO,FTT.TIPO";
        $sql .= " ORDER BY ID_TTACTIVIDAD,FTT.CODIGO,FTT.DESCRIPCION, AG.CODNODO,FTT.TIPO ASC";

        #echo $sql;
        $this->EjecutaConsulta($sql);


        $cortar = array_chunk($this->data, 10, true);
        foreach ($cortar as $indice1 => $consulta) {
            $cuentas = '';
            foreach ($consulta as $indice => $valor) {
                $cuentas .= ",''" . $valor['CUENTA'] . "''";
                $Ddato[$valor['CUENTA']] = $valor;
            }
            $cuentas = substr($cuentas, 1, strlen($cuentas));
            if ($cuentas != '') {

                $sql = "CALL TVCABLEEXE.SP_SENTENCIAS_SQL('
						SELECT 
						 SUTITL ,  SUACCT
						FROM
						CABLEDTA/SUBSMSL1 
						WHERE
						  SUACCT IN(" . $cuentas . " ) ')";


                #echo $sql;
                $this->EjecutaConsultaRR($sql);
                foreach ($this->dataRR as $indice => $valorRR) {
                    $Ddato[$valorRR['SUACCT']]['SUTYPE'] = $valorRR['SUTYPE'];
                    $Ddato[$valorRR['SUACCT']]['SUTITL'] = $valorRR['SUTITL'];
                }
            }
        }

        return $Ddato;
    }


    function ConsolidadoPreActividad($FechaI, $FechaF, $Area, $Aliado)
    {


        $Aliado = implode(",", $Aliado);

        /*PRECONSOLIDACIONES*/
        $this->data = "";
        $sql = "SELECT DIAAGENDA,CCOSTOS_NEW(ID_TTACTIVIDAD,CODNODO),A.CODNODO AS NODO,
					CAREA(CODNODO) AS AREA ,IDALIADO ,CALIADO(IDALIADO) AS ALIADO,ID_TTACTIVIDAD AS ID,
					CONSULTA_ACTIVIDAD(ID_TTACTIVIDAD,';') AS ACTIVIDAD, SUM(CANTIDAD) AS CANTIDAD , SUM(TOTAL_MO) AS TOTAL_MO,
					CCODSAP_MO(ID_TTACTIVIDAD) AS COD_SAP, BODEGA_SAP(IDALIADO,CODCIUDAD) AS BODEGA_SAP
				FROM AGENDA A INNER JOIN PRE_FACTURA F ON A.IDAGENDA= F.IDAGENDA 
				              INNER JOIN PRE_ACTIVIDAD FA ON F.ID_FACTURA=FA.ID_FACTURA
				WHERE DIAAGENDA BETWEEN TO_DATE('" . $FechaI . "','YYYY/MM/DD') 
					AND TO_DATE('" . $FechaF . "','YYYY/MM/DD')
				    AND FACTURADO = 'Y' AND F.ESTADO ='A'";

        if ($Aliado != "TODOS") {
            $sql .= " AND IDALIADO IN (" . $Aliado . ")";
        }
        if ($Area != "TODOS") {
            $sql .= " AND CAREA(A.CODNODO) IN (" . $Area . ")";
        }

        $sql .= " GROUP BY DIAAGENDA,CCOSTOS_NEW(ID_TTACTIVIDAD,CODNODO),CAREA(CODNODO),A.CODNODO,IDALIADO,BODEGA_SAP(IDALIADO,CODCIUDAD),ID_TTACTIVIDAD,CONSULTA_TTACTIVIDAD(ID_TTACTIVIDAD)";
        #echo $sql;
        $this->EjecutaConsulta($sql);

        return $this->data;
    }

    function OtPintadasRazon($FechaI, $FechaF, $Regional, $Ciudad, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
        if (is_array($Ciudad)) {
            $Ciudad = implode("','", $Ciudad);
        }


        /*INFORME  OT PINTADAS CON RAZON <> OK*/

        $this->data = "";


        $sql = " SELECT 
		AG.IDAGENDA, ag.idaliado, ag.cuenta, ag.id_tt, ag.codnodo, ag.diaagenda ,ag.codciudad,
		Consulta_Aliado (ag.idaliado) AS nomaliado,
		Consulta_Reg (ag.codciudad) AS region,  ag.programacion,
		Ctipotrabajo(ag.id_tt) AS TIPOTRABAJO, AG.ID_TT,
		ag.IDORDEN_DE_TRABAJO, ag.codnodo,ag.idagenda,
		CASE
		  WHEN ag.programacion='L' THEN 'NO' 
		  WHEN ag.programacion='O' THEN 'UM'
		  WHEN ag.programacion='E' THEN 'CI' 
		  END AS rubro,  
		  (SELECT DISTINCT ab.codigo_bodega_sap FROM ALIADO_BODEGA_SAP ab WHERE TO_NUMBER(ab.id_aliado) = ag.idaliado
		   AND ab.codciudad = ag.codciudad	AND ROWNUM < 2 ) AS BODEGASAP,
		  IV.tipo, IV.fabricante, IV.serial1, IV.antiguedad, IV.instalado,
		  Consultacodsap(IV.tipo, IV.fabricante) AS COD_SAP , 
		  CONSULTA_INVENTARIO(iv.tipo,iv.fabricante) AS NOMMATERIAL, 
		  Consulta_Rtt (Consultacodsap(IV.tipo, IV.fabricante) , ag.programacion, ag.codnodo) AS PEP,
		  Consulta_estructura(UPPER(ag.codnodo)) as ESTRUCTURE,0 as padre,CRES_VISITA (ag.codresultado) as razon_cierre
		  FROM 
		   CO_INVENTARIO  iv
		   INNER JOIN AGENDA ag ON (IV.idagenda = AG.idagenda )
		   INNER JOIN RR_NODOS N ON (AG.codnodo = N.codigo)
         WHERE 
		 AG.DIAAGENDA BETWEEN   TO_DATE ('" . $FechaI .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS')   
         AND TO_DATE ('" . $FechaF . " 23:59:59','YYYY/MM/DD HH24:MI:SS') 
		 AND ag.codresultado not in ('OK','0') 
		 AND IV.TIPO!='NUM' 
		 AND IV.INSTALADO='Y' 
		 AND IV.ESTADO IN ('A','N')
		 AND ag.programacion='O'
		 AND AG.ID_TT NOT IN(14,7,361,441,461,482,481)
		 AND AG.IDAGENDA NOT IN (SELECT AE.IDAGENDA_HIJA FROM AG_OT_EXCHANGE AE WHERE IV.IDAGENDA=AE.IDAGENDA_HIJA)";


        if ($Ciudad != "TODOS") {
            $sql .= " AND AG.CODCIUDAD IN ('" . $Ciudad . "')";
        }

        if ($Regional != "TODOS") {
            $sql .= " AND N.CODREGIONAL IN ('" . $Regional . "')";
        }

        if ($Aliado != "TODOS") {
            $sql .= " AND IDALIADO IN (" . $Aliado . ")";
        }   

        $sql .= "ORDER BY ag.idaliado, ag.cuenta";

        #echo $sql;
        $this->EjecutaConsulta($sql);


        set_time_limit(0);
        $datos = $this->data;
        $this->data = array();
        if (isset($datos[0])) {


            $AG_Padres = array();
            $UNO = 0;
            for ($i = 0; $i < count($datos); $i++) {
                if ($datos[$i]['PADRE'] != 0) {
                    $AG_Padres[$datos[$i]['PADRE'] . $datos[$i]['SERIAL1']] = $datos[$i]['PADRE'];
                }


                #imprimir($AG_Padres );
                ##imprimir($AG_Padres[$datos[$i]['IDAGENDA'].$datos[$i]['SERIAL1']]);
                if (!isset($AG_Padres[$datos[$i]['IDAGENDA'] . $datos[$i]['SERIAL1']])) {
                    $Antiguedad = (trim($datos[$i]['ANTIGUEDAD']) == 'N' ? "NUEVO" : (trim($datos[$i]['ANTIGUEDAD']) ==
                        'U' ? "RECUPERADO" : (trim($datos[$i]['ANTIGUEDAD']) == 'DS' ? "DESCONECTADO" :
                        "")));
                    $Sap = ($Antiguedad == 'RECUPERADO' ? 'R' : '');
                    $this->data[] = ($i + 1) . '|' . $datos[$i]['IDALIADO'] . '|' . $datos[$i]['NOMALIADO'] .
                        '|' . $datos[$i]['BODEGASAP'] . '|' . $Sap . $datos[$i]['COD_SAP'] . '|' . $datos[$i]['NOMMATERIAL'] .
                        '|' . $datos[$i]['TIPO'] . '|' . $datos[$i]['FABRICANTE'] . '|' . $datos[$i]['SERIAL1'] .
                        '|' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['IDORDEN_DE_TRABAJO'] . "|1|" . $datos[$i]['CODNODO'] .
                        '||' . $datos[$i]['INSTALADO'] . '|' . $Antiguedad . '|' . $datos[$i]['TIPOTRABAJO'] .
                        '|' . $datos[$i]['REGION'] . '|' . $datos[$i]['CODCIUDAD'] . '|' . $datos[$i]['DIAAGENDA'] .
                        '|G' . $datos[$i]['ID_TT'] . '-' . $datos[$i]['CODNODO'] . '-' . $datos[$i]['SERIAL1'] .
                        '-' . //$datos[$i]['IDORDEN_DE_TRABAJO'].'-'.$datos[$i]['CUENTA'].'|'.$datos[$i]['ESTRUCTURE'].'|'.$datos[$i]['RAZON_CIERRE'].'|'.$datos[$i]['OBSERVACION'].'|';
                        $datos[$i]['IDORDEN_DE_TRABAJO'] . '-' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['ESTRUCTURE'] .
                        '|' . $datos[$i]['RAZON_CIERRE'] . '|';
                    /*SI ENCUENTRA UN DECO PONE EL CONTROL REMORTO REPITIENDO LOS DATOS*/
                    if (trim($datos[$i]['TIPO']) == 'DDG' || trim($datos[$i]['TIPO']) == 'DE' ||
                        trim($datos[$i]['TIPO']) == 'DEC') {
                        $this->data[] = "C|" . $datos[$i]['IDALIADO'] . '|' . $datos[$i]['NOMALIADO'] .
                            '|' . $datos[$i]['BODEGASAP'] . '|40000030|' . $datos[$i]['NOMMATERIAL'] . '|' .
                            $datos[$i]['TIPO'] . '|' . $datos[$i]['FABRICANTE'] . '|' . $datos[$i]['SERIAL1'] .
                            '|' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['IDORDEN_DE_TRABAJO'] . '|1|' . $datos[$i]['CODNODO'] .
                            '||' . $datos[$i]['INSTALADO'] . '|' . $Antiguedad . '|' . $datos[$i]['TIPOTRABAJO'] .
                            '|' . $datos[$i]['REGION'] . '|' . $datos[$i]['CODCIUDAD'] . '|' . $datos[$i]['DIAAGENDA'] .
                            '|G' . $datos[$i]['ID_TT'] . '-' . $datos[$i]['CODNODO'] . '-' . $datos[$i]['SERIAL1'] .
                            '-' . $datos[$i]['IDORDEN_DE_TRABAJO'] . '-' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['ESTRUCTURE'] .
                            '|' . $datos[$i]['RAZON_CIERRE'] . '|';
                    }
                }
            }
        }
        return $this->data;

    }

    function ObservacionesOT($FechaI, $FechaF, $Regional, $Ciudad, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
        if (is_array($Ciudad)) {
            $Ciudad = implode("','", $Ciudad);
        }
        /*INFORME  OBSERVACIONES EN OT PINTADAS CON RAZON <> OK*/

        $this->data = "";


        $sql = " SELECT A.IDORDEN_DE_TRABAJO, A.DIAAGENDA, A.CALENDARIO, T.NOMBRE_TIPO, A.CODRESULTADO, A.OBSERVACION, L.NOMBRE AS NOMALIADO, M.NOMBRE AS NOMMOVIL, A.CODCIUDAD, A.CODNODO 
				FROM GESTIONNEW.AGENDA A INNER JOIN GESTIONNEW.ALIADOS L ON A.IDALIADO = L.IDALIADO 
				LEFT JOIN GESTIONNEW.MOVIL  M ON A.IDMOVIL = M.IDMOVIL 
				INNER JOIN GESTIONNEW.TIPO_TRABAJO T ON A.ID_TT = T.ID_TT 
				INNER JOIN GESTIONNEW.USUARIOS U ON A.IDUSUARIO = U.ID_USUARIO
				INNER JOIN GESTIONNEW.RR_NODOS N ON (A.CODNODO = N.CODIGO) 
				WHERE A.DIAAGENDA BETWEEN   TO_DATE ('" . $FechaI .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS')   
				AND TO_DATE ('" . $FechaF . " 23:59:59','YYYY/MM/DD HH24:MI:SS')
				AND A.IDORDEN_DE_TRABAJO NOT IN 
				(SELECT DISTINCT IDORDEN_DE_TRABAJO 
				FROM GESTIONNEW.AGENDA A WHERE A.DIAAGENDA BETWEEN   TO_DATE ('" . $FechaI .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS')   
				AND TO_DATE ('" . $FechaF .
            " 23:59:59','YYYY/MM/DD HH24:MI:SS') AND CODRESULTADO = 'OK' AND A.CALENDARIO = 'IN' AND A.PROGRAMACION = 'O' 
				AND A.ID_TT IN (8, 9, 10, 24, 381, 12, 22, 822, 14, 482))
				AND A.CALENDARIO = 'IN'
				AND A.PROGRAMACION = 'O'
				AND A.ID_TT IN (8, 9, 10, 24, 381, 12, 22, 822, 14, 482)";

        if ($Ciudad != "TODOS") {
            $sql .= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
        }

        if ($Regional != "TODOS") {
            $sql .= " AND N.CODREGIONAL IN ('" . $Regional . "')";
        }

        if ($Aliado != "TODOS") {
            $sql .= " AND A.IDALIADO IN (" . $Aliado . ")";
        }

        #echo $sql;
        $this->EjecutaConsulta($sql);


        set_time_limit(0);
        $datos = $this->data;
        $this->data = array();
        if (isset($datos[0])) {
            for ($i = 0; $i < count($datos); $i++) {

                $observ = eregi_replace("[\n|\r|\n\r]", ' ', $datos[$i]['OBSERVACION']);

                $this->data[] = ($i + 1) . '|' . $datos[$i]['IDORDEN_DE_TRABAJO'] . '|' . $datos[$i]['DIAAGENDA'] .
                    '|' . $datos[$i]['CALENDARIO'] . '|' . $datos[$i]['NOMBRE_TIPO'] . '|' . $datos[$i]['CODRESULTADO'] .
                    '|' . $observ . '|' . $datos[$i]['NOMALIADO'] . '|' . $datos[$i]['NOMMOVIL'] .
                    '|' . $datos[$i]['CODCIUDAD'] . '|' . $datos[$i]['CODNODO'] . '|';
            }
        }
        return $this->data;

    }


    function LlPintadasRazon($FechaI, $FechaF, $Regional, $Ciudad, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
        if (is_array($Ciudad)) {
            $Ciudad = implode("','", $Ciudad);
        }

        /*INFORME  LLAMADAS PINTADAS CON RAZON <> OK*/

        $this->data = "";


        $sql = " SELECT 
		AG.IDAGENDA, ag.idaliado, ag.cuenta, ag.id_tt, ag.codnodo, ag.diaagenda ,ag.codciudad,
		Consulta_Aliado (ag.idaliado) AS nomaliado,
		Consulta_Reg (ag.codciudad) AS region,  ag.programacion,
		Ctipotrabajo(ag.id_tt) AS TIPOTRABAJO, AG.ID_TT,
		ag.IDORDEN_DE_TRABAJO, ag.codnodo,ag.idagenda,
		CASE
		  WHEN ag.programacion='L' THEN 'NO' 
		  WHEN ag.programacion='O' THEN 'UM'
		  WHEN ag.programacion='E' THEN 'CI' 
		  END AS rubro,  
		  (SELECT DISTINCT ab.codigo_bodega_sap FROM ALIADO_BODEGA_SAP ab WHERE TO_NUMBER(ab.id_aliado) = ag.idaliado
		   AND ab.codciudad = ag.codciudad	AND ROWNUM < 2 ) AS BODEGASAP,
		  IV.tipo, IV.fabricante, IV.serial1, IV.antiguedad, IV.instalado,
		  Consultacodsap(IV.tipo, IV.fabricante) AS COD_SAP , 
		  CONSULTA_INVENTARIO(iv.tipo,iv.fabricante) AS NOMMATERIAL, 
		  Consulta_Rtt (Consultacodsap(IV.tipo, IV.fabricante) , ag.programacion, ag.codnodo) AS PEP,
		  Consulta_estructura(UPPER(ag.codnodo)) as ESTRUCTURE,0 as padre,CRES_VISITA (ag.codresultado) as razon_cierre
		  FROM 
		   CO_INVENTARIO  iv
		   INNER JOIN AGENDA ag ON (IV.idagenda = AG.idagenda )
		   INNER JOIN RR_NODOS N ON (AG.codnodo = N.codigo)
         WHERE 
		 AG.DIAAGENDA BETWEEN   TO_DATE ('" . $FechaI .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS')   
         AND TO_DATE ('" . $FechaF . " 23:59:59','YYYY/MM/DD HH24:MI:SS') 
		 AND ag.codresultado not in ('OK','0') 
		 AND IV.TIPO!='NUM' 
		 AND IV.INSTALADO='Y' 
		 AND IV.ESTADO IN ('A','N')
		 AND ag.programacion='L'
		 AND AG.ID_TT NOT IN(14,7,361,441,461,482,481)
		 AND AG.IDAGENDA NOT IN (SELECT AE.IDAGENDA_HIJA FROM AG_OT_EXCHANGE AE WHERE IV.IDAGENDA=AE.IDAGENDA_HIJA)";


        if ($Ciudad != "TODOS") {
            $sql .= " AND AG.CODCIUDAD IN ('" . $Ciudad . "')";
        }

        if ($Regional != "TODOS") {
            $sql .= " AND N.CODREGIONAL IN ('" . $Regional . "')";
        }

        if ($Aliado != "TODOS") {
            $sql .= " AND IDALIADO IN (" . $Aliado . ")";
        }

        $sql .= "ORDER BY ag.idaliado, ag.cuenta";

        #echo $sql;
        $this->EjecutaConsulta($sql);


        set_time_limit(0);
        $datos = $this->data;
        $this->data = array();
        if (isset($datos[0])) {


            $AG_Padres = array();
            $UNO = 0;
            for ($i = 0; $i < count($datos); $i++) {
                if ($datos[$i]['PADRE'] != 0) {
                    $AG_Padres[$datos[$i]['PADRE'] . $datos[$i]['SERIAL1']] = $datos[$i]['PADRE'];
                }
                #imprimir($AG_Padres );
                ##imprimir($AG_Padres[$datos[$i]['IDAGENDA'].$datos[$i]['SERIAL1']]);
                if (!isset($AG_Padres[$datos[$i]['IDAGENDA'] . $datos[$i]['SERIAL1']])) {
                    $Antiguedad = (trim($datos[$i]['ANTIGUEDAD']) == 'N' ? "NUEVO" : (trim($datos[$i]['ANTIGUEDAD']) ==
                        'U' ? "RECUPERADO" : (trim($datos[$i]['ANTIGUEDAD']) == 'DS' ? "DESCONECTADO" :
                        "")));
                    $Sap = ($Antiguedad == 'RECUPERADO' ? 'R' : '');
                    $this->data[] = ($i + 1) . '|' . $datos[$i]['IDALIADO'] . '|' . $datos[$i]['NOMALIADO'] .
                        '|' . $datos[$i]['BODEGASAP'] . '|' . $Sap . $datos[$i]['COD_SAP'] . '|' . $datos[$i]['NOMMATERIAL'] .
                        '|' . $datos[$i]['TIPO'] . '|' . $datos[$i]['FABRICANTE'] . '|' . $datos[$i]['SERIAL1'] .
                        '|' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['IDORDEN_DE_TRABAJO'] . "|1|" . $datos[$i]['CODNODO'] .
                        '||' . $datos[$i]['INSTALADO'] . '|' . $Antiguedad . '|' . $datos[$i]['TIPOTRABAJO'] .
                        '|' . $datos[$i]['REGION'] . '|' . $datos[$i]['CODCIUDAD'] . '|' . $datos[$i]['DIAAGENDA'] .
                        '|G' . $datos[$i]['ID_TT'] . '-' . $datos[$i]['CODNODO'] . '-' . $datos[$i]['SERIAL1'] .
                        '-' . $datos[$i]['IDORDEN_DE_TRABAJO'] . '-' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['ESTRUCTURE'] .
                        '|' . $datos[$i]['RAZON_CIERRE'] . '|';

                    /*SI ENCUENTRA UN DECO PONE EL CONTROL REMORTO REPITIENDO LOS DATOS*/
                    if (trim($datos[$i]['TIPO']) == 'DDG' || trim($datos[$i]['TIPO']) == 'DE' ||
                        trim($datos[$i]['TIPO']) == 'DEC') {
                        $this->data[] = "C|" . $datos[$i]['IDALIADO'] . '|' . $datos[$i]['NOMALIADO'] .
                            '|' . $datos[$i]['BODEGASAP'] . '|41000030|' . $datos[$i]['NOMMATERIAL'] . '|' .
                            $datos[$i]['TIPO'] . '|' . $datos[$i]['FABRICANTE'] . '|' . $datos[$i]['SERIAL1'] .
                            '|' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['IDORDEN_DE_TRABAJO'] . '|1|' . $datos[$i]['CODNODO'] .
                            '||' . $datos[$i]['INSTALADO'] . '|' . $Antiguedad . '|' . $datos[$i]['TIPOTRABAJO'] .
                            '|' . $datos[$i]['REGION'] . '|' . $datos[$i]['CODCIUDAD'] . '|' . $datos[$i]['DIAAGENDA'] .
                            '|G' . $datos[$i]['ID_TT'] . '-' . $datos[$i]['CODNODO'] . '-' . $datos[$i]['SERIAL1'] .
                            '-' . $datos[$i]['IDORDEN_DE_TRABAJO'] . '-' . $datos[$i]['CUENTA'] . '|' . $datos[$i]['ESTRUCTURE'] .
                            '|' . $datos[$i]['RAZON_CIERRE'] . '|';
                    }
                }
            }
        }
        return $this->data;

    }
    /*se corrige la variable que contenia el query principal ya que se encontraba con la variable $query y deberia ser $sql
	* @package Claro 
	* @author [HITSS – Johana Salcedo] 
	* @access public 
	* @copyright Copyright (c) 2015 Claro Colombia. 
	* @license Código Cerrado 
	* @version [2] 
	*/ 

    function InformeInventarios($FechaI, $FechaF, $Regional, $Ciudad, $Aliado)
    {
        $Aliado = implode(",", $Aliado);
        if (is_array($Ciudad)) {
            $Ciudad = implode("','", $Ciudad);
        }
        /*INFORME  INVENTARIOS*/

        $this->data = $Ddato = array();

			$sql="SELECT 
				CONSULTA_INVENTARIO(IV.TIPO,IV.FABRICANTE) AS NOMBRE_MATERIAL,
				IV.TIPO,
				IV.FABRICANTE,
				IV.SERIAL1    AS SERIAL,
				N.CODDIVISION AS DIVISION,
				AG.CODCIUDAD  AS COMUNIDAD,
				AG.CUENTA,
				AG.IDORDEN_DE_TRABAJO AS OT
				FROM CO_INVENTARIO IV
				INNER JOIN AGENDA AG ON AG.IDAGENDA=IV.IDAGENDA AND AG.DIAAGENDA BETWEEN TO_DATE ('" . $FechaI ." 00:00:00','YYYY/MM/DD HH24:MI:SS') AND TO_DATE ('" . $FechaF . " 23:59:59','YYYY/MM/DD HH24:MI:SS')
				INNER JOIN RR_NODOS N ON N.CODIGO=AG.CODNODO
				WHERE IV.TIPO!     ='NUM'";
			if ($Regional != "TODOS") {		
				$sql .= " AND N.CODREGIONAL IN ('" . $Regional . "') ";
			}
			if ($Ciudad != "TODOS") {
				$sql .= " AND AG.CODCIUDAD  IN ('" . $Ciudad . "') ";
			}
			if ($Aliado != "TODOS") {
				$sql .= " AND IDALIADO  IN (" . $Aliado . ") ";
			}
		
        #echo $sql;
        $this->EjecutaConsulta($sql);
        $cortar = array_chunk($this->data, 40, true);
        //		   Imprimir($cortar);exit();
        //$cortar= array_chunk($this->data,10,true);
        foreach ($cortar as $indice1 => $consulta) {
            $cuentas = '';
            foreach ($consulta as $indice => $valor) {
                $cuentas .= ",''" . $valor['CUENTA'] . "''";
                $Ddato[$valor['CUENTA']] = $valor;
            }
            $cuentas = substr($cuentas, 1, strlen($cuentas));
            if ($cuentas != '') {

                $sql = "CALL TVCABLEEXE.SP_SENTENCIAS_SQL('
					SELECT
                       INADDR SERIAL2, ININST ESTADO_ACTUAL, 
					   INPSTA ESTADO_ANTERIOR, ININSC||ININSY||''/''||ININSM||''/''||ININSD  FECHA ,INACCT CUENTA
                    FROM
                      CABLEDTA/INVMSTL4
                       WHERE
                        INACCT IN(" . $cuentas .
                    ") AND  (INITMC <> ''NUM'' AND INMANC <> ''RES'')')";
                #echo $sql;
                $this->EjecutaConsultaRR($sql);
                foreach ($this->dataRR as $indice => $valorRR) {
                    $Ddato[$valorRR['CUENTA']]['SERIAL2'] = $valorRR['SERIAL2'];
                    $Ddato[$valorRR['CUENTA']]['ESTADO_ACTUAL'] = $valorRR['ESTADO_ACTUAL'];
                    $Ddato[$valorRR['CUENTA']]['ESTADO_ANTERIOR'] = $valorRR['ESTADO_ANTERIOR'];
                    $Ddato[$valorRR['CUENTA']]['FECHA'] = $valorRR['FECHA'];
                }
            }
        }

        return $Ddato;
    }

    function InformeCierreCiclo($FechaI, $FechaF, $Regional, $Aliado,$CarpetaIT)
    {

        $Aliado = implode(",", $Aliado);
		$CarpetaIT = implode("','", $CarpetaIT);


        $this->data = $Ddato = array();

        $sql = "Select A.CUENTA,A.SUSCRIPTOR,cll || ' ' || numdir || ' ' || apto AS DIRECCION,A.TELEFONO1,A.TELEFONO2,
				Consulta_estructura(UPPER(a.codnodo)) as divi_area_zona_distri_unid,A.CODNODO AS NODO,
				CONSULTA_ALIADO(A.IDALIADO)AS ALIADOS, CCIUDAD (A.CODCIUDAD) AS CIUDAD,
				A.ESTADO AS ESTADO_AGENDA,A.DIAAGENDA AS FECHA_CREACION,A.FECHA_AGENDO, A.HORA_LLEGADA, A.HORA_SALIDA,
				CMOVIL (A.IDMOVIL) AS MOVIL, A.IDORDEN_DE_TRABAJO AS ORDEN_O_LLAMADA,
				A.DEMORA AS REPORTA_DEMORA,A.CODRESULTADO AS RESULTADO_VISITA,A.PROGRAMACION AS TIPO_DE_AGENDA,C_UNIBI (A.CODCIUDAD) as tipo_red,
				CTIPOTRABAJO (A.ID_TT) AS CARPETA,CONSULTA_USUARIO(A.IDUSUARIO) AS USUARIO_AGENDO,CONSULTA_USUARIO(L.IDUSUARIO) AS USUARIO_CERRO,
				A.CODRESULTADO AS CODIGO_CAUSA,R.DESCRIPCION AS DESCRIPCION_CAUSA
			FROM AGENDA A
				INNER JOIN RESULTADO_VISITA R ON A.CODRESULTADO = R.CODRESULTADO
				INNER JOIN RR_NODOS N ON (A.codnodo = N.codigo)
				INNER JOIN LOG_CO L ON (A.idagenda = L.idagenda)
			WHERE A.DIAAGENDA BETWEEN TO_DATE ('" . $FechaI .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS') 
								  AND TO_DATE ('" . $FechaF . " 23:59:59','YYYY/MM/DD HH24:MI:SS')";


        if ($Aliado != "TODOS") {
            $sql .= " AND A.IDALIADO IN (" . $Aliado . ")";
        }
        if ($Regional != "TODOS") {
            $sql .= " AND N.CODREGIONAL IN ('" . $Regional . "')";
        }
		if ($CarpetaIT != "TODOS") {
            $sql .= " AND A.ID_TT IN ('" . $CarpetaIT . "')";
        }

        //echo $sql;
        $this->EjecutaConsulta($sql);
        $cortar = array_chunk($this->data, 40, true);
        //Imprimir($cortar);exit();
        //$cortar= array_chunk($this->data,10,true);
        foreach ($cortar as $indice1 => $consulta) {
            $cuentas = '';
            foreach ($consulta as $indice => $valor) {
                $cuentas .= ",''" . $valor['CUENTA'] . "''";
                $Ddato[$valor['CUENTA']] = $valor;
                $Ddato[$valor['CUENTA']]['TIPO_CLIENTE_SEG'] = '';
                $Ddato[$valor['CUENTA']]['TIPO_CLIENTE'] = '';
                $Ddato[$valor['CUENTA']]['TOTAL_SERVICIO'] = '';
                $Ddato[$valor['CUENTA']][1] = '';
                $Ddato[$valor['CUENTA']][2] = '';
                $Ddato[$valor['CUENTA']][3] = '';
                $Ddato[$valor['CUENTA']][4] = '';
                $Ddato[$valor['CUENTA']][5] = '';
                $Ddato[$valor['CUENTA']][6] = '';
                $Ddato[$valor['CUENTA']][7] = '';
                $Ddato[$valor['CUENTA']][8] = '';
                $Ddato[$valor['CUENTA']][9] = '';

            }
            $cuentas = substr($cuentas, 1, strlen($cuentas));
            if ($cuentas != '') {
                $sql = "CALL TVCABLEEXE.SP_SENTENCIAS_SQL('	
					SELECT  SUTITL TIPO_CLIENTE_SEG, SUTYPE TIPO_CLIENTE, USSRVÑ TOTAL_SERVICIO, SASERV SERVICIO,SACATG CATEGORIA,SUACCT CUENTA
			          FROM CABLEDTA/subsms99 
					      INNER JOIN CABLEDTA/uservi98  ON  (SUAKYÑ = USAKYÑ) 
                          INNER JOIN CABLEDTA/SRVMSTL1  ON (USSERV = SASERV)
                      WHERE
						   SUACCT IN(" . $cuentas . " )')";

                #echo $sql;
                $this->EjecutaConsultaRR(utf8_decode($sql));
                //imprimir  ($this->dataRR); exit;
                foreach ($this->dataRR as $indice => $valorRR) {
                    //$Ddato [$valorRR ['CUENTA']] ['USUARIO_CERRO'] = $valorRR['USUARIO_CERRO'];
                    $Ddato[$valorRR['CUENTA']]['TIPO_CLIENTE_SEG'] = $valorRR['TIPO_CLIENTE_SEG'];
                    $Ddato[$valorRR['CUENTA']]['TIPO_CLIENTE'] = $valorRR['TIPO_CLIENTE'];
                    $Ddato[$valorRR['CUENTA']]['TOTAL_SERVICIO'] = @$Ddato[$valorRR['CUENTA']]['TOTAL_SERVICIO'] +
                        $valorRR['TOTAL_SERVICIO'];
                    $Ddato[$valorRR['CUENTA']][$valorRR['CATEGORIA']] .= ' ' . $valorRR['SERVICIO'] .
                        '|';

                }
                $this->dataRR = array();
                //Imprimir($Ddato);exit();
            }
            unset($cortar[$indice1]);
        }

        return $Ddato;
    }

    function InformeVentasPC($FechaI, $FechaF, $Regional, $Ciudad, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);

        /*INFORME  PC*/

        $this->data = $Ddato = array();

        $sql = " SELECT ROWNUM AS N, CONSULTA_ALIADO (AG.IDALIADO) AS ALIADO, (SELECT DISTINCT AB.CODIGO_BODEGA_SAP FROM ALIADO_BODEGA_SAP AB WHERE TO_NUMBER(AB.ID_ALIADO) = AG.IDALIADO                                        
           AND AB.CODCIUDAD = AG.CODCIUDAD    AND ROWNUM < 2 ) AS BODEGASAP, I.CODIGO_SAP AS COD_SAP_MATERIAL,
           I.NOMBRE AS NOMBRE_MATERIAL,I.TIPO, CHO_INV(IV.ID_INV) AS FABRICANTE, EI.SERIAL AS SERIAL, 
           AG.CUENTA, AG.IDORDEN_DE_TRABAJO, '1' AS CANTIDAD, AG.CODNODO, CONSULTA_RTT (I.CODIGO_SAP , AG.PROGRAMACION, AG.CODNODO) AS PEP,
           'Y' AS INSTALADO, 'NUEVO' AS ANTIGUEDAD, CTIPOTRABAJO(AG.ID_TT) AS TIPOTRABAJO, 
           CONSULTA_REG (AG.CODCIUDAD) AS REGION, AG.CODCIUDAD, AG.DIAAGENDA , 'UM'AS RUBRO,
           CONSULTA_ESTRUCTURA(UPPER(AG.CODNODO)) AS ESTRUCTURE                                                  
          FROM                                         
            FAC_OPS FA                                        
           INNER JOIN ENT_AGENDA AG ON (FA.IDAGENDA = AG.IDAGENDA )                                        
           INNER JOIN ENT_COINVENTARIO  IV ON (FA.IDAGENDA=IV.IDAGENDA AND IV.ESTADO = 'A')                                        
           INNER JOIN INVENTARIO I ON (IV.ID_INV =I.ID_INV AND I.ESTADO = 'A')                                        
           INNER JOIN ENT_INVENTARIO EI ON (EI.ID_SERIAL = IV.ID_SERIAL AND EI.ESTADO = 'N') INNER JOIN RR_NODOS N ON (AG.codnodo = N.codigo)                                        
         WHERE                                         
          FA.FECHA BETWEEN  TO_DATE ('" . $FechaI .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS')   
         AND TO_DATE ('" . $FechaF .
            " 23:59:59','YYYY/MM/DD HH24:MI:SS')  AND FA.PROGRAMACION = 'O' ";

        if ($Regional != "TODOS") {
            $sql .= " AND N.CODREGIONAL IN ('" . $Regional . "')";
        }

        if ($Ciudad != "TODOS") {
            $sql .= " AND AG.CODCIUDAD IN ('" . $Ciudad . "')";
        }

        if ($Aliado != "TODOS") {
            $sql .= " AND AG.IDALIADO IN (" . $Aliado . ")";
        }

        $sql .= " ORDER BY  N, FA.IDALIADO, FA.CUENTA ";

        //echo $sql;

        $this->EjecutaConsulta($sql);

        return $this->data;
    }


    function InformeMoviles($Ciudad, $Aliado)
    {

        $Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
        $Permisos = AtenticacionUsuario();

        /*INFORME  MOVILES*/

        $this->data = $Ddato = array();
		
		/**
		* @autor John Camilo Trilleros Restrepo
		* @uses  Fecha Modificacion: 2013/04/16 Dexon: 661583, Se cambio el orden de las columnas, se quito la condicion de estado del MOVIL.
		*/
		
        $sql = " SELECT /*+ ALL_ROWS  INDEX(m IDX$\$_45D60001) */CONSULTA_ALIADO (M.IDALIADO) AS ALIADO, CONSULTA_REG (CODCIUDAD) AS REGIONAL,  			
			/*M.NOMBRE AS NOMBRE_MOVIL,*/ M.NOMBRE AS ID_MOVIL,RT.NOMBRE,/*M.IDMOVIL,*/M.NOMBRE_USU AS NOMBRE_TECNICO,M.CEDULA AS CEDULA_TECNICO,
				M.APELLIDO_USU AS NOMBRE_SUPERVISOR,M.CELULAR AS CELULAR_TECNICO,M.VEHICULO AS TIPO_VEHICULO, M.PLACA, M.ELITE, M.ACTIVO as ESTADO,        
				C.CODCIUDAD AS CIUDAD,U.NOMBRE AS NOMBRE_USU_MOD, U.ID_USUARIO AS CEDULA_USU_MOD, U.TELEFONO, M.IDRADIO,M.TIPO_RED FROM MOVIL M
				LEFT JOIN USUARIOS U ON (U.idmovil = M.idmovil) AND U.ESTADO = 'A'
                LEFT JOIN MOVIL_CIUDADES C ON (C.NOMBREMOVIL = M.NOMBRE) AND C.ACTIVO = 'Y'
                LEFT JOIN RR_TECHLIST RT ON M.NOMBRE = RT.CODIGO
                WHERE  /*M.ACTIVO = 'Y' AND */ M.TIPO_RED = '".$Permisos['TIPO_RED']."'";		

        if ($Ciudad != "TODOS") {
			  $sql .= "AND FN_GET_CIUD_MOVIL(M.NOMBRE,2) LIKE '%" . $Ciudad . "%'"; 
        }

        if ($Aliado != "TODOS") {
            $sql .= " AND M.IDALIADO IN (" . $Aliado . ")";
        }

        $sql .= " ORDER BY ID_MOVIL ";  // Dexon : 661583, John Camilo Trilleros Restrepo
        
        $this->EjecutaConsulta($sql);

        return $this->data;
    }

    function InformeCapacidades($FechaI, $FechaF, $Ciudad, $Aliado, $Carpeta)
    {

		$rescarpeta = array();
		$resciudad = array();
		$resaliado = array();
		
		if(count($Aliado)>1)
		{
			foreach ($Aliado as $aliado) 
			{
				if (is_numeric($aliado)) 
				{
					$resaliado[] = $aliado; 
				}
			} 	
		}else
		{
			$resaliado = $Aliado;
		}
		
		if(count($Ciudad)>1)
		{
			foreach ($Ciudad as $ciudad) 
			{
				if ($ciudad != "TODOS") 
				{
					$resciudad[] = $ciudad; 
				}
			} 	
		}else
		{
			$resciudad = $Ciudad;
		}
		if(count($Carpeta)>1)
		{
			foreach ($Carpeta as $carpeta) 
			{
				if (is_numeric($carpeta)) 
				{
					$rescarpeta[] = $carpeta; 
				}
			} 	
		}else
		{
			$rescarpeta = $Carpeta;
		}
		
		$resaliado = implode(",", $resaliado);
        $resciudad = implode("','", $resciudad);
		$rescarpeta = implode(",", $rescarpeta);

        /*INFORME  CAPACIDADES*/

        $this->data = $Ddato = array();

        $sql = " SELECT AG.IDAGENDA,AG.IDORDEN_DE_TRABAJO AS ORDEN_TRABAJO, AG.CUENTA AS CUENTA, CTIPOTRABAJO (AG.ID_TT) TIPOS_TRABAJO, CALIADO (AG.IDALIADO) AS NOMBRE_ALIADO, CMOVIL (AG.IDMOVIL) AS MOVIL,
                AG.CODRESULTADO AS RESULTADO, AG.HORA_LLEGADA AS FECHA_HORA_LLEGADA, AG.HORA_SALIDA AS FECHA_HORA_SALIDA, CREGIONAL (AG.CODCIUDAD) AS REGIONAL,
                CCIUDAD (AG.CODCIUDAD) CIUDAD,(RH1.HINICIAL_RH ||'-'|| RH1.HFINAL_RH) AS FRANJA_ESCOJIDA,AG.DIAAGENDA AS FECHA_AGENDA_ESCOJIDA,
                RH.HINICIAL_RH||'-'||RH.HFINAL_RH AS FRANJA_MAS_CERCANA,  
                CC.FECHA AS FECHA_AGENDA_MAS_CERCANA, CC.CUPOS AS CANTIDAD_DE_CUPOS, CC.CAPACHECK AS SUSCRIPTOR_MANIFESTO_CAPACIDAD, AG.FECHA_AGENDO AS FECHA_AGENDA_USUARIO, AG.ESTADO AS ESTADO_AGENDA,
                AG.CODNODO AS NODO,CONSULTA_USUARIO (AG.IDUSUARIO) AS USUARIO_AGENDO
				FROM CAPACIDAD_CUPOS CC
                INNER JOIN AGENDA AG ON AG.IDAGENDA=CC.IDAGENDA
                INNER JOIN RANGO_HORA RH ON RH.ID_RH = CC.ID_RH
                INNER JOIN RANGO_HORA RH1 ON RH1.ID_RH = AG.ID_RH
			 WHERE  AG.DIAAGENDA BETWEEN  TO_DATE ('" . $FechaI ." 00:00:00','YYYY/MM/DD HH24:MI:SS')   
				AND TO_DATE ('" . $FechaF . " 23:59:59','YYYY/MM/DD HH24:MI:SS')";

       	if ($resaliado != "TODOS") {
            $sql .= " AND AG.IDALIADO IN (" . $resaliado . ")";
		}
        if ($resciudad != "TODOS") {
            $sql .= " AND AG.CODCIUDAD IN ('" . $resciudad . "')";
        }
        if ($rescarpeta != "TODOS") {
            $sql .= " AND AG.ID_TT IN (" . $rescarpeta . ")";
        }
        $this->EjecutaConsulta($sql);

        $agendas = $this->data;
        
        if(ValRolInterno('SLF001')):        
        
            $_data = array();
                    
            foreach($agendas as $data):
            
                $data['SELF'] = '';            
                $data['SERV'] = '';
                $data['APROVISIONAMIENTO'] = ''; 
                $serv_array = array();
                $aprov_array = array();
                $sserv = '';
                $saprov = '';
				
                $selft= "SELECT ID_SELF_AGENDA,
						  ID_AGENDA,
						  FECHA_MODIFICACION,
						  ID_USUARIO,
						  ESTADO,
						  ID_CONFIGURACION,
						  ESTADO_CLIENTE
						FROM SELF_AGENDA
						WHERE ID_AGENDA=  '".$data['IDAGENDA']."'";
                
                $this->EjecutaConsulta($self);
                
                $self_agenda = $this->data;
                
                if($self_agenda):
                                    
                    $serv_sql = "SELECT SAS.SERVICIO FROM SELF_AGENDA SA
                    INNER JOIN SELF_AGENDA_SERVICIOS SAS ON SAS.ID_SELF_AGENDA = SA.ID_SELF_AGENDA 
                    INNER JOIN SELF_ESTADOS SE ON SE.ID_ESTADO = SAS.ID_ESTADO
                    WHERE SA.ID_SELF_AGENDA = '".$self_agenda[0]['ID_SELF_AGEDA']."' GROUP BY SAS.SERVICIO";
                    
                    $this->EjecutaConsulta($serv_sql);
                    
                    $servicios = $this->data;
                    
                    if($servicios):
                        
                        foreach($servicios as $servicio):
                        
                            $serv_array[] = $servicio['SERVICIO'];
                        
                        endforeach;
                        
                        $sserv = implode('|', $serv_array);
                        
                    endif;
                    
                    $aprov_sql = "SELECT SE.DESCRIPCION FROM SELF_AGENDA SA
                    INNER JOIN SELF_AGENDA_SERVICIOS SAS ON SAS.ID_SELF_AGENDA = SA.ID_SELF_AGENDA
                    INNER JOIN SELF_ESTADOS SE ON SE.ID_ESTADO = SAS.ID_ESTADO
                    WHERE SA.ID_SELF_AGENDA = '".$self_agenda[0]['ID_SELF_AGENDA']."' GROUP BY SE.DESCRIPCION";
                    
                    $this->EjecutaConsulta($aprov_sql);
                    
                    $aprovisionamientos = $this->data;
                    
                    if($aprovisionamientos):
                    
                        foreach($aprovisionamientos as $aprovisionamiento):
                        
                            $aprov_array[] = $aprovisionamiento['DESCRIPCION'];
                        
                        endforeach;
                        
                        $saprov = implode('|', $aprov_array);
                    
                    endif;
                    
                    $data['SELF'] = 1;
                    
                    $data['SERV'] = $sserv;
                    
                    $data['APROVISIONAMIENTO'] = $saprov;
                                    
                endif;
                
                unset($data['IDAGENDA']);
                
                $_data[] = $data;
                
            endforeach;
            
        else:
        
            $_data = $agendas;
        
        endif;
        
        return $_data;
    }


    //FUNCION DE TIPO DE CLIENTE DE RR

    function ConsultaTipoCliente($datos)
    {

        $cortar = array_chunk($datos, 40, true);

        //$cortar= array_chunk($this->data,10,true);
        foreach ($cortar as $indice1 => $consulta) {
            $cuentas = '';
            foreach ($consulta as $indice => $valor) {
                #$cuentas .= ",''" .$valor ['CUENTA']."''";
                $cuentas .= ",''" . $valor['CUENTA'] . "''";
                $Ddato[$valor['CUENTA']] = $valor;
            }
            $cuentas = substr($cuentas, 1, strlen($cuentas));
            if ($cuentas != '') {

                $sql = "CALL TVCABLEEXE.SP_SENTENCIAS_SQL('
						SELECT 
						 SUTITL ,  SUACCT
						FROM
						CABLEDTA/SUBSMSL1 
						WHERE
						  SUACCT IN(" . $cuentas . " ) ')";

                #echo $sql;
                $this->EjecutaConsultaRR($sql);
                foreach ($this->dataRR as $indice => $valorRR) {
                    $Ddato[$valorRR['SUACCT']]['SUTYPE'] = $valorRR['SUTYPE'];
                    $Ddato[$valorRR['SUACCT']]['SUTITL'] = $valorRR['SUTITL'];
                }
            }
        }

        return $Ddato;
    }

    public function InformeCapacidad($id_tt, $id_rf, $cod_ciudad, $idaliado)
    {
        $ciudad = implode("','", $cod_ciudad);

        $id_trabajo = $id_tt;
        $idRfecha = $id_rf;
        $idcontratista = implode("','", $idaliado);
        $tipotrabajo = implode("','", $id_tt);
        $PaMostrar = array();
        $Permisos = AtenticacionUsuario();


        if (trim($idcontratista) != "") {
            $sql = "SELECT NOMBRE, IDALIADO FROM ALIADOS  WHERE idALIADO = '" . $idcontratista .
                "' AND SEGMENTO = '" . $Permisos['TIPO_RED'] . "' ";
            $Query = $sql;
            //$GestionDB->ConsultaArray ( $Query );
            $this->EjecutaConsulta($Query);
            $array_agenda = $this->data;
            $sql = "SELECT NOMBRE_TIPO, ID_TT FROM tipo_trabajo  WHERE id_tt = '" . $tipotrabajo .
                "' AND SEGMENTO = '" . $Permisos['TIPO_RED'] . "' ";
            $Query = $sql;
            $this->EjecutaConsulta($Query);
            $TT = $this->data;
            $nomcontratista = $array_agenda[0]['NOMBRE'];
            $nomtrabajo = $TT[0]['NOMBRE_TIPO'];
        }

        $sql = "SELECT RF.ID_RF, RF.FINICIAL_RF, RF.FFINAL_RF, RF.ID_CIUDADTT 
				FROM RANGO_FECHA RF
				INNER JOIN CIUDAD_TIPOTRABAJO CT 
				ON CT.IDCIUDADTT = RF.ID_CIUDADTT 
				AND CT.CODCIUDAD='" . $ciudad . "'
				AND CT.ID_TT = '" . $tipotrabajo . "' 
				WHERE  RF.ID_RF = '" . $idRfecha ."'
				ORDER BY RF.FINICIAL_RF";

        $Query = $sql;
        $this->EjecutaConsulta($Query);
        $RangoHoras = $this->data;
		#Formato produccion
        $diaini = strtotime($RangoHoras[0]['FINICIAL_RF']);
        $diafin = strtotime($RangoHoras[0]['FFINAL_RF']);
		
		#Formato 56
		/*
		$FINI_EXPLODE = explode('/',$RangoHoras[0]['FINICIAL_RF']);
		$diaini = strtotime($FINI_EXPLODE[2].'-'.$FINI_EXPLODE[1].'-'.$FINI_EXPLODE[0]);
		$FFIN_EXPLODE = explode('/',$RangoHoras[0]['FFINAL_RF']);		
		$diafin = strtotime($FFIN_EXPLODE[2].'-'.$FFIN_EXPLODE[1].'-'.$FFIN_EXPLODE[0]);
		*/   
		
        $incr = 86400; //UN DIA EQUIVALE A 86400 SEG

        $ciudadTTID=(empty($RangoHoras[0]['ID_CIUDADTT']) OR !isset($RangoHoras[0]['ID_CIUDADTT']))?0:$RangoHoras[0]['ID_CIUDADTT'];
        $sql = "SELECT DISTINCT H.ID_RH, 
				  H.HINICIAL_RH INI, 
				  HFINAL_RH FIN 
				FROM RANGO_HORA H
				INNER JOIN RANGO_FECHA F  ON F.ID_RF = H.ID_RF
				WHERE ID_CIUDADTT = ".$ciudadTTID." AND (";
        $aux_sql = "";
        $sql1 = "";
        $sql2 = "";
        $sql3 = "";
        $sql4 = "";
        $sql5 = "";
        $sql6 = "";
        $sql7 = "";

        for ($i = $diaini; $i <= $diafin; $i += $incr) {
            $aux_sql .= "TO_DATE ('" . date('Y/m/d', $i) ." 00:00:00', 'yyyy/mm/dd hh24:mi:ss') BETWEEN finicial_rf AND ffinal_rf or ";
        }

        $aux_sql = substr($aux_sql, 0, strlen($aux_sql) - 3);

        $sql = $sql . $aux_sql . ") ORDER BY ini";

        $Query = $sql;
        $this->EjecutaConsulta($Query);
        $RangoHoras1 = $this->data;

        foreach ($RangoHoras1 as $Indice => $valor) {

            $sql1 .= 'capac."cap' . $valor['ID_RH'] . '" - NVL (agend."ag' . $valor['ID_RH'] .
                '", 0) "' . $valor['ID_RH'] . '", capac."cap' . $valor['ID_RH'] . '" "c' . $valor['ID_RH'] .
                '", ';

            $sql2 = " SUM(CASE WHEN id_rh = '" . $valor['ID_RH'] . "' ";
            $sql3 = ' THEN capacidad END) "cap' . $valor['ID_RH'] . '", ';

            $sql5 = " COUNT(CASE WHEN id_rh = '" . $valor['ID_RH'] . "'";
            $sql6 = ' THEN 1 END) "ag' . $valor['ID_RH'] . '", ';

            $sql4 .= $sql2 . $sql3;
            $sql7 .= $sql5 . $sql6;
        }

        $sql1 = substr($sql1, 0, strlen($sql1) - 2);
        $sql4 = substr(',' . substr($sql4, 0, strlen($sql4) - 2), 0, strlen(',' . substr
            ($sql4, 0, strlen($sql4) - 2)) - 1) . '"';
        $sql7 = substr(',' . substr($sql7, 0, strlen($sql7) - 2), 0, strlen(',' . substr
            ($sql7, 0, strlen($sql7) - 2)) - 1) . '"';

/** 
* Se hace la validación si esta definido o diferente de vacio el campo $sql4
* @package Claro 
* @author [HITSS – Daniel Rodriguez] 
* @access public 
* @copyright Copyright (c) 2016 Claro Colombia. 
* @license Código Cerrado 
* @version [1] 
*/
        if (trim($idcontratista) == "todos") {
            $sql_contr = " ";
            $order = " ";
            $aliado = "";
        } else {
            $sql_contr = "AND idaliado IN (" . $idcontratista . ")";
            $order = ", idaliado ";
            $aliado = "idaliado, ";
        }
	$filterarray = '';
		if(isset($sql4) && !empty($sql4)  ){
			$filterarray[]="'".$sql4."'";
		}else{
			$filterarray[]="";
		}
		$sqlfilter='';
		if(count($filterarray)>0){
			$sqlfilter= implode($filterarray);
		}else{
			$sqlfilter= '';
		}
		
		$sqlfilter = str_replace ("'", "" , $sqlfilter);
		#echo $sqlfilter; die();
        $sqlCap = "SELECT ".$aliado." TO_CHAR (C.FECHA, 'YYYY/MM/DD') FECHA ".$sqlfilter."
					FROM CAPACIDAD_ALIADOS C
					INNER JOIN RANGO_FECHA F ON F.ID_RF = C.ID_RF
					INNER JOIN CIUDAD_TIPOTRABAJO CT ON  CT.IDCIUDADTT = F.ID_CIUDADTT   
					WHERE CT.ID_TT = '".$tipotrabajo."'
					AND FECHA BETWEEN TO_DATE ('".date('Y/m/d',$diaini)." 00:00:00','YYYY/MM/DD HH24:MI:SS') 
					AND TO_DATE ('".date('Y/m/d',$diafin)." 23:59:59','YYYY/MM/DD HH24:MI:SS')  
					" . $sql_contr . "
					AND CT.CODCIUDAD = '" . $ciudad ."'
					GROUP BY C.FECHA" . $order . "
					ORDER BY C.FECHA";

        $sqlAg = "SELECT " . $aliado . "TO_CHAR (DIAAGENDA, 'YYYY/MM/DD') FECHA " . $sql7 .
            "  FROM AGENDA WHERE ID_TT = '" . $tipotrabajo .
            "' AND DIAAGENDA BETWEEN TO_DATE ('" . date('Y/m/d', $diaini) .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS') AND TO_DATE ('" . date('Y/m/d', $diafin) .
            " 23:59:59','YYYY/MM/DD HH24:MI:SS') " . $sql_contr . " AND CODCIUDAD = '" . $ciudad .
            "' AND ESTADO IN ('V', 'A') GROUP BY DIAAGENDA" . $order . " ORDER BY FECHA";

        $Query = $sqlCap;
        $this->EjecutaConsulta($Query);
        $Capacidad = $this->data;

        $Query = $sqlAg;
        $this->EjecutaConsulta($Query);
        $Agendado = $this->data;

        foreach ($Agendado as $datos) {
            $Uxfecha = $datos['FECHA'];
            $NAgendado[$Uxfecha] = $datos;
        }

        foreach ($Capacidad as $datos) {
            $Uxfecha = $datos['FECHA'];
            $NCapacidad[$Uxfecha] = $datos;
        }

        if (is_array($Capacidad) && count($Capacidad) > 0) {

            foreach ($NCapacidad as $ux => $datas) {
                foreach ($datas as $id => $val) {
                    if ($id == 'IDALIADO') {
                        $PaMostrar[$ux][$id] = $val;
                    }
                    if (substr($id, 0, 3) == 'cap') {
                        $PaMostrar[$ux]['CAPACIDAD'][substr($id, 3)] = $val;
                        if (is_array(@$NAgendado[$ux])) {
                            if (@$NAgendado[$ux]['ag' . substr($id, 3)] != '') {
                                $PaMostrar[$ux]['AGENDADOS'][substr($id, 3)] = $NAgendado[$ux]['ag' . substr($id,
                                    3)];
                            } else {
                                $PaMostrar[$ux]['AGENDADOS'][substr($id, 3)] = 0;
                            }
                        } else {
                            $PaMostrar[$ux]['AGENDADOS'][substr($id, 3)] = 0;
                        }
                    }
                }
            }
        }
        foreach ($RangoHoras1 as $value) {
            $FranjasHorarias[$value['ID_RH']] = $value['INI'] . " - " . $value['FIN'];
        }
        $retorno = array();
        foreach ($PaMostrar as $key => $value) {
            $arrayRetorno = array();
			$verificaID = array();

            foreach ($value as $subkey => $subvalue) {
                if (is_array($subvalue)) {
                    foreach ($subvalue as $subsubkey => $subsubvalue) {
						if(!isset($verificaID[$subsubkey]))
						{
							$arrayRetorno['FECHA'] = $key;
							$arrayRetorno['CIUDAD'] = $ciudad;
							$arrayRetorno['IDALIADO'] = $IdAliado;
							$arrayRetorno['NOMBREALIADO'] = $nomcontratista;
							$arrayRetorno['TIPOTRABAJO'] = $nomtrabajo;
							$arrayRetorno['FRANJAHORA'] = $FranjasHorarias[$subsubkey];
							$arrayRetorno['CAPACIDAD'] = $value['CAPACIDAD'][$subsubkey];
							$arrayRetorno['AGENDADOS'] = $value['AGENDADOS'][$subsubkey];
							$arrayRetorno['DIFCPAAGE'] = $value['CAPACIDAD'][$subsubkey] - $value['AGENDADOS'][$subsubkey];
							$retorno[] = $arrayRetorno;
							$verificaID[$subsubkey]= $subsubkey;
						}
                    }
                } else {
                    $IdAliado = $subvalue;
                }

            }
        }

        return $retorno;
    }

    function InformeCargueUnidades($FechaI, $FechaF, $numero, $calle, $apartamento,
        $Ciudad, $division, $zip, $respuesta, $newNumero, $newCalle, $newApartamento, $newCiudad,
        $newDivision, $newCiudad)
    {
        /*INFORME  CARGUE UNIDADES*/
        //echo $FechaI.PHP_EOL.$FechaF.PHP_EOL;
        //print_r($Ciudad);
        $cadena = '';
        $this->data = '';
        $sql = " SELECT LOG.ID_LOG, US.USUARIO, LOG.NUMERO, LOG.CALLE, LOG.APARTAMENTO, CIUDAD,CCIUDAD(LOG.CIUDAD) AS CIUDAD, 
			LOG.DIVISION, LOG.NEWNUMERO, LOG.NEWCALLE, LOG.NEWAPARTAMENTO, LOG.NEWCIUDAD,CCIUDAD(LOG.NEWCIUDAD) AS NEWCIUDAD, 
			LOG.NEWDIVISION, LOG.NEWZIP, LOG.RESPUESTA, LOG.FECHA
			FROM LOG_ACT_UNIDAD LOG INNER JOIN USUARIOS US ON (LOG.IDUSUARIO = US.ID_USUARIO)
			 WHERE                                         
				LOG.FECHA BETWEEN  TO_DATE ('" . $FechaI .
            " 00:00:00','YYYY/MM/DD HH24:MI:SS')   
				AND TO_DATE ('" . $FechaF . " 23:59:59','YYYY/MM/DD HH24:MI:SS')";
        if (isset($numero) && $numero != '') {
            $cadena = " AND LOG.NUMERO=" . $numero;
        }
        if (isset($calle) && $calle != '') {
            $cadena = " AND LOG.CALLE='" . $calle . "'";
        }
        if (isset($apartamento) && $apartamento != '') {
            $cadena = " AND LOG.APARTAMENTO='" . $apartamento . "'";
        }
        if (isset($division) && $division != '') {
            $cadena = " AND LOG.DIVISION='" . $division . "'";
        }
        if (isset($zip) && $zip != '') {
            $cadena = " AND LOG.NEWZIP=" . $zip;
        }
        if ($Ciudad[0] != "TODOS") {
            $cadena .= " AND LOG.CIUDAD = '" . $Ciudad[0] . "'";
        }
        if (isset($newNumero) && $newNumero != '') {
            $cadena = " AND LOG.NEWNUMERO=" . $newNumero;
        }
        if (isset($newCalle) && $newCalle != '') {
            $cadena = " AND LOG.NEWCALLE='" . $newCalle . "'";
        }
        if (isset($newApartamento) && $newApartamento != '') {
            $cadena = " AND LOG.NEWAPARTAMENTO='" . $newApartamento . "'";
        }
        if (isset($newDivision) && $newDivision != '') {
            $cadena = " AND LOG.NEWDIVISION='" . $newDivision . "'";
        }
        if ($newCiudad[0] != "TODOS") {
            $cadena .= " AND LOG.NEWCIUDAD = '" . $newCiudad[0] . "'";
        }
        if ($respuesta == '0') {
            $cadena .= " AND LOG.RESPUESTA = '" . $respuesta . "'";
        }


        $sql .= $cadena;
        $this->EjecutaConsulta($sql);

        return $this->data;
    }


    function InformeLlamadasAbiertas($FechaI, $FechaF, $Regional, $Aliado, $Carpeta)
    {

        list($a1, $m1, $d1) = explode("/", $FechaI);
        $FechaInicial = $d1 . "/" . $m1 . "/" . $a1;
        list($a1, $m1, $d1) = explode("/", $FechaF);
        $FechaFinal = $d1 . "/" . $m1 . "/" . $a1;
        if (is_array($Regional)) {
            $Regional = implode("','", $Regional);
        }
        $Carpeta = implode("','", $Carpeta);
        $Aliado = implode(",", $Aliado);

       $sql = "CALL TVCABLEEXE.SP_SENTENCIAS_SQL('
					SELECT SCSCÑ,SCSTAT, SCCALC||SCCALY||''/''||SCCALM||''/''||SCCALD AS FECHA, SCACCT, TRIM(SCHOME)||''  ''||TRIM(STSTNM)||''  ''||TRIM(SCAPTÑ) AS DIRECCION 
					FROM CABLEDTA/SERVCAL1 INNER JOIN CABLEDTA/STRTMSL2 ON STSTRÑ = SCSTRÑ
					WHERE  SCSTAT = ''N'' 
					AND TO_DATE (SCCALC||SCCALY||''/''||SCCALM||''/''||SCCALD, ''YYYY/MM/DD'')
					>= TO_DATE (''".$FechaI."'', ''YYYY/MM/DD'') 
					ORDER BY SCSCÑ ')";

        $this->EjecutaConsultaRR($sql);

        $Ddato = array();

        $cortar = array_chunk($this->dataRR, 50, true);

        foreach ($cortar as $indice1 => $consulta) {

            $ordenTrabajos = "";

            foreach ($consulta as $indice => $valor) {

                $ordenTrabajos .= ",'" . $valor['SCSCÑ'] . "'";
                $Ddato[$valor['SCSCÑ']] = $valor;
                $Ddato[$valor['SCSCÑ']]['CUENTA'] = '';
                $Ddato[$valor['SCSCÑ']]['ORDEN'] = '';
                $Ddato[$valor['SCSCÑ']]['PROGRAMACION'] = '';
                $Ddato[$valor['SCSCÑ']]['ESTADO'] = '';
                $Ddato[$valor['SCSCÑ']]['FECHA_CREACION'] = '';
                $Ddato[$valor['SCSCÑ']]['CODNODO'] = '';
                $Ddato[$valor['SCSCÑ']]['NODO'] = '';
                $Ddato[$valor['SCSCÑ']]['ESTRUCTURA'] = '';
                $Ddato[$valor['SCSCÑ']]['RESULTADO'] = '';
                $Ddato[$valor['SCSCÑ']]['DIAAGENDA'] = '';
                $Ddato[$valor['SCSCÑ']]['CARPETA'] = '';
                $Ddato[$valor['SCSCÑ']]['ALIADO'] = '';
                $Ddato[$valor['SCSCÑ']]['REGIONAL'] = '';
            }


            $ordenTrabajos = substr($ordenTrabajos, 1, strlen($ordenTrabajos));

            if ($ordenTrabajos != '') {

                $sql = "SELECT A.CUENTA,
                  A.IDORDEN_DE_TRABAJO ORDEN,
                  A.PROGRAMACION,
                  A.ESTADO,
                  A.FECHA_AGENDO FECHA_CREACION,
                  A.CODNODO,
                  CNODO(A.CODNODO) NODO,
                  CONSULTA_ESTRUCTURA(A.CODNODO) ESTRUCTURA,
                  A.CODRESULTADO RESULTADO,
                  A.DIAAGENDA,
                  CTIPOTRABAJO(A.ID_TT ) AS CARPETA,
                  CALIADO(A.IDALIADO) AS ALIADO,
                  CREGIONAL (A.CODCIUDAD) AS REGIONAL
                FROM AGENDA A INNER JOIN RR_NODOS N ON N.CODIGO = A.CODNODO 
                WHERE A.PROGRAMACION='L' AND A.FECHA_AGENDO BETWEEN TO_DATE('" . $FechaInicial .
                    " 00:00:00','DD/MM/YYYY HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                    " 23:59:59','DD/MM/YYYY HH24:MI:SS')
                     AND A.IDORDEN_DE_TRABAJO IN (" . $ordenTrabajos . ") ";


                if ($Regional != "TODOS") {
                    $sql .= " AND N.CODREGIONAL IN ('" . $Regional . "')";
                }

                if ($Carpeta != "TODOS") {
                    $sql .= " AND A.ID_TT IN ('" . $Carpeta . "')";
                }

                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (" . $Aliado . ")";
                }

                $this->EjecutaConsulta($sql);


                foreach ($this->data as $indice => $valorRR) {

                    $Ddato[$valorRR['ORDEN']]['CUENTA'] = $valorRR['CUENTA'];
                    $Ddato[$valorRR['ORDEN']]['ORDEN'] = $valorRR['ORDEN'];
                    $Ddato[$valorRR['ORDEN']]['PROGRAMACION'] = $valorRR['PROGRAMACION'];
                    $Ddato[$valorRR['ORDEN']]['FECHA_CREACION'] = $valorRR['FECHA_CREACION'];
                    $Ddato[$valorRR['ORDEN']]['CODNODO'] = $valorRR['CODNODO'];
                    $Ddato[$valorRR['ORDEN']]['NODO'] = $valorRR['NODO'];
                    $Ddato[$valorRR['ORDEN']]['ESTRUCTURA'] = $valorRR['ESTRUCTURA'];
                    $Ddato[$valorRR['ORDEN']]['ESTADO'] = $valorRR['ESTADO'];
                    $Ddato[$valorRR['ORDEN']]['RESULTADO'] = $valorRR['RESULTADO'];
                    $Ddato[$valorRR['ORDEN']]['DIAAGENDA'] = $valorRR['DIAAGENDA'];
                    $Ddato[$valorRR['ORDEN']]['CARPETA'] = $valorRR['CARPETA'];
                    $Ddato[$valorRR['ORDEN']]['ALIADO'] = $valorRR['ALIADO'];
                    $Ddato[$valorRR['ORDEN']]['REGIONAL'] = $valorRR['REGIONAL'];

                }
             

            }

            unset($cortar[$indice1]);

        }
        return $Ddato;
    }

    function LogFraudes($FechaI, $FechaF, $Regional, $Aliado, $Carpeta)
    {

        list($a1, $m1, $d1) = explode("/", $FechaI);
        $FechaInicial = $d1 . "/" . $m1 . "/" . $a1;
        list($a1, $m1, $d1) = explode("/", $FechaF);
        $FechaFinal = $d1 . "/" . $m1 . "/" . $a1;
        if (is_array($Regional)) {
            $Regional = implode("','", $Regional);
        }
        $Carpeta = implode("','", $Carpeta);
        $Aliado = implode(",", $Aliado);

            $ordenTrabajos = "";
            $ordenTrabajos = substr($ordenTrabajos, 1, strlen($ordenTrabajos));

            if ($ordenTrabajos == '') {

               $sql = "SELECT
                    	A.IDALIADO,RRC.CODREGIONAL AS REGIONAL,A.CODCIUDAD,A.FECHA_AGENDO,A.ID_TT,A.CUENTA,A.IDORDEN_DE_TRABAJO AS ORDEN,L.ELEMENTO,L.VALOR,L.MODIFICACION,L.IDUSUARIO,US.NOMBRE,P.DESCRIPCION,A.IDUSUARIO AS USUARIO,L.ACCION
                    FROM
                    	AGENDA A
                    INNER JOIN LOG_CO L ON A .IDAGENDA = L.IDAGENDA
                    INNER JOIN RR_CIUDADES RRC ON A .CODCIUDAD = RRC.CODIGO
                    INNER JOIN USUARIOS US ON US.ID_USUARIO = L.IDUSUARIO
                    INNER JOIN US_PERFILES P ON P.ID_PERFIL = US.ID_PERFIL
                    WHERE
                     L.MODIFICACION BETWEEN TO_DATE('" . $FechaInicial .
                    " 00:00:00','DD/MM/YYYY HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                    " 23:59:59','DD/MM/YYYY HH24:MI:SS')";

                if ($Carpeta != "TODOS") {
                    $sql .= " AND A.ID_TT IN ('".$Carpeta."')";
                }

                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
                
                //echo $sql;
                $this->EjecutaConsulta($sql);
                
                set_time_limit(0);
                return $this->data;
             

            }

    }
	
	function InformeCierresOperacion($FechaI, $FechaF, $Aliado, $Regional, $Ciudad, $Carpeta)
    {
        list($a1, $m1, $d1) = explode("/", $FechaI);
        $FechaInicial = $d1 . "/" . $m1 . "/" . $a1;
        list($a1, $m1, $d1) = explode("/", $FechaF);
        $FechaFinal = $d1 . "/" . $m1 . "/" . $a1;
		
		$rescarpeta = array();
		$resciudad = array();
		$resregional = array();
		
		if(count($Aliado)>1)
		{
			foreach ($Aliado as $aliado) 
			{
				if (is_numeric($aliado)) 
				{
					$resaliado[] = $aliado; 
				}
			} 	
		}else
		{
			$resaliado = $Aliado;
		}
		
		if(count($Regional)>1)
		{
			foreach ($Regional as $regional) 
			{
				if ($regional != "TODOS") 
				{
					$resregional[] = $regional; 
				}
			} 	
		}else
		{
			$resregional = $Regional;
		}
		
		
		if(count($Ciudad)>1)
		{
			foreach ($Ciudad as $ciudad) 
			{
				if ($ciudad != "TODOS") 
				{
					$resciudad[] = $ciudad; 
				}
			} 	
		}else
		{
			$resciudad = $Ciudad;
		}
		if(count($Carpeta)>1)
		{
			foreach ($Carpeta as $carpeta) 
			{
				if (is_numeric($carpeta)) 
				{
					$rescarpeta[] = $carpeta; 
				}
			} 	
		}else
		{
			$rescarpeta = $Carpeta;
		}
		
		$resaliado = implode(",", $resaliado);
		$resregional = implode("','", $resregional);
        $resciudad = implode("','", $resciudad);
		$rescarpeta = implode(",", $rescarpeta);
	

        $this->data = "";
        $sql = "SELECT DISTINCT * FROM (
				SELECT  AL.NOMBRE AS ALIADO,AG.DIAAGENDA,N.CODREGIONAL,AG.CODCIUDAD,TT.NOMBRE_TIPO,AG.CUENTA,AG.IDORDEN_DE_TRABAJO,
				EAG.NOMBRE_ESTADO_AGENDA,(RH.HINICIAL_RH||'-'||RH.HFINAL_RH) AS  FRANJA,AG.CODRESULTADO,RVI.DESCRIPCION,
				CASE WHEN CO.IDUSUARIOS IS NOT NULL AND TO_DATE(CO.FECHACIERRE,'YYYY/MM/DD HH24:MI:SS')-TO_DATE(AG.DIAAGENDA,'YYYY/MM/DD HH24:MI:SS') < 1 THEN 'Y' ELSE 'N' END AS CIERRE, 
				CASE WHEN CO.IDUSUARIOS IS NOT NULL AND TO_DATE(CO.FECHACIERRE,'YYYY/MM/DD HH24:MI:SS')-TO_DATE(AG.DIAAGENDA,'YYYY/MM/DD HH24:MI:SS') < 1 THEN TO_CHAR(USU.NOMBRE) ELSE '' END  AS IDUSUARIOS,
				CASE WHEN CO.IDUSUARIOS IS NOT NULL AND TO_DATE(CO.FECHACIERRE,'YYYY/MM/DD HH24:MI:SS')-TO_DATE(AG.DIAAGENDA,'YYYY/MM/DD HH24:MI:SS') < 1 THEN TO_CHAR(CO.FECHACIERRE) ELSE '' END  AS FECHACIERRE,
				CASE WHEN CO.IDUSUARIOS IS NOT NULL AND TO_DATE(CO.FECHACIERRE,'YYYY/MM/DD HH24:MI:SS')-TO_DATE(AG.DIAAGENDA,'YYYY/MM/DD HH24:MI:SS') >= 1 THEN 'Y' ELSE 'N' END AS CIERRE_ESP, 
				CASE WHEN CO.IDUSUARIOS IS NOT NULL AND TO_DATE(CO.FECHACIERRE,'YYYY/MM/DD HH24:MI:SS')-TO_DATE(AG.DIAAGENDA,'YYYY/MM/DD HH24:MI:SS') >= 1 THEN TO_CHAR(USU.NOMBRE) ELSE '' END  AS IDUSUARIOS_ESP,
				CASE WHEN CO.IDUSUARIOS IS NOT NULL AND TO_DATE(CO.FECHACIERRE,'YYYY/MM/DD HH24:MI:SS')-TO_DATE(AG.DIAAGENDA,'YYYY/MM/DD HH24:MI:SS') >= 1 THEN TO_CHAR(CO.FECHACIERRE) ELSE '' END  AS FECHACIERRE_ESP
				FROM AGENDA AG
				INNER JOIN TIPO_TRABAJO TT ON TT.ID_TT = AG.ID_TT
				LEFT JOIN RR_NODOS N ON N.CODIGO = AG.CODNODO
				LEFT JOIN ALIADOS AL ON AL.IDALIADO = AG.IDALIADO
				LEFT JOIN ESTADO_AGENDA EAG ON EAG.ID_ESTADO_AGENDA = AG.ESTADO
				LEFT JOIN RANGO_HORA RH ON RH.ID_RH = AG.ID_RH
				LEFT JOIN RANGO_FECHA RF ON RF.ID_RF = RH.ID_RF
				LEFT JOIN RESULTADO_VISITA RVI ON RVI.CODRESULTADO = AG.CODRESULTADO
				LEFT JOIN CIERRE_OPERACION CO ON CO.ID_TT = TT.ID_TT AND AG.DIAAGENDA = CO.DIAAGENDA 
				AND AG.IDALIADO = CO.IDALIADO AND AG.CODCIUDAD = CO.CODCIUDAD
				LEFT JOIN USUARIOS USU ON USU.ID_USUARIO = CO.IDUSUARIOS
				WHERE TT.ACTIVO = 'Y' AND 
				AG.DIAAGENDA BETWEEN TO_DATE('".$FechaInicial." 00:00:00','DD/MM/YYYY HH24:MI:SS') AND TO_DATE('".$FechaFinal." 23:59:59','DD/MM/YYYY HH24:MI:SS')
				";

        if ($resaliado != "TODOS") {
            $sql .= " AND AG.IDALIADO IN (" . $resaliado . ")";
}
		if ($resregional != "TODOS") {
            $sql .= " AND N.CODREGIONAL IN ('" . $resregional . "')";
        }
        if ($resciudad != "TODOS") {
            $sql .= " AND AG.CODCIUDAD IN ('" . $resciudad . "')";
        }
        if ($rescarpeta != "TODOS") {
            $sql .= " AND AG.ID_TT IN (" . $rescarpeta . ")";
        }
		$sql .= ")";
        $this->EjecutaConsulta($sql);
        return $this->data;
    }
    
function Reincidencia($FechaInicial, $FechaFinal, $Aliado, $pyme, $ok)
    {
          $Aliado = implode(",", $Aliado);
               $sql = "select a.IDORDEN_DE_TRABAJO ticket , a.CUENTA cuenta , a.TIPO_USER tip_cli, a.CODRESULTADO estado,  a.APTO2 ||' '||d.NOMBRE , c.USUARIO , e.DESCRIPCION , to_char(b.FECHA_NOTA ,'yyyy-MM-dd') fechanota, 
to_char(b.FECHA_NOTA ,'HH24:MI') horanota, N5.DESCRIPCION as tipificacion, ltrim(rtrim(b .RAZON || ' - ' || b .SUBRAZON )), ltrim(rtrim(b.NOTA)),  A.TIPO_USER AS CODIGO_SEG
                       from agenda a left join TCK_NOTAS_LLAMADA b on a.IDORDEN_DE_TRABAJO=b.ID_LLAMADA
                       inner join USUARIOS c on c.ID_USUARIO= b.USUARIO_RR 
                       inner join RR_SERV_AFECTADO d on d.CODIGO= a.APTO2
                       left join TICKETS d on d.ID_LLAMADA= a.IDORDEN_DE_TRABAJO 
					   inner JOIN TCK_RAZONES_NOTAS N5 ON B.RAZON=N5.COD_RAZON AND B.SUBRAZON=N5.COD_SUBRAZON
                       INNER JOIN US_CARGOS e on c.COD_CARGO= e.COD_CARGO  where a.PROGRAMACION ='T' 
					   and b.FECHA_NOTA BETWEEN TO_DATE('" . $FechaInicial .
                       " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                       " 23:59:59','YYYY-MM-DD HH24:MI:SS')
					   ";

              
                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " ORDER BY b.FECHA_NOTA ASC ";
                //echo $sql;
                $this->EjecutaConsulta($sql);
                
                set_time_limit(0);
                return $this->data;
             

    }
	
		function consultaRazones($cuentas, $OT)
	{
	    //imprimir($cuentas);
		if ($cuentas != '') {

                $sql = "  SELECT SHWRK1, SHWRK2, SHWRK3 
						  FROM ".CDATALIB.".SERVHIST 
						  WHERE SHSCÑ=".$OT."
						  AND SHACCT=".$cuentas."
						  ";
				
                $this->EjecutaConsultaRR($sql);
				
				if($this->dataRR<>array())
				{
					//$Ddato = '';
					foreach ($this->dataRR as $indice => $valorRR) {
						$Ddato['NIVEL1'] = @$valorRR['SHWRK1'];
						$Ddato['NIVEL2'] = @$valorRR['SHWRK2'];
						$Ddato['NIVEL3'] = @$valorRR['SHWRK3'];
					}
				}else
				{
				     $Ddato = array("NIVEL1"=>'', "NIVEL2"=>'', "NIVEL3"=>'');
				}
            //imprimir($Ddato);
			return $Ddato;
			
			}

	
	}
    
    function Masivos($FechaInicial, $FechaFinal,$Aliado, $pyme, $ok)
    {
				
               $Aliado = implode(",", $Aliado);
               $sql = "select a.IDORDEN_DE_TRABAJO ticket, a.CUENTA CUENTA, a.TIPO_USER tip_cli, a.CODRESULTADO ESTADO,a.APTO2 ||' '||d.NOMBRE,k.USUARIO, j.DESCRIPCION ,to_char(a.FECHA_AGENDO ,'yyyy-MM-dd') fechacrea, 
				to_char(a.FECHA_AGENDO ,'HH24:MI') horacrea,c.NOMBRE SINTOMA, DECODE(TRIM(TRANSLATE(a.AVISOSAP,'0123456789',' ')), NULL, a.AVISOSAP,'') as aviso, to_char(g.FECHAINGRESO ,'yyyy-MM-dd') fechacierre,to_char(g.FECHAINGRESO ,'HH24:MI') horacierra, l.USUARIO AS USU_RES, m.DESCRIPCION AS DESC_CARGO,
				 REGEXP_REPLACE(replace(replace(trim(g.NOTA),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') NOTA, 
				 g.TIPO_CIERRE_PQR, 
				 A.TIPO_USER AS CODIGO_SEG,
				CASE 
					WHEN 
				TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2) IS NULL
				THEN 
					CASE 
					WHEN 
						 TRUNC(abs((X.SALIDA-a.FECHA_AGENDO)*24),2) IS NULL
					THEN 
						 TRUNC(abs((g.FECHAINGRESO-a.FECHA_AGENDO)*24),2)
					ELSE
						 TRUNC(abs((X.SALIDA-a.FECHA_AGENDO)*24),2)
				END
				 WHEN
					TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2) IS NULL 
				THEN
					TRUNC(abs((g.FECHAINGRESO-a.FECHA_AGENDO)*24),2)
				ELSE
					TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2)
				END,
				CASE 
					WHEN 
						 x.SALIDA IS NULL
					THEN 
						 g.FECHAINGRESO
					ELSE
						 x.SALIDA
				END
				from agenda a left JOIN TCK_CIERRE_PQR_LLS_VISOR g on g.IDAGENDA=a.IDAGENDA
				left join RR_ESTADO_LLS c on c.CODIGO=a.NUMDIR2 
				left join RR_SERV_AFECTADO d on d.CODIGO= a.APTO2
				LEFT JOIN TICKETS b on b.ID_AGENDA=a.IDAGENDA
				left join USUARIOS k on k.ID_USUARIO= a.idusuario
				left join USUARIOS l on l.ID_USUARIO= g.idusuario
				left JOIN US_CARGOS j on j.COD_CARGO=k.COD_CARGO
				left JOIN US_CARGOS m on m.COD_CARGO=l.COD_CARGO
				left join RR_RAZONES_LLS N1 on N1.CODIGO=SUBSTR(g.NIVELESLLS , 0,3)
				left join RR_RAZONES_LLS N2 on N2.CODIGO=SUBSTR(g.NIVELESLLS , 5,3)
				left join RR_RAZONES_LLS N3 on N3.CODIGO=SUBSTR(g.NIVELESLLS , 9,3)
				left join (select DISTINCT HIS_ID_AGENDA, MAX(HIS_HORA_SAL) AS SALIDA from HIS_PQR_ESCALAMIENTO GROUP BY HIS_ID_AGENDA ORDER BY SALIDA DESC) x  on x.HIS_ID_AGENDA = a.IDAGENDA
				where a.PROGRAMACION='T' 
				AND length(translate(trim(a.AVISOSAP),' +-.0123456789',' ')) is null and a.AVISOSAP is not null and a.AVISOSAP <> 0
				and a.FECHA_AGENDO BETWEEN TO_DATE('" . $FechaInicial ." 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal ." 23:59:59','YYYY-MM-DD HH24:MI:SS') 
				";

                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " order by a.FECHA_AGENDO desc ";
                //echo $sql;
                $this->EjecutaConsulta($sql);
				$queryCompleto = $this->data;
				//imprimir($queryCompleto);
				//die;
				$nivelesRRTickets = array();
				
				foreach ($queryCompleto as $id => $valorData) 
				{
				   $nivelesRRTickets = $this->consultaRazones($valorData['CUENTA'], $valorData['TICKET']);
				   //imprimir($nivelesRRTickets);
				   //die;
				   if($nivelesRRTickets<>array())
				   {
				       $queryNombreNivelesOpe = array();
					   if(@$nivelesRRTickets['NIVEL1']!='')
					   {
						$sql = "select TCKT.NIVEL1, TCKT.NIVEL2, TCKT.NIVEL3 
							   FROM TCK_RESPUESTAS_PQR TCKT
							   LEFT JOIN WRAZON_LLS RAOPE ON (RAOPE.CODIGO = TCKT.NIVEL3)
					           WHERE TCKT.NIVEL3 IN ('".@$nivelesRRTickets['NIVEL1']."') 
							   AND TCKT.ESTADO = 'A'
							   ";
						$this->EjecutaConsulta($sql);
						$queryNombreNivelesOpe = $this->data;
						
						if($queryNombreNivelesOpe<>array())
						{
						   @$nivelesRRTickets = $queryNombreNivelesOpe[0];
						}
					   }
					   
					   $sql = "select NOMBRE 
							   FROM RR_RAZONES_LLS 
					           WHERE CODIGO IN ('".@$nivelesRRTickets['NIVEL1']."', '".@$nivelesRRTickets['NIVEL2']."', '".@$nivelesRRTickets['NIVEL3']."')
							   ";
					   $this->EjecutaConsulta($sql);
					   $queryNombreNiveles = $this->data;
                       //imprimir($queryNombreNiveles);
					   if($queryNombreNiveles<>array())
					   {
					      foreach($queryNombreNiveles as $id2 => $valorData2)
						  {
							  $count = $id2+1;
							  $valorData['NIVEL'.$count]= $valorData2['NOMBRE'];
						  }
					   }else
					   {
					      $valorData['NIVEL1']='';
						  $valorData['NIVEL2']='';
						  $valorData['NIVEL3']='';
					   }
				   }
				   
				   $valores[] = $valorData;
				}
				 set_time_limit(0);
				return $valores;
				//die;
    }
    
     function Mtositio($FechaInicial, $FechaFinal,$Aliado, $pyme, $ok)
    {


               $Aliado = implode(",", $Aliado);
               $sql = "select a.IDORDEN_DE_TRABAJO ticket, a.CUENTA CUENTA, a.CODCIUDAD ciudad, a.CLL || ' ' ||A.NUMDIR || ' ' || A.APTO  AS DIRECCION, a.TELEFONO1 telefono,a.CODNODO nodo,  a.CODRESULTADO ESTADO,p.ser ||' '||d.NOMBRE servafec,to_char(a.FECHA_AGENDO,'yyyy-MM-dd') fechacrea, 
                       to_char(a.FECHA_AGENDO ,'HH24:MI') horacrea,a.DIAAGENDA fechaagenda , a.IDALIADO, to_char(a.HORA_LLEGADA ,'HH24:MI') hlleagen, to_char(a.HORA_SALIDA ,'HH24:MI') hsalagen,a.IDMOVIL movil, a.DEMORA demora ,
                       f.USUARIO areares, h.DESCRIPCION  areacier, a.CODRESULTADO resvisita, 
					   REGEXP_REPLACE(replace(replace(trim(g.NOTA),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') notacie, 
					   g.TIPO_CIERRE_PQR proce, 
					   A.ESTADO AS ESTADO_LLS, A.TIPO_USER AS CODIGO_SEG 
                       from agenda a LEFT JOIN TCK_CIERRE_PQR_LLS_VISOR g on g.IDAGENDA=a.IDAGENDA
                       left join RR_ESTADO_LLS c on c.CODIGO=a.NUMDIR2 
                       left JOIN TICKETS b on b.ID_AGENDA=a.IDAGENDA
                       left join USUARIOS f on f.ID_USUARIO=g.idusuario
                       left join USUARIOS k on k.ID_USUARIO= a.idusuario
                       left JOIN US_CARGOS h on h.COD_CARGO=F.COD_CARGO
                       left JOIN US_CARGOS j on j.COD_CARGO=k.COD_CARGO
					   left join RR_RAZONES_LLS N1 on N1.CODIGO=SUBSTR(g.NIVELESLLS , 0,3)
					   left join RR_RAZONES_LLS N2 on N2.CODIGO=SUBSTR(g.NIVELESLLS , 5,3)
					   left join RR_RAZONES_LLS N3 on N3.CODIGO=SUBSTR(g.NIVELESLLS , 9,3)
                       left join (select trim(IDORDEN_DE_TRABAJO) ot, APTO2 ser from agenda where  PROGRAMACION='T' AND IDALIADO='5000000012') p on trim(a.IDORDEN_DE_TRABAJO)=p.ot
                       left join RR_SERV_AFECTADO d on d.CODIGO= p.ser
                       where a.PROGRAMACION='L' AND
                       a.FECHA_AGENDO BETWEEN TO_DATE('" . $FechaInicial .
                       " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                       " 23:59:59','YYYY-MM-DD HH24:MI:SS')";

              
                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " order by a.FECHA_AGENDO desc ";
                //echo $sql;
                $this->EjecutaConsulta($sql);
				$queryCompleto = $this->data;
				//imprimir($queryCompleto);
				//die;
				$nivelesRRTickets = array();
				
				foreach ($queryCompleto as $id => $valorData) 
				{
				   $nivelesRRTickets = $this->consultaRazones($valorData['CUENTA'], $valorData['TICKET']);
				   //imprimir($nivelesRRTickets);
				   //die;
				   if($nivelesRRTickets<>array())
				   {
				       $queryNombreNivelesOpe = array();
					   if(@$nivelesRRTickets['NIVEL1']!='')
					   {
						$sql = "select TCKT.NIVEL1, TCKT.NIVEL2, TCKT.NIVEL3 
							   FROM TCK_RESPUESTAS_PQR TCKT
							   LEFT JOIN WRAZON_LLS RAOPE ON (RAOPE.CODIGO = TCKT.NIVEL3)
					           WHERE TCKT.NIVEL3 IN ('".@$nivelesRRTickets['NIVEL1']."') 
							   AND TCKT.ESTADO = 'A'
							   ";
						$this->EjecutaConsulta($sql);
						$queryNombreNivelesOpe = $this->data;
						
						if($queryNombreNivelesOpe<>array())
						{
						   @$nivelesRRTickets = $queryNombreNivelesOpe[0];
						}
					   }
					   
					   $sql = "select NOMBRE 
							   FROM RR_RAZONES_LLS 
					           WHERE CODIGO IN ('".@$nivelesRRTickets['NIVEL1']."', '".@$nivelesRRTickets['NIVEL2']."', '".@$nivelesRRTickets['NIVEL3']."')
							   ";
					   $this->EjecutaConsulta($sql);
					   $queryNombreNiveles = $this->data;
                       //imprimir($queryNombreNiveles);
					   if($queryNombreNiveles<>array())
					   {
					      foreach($queryNombreNiveles as $id2 => $valorData2)
						  {
							  $count = $id2+1;
							  $valorData['NIVEL'.$count]= $valorData2['NOMBRE'];
						  }
					   }else
					   {
					      $valorData['NIVEL1']='';
						  $valorData['NIVEL2']='';
						  $valorData['NIVEL3']='';
					   }
				   }
				   
				   $valores[] = $valorData;
				}
				set_time_limit(0);
				return $valores;
                
                //set_time_limit(0);
                //return $this->data;


    }
    function Noc($FechaInicial, $FechaFinal,$Aliado, $pyme, $ok)
    {
$Aliado = implode(",", $Aliado);
              $sql = "select a.IDORDEN_DE_TRABAJO ticket, a.CUENTA CUENTA,a.CODCIUDAD CIUDAD, A.NUMDIR AS DIRECCION,  b.LINEA_TELEFONICA,a.CODNODO nodo, a.CODRESULTADO ESTADO,a.APTO2 ||' '||d.NOMBRE,to_char(a.FECHA_AGENDO ,'yyyy-MM-dd') fechacrea, 
to_char(a.FECHA_AGENDO ,'HH24:MI') horacrea,c.NOMBRE SINTOMA, DECODE(TRIM(TRANSLATE(a.AVISOSAP,'0123456789',' ')), NULL, a.AVISOSAP,'') aviso,
to_char(a.FECHA_AGENDO,'yyyy-MM-dd') fechaescala,
to_char(a.FECHA_AGENDO ,'HH24:MI') horaescal, 
f.USUARIO usuesc, 
h.NOMBRE  areaesc,
 -- x.HIS_OBSERVACION,
REGEXP_REPLACE(replace(replace(trim(HIST.HIS_OBSERVACION),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') notacie, 
j.NOMBRE, 
 A.TIPO_USER AS CODIGO_SEG,
CASE 
	WHEN 
 TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2) IS NULL 
THEN 
  TRUNC(abs((HIST.HIS_HORA_SAL-a.FECHA_AGENDO)*24),2)
ELSE
  TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2)
END,
CASE 
	WHEN 
	 x.SALIDA IS NULL
THEN 
	 g.FECHAINGRESO
ELSE
	 x.SALIDA
END
from agenda a 
left JOIN TCK_CIERRE_PQR_LLS_VISOR g on g.IDAGENDA=a.IDAGENDA
left join RR_ESTADO_LLS c on c.CODIGO=a.NUMDIR2 
left join RR_SERV_AFECTADO d on d.CODIGO= a.APTO2
left JOIN TICKETS b on b.ID_AGENDA=a.IDAGENDA
left join (select HIS_ID_AGENDA, MAX(HIS_HORA_ENT) AS SALIDA, MAX(HIS_ID) AS VALOR from HIS_PQR_ESCALAMIENTO  GROUP BY HIS_ID_AGENDA) x  on x.HIS_ID_AGENDA = a.IDAGENDA
LEFT JOIN HIS_PQR_ESCALAMIENTO HIST ON (HIST.HIS_ID = X.VALOR)
left join USUARIOS f on f.ID_USUARIO=HIST.HIS_USUARIO
INNER JOIN ALIADOS h on h.IDALIADO=HIST.HIS_AREA_ESCALA
INNER JOIN ALIADOS J on J.IDALIADO=HIST.HIS_AREA_ESCALADA
where a.PROGRAMACION='T' ";
  if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
                else{
                    $sql .= "AND A.IDALIADO IN (Select c.IDALIADO from ALIADOS c where c.NOMBRE like ('%NOC%'))";   
                }                     
                    $sql.=" and a.FECHA_AGENDO BETWEEN TO_DATE('" . $FechaInicial .
                       " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                       " 23:59:59','YYYY-MM-DD HH24:MI:SS')";

                
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
               
			   
			    if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " order by a.FECHA_AGENDO desc ";

                $this->EjecutaConsulta($sql);
                
                set_time_limit(0);
                return $this->data;


    }
    
    function PQR_OPE($FechaInicial, $FechaFinal,$Aliado, $pyme, $ok, $pqr)
    {
	$valores=array();
if($pqr == 'NO'){$pqr = ' LEFT ';}else{$pqr = ' INNER ';}
$Aliado = implode(",", $Aliado);
               $sql = "select distinct a.IDORDEN_DE_TRABAJO ticket, b.NUM_PQR PQR,a.CUENTA cuenta ,a.SUSCRIPTOR,  a.TIPO_USER tip_cli,b.TIPO_PQR TIPPQR, a.CODRESULTADO ESTADO, b.RECIBER_METHOD medio,d.NOMBRE,  l.NOMBRE  areaesc, 
						 to_char(a.FECHA_AGENDO ,'yyyy-MM-dd') fechacrea, 
						 to_char(a.FECHA_AGENDO ,'HH24:MI') horacrea,
						 i.USUARIO USUCREA, c.NOMBRE SINTOMA,  b.RAZON_LLAMADA || ' ' || B.SUBRAZON_LLAMADA,  
						 DECODE(TRIM(TRANSLATE(a.AVISOSAP,'0123456789',' ')), NULL, a.AVISOSAP,'') aviso,
						 CASE
						 WHEN 
						   g.FECHAINGRESO IS NULL AND a.CODRESULTADO = 'OK'
						 THEN 
						   to_char(a.HORA_SALIDA,'yyyy-MM-dd')
						 ELSE
						   to_char(g.FECHAINGRESO,'yyyy-MM-dd')
						 END AS fechacierre,
						 CASE
						 WHEN 
						   g.FECHAINGRESO IS NULL
						 THEN 
						   to_char(a.HORA_SALIDA ,'HH24:MI')
						 ELSE
						   to_char(g.FECHAINGRESO ,'HH24:MI')
						 END AS horacierre,
						 f.USUARIO usucierra, 
						 h.DESCRIPCION  areacier,
						 REGEXP_REPLACE(replace(replace(trim(g.NOTA),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') NOTA, 
						 g.TIPO_CIERRE_PQR,
						 b.num_cun1 cun1,b.num_cun2 cun2,
						 round(abs(g.FECHAINGRESO-a.FECHA_AGENDO)*24)  diferencia_horas, 
						 abs(g.FECHAINGRESO-a.FECHA_AGENDO)*24  diferencia_dias_decimales, 
						 trunc(g.FECHAINGRESO-a.FECHA_AGENDO) as  diferencia_dias, 
						 B.CORREO, A.CODNODO, 
						 A.TIPO_USER AS CODIGO_SEG, 
						 N4.NOMBRE AS NOMNODO, 
						 N5.NOMBRE AS NOMCIUDAD,
						 N6.NOMBRE AS NOMREGIONAL,
						 N7.NOMBRE AS NOMZONA,
						 N8.NOMBRE AS NOMAREA,
						 N9.NOMBRE AS NOMDISTRITO,
						 N5.CODIGO AS NN1,
						 N6.CODIGO AS NN2,
						 N7.CODIGO AS NN3,
						 N8.CODIGO AS NN4,
						 N9.CODIGO AS NN5,
						 c.CODIGO AS NN6,
						 to_char(a.FECHA_AGENDO, 'HH24:MI:SS') as hora_agenda,
						 N12.HINICIAL_RH || ' - ' || N12.HFINAL_RH AS FRANJA_CERCANA,
						 N10.FECHA AS FECHA_CERCANA,
						 N11.HINICIAL_RH || ' - ' || N11.HFINAL_RH AS FRANJA_ESCOGIDA,
						 A.DIAAGENDA AS FECHA_ESCOGIDA,
						 A.ESTADO AS ESTADO_AGENDA_V, N13.NOMBRE AS DIVISION
						 from agenda a 
						 left JOIN TCK_CIERRE_PQR_LLS_VISOR g on a.IDAGENDA=g.IDAGENDA 
						 $pqr JOIN TICKETS b on b.ID_AGENDA=a.IDAGENDA
						 left join RR_SERV_AFECTADO d on d.CODIGO = b.COD_SERV_AFECTADO
						 left join RR_ESTADO_LLS c on c.CODIGO=b.AREA
						 left join USUARIOS f on f.ID_USUARIO=g.IDUSUARIO
						 left JOIN US_CARGOS h on h.COD_CARGO=F.COD_CARGO
						 left join ALIADOS l on l.IDALIADO=a.IDALIADO
						 LEFT join USUARIOS i on i.ID_USUARIO=a.IDUSUARIO
						 LEFT join RR_RAZONES_LLS N1 on N1.CODIGO=SUBSTR(g.NIVELESLLS , 0,3)
						 LEFT join RR_RAZONES_LLS N2 on N2.CODIGO=SUBSTR(g.NIVELESLLS , 5,3)
						 LEFT join RR_RAZONES_LLS N3 on N3.CODIGO=SUBSTR(g.NIVELESLLS , 9,3)
						 left join RR_NODOS N4 on N4.CODIGO= a.CODNODO
						 left join RR_CIUDADES N5 on N5.CODIGO= N4.CODCIUDAD
						 left join RR_REGIONALES N6 ON N6.CODIGO= N4.CODREGIONAL
						 left join RR_ZONAS N7 ON N7.CODIGO= N4.CODZONA
						 left join RR_AREAS N8 ON N8.CODIGO= N4.CODAREA
						 left join RR_DISTRITOS N9 ON N9.CODIGO= N4.CODDISTRITO
						 left join CAPACIDAD_CUPOS N10 ON  N10.IDAGENDA=A.IDAGENDA
						 LEFT JOIN RANGO_HORA N11 ON N11.ID_RH=A.ID_RH
						 LEFT JOIN RANGO_HORA N12 ON N12.ID_RH=N10.ID_RH
						 left join RR_DIVISIONES N13 ON N13.CODIGO = N4.CODDIVISION
						 where a.PROGRAMACION='L'  
						 and a.FECHA_AGENDO BETWEEN TO_DATE('". $FechaInicial ." 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('". $FechaFinal ." 23:59:59','YYYY-MM-DD HH24:MI:SS')  ";
                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.") ";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
               // $sql .= " order by a.FECHA_AGENDO desc ";
               //echo $sql;
                $this->EjecutaConsulta($sql);
                $queryCompleto = $this->data;
				//imprimir($queryCompleto);
				//die;
				$nivelesRRTickets = array();
				
				foreach ($queryCompleto as $id => $valorData) 
				{
				   $nivelesRRTickets = $this->consultaRazones($valorData['CUENTA'], $valorData['TICKET']);
				   //imprimir($nivelesRRTickets);
				   //die;
				   if($nivelesRRTickets<>array())
				   {
				       $queryNombreNivelesOpe = array();
					   if(@$nivelesRRTickets['NIVEL1']!='')
					   {
						$sql = "select TCKT.NIVEL1, TCKT.NIVEL2, TCKT.NIVEL3 
							   FROM TCK_RESPUESTAS_PQR TCKT
							   LEFT JOIN WRAZON_LLS RAOPE ON (RAOPE.CODIGO = TCKT.NIVEL3)
					           WHERE TCKT.NIVEL3 IN ('".@$nivelesRRTickets['NIVEL1']."') 
							   AND TCKT.ESTADO = 'A'
							   ";
						$this->EjecutaConsulta($sql);
						$queryNombreNivelesOpe = $this->data;
						
						if($queryNombreNivelesOpe<>array())
						{
						   @$nivelesRRTickets = $queryNombreNivelesOpe[0];
						}
					   }

					   $sql = "select NOMBRE 
							   FROM RR_RAZONES_LLS 
					           WHERE CODIGO IN ('".@$nivelesRRTickets['NIVEL1']."', '".@$nivelesRRTickets['NIVEL2']."', '".@$nivelesRRTickets['NIVEL3']."')
							   ";
					   $this->EjecutaConsulta($sql);
					   $queryNombreNiveles = $this->data;
                       //imprimir($queryNombreNiveles);
					   if($queryNombreNiveles<>array())
					   {
					      foreach($queryNombreNiveles as $id2 => $valorData2)
						  {
							  $count = $id2+1;
							  $valorData['NIVEL'.$count]= $valorData2['NOMBRE'];
						  }
					   }else
					   {
					      $valorData['NIVEL1']='';
						  $valorData['NIVEL2']='';
						  $valorData['NIVEL3']='';
					   }
				   }
				   
				   //$valores[] = $valorData;
				   $valores[$valorData['TICKET']] = $valorData;
				}
				 set_time_limit(0);
				return $valores;
             

//            }

    }
	
function PQR($FechaInicial, $FechaFinal,$Aliado, $pyme, $ok)
    {
$valores=array();
$Aliado = implode(",", $Aliado);
               $sql = "select DISTINCT 
						a.IDORDEN_DE_TRABAJO AS ticket,
						CASE 
							WHEN 
						     x.SALIDA IS NULL AND A.CODRESULTADO <> 'TCK' 
						THEN 
							CASE
								WHEN
							     g.FECHAINGRESO IS NULL AND A.CODRESULTADO = 'OK'
						  THEN
							  A.HORA_SALIDA
						   ELSE 
							  g.FECHAINGRESO
						   END 
							ELSE
								CASE
								 WHEN
									A.CODRESULTADO = 'OK'
									THEN
									 x.SALIDA
									ELSE 
									 g.FECHAINGRESO
							END
						END AS FECHA_SOLUCION,
						b.NUM_PQR PQR,
						a.CUENTA cuenta,
						a.SUSCRIPTOR,  
						a.TIPO_USER tip_cli,
						b.TIPO_PQR TIPPQR, 
						a.CODRESULTADO ESTADO, 
						b.RECIBER_METHOD medio,
						a.APTO2 ||' '||d.NOMBRE, 
						l.NOMBRE  areaesc, 
						to_char(a.FECHA_AGENDO ,'yyyy-MM-dd') fechacrea, 
						to_char(a.FECHA_AGENDO ,'HH24:MI') horacrea,
						i.USUARIO USUCREA, 
						c.NOMBRE SINTOMA,  
						b.RAZON_LLAMADA || ' ' || B.SUBRAZON_LLAMADA,  
						DECODE(TRIM(TRANSLATE(a.AVISOSAP,'0123456789',' ')), NULL, a.AVISOSAP,'') aviso,
						CASE 
						 WHEN g.FECHAINGRESO IS NULL AND A.CODRESULTADO = 'OK'
						  THEN 
							to_char(a.HORA_SALIDA,'yyyy-MM-dd')
						  ELSE 
							to_char(g.FECHAINGRESO,'yyyy-MM-dd')
						END AS FECHA_CIERRE, 
						CASE 
						 WHEN g.FECHAINGRESO IS NULL AND A.CODRESULTADO = 'OK'
						  THEN 
							to_char(a.HORA_SALIDA ,'HH24:MI')
						  ELSE 
							to_char(g.FECHAINGRESO ,'HH24:MI')
						END AS HORA_CIERRE, 
						f.USUARIO usucierra, 
						h.DESCRIPCION  areacier,
						REGEXP_REPLACE(replace(replace(trim(g.NOTA),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') NOTA, 
						g.TIPO_CIERRE_PQR,
						b.num_cun1 cun1,
						b.num_cun2 cun2,
						CASE 
						 WHEN g.FECHAINGRESO IS NULL AND A.CODRESULTADO = 'OK'
						  THEN 
												 round(abs(a.HORA_SALIDA-a.FECHA_AGENDO)*24)
						  ELSE 
												 round(abs(g.FECHAINGRESO-a.FECHA_AGENDO)*24)
						END AS diferencia_horas,
						CASE 
						 WHEN g.FECHAINGRESO IS NULL AND A.CODRESULTADO = 'OK'
						  THEN 
											   TRUNC(abs((a.HORA_SALIDA-a.FECHA_AGENDO)*24),2)
						  ELSE 
												 TRUNC(abs((g.FECHAINGRESO-a.FECHA_AGENDO)*24),2)
						END AS diferencia_dias_decimales,
						CASE 
						 WHEN g.FECHAINGRESO IS NULL AND A.CODRESULTADO = 'OK'
						  THEN 
												 trunc(a.HORA_SALIDA-a.FECHA_AGENDO)
						  ELSE 
												 trunc(g.FECHAINGRESO-a.FECHA_AGENDO)
						END AS diferencia_dias,
						B.CORREO,
						A.CODNODO, 
						N10.CODREGIONAL, 
						A.CODCIUDAD AS DIV, 
						A.TIPO_USER AS CODIGO_SEG, 
						CASE 
							WHEN 
						TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2) IS NULL AND s.NOMBRE IS NOT NULL
						THEN 
						  TRUNC(abs((X.SALIDA-a.FECHA_AGENDO)*24),2)
						 WHEN
							TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2) IS NULL OR s.NOMBRE IS NOT NULL
						THEN
						  CASE
						   WHEN
											TRUNC(abs((g.FECHAINGRESO-a.FECHA_AGENDO)*24),2) IS NULL AND A.CODRESULTADO = 'OK'
						   THEN
							 
											TRUNC(abs((a.HORA_SALIDA-a.FECHA_AGENDO)*24),2)
							ELSE 
							 TRUNC(abs((g.FECHAINGRESO-a.FECHA_AGENDO)*24),2)
							END
						ELSE
						  TRUNC(abs((g.FECHASOLUCION-a.FECHA_AGENDO)*24),2)
						END,
						CASE 
							WHEN 
						     s.NOMBRE IS NOT NULL  OR TRUNC(abs((g.FECHAINGRESO-a.FECHA_AGENDO)*24),2) IS NULL
						THEN 
						 CASE	
							WHEN s.NOMBRE IS NULL
						  THEN 
							 l.NOMBRE
						  ELSE 
							 s.NOMBRE
						  END
						ELSE
							 l.NOMBRE
						END
						from agenda a 
						left JOIN TCK_CIERRE_PQR_LLS_VISOR g on g.IDAGENDA=a.IDAGENDA
						left join RR_ESTADO_LLS c on c.CODIGO=a.NUMDIR2 
						left join RR_SERV_AFECTADO d on d.CODIGO= a.APTO2
						left JOIN TICKETS b on b.ID_AGENDA=a.IDAGENDA
						left join USUARIOS f on f.ID_USUARIO=g.IDUSUARIO
						left JOIN US_CARGOS h on h.COD_CARGO=F.COD_CARGO
						left join ALIADOS l on l.IDALIADO=a.IDALIADO
						LEFT join USUARIOS i on i.ID_USUARIO=a.IDUSUARIO
						LEFT join RR_RAZONES_LLS N1 on N1.CODIGO=SUBSTR(g.NIVELESLLS , 0,3)
						LEFT join RR_RAZONES_LLS N2 on N2.CODIGO=SUBSTR(g.NIVELESLLS , 5,3)
						LEFT join RR_RAZONES_LLS N3 on N3.CODIGO=SUBSTR(g.NIVELESLLS , 9,3)
						LEFT join RR_CIUDADES N10 ON (A.CODCIUDAD=N10.CODIGO)
						left join (select DISTINCT HIS_ID_AGENDA, MAX(HIS_HORA_SAL) AS SALIDA, MAX(HIS_AREA_ESCALA) AS ESCALA from HIS_PQR_ESCALAMIENTO WHERE RAZON_PQR IS NOT NULL GROUP BY HIS_ID_AGENDA ORDER BY SALIDA DESC) x  on x.HIS_ID_AGENDA = a.IDAGENDA
						left join ALIADOS s on s.IDALIADO=x.ESCALA
						where a.PROGRAMACION='T' and a.FECHA_AGENDO BETWEEN TO_DATE('" . $FechaInicial .
                    " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                    " 23:59:59','YYYY-MM-DD HH24:MI:SS')";

              
                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " order by fechacrea desc ";
               //echo $sql;
                $this->EjecutaConsulta($sql);
               $queryCompleto = $this->data;
				//imprimir($queryCompleto);
				//die;
				$nivelesRRTickets = array();
				
				foreach ($queryCompleto as $id => $valorData) 
				{
				   $nivelesRRTickets = $this->consultaRazones($valorData['CUENTA'], $valorData['TICKET']);
				   //imprimir($nivelesRRTickets);
				   //die;
				   if($nivelesRRTickets<>array())
				   {
				        
					   $queryNombreNivelesOpe = array();
					   if(@$nivelesRRTickets['NIVEL1']!='')
					   {
						$sql = "select TCKT.NIVEL1, TCKT.NIVEL2, TCKT.NIVEL3 
							   FROM TCK_RESPUESTAS_PQR TCKT
							   LEFT JOIN WRAZON_LLS RAOPE ON (RAOPE.CODIGO = TCKT.NIVEL3)
					           WHERE TCKT.NIVEL3 IN ('".@$nivelesRRTickets['NIVEL1']."') 
							   AND TCKT.ESTADO = 'A'
							   ";
						$this->EjecutaConsulta($sql);
						$queryNombreNivelesOpe = $this->data;
						
						if($queryNombreNivelesOpe<>array())
						{
						   @$nivelesRRTickets = $queryNombreNivelesOpe[0];
						}
						
					   }
					   $sql = "select NOMBRE 
							   FROM RR_RAZONES_LLS 
					           WHERE CODIGO IN ('".@$nivelesRRTickets['NIVEL1']."', '".@$nivelesRRTickets['NIVEL2']."', '".@$nivelesRRTickets['NIVEL3']."')
							   ";
					   $this->EjecutaConsulta($sql);
					   $queryNombreNiveles = $this->data;
                       //imprimir($queryNombreNiveles);
					   if($queryNombreNiveles<>array())
					   {
					      foreach($queryNombreNiveles as $id2 => $valorData2)
						  {
							  $count = $id2+1;
							  $valorData['NIVEL'.$count]= $valorData2['NOMBRE'];
						  }
					   }else
					   {
					      $valorData['NIVEL1']='';
						  $valorData['NIVEL2']='';
						  $valorData['NIVEL3']='';
					   }
				   }
				   $valores[$valorData['TICKET']] = $valorData;
				   //$valores[] = $valorData;
				}
				 set_time_limit(0);
				return $valores;
             

//            }

    }
    
function NotasPQR($FechaInicial, $FechaFinal,$Aliado, $pyme, $ok)
    {

$Aliado = implode(",", $Aliado);
               $sql = "select a.IDORDEN_DE_TRABAJO ticket, b.NUM_PQR PQR,a.CUENTA cuenta ,a.SUSCRIPTOR nom_cliente,  a.TIPO_USER tip_cli,b.TIPO_PQR TIPPQR, a.CODRESULTADO ESTADO, b.RECIBER_METHOD medio,a.APTO2 ||' '||d.NOMBRE,  f.NOMBRE  arearesp, to_char(a.FECHA_AGENDO ,'yyyy-MM-dd') fechacrea, 
to_char(a.FECHA_AGENDO ,'HH24:MI') horacrea,i.USUARIO USUCREA,  DECODE(TRIM(TRANSLATE(a.AVISOSAP,'0123456789',' ')), NULL, a.AVISOSAP,'') aviso,m.usuario, o.DESCRIPCION, to_char(n.FECHA_NOTA,'yyyy-MM-dd') fechanota,to_char(n.FECHA_NOTA ,'HH24:MI') horanota, N5.DESCRIPCION tip , n.RAZON || ' ' || n.SUBRAZON marctip ,
REGEXP_REPLACE(replace(replace(trim(n.NOTA),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') NOTA,
A.TIPO_USER AS CODIGO_SEG 
from agenda a LEFT JOIN TCK_CIERRE_PQR_LLS_VISOR g on g.IDAGENDA=a.IDAGENDA
left join RR_ESTADO_LLS c on c.CODIGO=a.NUMDIR2 
LEFT join RR_SERV_AFECTADO d on d.CODIGO= a.APTO2
left JOIN TICKETS b on b.ID_AGENDA=a.IDAGENDA
left join ALIADOS f on f.IDALIADO=a.IDALIADO
LEFT join USUARIOS i on i.ID_USUARIO=a.IDUSUARIO
left join TCK_NOTAS_LLAMADA n on a.IDORDEN_DE_TRABAJO=n.ID_LLAMADA
left join usuarios m on m.ID_USUARIO=n.USUARIO_RR
left JOIN US_CARGOS o on o.COD_CARGO=m.COD_CARGO
left JOIN TCK_RAZONES_NOTAS N5 ON n.RAZON=N5.COD_RAZON AND n.SUBRAZON=N5.COD_SUBRAZON
where a.PROGRAMACION='T' and
                     n.FECHA_NOTA BETWEEN TO_DATE('" . $FechaInicial .
                    " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                    " 23:59:59','YYYY-MM-DD HH24:MI:SS')";

              
                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " order by n.FECHA_NOTA desc ";
                //echo $sql;
                $this->EjecutaConsulta($sql);
                
                set_time_limit(0);
                return $this->data;
             

//            }

    }
    
    function OtrosIvr($FechaInicial, $FechaFinal,$Aliado,$correo='', $pyme, $ok)
    { 
$Aliado = implode(",", $Aliado);
               $sql = "select a.CUENTA cuenta ,a.suscriptor, b.linea_telefonica, a.telefono2,  a.TIPO_USER tip_cli,a.CODCIUDAD AS comunidad, N10.CODREGIONAL division, a.CODCIUDAD ciudad, a.ID_AG_EDIFICIO matriz, b.RAZON_LLAMADA || ' ' || B.SUBRAZON_LLAMADA caumar,
REGEXP_REPLACE(replace(replace(trim(g.NOTA),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') NOTA, 
a.FECHA_AGENDO, i.usuario, a.IDORDEN_DE_TRABAJO ticket,b.num_cun1 cun1,b.num_cun2 cun2 ,a.AVISOSAP, N1.NOMBRE NIVEL1,N2.NOMBRE NIVEL2,N3.NOMBRE NIVEL3, A.TIPO_USER AS CODIGO_SEG
from agenda a
left JOIN TCK_CIERRE_PQR_LLS_VISOR g on g.IDAGENDA=a.IDAGENDA
left JOIN TICKETS b on b.ID_AGENDA=a.IDAGENDA
left join USUARIOS f on f.ID_USUARIO=g.IDUSUARIO
left JOIN US_CARGOS h on h.COD_CARGO=F.COD_CARGO
LEFT join USUARIOS i on i.ID_USUARIO=a.IDUSUARIO
LEFT join RR_RAZONES_LLS N1 on N1.CODIGO=SUBSTR(g.NIVELESLLS , 0,3)
LEFT join RR_RAZONES_LLS N2 on N2.CODIGO=SUBSTR(g.NIVELESLLS , 5,3)
LEFT join RR_RAZONES_LLS N3 on N3.CODIGO=SUBSTR(g.NIVELESLLS , 9,3)
LEFT join RR_CIUDADES N10 on A.CODCIUDAD=N10.CODIGO
where a.programacion='T' and
                     a.FECHA_AGENDO BETWEEN TO_DATE('" . $FechaInicial .
                    " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                    " 23:59:59','YYYY-MM-DD HH24:MI:SS')";

              
                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
                if ($correo == "1") {
                    $sql .= " AND b.CORREO IS NULL";
                }
                elseif ($correo == "0") {
                    $sql .= " AND b.CORREO IS NOT NULL";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " order by a.FECHA_AGENDO desc ";
                //echo $sql;
                $this->EjecutaConsulta($sql);
                
                set_time_limit(0);
                return $this->data;
             

//            }

    }
     function RR_PQR($FechaInicial, $FechaFinal, $pyme = '', $ok = '')
    { 
         $rr=new DatosRR();
				      $sql = "select 
								  agen.IDAGENDA AS IDAGEN, tck.NUM_PQR AS PQR, agen.CODRESULTADO AS ESTADOAGEN , agen.IDORDEN_DE_TRABAJO AS TICKET, agen.CUENTA AS CUEN, 
								  agen.FECHA_AGENDO AS FEC, agen.tipo_user as SEGME, ali.nombre AS ALIADO, agen.CALENDARIO AS CALENDARIO
								from agenda agen
								left join tickets tck on (tck.id_agenda=agen.IDAGENDA)
								left join aliados ali on (ali.idaliado=agen.idaliado)
								where agen.FECHA_AGENDO 
								  BETWEEN TO_DATE('". $FechaInicial ." 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('". $FechaFinal ." 23:59:59','YYYY-MM-DD HH24:MI:SS')
								and agen.programacion IN ('T','L')
					";
//                if ($Aliado != "TODOS") {
//                    $sql .= " AND A.IDALIADO IN ('".$Aliado."')";
//                }
                
                  $sql.= " order by tck.FECHA_HORA_CREACION desc";
                  $sql;
                  $this->EjecutaConsulta($sql);
                  set_time_limit(0);
                  $est_pqr[]='';
                  $est_sc[]='';
				  $i=0;
                  foreach ($this->data as $id => $valorPQR) 
				  {
					  if($valorPQR['PQR'] != '')
					  {
					   $RRPQR= $rr->consultarPQR($valorPQR['PQR']);
					  }else
					  {
					    $RRPQR= array();
					  }
					  $RRSC= $rr->Consultar_ServiceCall ($valorPQR['TICKET']);
					  $consultaRRWC = $rr->ResultadoOT;
				   
					  $est_pqr[$i]=$RRPQR;
					  $est_sc[$i]=$consultaRRWC;
					  $i++; 
                   
                  }
                  $respqr[]='';
                  $j=0;
                  foreach ($this->data as $id => $valorPQR) 
				  {
					  $respqr[$j]['AGENDA']= $valorPQR['IDAGEN'];
					  $respqr[$j]['CUENTA']= $valorPQR['CUEN'];
					  $respqr[$j]['NUMTICK']= $valorPQR['TICKET'];
					  $respqr[$j]['NUMPQR']= $valorPQR['PQR'];
					  $respqr[$j]['ESTAGEN']= $valorPQR['ESTADOAGEN'];
					  $respqr[$j]['ESTARR']= @$est_pqr[$j]['ESTADOPQR'];
					  $respqr[$j]['ESTARRDESC']= @$est_pqr[$j]['ESTADOPQRDESC'];
					  $respqr[$j]['ESTASC']= $est_sc[$j]['ESTADO_OT'];
					  $respqr[$j]['FECHA']= $valorPQR['FEC'];
					  $respqr[$j]['SEGME']= $valorPQR['SEGME'];
					  $respqr[$j]['ALIADO']= $valorPQR['ALIADO'];
					  $respqr[$j]['CALENDARIO']= $valorPQR['CALENDARIO'];
					  
					  $j++;
                  }
               
     return $respqr;  

    }
	
	function NOTAS_PQR($FechaInicial, $FechaFinal,$Aliado, $pyme, $ok)
    {

$Aliado = implode(",", $Aliado);
               $sql = "SELECT
						 A .IDORDEN_DE_TRABAJO ticket,
						 b.NUM_PQR PQR,
						 A .CUENTA cuenta,
						 A .SUSCRIPTOR,
						 A .TIPO_USER tip_cli,
						 'T' TIPPQR,
						  'F' ESTADO_PQR,
						 b.RECIBER_METHOD medio,
						 A .APTO2 || ' ' || D .NOMBRE,
						 TO_CHAR(
						  N4.FECHA_NOTA,
						  'yyyy-MM-dd'
						 )fechacrea,
						 TO_CHAR(N4.FECHA_NOTA, 'HH24:MI')horacrea,
						 i.USUARIO USUCREA,
						 c.NOMBRE SINTOMA,
						 N5.DESCRIPCION,
						  H .DESCRIPCION areacier,
						  REGEXP_REPLACE(replace(replace(trim(N4.NOTA),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') NOTA, 
						  'AGREE' procedencia,
						  '1' TIEMPO_VIDA,
						  A .CODRESULTADO ESTADO, A.TIPO_USER AS CODIGO_SEG
						FROM
						agenda A
						LEFT JOIN TCK_CIERRE_PQR_LLS_VISOR G ON G .IDAGENDA = A .IDAGENDA
						LEFT JOIN RR_ESTADO_LLS c ON c.CODIGO = A .NUMDIR2
						INNER JOIN RR_SERV_AFECTADO D ON D .CODIGO = A .APTO2
						LEFT JOIN TICKETS b ON b.ID_AGENDA = A .IDAGENDA
						LEFT JOIN ALIADOS l ON l.IDALIADO = A .IDALIADO
						LEFT JOIN USUARIOS i ON i.ID_USUARIO = A .IDUSUARIO
						LEFT JOIN RR_TIPO_RES_PQR j ON j.CODIGO = G .TIPO_CIERRE_PQR
						INNER JOIN TCK_NOTAS_LLAMADA N4 ON (N4.ID_LLAMADA=A .IDORDEN_DE_TRABAJO) 
						LEFT JOIN USUARIOS f ON f.ID_USUARIO = N4.USUARIO_RR
						LEFT JOIN US_CARGOS H ON H .COD_CARGO = F.COD_CARGO
						INNER JOIN TCK_RAZONES_NOTAS N5 ON N5.COD_RAZON=N4.RAZON AND N4.SUBRAZON=N5.COD_SUBRAZON
						where a.PROGRAMACION='T' and N4.FECHA_NOTA BETWEEN TO_DATE('". $FechaInicial ." 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('". $FechaFinal ." 23:59:59','YYYY-MM-DD HH24:MI:SS')";

              
                if ($Aliado != "TODOS") {
                    $sql .= " AND A.IDALIADO IN (".$Aliado.")";
                }
				
				if ($pyme == "1") {
                    $sql .= " AND A.TIPO_USER IN ('9','91','92','93','94','95')  ";
                }else if($pyme == "2")
				{
				    $sql .= " AND A.TIPO_USER NOT IN ('9','91','92','93','94','95')  ";
				}
				
				if ($ok == "1") {
                    $sql .= " AND A.CODRESULTADO <> 'OK' ";
                }elseif($ok == "2")
				{
				    $sql .= " AND A.CODRESULTADO = 'OK' ";
				}else
				{
				   $sql .= "";
				}
				
                $sql .= " order by N4.FECHA_NOTA desc ";
               //echo $sql;
			    
                $this->EjecutaConsulta($sql);
             
				
				//$this->EjecutaConsulta($sql2);
                set_time_limit(0);
                return $this->data;
             

//            }

    }
	
	
function datacredito($FechaInicial, $FechaFinal)
    {

//$Aliado = implode("','", $Aliado);
               $sql = "
			   SELECT LOGW.CEDULA,
			   LOGW.APELLIDO,
			   LOGW.USUARIO,
			   LOGW.OPCION,
			   LOGW.FECHAIN,
			   CLI.TIDENTIFICACION,
			   CLI.NOMBRES,
			   CLI.PRIMER_APELLIDO,
			   CLI.SEGUNDO_APELLIDO,
			   CLI.SCORE,
			   CLI.PUNTAJE,
			   CLI.TIPORESPUESTA,
			   CLI.CLASIFICACION,
			   CLI.OBSERVACIONES,
			   CLI.CIUDAD
			   FROM CDC_SC_CLIENTE CLI
			   LEFT JOIN CDC_LOGWS LOGW ON LOGW.CEDULA = CLI.IDCLIENTE
			   WHERE 
			   LOGW.FECHAIN BETWEEN TO_DATE('" . $FechaInicial .
                    " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
                    " 23:59:59','YYYY-MM-DD HH24:MI:SS')";

                $sql .= " order by LOGW.FECHAIN desc ";
               //echo $sql;
                $this->EjecutaConsulta($sql);
                
                set_time_limit(0);
                return $this->data;
             

//            }

    }
/**
*	@autor: <jlopezch@everis.com>. 
*	Se agrega la consulta que genera el reporte de Capacidades.  	
*   @version 1.0
*/
	function ConsultasCapacidades($FechaInicial, $FechaFinal, $Aliado, $Regional, $Ciudad)
    {
			$Aliado = implode(",", $Aliado);
			$sql = "SELECT
					  RANG.HINICIAL_RH || ' - ' || RANG.HFINAL_RH AS FRANJA,
					  CAPA.CAPACIDAD,
					  CAPA.FECHA,
					  CAPA.IDUSUARIO,
					  USUA.NOMBRE AS USUARIO,
					  AL.NOMBRE,
					  CAPA.CODCIUDAD,
					  CIU.CODREGIONAL,
					  PERF.DESCRIPCION,
					  TRAB.NOMBRE_TIPO,
					  CAPA.IP,
					  CAPA.FECHA_INGRESO,
					  CAPA.FECHA_MODIFICACION
					 FROM CAPACIDAD_ALIADOS CAPA
					 INNER JOIN USUARIOS USUA ON (USUA.ID_USUARIO=CAPA.IDUSUARIO)
					 INNER JOIN RANGO_HORA RANG ON (RANG.ID_RH = CAPA.ID_RH)
					 INNER JOIN ALIADOS AL ON (AL.IDALIADO = CAPA.IDALIADO)
					 INNER JOIN RR_CIUDADES CIU ON (CIU.CODIGO=CAPA.CODCIUDAD)
					 INNER JOIN US_PERFILES PERF ON (PERF.ID_PERFIL=USUA.ID_PERFIL)
					 INNER JOIN TIPO_TRABAJO TRAB ON (TRAB.ID_TT = CAPA.ID_TT)
					 WHERE CAPA.FECHA BETWEEN TO_DATE('". $FechaInicial ." 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('". $FechaFinal ." 23:59:59','YYYY-MM-DD HH24:MI:SS')
			";
				if ($Aliado != "TODOS") {
                    $sql.= " AND CAPA.IDALIADO IN (".$Aliado.")";
                }
				
				if ($Regional != "TODOS") {
					$sql.= " AND CIU.CODREGIONAL IN ('" . $Regional . "')";
				}
				if ($Ciudad[0] != "TODOS") {
					$sql.= " AND CAPA.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				
                $sql.= " order by CAPA.FECHA desc ";
               //echo $sql;
                $this->EjecutaConsulta($sql);
                set_time_limit(0);
                return $this->data;


}

/**
*	@autor: <jlopezch@everis.com>. 
*	Se agrega la consulta que genera el reporte de Rangos.  	
*   @version 1.0
*/
	function ConsultasRangos($FechaInicial, $FechaFinal,$Regional, $Ciudad, $Carpeta)
    {
     $Carpeta = implode("','", $Carpeta);
//$Aliado = implode("','", $Aliado);
				   $sql = "SELECT 			
						   D.ID_RH ,D.HINICIAL_RH ||' - '|| D.HFINAL_RH AS HORAS, A.ID_RF, B.ID_USUARIO, B.NOMBRE , C.DESCRIPCION, B.ID_USUARIO AS CEDULA, 
						   TO_CHAR(A.FECHA_INGRESO,'YYYY-MM-DD') AS FECHA_INGRESO,
						   TO_CHAR(A.FECHA_MODIFICACION,'YYYY-MM-DD') AS FECHA_MODIFICACION,
						   TO_CHAR(A.FECHA_MODIFICACION,'HH24:MI:SS') AS HORA_MODIFICACION,
						   G.NOMBRE_TIPO AS TIPO_TRABAJO,F.NOMBRE AS CIUDAD, F.CODREGIONAL,
						   A.IP
							FROM 				
							RANGO_FECHA A
							INNER JOIN	USUARIOS B ON A.ID_USUARIO=B.ID_USUARIO
							INNER JOIN 	US_PERFILES C ON C.ID_PERFIL = B.ID_PERFIL
							INNER JOIN 	RANGO_HORA D ON A.ID_RF = D.ID_RF
							INNER JOIN 	CIUDAD_TIPOTRABAJO E ON E.IDCIUDADTT = A.ID_CIUDADTT
							INNER JOIN 	RR_CIUDADES F ON E.CODCIUDAD = F.CODIGO
							INNER JOIN 	TIPO_TRABAJO G ON G.ID_TT = E.ID_TT
							WHERE A.FECHA_INGRESO >= TO_DATE('" . $FechaInicial ." 00:00:00','YYYY-MM-DD HH24:MI:SS') 
							AND A.FECHA_INGRESO <= TO_DATE('" . $FechaFinal ." 23:59:59','YYYY-MM-DD HH24:MI:SS')
					";

				if ($Regional != "TODOS") {
					$sql.= " AND F.CODREGIONAL IN ('" . $Regional . "')";
				}
				if ($Ciudad[0] != "TODOS") {
					$sql.= " AND F.CODIGO IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND E.ID_TT IN ('" . $Carpeta . "')";
				}
				
				$sql .= " order by A.FECHA_MODIFICACION desc ";
               //echo $sql;
                $this->EjecutaConsulta($sql);
                set_time_limit(0);
                return $this->data;

    }
	
	/**
*	@autor: <jlopezch@everis.com>. 
*	Se agrega la consulta que genera el reporte de Aliados.  	
*   @version 1.0
*/	
	function ConsultasAliados($FechaInicial, $FechaFinal,$Regional, $Ciudad, $Carpeta)
    {
   
//$Aliado = implode("','", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);
        
		
               $sql = " SELECT    B.ID_USUARIO, B.NOMBRE, C.DESCRIPCION , B.ID_USUARIO AS CEDULA,
						  TO_CHAR(TO_DATE(A.FECHA_INGRESO), 'YYYY-MM-DD')AS FECHA_INGRESO,
						  TO_CHAR(TO_DATE(A.FECHA_INGRESO), 'HH24:MI:SS')AS HORA_INGRESO, 
						  TO_CHAR(TO_DATE(A.FECHA_MODIFICACION), 'YYYY-MM-DD')AS FECHA_MODIFICACION,
						  TO_CHAR(TO_DATE(A.FECHA_MODIFICACION), 'HH24:MI:SS')AS HORA_MODIFICACION, 
						  D.CODIGO AS CODIGO_NODO,
						  D.NOMBRE AS NODOS, A.ACTIVO, E.NOMBRE_TIPO AS TIPO_TRABAJO,G.NOMBRE AS CIUDAD, G.CODREGIONAL ,H.NOMBRE AS ALIADO,A.IP  
						  FROM ALIADOS_NODOS A
						  INNER JOIN USUARIOS B
						  ON B.ID_USUARIO=A.ID_USUARIO
						  INNER JOIN  US_PERFILES C
						  ON C.ID_PERFIL = B.ID_PERFIL
						  INNER JOIN  RR_NODOS D 
						  ON D.CODIGO = A.COD_NODO
						  INNER JOIN  TIPO_TRABAJO E
						  ON E.ID_TT = A.ID_TT
						  INNER JOIN  ALIADOS_CIUDADES F
						  ON F.IDALIADOCIUDAD = A.IDALIADOCIUDAD
						  INNER JOIN  RR_CIUDADES G
						  ON G.CODIGO = F.CODCIUDAD
						  INNER JOIN ALIADOS H ON H.IDALIADO=F.IDALIADO
						  WHERE A.FECHA_MODIFICACION BETWEEN TO_DATE('". $FechaInicial ." 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('". $FechaFinal ." 23:59:59','YYYY-MM-DD HH24:MI:SS')";


				if ($Regional != "TODOS") {
					$sql.= " AND G.CODREGIONAL IN ('" . $Regional . "')";
				}
				if ($Ciudad != "TODOS") {
					$sql.= " AND G.CODIGO IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND E.ID_TT IN ('" . $Carpeta . "')";
				}
				
				$sql .= " order by A.FECHA_MODIFICACION desc ";
				//DIE;
               #echo $sql;
                $this->EjecutaConsulta($sql);
                
                set_time_limit(0);
                return $this->data;

    }
	
	function otPAdre($ot)
	{
	    $datapen = GetData($ot,'C');
		$otPadre = 0;
		if(@$datapen['DWONYX'][0] != array())
		{
			foreach(@$datapen['DWONYX'] as $ids =>$ss)
			{
				if(@$ss['key'] == 'PADRE')
					$otPadre = @$ss['value'];
			}
		}
		return $otPadre;
	}
	
	function MovilAsociada($idagenda)
	{
	    
		$sql = " select m.idmovil, m.idagenda, ali.nombre, mv.nombre as movil
					from moviles_agenda m 
					inner join movil mv on (m.idmovil = mv.idmovil)
					LEFT JOIN ALIADOS ALI ON (mv.IDALIADO=ALI.IDALIADO)
					where 
					m.idagenda='".$idagenda."'";
					
		$this->EjecutaConsulta($sql);
		//imprimir($this->data);
		return $this->data;
		//return $otPadre;
	}
	
	function InformeCorporativo($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta)
    {

		$queryCompleto = array();
		$valores = array();
		$quotes = array();
		$Aliado = implode(",", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);
		
              $sql = " select 
					a.IDAGENDA, 
					a.DIAAGENDA, 
					a.IDORDEN_DE_TRABAJO,  
					TTI.NOMBRE_TIPO, 
					ALI.NOMBRE, 
					A.ESTADO, 
					A.PROGRAMACION, 
					A.CODRESULTADO, 
					A.IDUSUARIO, 
					USU.NOMBRE AS NOMBRE_USUARIO, 
					A.FECHA_AGENDO, 
					RH.HINICIAL_RH || ' ' || RH.HFINAL_RH,  
					A.SUSCRIPTOR, 
					A.CODCIUDAD,
					REGEXP_REPLACE(replace(replace(trim(A.OBSERVACION),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') OBSERVACION,
					A.HORA_LLEGADA,
					A.HORA_SALIDA,
					DBMS_LOB.substr(REGEXP_REPLACE(replace(replace(trim(A.NOTAS),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]',''),4000) NOTAS,
					DBMS_LOB.substr(REGEXP_REPLACE(replace(replace(trim(A.NOTAS),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]',''),4000) NOTAS,
					REGEXP_REPLACE(replace(replace(trim(A.CLL),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') DIRECCION, 
					A.TAGNUM AS TELEFONO,
					REGEXP_REPLACE(replace(replace(trim(A.BARRIO),chr(13)||chr(10), ' '), chr(9), ' '),'[^A-Za-z0-9ÁÉÍÓÚáéíóú ]','') DIRECCION2,
					
					A.NUMDIR2 AS CLIENTE,
					A.NROVISITA,
					A.CUENTA,
					OTE.NOMBRE AS NOMTEC,
					(SELECT DISTINCT count(IDAGENDA)-1 FROM AGENDA WHERE IDORDEN_DE_TRABAJO = A.IDORDEN_DE_TRABAJO AND PROGRAMACION = 'C') AS REAGENDA,
					A.CIUDAD2 AS PADRE,
					A.VENDOROT AS ASIGNACION_ONIX,
					A.NUMDIR AS SERVICIO
					from agenda a
					  LEFT JOIN ALIADOS ALI ON (a.IDALIADO=ALI.IDALIADO)
					  LEFT JOIN TIPO_TRABAJO TTI ON (TTI.ID_TT = A.ID_TT)
					  LEFT JOIN RANGO_HORA RH ON (RH.ID_RH= A.ID_RH)
					  LEFT JOIN ONIX_TECNICOS_AGENDA OTA ON (A.IDAGENDA = OTA.ID_AGENDA)
					  LEFT JOIN ONIX_TECNICOS OTE ON (OTA.ID_ONIX_TECNICOS = OTE.ID_ONIX_TECNICOS)
					  LEFT JOIN USUARIOS USU ON (A.IDUSUARIO = USU.ID_USUARIO)
					where a.PROGRAMACION='C'
					and a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')					 
					";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND A.ID_TT IN ('" . $Carpeta . "')";
				}
				
				
				              
                $this->EjecutaConsulta($sql);
				$queryCompleto = $this->data;
				set_time_limit(0);

				$movilAsociada = array();
				foreach ($queryCompleto as $id => $valorData) 
				{									
				    $movilAsociada = $this->MovilAsociada($valorData['IDAGENDA']);
				   if($movilAsociada<>array())
				   {
					   foreach ($movilAsociada as $id2 => $moviles)
					   {
						  if($valorData['IDAGENDA'] == $moviles['IDAGENDA'])
						  {
							$valorData['MOVIL'] = $moviles['MOVIL'];
							$valorData['ALIADOMOVIL'] = $moviles['NOMBRE'];
							
						  }
						  
						   //$valorData['OTPADRE'] = $otPadre;
						   $valores[] = $valorData;
						   
					   }
				   }else
				   {
					       $valorData['MOVIL'] = '';
						   $valorData['ALIADOMOVIL'] = '';						   
						   $valores[] = $valorData;
						   
				   }
				  $quotes[] = 'ENT_QUOTES';
				}
				$str = array(";","'");
					
				foreach($valores as $v){
					$valores1 = array_map('htmlentities',array_map('utf8_decode',$v));
					$valores2[] = str_replace($str,"",$valores1);
				}				
				
				set_time_limit(0);
                return $valores;
    }

	/*
	* Reporte de los estatus de las OT 
	* Es necesario tener un reporte de los estatus de las OT, para realizar seguimiento.
	*/
	function InformeStatusOT($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
		
              $sql = " SELECT /*+ PARALLEL(O, 2) */ DISTINCT A.CIUDAD2       AS OTP,
							  a.IDORDEN_DE_TRABAJO AS OTH,
							  A.ESTADO             AS ESTATUS,
							  ALI.NOMBRE           AS ALIADO,
							  MO.NOMBRE           AS TECNICO,
							  ODF.USUARIO_CREO    AS INTERVENTOR,
							  A.CODCIUDAD          AS CIUDAD,
							  A.SUSCRIPTOR         AS CLIENTE,
							  A.CODRESULTADO       AS ESTADO_CIERRE,
								CASE
									  WHEN ODF.TIPO_ROL ='1' THEN 'Y' 
									  ELSE 'N'
									  END AS INTERVENTORIA
							FROM agenda a
							LEFT JOIN ALIADOS ALI ON (a.IDALIADO=ALI.IDALIADO)
							LEFT JOIN ONIX_TECNICOS_AGENDA OTA ON (A.IDAGENDA = OTA.ID_AGENDA)
							LEFT JOIN MOVILES_AGENDA MA ON MA.IDAGENDA = A.IDAGENDA 
							LEFT JOIN MOVIL MO ON MO.IDMOVIL = MA.IDMOVIL
							LEFT JOIN ONIX_DATOS_FACTURA ODF ON (ODF.ID_AGENDA = A.IDAGENDA)
							LEFT JOIN USUARIOS USU ON (ODF.USUARIO_CREO  = USU.ID_USUARIO)
							LEFT JOIN ONIX_DATOS_CORP ODC ON (ODC.IDAGENDA = A.IDAGENDA)
					where A.PROGRAMACION='C'
					and a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD') ";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                set_time_limit(0);
                return $this->data;
    }
	
	/*
	* REPORTE DE CORRECCIONES REALIZADAS POR INTERVENTORÍA 
	* Es necesario tener un reporte de las OT que fueron modificadas por parte de Interventoría.  
	*/
	function InformeCorInterventoria($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
		
              $sql = " SELECT /*+ PARALLEL(O, 2) */ DISTINCT
							 A.IDAGENDA AS IDAG,
							 ALI.IDALIADO AS NIT_ALIADO,
							 ALI.NOMBRE AS ALIADO,
							 TT.CODSAP_MT AS COD_SAP,
							 TT.CODIGO AS COD_ACTIVIDAD,
                             TT.DESCRIPCION AS DESCRIPCION,
							 TO_CHAR(A.DIAAGENDA,'DD/MM/YYYY') AS FECHA_AGENDAMIENTO,
							 A.CIUDAD2 AS OTP,
							 A.IDORDEN_DE_TRABAJO AS OTH,
							 TT.NROSAP AS CeCo,
							 OSDS.CODIG_PROYECT_PEP AS PEP,
							 OSDS.ID_ONIX_SDS AS ID_SDS,
							 OSDS.DESCRIPCION AS NOMBRE_SDS,
							 MV.IDRADIO AS TIPO_CLIENTE,
							 OCE.GRAFO AS GRAFO,
							 CASE
							 WHEN OCE.NIT = A.AVISOSAP THEN 'Y'
							 ELSE 'N'
							 END CLIENTE_ESPECIAL,
							 A.SUSCRIPTOR AS NOMBRE_CLIENTE,
							 A.CODCIUDAD AS CIUDAD, 
							 A.CLL AS DIRECCION,
							 OSCE.DESCRIPCION AS SEDE,
							 MV.NOMBRE AS MOVIL,
							 MV.VEHICULO AS CC,
							 A.PROGRAMACION AS CLASE,
							 USU.NOMBRE AS USUARIO,
							 TO_CHAR(PFA.FEC_REGISTRO,'DD/MM/YYYY') AS FECHA_FACTURA,
							 AC.CANTIDAD AS CANTIDAD,
							 AC.TOTAL_MO AS VALOR_MO,
							 AC.TOTAL_MT AS VALOR_MATERIAL,
							 AC.TOTAL AS TOTAL,
							 A.ESTADO AS PROGRAMACION,
							 A.CODRESULTADO AS VISITAS,
							 A.CODCIUDAD AS COMUNIDAD
							 FROM AGENDA A
							 INNER JOIN ALIADOS ALI ON (A.IDALIADO=ALI.IDALIADO)
							 INNER JOIN PRE_FACTURA PFA ON PFA.IDAGENDA = A.IDAGENDA AND PFA.TIPO_ROL = '1'
							 INNER JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO = 'A'
							 INNER JOIN FAC_TTACTIVIDAD TT ON TT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD
							 INNER JOIN PRE_ITEM IT ON IT.ID_ACTIVIDAD = AC.ID_ACTIVIDAD
							 INNER JOIN FAC_MATERIALES MT ON MT.IDMATERIALES=IT.ID_MOMTR
							 INNER JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1' AND ODF.VALIDACION = '0'
							 INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS = ODF.SDS AND OSDS.ESTADO = '1'
							 INNER JOIN MOVILES_AGENDA MA ON MA.IDAGENDA = A.IDAGENDA
							 INNER JOIN MOVIL MV ON (MV.IDMOVIL = MA.IDMOVIL)
							 INNER JOIN USUARIOS USU ON (A.IDUSUARIO = USU.ID_USUARIO)
							 LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
							 LEFT JOIN ONIX_DATOS_CORP ODC ON ODC.IDAGENDA = A.IDAGENDA
							 LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
					WHERE A.PROGRAMACION='C'
					and a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";
					
				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                $queryCompleto = $this->data;
				if($queryCompleto<>array())
				{
				   $queryTec = array();
				   foreach($queryCompleto as $id => $value)
				   {
						if($value['IDAG'])
						{
							$sqlTec = "SELECT OTA.NOMBRE, OTA.CEDULA 
										FROM ONIX_TECNICOS_AGENDA OTE
										INNER JOIN ONIX_TECNICOS OTA ON (OTE.ID_ONIX_TECNICOS = OTA.ID_ONIX_TECNICOS)
										WHERE OTE.ID_AGENDA = '".$value['IDAG']."'
										";
							 $this->EjecutaConsulta($sqlTec);
							 $queryTec = $this->data;
							 if($queryTec<>array())
							 {
							   $value['MOVIL'] =  $queryTec[0]['NOMBRE'];
							   $value['CC'] =  $queryTec[0]['CEDULA'];
							 }else
							 {
							   $value['MOVIL'] = '';
							   $value['CC'] = '';
							 }
					
						}
						$valores[] = $value;
				   }
				}
				set_time_limit(0);
                return $valores;
    }
	
	/*
	* Informe de Soporte de la Operación
	* Requerido para la conciliación con el Aliado. 
	*/
	function InformeSopOperacion($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
              $sql = " SELECT /*+ PARALLEL(O, 2) */ DISTINCT 
                               ALI.IDALIADO AS NIT_ALIADO,
                               ALI.NOMBRE AS ALIADO,
							   TT.CODSAP_MT AS COD_SAP,
                               TT.CODIGO AS COD_ACTIVIDAD,
                               TT.DESCRIPCION AS DESCRIPCION,
                               TO_CHAR(A.DIAAGENDA,'DD/MM/YYYY') AS DIA_AGENDA,
							   TT.NOMBRE_TIPO,
                               TO_CHAR(A.FECHA_AGENDO,'DD/MM/YYYY') AS FECHA_AGENDAMIENTO,
                               A.CIUDAD2 AS OTP,
                               A.IDORDEN_DE_TRABAJO AS OTH,
							   CASE
							   WHEN TT.TIPO = 'C' THEN 
								'999999999'
								ELSE 
								TT.NROSAP
								END CeCo,	
  							   CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN 
										OSCE.GRAFO
								ELSE
										OCE.GRAFO
								END
								ELSE
										CAST( OSDS.ID_ONIX_SDS as VARCHAR2(50)) 
							   END AS ID_SDS,
                               CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN OSCE.CODIG_PROYE_PEP
								ELSE
										OCE.CODIG_PROYE_PEP
									END
								ELSE
								    CASE WHEN ODF.TECNOLOGIA != 'GPON' THEN OSDS.CODIG_PROYECT_PEP
								ELSE
										OSDS.CODIG_PROYECT_GPON_PEP
								   END
								END AS PEP,
                               OSDS.DESCRIPCION AS NOMBRE_SDS,
                               OSCE.DESCRIPCION AS SEDE,
                               MV.IDRADIO AS TIPO_CLIENTE,
                               A.AVISOSAP AS ID_CLIENTE,
                               A.SUSCRIPTOR AS NOMBRE_CLIENTE,
                               A.CODCIUDAD AS CIUDAD,
                               A.CLL AS DIRECCION,
							   MV.NOMBRE AS MOVIL_TEC,
                               MV.NOMBRE AS MOVIL,
                               MV.VEHICULO AS CC,
                               USU.NOMBRE AS USUARIO,
                               TO_CHAR(PFA.FEC_REGISTRO,'DD/MM/YYYY') AS FECHA_FACTURA,
                               AC.CANTIDAD AS CANTIDAD,
                               A.ESTADO AS REPROGRAMADA,
                               A.CODRESULTADO AS VISITAS,
                               A.CODCIUDAD AS COMUNIDAD,
							   A.IDAGENDA AS IDAG
                               FROM AGENDA A
                               INNER JOIN ALIADOS ALI ON (a.IDALIADO=ALI.IDALIADO)
							   INNER JOIN TIPO_TRABAJO TT ON (A.ID_TT = TT.ID_TT)
                               LEFT JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1'
                               INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS = ODF.SDS AND OSDS.ESTADO = '1'
                               LEFT JOIN PRE_FACTURA PFA ON PFA.IDAGENDA = A.IDAGENDA AND PFA.TIPO_ROL = '1'
                               LEFT JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO = 'A'
                               LEFT JOIN FAC_TTACTIVIDAD TT ON TT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD
                               LEFT JOIN PRE_ITEM IT ON IT.ID_ACTIVIDAD = AC.ID_ACTIVIDAD AND IT.TIPO = 'MT'
                               LEFT JOIN FAC_MATERIALES MT ON MT.IDMATERIALES=IT.ID_MOMTR
                               INNER JOIN MOVILES_AGENDA MA ON MA.IDAGENDA = A.IDAGENDA
                               INNER JOIN MOVIL MV ON (MV.IDMOVIL = MA.IDMOVIL)
                               INNER JOIN USUARIOS USU ON (A.IDUSUARIO = USU.ID_USUARIO)
                               LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
                               LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
                               LEFT JOIN ONIX_DATOS_CORP ODC ON ODC.IDAGENDA = A.IDAGENDA
					WHERE a.PROGRAMACION='C'
					AND a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                $queryCompleto = $this->data;
				if($queryCompleto<>array())
				{
				   $queryTec = array();
				   foreach($queryCompleto as $id => $value)
				   {
						if($value['IDAG'])
						{
							$sqlTec = "SELECT OTA.NOMBRE, OTA.CEDULA 
										FROM ONIX_TECNICOS_AGENDA OTE
										INNER JOIN ONIX_TECNICOS OTA ON (OTE.ID_ONIX_TECNICOS = OTA.ID_ONIX_TECNICOS)
										WHERE OTE.ID_AGENDA = '".$value['IDAG']."'
										";
							 $this->EjecutaConsulta($sqlTec);
							 $queryTec = $this->data;
							 if($queryTec<>array())
							 {
							   $value['MOVIL'] =  $queryTec[0]['NOMBRE'];
							   $value['CC'] =  $queryTec[0]['CEDULA'];
							 }else
							 {
							   $value['MOVIL'] = '';
							   $value['CC'] = '';
							 }
							 
							unset($value['IDAG']);
						}
						$valores[] = $value;
				   }
				}
				set_time_limit(0);
                return $valores;
    }
	
	/*
	* Informe de Serializados (CPEs)
	* Requerido para la conciliación con el Aliado OK. 
	*/
	function InformeSerializadosCPEs($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
              $sql = " SELECT /*+ PARALLEL(O, 2) */ DISTINCT '' AS ID,
								ALI.IDALIADO AS NIT_ALIADO,
								ALI.NOMBRE AS ALIADO,
								ABS.CODIGO_CENTRO_SAP AS CENTRO,
								ABS.CODIGO_BODEGA_SAP AS BODEGA_SAP,
								CI.MATERIAL  AS COD_SAP,
								CI.DENOMINACION AS NOMBRE_MATERIAL,
								'' AS TIPO,
								'' AS FABRICANTE,
								CI.SERIAL AS SERIAL,
								A.IDORDEN_DE_TRABAJO AS OTH, 
								A.CIUDAD2 AS OTP,
								'1' AS CANTIDAD,
								CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN 
										OSCE.GRAFO
								ELSE
										OCE.GRAFO
								END
								ELSE
										CAST( OSDS.ID_ONIX_SDS as VARCHAR2(50)) 
							   END AS ID_SDS,
								CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN OSCE.CODIG_PROYE_PEP
								ELSE
										OCE.CODIG_PROYE_PEP
									END
								ELSE
								    CASE WHEN ODF.TECNOLOGIA != 'GPON' THEN OSDS.CODIG_PROYECT_PEP
								ELSE
										OSDS.CODIG_PROYECT_GPON_PEP
								   END
								END AS PEP,
								'' AS INSTALADO,
								CI.VALORACION AS CLASE_VALORACION,
								CTIPOTRABAJO(A.ID_TT) AS TIPO_TRABAJO,
								'' AS DIVISON,
								A.CODCIUDAD AS COMUNIDAD,
								A.FECHA_AGENDO AS FECHA_INICIAL_AGENDA,
								OSDS.ID_ONIX_SDS || '-' || CI.SERIAL || '-' || A.CIUDAD2 || '-' || A.IDORDEN_DE_TRABAJO AS SDS_SERIAL_OTP_OTH,
								A.SUSCRIPTOR AS NOMBRE_CLIENTE,
								PFA.FEC_REGISTRO AS FECHA_CIERRE
								FROM AGENDA A
								INNER JOIN ALIADOS ALI ON (a.IDALIADO=ALI.IDALIADO)
								LEFT JOIN ALIADO_BODEGA_SAP_COR ABS ON ABS.ID_ALIADO = ALI.IDALIADO AND ABS.CODCIUDAD = A.CODCIUDAD
								INNER JOIN PRE_FACTURA PFA ON PFA.IDAGENDA = A.IDAGENDA AND PFA.TIPO_ROL = '1'
								INNER JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO = 'A'
								INNER JOIN CO_INVENTARIO_ONYX CI ON CI.ID_AGENDA = A.IDAGENDA
								INNER JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1'
								INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS = ODF.SDS AND OSDS.ESTADO = '1'
								LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
								LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
					WHERE a.PROGRAMACION='C'
					AND a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                set_time_limit(0);
                return $this->data;
    }
	
	
    /*
	* Informe de Serializados (CPEs)
	* Requerido para la conciliación con el Aliado OK. 
	*/
	function InformeNoSerializadosCPEs($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
              $sql = " SELECT /*+ PARALLEL(O, 2) */ DISTINCT '' ID,
								ALI.IDALIADO AS NIT_ALIADO,
								ALI.NOMBRE AS ALIADO,
								ABS.CODIGO_CENTRO_SAP AS CENTRO,
								ABS.CODIGO_BODEGA_SAP AS BODEGA_SAP,
								MT.CODMATERIAL_SAP     AS COD_SAP,
								MT.DESCRIPCION AS NOMBRE_MATERIAL,
								'' AS TIPO,
								'' AS FABRICANTE,
								'' AS SERIAL,
								A.IDORDEN_DE_TRABAJO AS OTH, 
								A.CIUDAD2 AS OTP,
								IT.CANTIDAD AS CANTIDAD,
								CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN 
										OSCE.GRAFO
								ELSE
										OCE.GRAFO
								END
								ELSE
										CAST( OSDS.ID_ONIX_SDS as VARCHAR2(50)) 
							   END AS ID_SDS,
								CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN OSCE.CODIG_PROYE_PEP
								ELSE
										OCE.CODIG_PROYE_PEP
									END
								ELSE
								    CASE WHEN ODF.TECNOLOGIA != 'GPON' THEN OSDS.CODIG_PROYECT_PEP
								ELSE
										OSDS.CODIG_PROYECT_GPON_PEP
								   END
								END AS PEP,
								'' AS INSTALADO,
								'VALORADO' AS CLASE_VALORACION,
								CTIPOTRABAJO(A.ID_TT) AS TIPO_TRABAJO,
								'' AS DIVISON,
								A.CODCIUDAD AS COMUNIDAD,
								A.FECHA_AGENDO AS FECHA_INICIAL_AGENDA,
								OSDS.ID_ONIX_SDS  || '-' || A.CIUDAD2 || '-' || A.IDORDEN_DE_TRABAJO AS SDS_SERIAL_OTP_OTH,
								A.SUSCRIPTOR AS NOMBRE_CLIENTE,
								PFA.FEC_REGISTRO AS FECHA_CIERRE
								FROM AGENDA A
								INNER JOIN ALIADOS ALI ON (a.IDALIADO=ALI.IDALIADO)
								LEFT JOIN ALIADO_BODEGA_SAP_COR ABS ON ABS.ID_ALIADO = ALI.IDALIADO AND ABS.CODCIUDAD = A.CODCIUDAD
								INNER JOIN PRE_FACTURA PFA ON PFA.IDAGENDA = A.IDAGENDA AND PFA.TIPO_ROL = '1'
								INNER JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO = 'A'
								INNER JOIN PRE_ITEM IT ON IT.ID_ACTIVIDAD = AC.ID_ACTIVIDAD
								INNER JOIN FAC_MATERIALES MT ON MT.IDMATERIALES=IT.ID_MOMTR
								INNER JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1'
								INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS = ODF.SDS AND OSDS.ESTADO = '1'
								LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
								LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
					WHERE a.PROGRAMACION='C'
					AND a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                set_time_limit(0);
                return $this->data;
    }
	
	/*
	* Informe de Cuenta a Cuenta OK
	*/
	function InformeCuentaCuenta($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
        $sql = "SELECT /*+ PARALLEL(O, 2) */ DISTINCT
								A.IDAGENDA AS ID,
								A.IDAGENDA AS IDAG,
								TT.CODIGO AS ACTIVIDAD,
								TT.DESCRIPCION AS DESCRIPCION_ACTIVIDAD,
								CASE WHEN FMO.CODIGOMANO_OBRA IS NULL THEN TT.CODIGO ELSE FMO.CODIGOMANO_OBRA END AS CODTRABAJO2,
								CASE WHEN FMO.CODIGOMANO_OBRA IS NULL THEN TT.DESCRIPCION ELSE FMO.DESCRIPCION END AS DESCRIPCION_TRABAJO2,
								TO_CHAR(PFA.FEC_REGISTRO,'DD/MM/YYYY') AS FECHA_CIERRE,
								ALI.ALIADO_SAP AS NIT_ALIADO,
								ALI.NOMBRE AS ALIADO,
								MV.NOMBRE AS MOVIL_TEC,
								MV.NOMBRE AS MOVIL,
								MV.VEHICULO AS CC,
								A.CUENTA AS CUENTA,
								'RE' AS T_USER,
								A.IDORDEN_DE_TRABAJO AS OTH,
								A.CIUDAD2 AS OTP,
								A.CODCIUDAD AS CIUDAD, 
								CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN 
										OSCE.GRAFO
								ELSE
										OCE.GRAFO
								END
								ELSE
										CAST( OSDS.ID_ONIX_SDS as VARCHAR2(50)) 
							   END AS ID_SDS,
								CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN OSCE.CODIG_PROYE_PEP
								ELSE
										OCE.CODIG_PROYE_PEP
									END
								ELSE
								    CASE WHEN ODF.TECNOLOGIA != 'GPON' THEN OSDS.CODIG_PROYECT_PEP
								ELSE
										OSDS.CODIG_PROYECT_GPON_PEP
								   END
								END AS PEP,
								CASE
								WHEN TT.TIPO = 'C' THEN 
								'9999999999'
								ELSE 
								TT.NROSAP
								END CeCo,
								A.CLL AS DIRECCION,
								A.PROGRAMACION AS CLASE,
								USU.NOMBRE AS USUARIO,
								TO_CHAR(PFA.FEC_REGISTRO,'DD/MM/YYYY') AS FACTURADO,
								IT.CANTIDAD AS CANTIDAD,
								AC.TOTAL_MO AS VALOR_MO,
								AC.TOTAL_MT AS VALOR_MATERIAL,
								AC.TOTAL AS TOTAL,
								A.CODRESULTADO AS VISITAS,
								TT.CODSAP_MT AS COD_SAP,
								'NR' AS TARIFA,
								'NR' AS ESTRATO,
								A.CODCIUDAD AS COMUNIDAD,
								'NR' AS CATEGORIA_PRECIO,
								A.SUSCRIPTOR AS NOMBRE_CLIENTE,
								CASE
								 WHEN ODF.SEDE != '0' THEN 
										OSDS.CODIG_PROYECT_PEP
								 ELSE 
										' '
								END SEDE,
								OSCE.DESCRIPCION AS SEDE,
								USU1.NOMBRE AS INTERVENTOR
								FROM AGENDA A
								INNER JOIN ALIADOS ALI ON (A.IDALIADO=ALI.IDALIADO)
								INNER JOIN FAC_CONTRATOMARCO FC ON FC.IDALIADO = ALI.IDALIADO AND FC.ESTADO = 'A'
								INNER JOIN PRE_FACTURA PFA ON PFA.IDAGENDA = A.IDAGENDA AND PFA.TIPO_ROL = '1'
								INNER JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO = 'A'
								INNER JOIN FAC_TTACTIVIDAD TT ON TT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD
								INNER JOIN FAC_MOMTR_TTACT FMT ON FMT.ID_TTACTIVIDAD = TT.ID_TTACTIVIDAD AND FMT.ESTADO = 'A' AND FMT.TIPO = 'MO'
								INNER JOIN PRE_ITEM IT ON IT.ID_ACTIVIDAD = AC.ID_ACTIVIDAD
								LEFT JOIN FAC_MANOOBRA FMO ON (IT.ID_MOMTR = FMO.IDMANO_OBRA)
								INNER JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1'
								INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS = ODF.SDS AND OSDS.ESTADO = '1'
								INNER JOIN MOVILES_AGENDA MA ON MA.IDAGENDA = A.IDAGENDA
								INNER JOIN MOVIL MV ON (MV.IDMOVIL = MA.IDMOVIL)
								INNER JOIN USUARIOS USU ON (A.IDUSUARIO = USU.ID_USUARIO)
								LEFT JOIN USUARIOS USU1 ON (PFA.IDUSUARIO = USU1.ID_USUARIO)
								LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
								LEFT JOIN ONIX_DATOS_CORP ODC ON ODC.IDAGENDA = A.IDAGENDA
								LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
								WHERE A.PROGRAMACION='C'
								AND IT.TIPO = 'MO'
								AND A.CODRESULTADO = 'OK'
					AND a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";
				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                $queryCompleto = $this->data;
				if($queryCompleto<>array())
				{
				   $queryTec = array();
				   foreach($queryCompleto as $id => $value)
				   {
				        $idVal = $id+1;
						$value['ID'] = $idVal;
						if($value['IDAG'])
						{
							$sqlTec = "SELECT OTA.NOMBRE, OTA.CEDULA 
										FROM ONIX_TECNICOS_AGENDA OTE
										INNER JOIN ONIX_TECNICOS OTA ON (OTE.ID_ONIX_TECNICOS = OTA.ID_ONIX_TECNICOS)
										WHERE OTE.ID_AGENDA = '".$value['IDAG']."'
										";
							 $this->EjecutaConsulta($sqlTec);
							 $queryTec = $this->data;
							 if($queryTec<>array())
							 {
							   $value['MOVIL'] =  $queryTec[0]['NOMBRE'];
							   $value['CC'] =  $queryTec[0]['CEDULA'];
							 }else
							 {
							   $value['MOVIL'] = '';
							   $value['CC'] = '';
							 }
							 
						  unset($value['IDAG']);	 
						}
						$valores[] = $value;
				   }
				}
				set_time_limit(0);
                return $valores;
    }
	
	/*
	* Informe de Cuenta a Cuenta No Existosa
	* Este informe es necesario para liquidar las visitas de actividades no Exitosas OK.
	*/
	function InformeCuentaNoExitosa($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
        $Ciudad = implode("','", $Ciudad);
              $sql = "SELECT /*+ PARALLEL(O, 2) */ DISTINCT
								A.IDAGENDA AS ID,
								TT.CODIGO AS ACTIVIDAD,
								TT.DESCRIPCION AS DESCRIPCION_ACTIVIDAD,
								CASE WHEN FMO.CODIGOMANO_OBRA IS NULL THEN TT.CODIGO ELSE FMO.CODIGOMANO_OBRA END AS CODTRABAJO2,
								CASE WHEN FMO.CODIGOMANO_OBRA IS NULL THEN TT.DESCRIPCION ELSE FMO.DESCRIPCION END AS DESCRIPCION_TRABAJO2,
								TO_CHAR(PFA.FEC_REGISTRO,'DD/MM/YYYY') AS FECHA_CIERRE,
								ALI.IDALIADO AS NIT_ALIADO,
								ALI.NOMBRE AS ALIADO,
								MV.NOMBRE AS MOVIL,
								MV.VEHICULO AS CC,
								A.CUENTA AS CUENTA,
								'RE' AS T_USER,
								A.IDORDEN_DE_TRABAJO AS OTH,
								A.CIUDAD2 AS OTP,
								A.CODCIUDAD AS CIUDAD, 
								OSDS.ID_ONIX_SDS AS ID_SDS,
								CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN OSCE.CODIG_PROYE_PEP
								ELSE
										OCE.CODIG_PROYE_PEP
									END
								ELSE
								    CASE WHEN ODF.TECNOLOGIA != 'GPON' THEN OSDS.CODIG_PROYECT_PEP
								ELSE
										OSDS.CODIG_PROYECT_GPON_PEP
								   END
								END AS PEP,
								CASE
								WHEN TT.TIPO = 'C' THEN 
								'999999999'
								ELSE 
								TT.NROSAP
								END CeCo,
								A.CLL AS DIRECCION,
								A.PROGRAMACION AS CLASE,
								USU.NOMBRE AS USUARIO,
								TO_CHAR(PFA.FEC_REGISTRO,'DD/MM/YYYY') AS FACTURADO,
								AC.CANTIDAD AS CANTIDAD,
								AC.TOTAL_MO AS VALOR_MO,
								AC.TOTAL_MT AS VALOR_MATERIAL,
								AC.TOTAL AS TOTAL,
								A.CODRESULTADO AS VISITAS,
								TT.CODSAP_MT AS COD_SAP,
								'NR' AS TARIFA,
								'NR' AS ESTRATO,
								A.CODCIUDAD AS COMUNIDAD,
								'NR' AS CATEGORIA_PRECIO,
								A.SUSCRIPTOR AS NOMBRE_CLIENTE,
								CASE
								 WHEN ODF.SEDE != '0' THEN 
										OSDS.CODIG_PROYECT_PEP
								 ELSE 
										' '
								END SEDE,
								OSCE.DESCRIPCION AS SEDE,
								USU1.NOMBRE AS INTERVENTOR
								FROM AGENDA A
								INNER JOIN ALIADOS ALI ON (A.IDALIADO=ALI.IDALIADO)
								INNER JOIN FAC_CONTRATOMARCO FC ON FC.IDALIADO = ALI.IDALIADO AND FC.ESTADO = 'A'
								INNER JOIN PRE_FACTURA PFA ON PFA.IDAGENDA = A.IDAGENDA AND PFA.TIPO_ROL = '1'
								INNER JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO = 'A'
								INNER JOIN FAC_TTACTIVIDAD TT ON TT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD
								INNER JOIN FAC_MOMTR_TTACT FMT ON FMT.ID_TTACTIVIDAD = TT.ID_TTACTIVIDAD AND FMT.ESTADO = 'A' AND FMT.TIPO = 'MO'
								INNER JOIN PRE_ITEM IT ON IT.ID_ACTIVIDAD = AC.ID_ACTIVIDAD
								LEFT JOIN FAC_MANOOBRA FMO ON (IT.ID_MOMTR = FMO.IDMANO_OBRA)
								INNER JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1'
								INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS = ODF.SDS AND OSDS.ESTADO = '1'
								INNER JOIN MOVILES_AGENDA MA ON MA.IDAGENDA = A.IDAGENDA
								INNER JOIN MOVIL MV ON (MV.IDMOVIL = MA.IDMOVIL)
								INNER JOIN USUARIOS USU ON (A.IDUSUARIO = USU.ID_USUARIO)
								LEFT JOIN USUARIOS USU1 ON (PFA.IDUSUARIO = USU1.ID_USUARIO)
								LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
								LEFT JOIN ONIX_DATOS_CORP ODC ON ODC.IDAGENDA = A.IDAGENDA
								LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
								WHERE A.PROGRAMACION='C'
								AND IT.TIPO = 'MO'
								AND A.CODRESULTADO <> 'OK'
					AND a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                set_time_limit(0);
                return $this->data;
    }

	/*
	* Informe Financiero de Actividades de MO OK
	*/
	function InformeFinancieroActividadesMO($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
		
        $Ciudad = implode("','", $Ciudad);
              $sql = "SELECT /*+ PARALLEL(O, 2) */ DISTINCT 
							  	TO_CHAR(PFA.FEC_REGISTRO,'DD/MM/YYYY') AS FECHA_CIERRE,
								CASE
								WHEN TT.TIPO = 'C' THEN 
								'9999999999'
								ELSE 
								TT.NROSAP
								END C_COSTOS,
							  CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN 
										OSCE.GRAFO
								ELSE
										OCE.GRAFO
								END
								ELSE
										CAST( OSDS.ID_ONIX_SDS as VARCHAR2(50)) 
							   END AS ID_SDS,
							  ALI.IDALIADO              AS ID_ALIADO,
							  ALI.NOMBRE                AS ALIADO,
							  TT.ID_TTACTIVIDAD         AS ID_ACTIVIDAD,
							  TT.CODIGO                 AS COD_ACTIVIDAD,
							  TT.DESCRIPCION            AS DESCRIP_ACTIVIDAD,
							  IT.CANTIDAD               AS CANTIDAD,
							  AC.TOTAL_MO               AS VALOR_MO,
							  TT.CODSAP_MT              AS COD_SAP,
							  ABS.CODIGO_BODEGA_SAP     AS BODEGA_SAP
							FROM AGENDA A
							INNER JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1'
							INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS  = ODF.SDS AND OSDS.ESTADO = '1'
							INNER JOIN ALIADOS ALI ON (A.IDALIADO=ALI.IDALIADO) 
							INNER JOIN PRE_FACTURA PFA ON PFA.IDAGENDA  = A.IDAGENDA AND PFA.TIPO_ROL = '1'
							INNER JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO    = 'A'
							INNER JOIN PRE_ITEM IT ON IT.ID_ACTIVIDAD = AC.ID_ACTIVIDAD AND IT.TIPO = 'MO'
							INNER JOIN FAC_TTACTIVIDAD TT ON TT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD
							LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
							LEFT JOIN ALIADO_BODEGA_SAP_COR ABS ON ABS.ID_ALIADO    = ALI.IDALIADO AND ABS.CODCIUDAD   = A.CODCIUDAD
							LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
							WHERE A.PROGRAMACION='C'
					AND a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
					TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                $this->EjecutaConsulta($sql);
                set_time_limit(0);
                return $this->data;
    }
	
	/*
	* Informe Reporte MO
	* Se requiere adicionar un informe para liquidación de MO, acorde con el proceso establecido en MG Residencial, para SAP sinergia OK
	*/
	function InformeReporteMO($FechaInicial, $FechaFinal,$Aliado, $Ciudad/*, $Carpeta*/)
    {
		$queryCompleto = array();
		$valores = array();
		$Aliado = implode(",", $Aliado);
		$a = str_replace('/','',date('d/m/Yhis',strtotime(date('Y/m/d'))));
		$b = 'RTP0192MO'.$a;
        $Ciudad = implode("','", $Ciudad);
		
              $sql = "SELECT /*+ PARALLEL(O, 2) */ DISTINCT TRIM('CO8') AS GRUPO_DE_COMPRA,
								  '".$b."' AS FILENAME,
                                  'CO' AS PAIS,
								  'CO02' AS SOCIEDAD,
								  'RF' AS OPERACIÓN,
								  TT.CODIGO AS CODTRABAJO,
								  TT.DESCRIPCION AS DESCRIPCION_TRABAJO,
								  CASE WHEN FMO.CODIGOMANO_OBRA IS NULL THEN TT.CODIGO ELSE FMO.CODIGOMANO_OBRA END AS CODTRABAJO2,
								  CASE WHEN FMO.CODIGOMANO_OBRA IS NULL THEN TT.DESCRIPCION ELSE FMO.DESCRIPCION END AS DESCRIPCION_TRABAJO2,
								  TO_CHAR(PFA.FEC_REGISTRO, 'YYYY/MM/DD') AS FECHA_CIERRE,
                                  ALI.ALIADO_SAP        AS NIT_ALIADO,
                                  Replace(ALI.nombre, Chr(9), ' ') AS NOMBRE_PROVEEDOR,
								  MV.NOMBRE          AS MOVIL,
                                  MV.CEDULA          AS CEDULA,
								  MV.NOMBRE          AS NOMBRE_TECNICO,
								  A.CUENTA           AS CUENTA,
                                  CASE WHEN A.TIPO_USER IS NULL THEN 'RE' ELSE A.TIPO_USER END AS TIPO_USER,				
                                  A.IDORDEN_DE_TRABAJO AS OTH,
                                  RRC.CODREGIONAL      AS REGIONAL,
                                  A.CODCIUDAD          AS COD_CIUDAD,
								  CASE WHEN OCE.NIT = A.AVISOSAP THEN 
								  CASE WHEN ODF.SEDE != 0 AND OSCE.DESCRIPCION != 'SIN SEDE'
									THEN 
										OSCE.GRAFO
									ELSE
											OCE.GRAFO
									END
									ELSE
											CAST( OSDS.ID_ONIX_SDS as VARCHAR2(50)) 
								   END AS ID_SDS,
                                  CASE
								    WHEN OSDS.TIPO_RED = '1' OR OSDS.TIPO_RED = '2'
								    THEN
								      CASE
								        WHEN OSDS.TIPO_RED = '1'
								        THEN CAST( '2' AS VARCHAR2(50))
								        ELSE CAST( '1' AS VARCHAR2(50))
								      END
								    ELSE CAST( OSDS.TIPO_RED AS VARCHAR2(50))
								  END           AS RED_NODO,
                                  IT.CANTIDAD          AS CANTIDAD,                    
                                  CASE WHEN TT.TIPO = 'C' THEN '9999999999' ELSE TT.NROSAP END CECO,
                                  CASE WHEN A.ESTRATO IS NULL THEN 'NR' ELSE A.ESTRATO END AS ESTRATO,
								  A.IDAGENDA AS IDAG
								FROM AGENDA A
								INNER JOIN ALIADOS ALI ON (A.IDALIADO=ALI.IDALIADO)
								INNER JOIN MOVILES_AGENDA MA ON MA.IDAGENDA = A.IDAGENDA
								INNER JOIN MOVIL MV ON (MV.IDMOVIL = MA.IDMOVIL)
								INNER JOIN RR_CIUDADES RRC ON RRC.CODIGO = A.CODCIUDAD
								INNER JOIN ONIX_DATOS_FACTURA ODF ON ODF.ID_AGENDA = A.IDAGENDA AND ODF.TIPO_ROL = '1'
								INNER JOIN ONIX_SDS OSDS ON OSDS.ID_SDS  = ODF.SDS AND OSDS.ESTADO = '1'
								INNER JOIN PRE_FACTURA PFA ON PFA.IDAGENDA  = A.IDAGENDA AND PFA.TIPO_ROL = '1'
								INNER JOIN PRE_ACTIVIDAD AC ON AC.ID_FACTURA = PFA.ID_FACTURA AND AC.ESTADO = 'A'
								INNER JOIN PRE_ITEM IT ON IT.ID_ACTIVIDAD = AC.ID_ACTIVIDAD AND IT.TIPO = 'MO'
								LEFT JOIN FAC_MANOOBRA FMO ON (IT.ID_MOMTR = FMO.IDMANO_OBRA) 
								INNER JOIN FAC_TTACTIVIDAD TT ON TT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD
								LEFT JOIN ONIX_CLIENTE_ESPECIAL OCE ON OCE.NIT = A.AVISOSAP
								LEFT JOIN ONIX_SEDE_CLIENTE_ESPECIAL OSCE ON OSCE.ID_ONIX_SEDE_CLIENTE_ESPECIAL = ODF.SEDE
								WHERE A.PROGRAMACION ='C'
							AND a.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial . "','YYYY/MM/DD') AND 
							TO_DATE('" . $FechaFinal . "','YYYY/MM/DD')";

				if ($Aliado != "TODOS") {
                    $sql.= " AND A.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND A.CODCIUDAD IN ('" . $Ciudad . "')";
				}
               
                 $this->EjecutaConsulta($sql);
                 $queryCompleto = $this->data;

				if($queryCompleto<>array())
				{
				   $queryTec = array();
				   foreach($queryCompleto as $id => $value)
				   {
						if($value['IDAG'])
						{
							$sqlTec = "SELECT OTA.NOMBRE, OTA.CEDULA 
										FROM ONIX_TECNICOS_AGENDA OTE
										INNER JOIN ONIX_TECNICOS OTA ON (OTE.ID_ONIX_TECNICOS = OTA.ID_ONIX_TECNICOS)
										WHERE OTE.ID_AGENDA = '".$value['IDAG']."'
										";
							 $this->EjecutaConsulta($sqlTec);
							 $queryTec = $this->data;
							 if($queryTec<>array())
							 {
							   $value['NOMBRE_TECNICO'] =  $queryTec[0]['NOMBRE'];
							   $value['CEDULA'] =  $queryTec[0]['CEDULA'];
							 }else
							 {
							   $value['NOMBRE_TECNICO'] = '';
							   $value['CEDULA'] = '';
							 }
						
						  unset($value['IDAG']);
						}
						
						$valores[] = $value;
				   }
				}
				set_time_limit(0);
                return $valores;
				
				
				
				
    }

/***/
/*
/*  Informe para validar equipos y codigos de distribucion para DTH
/*
/*
/***/	
public function cantidadCostos($idgrupo, $nodo, $diaagenda, $idMaterial, $cantidad)
	{
	   $sql = "select ROUND(
							Fac_Promediomt_Cg(
							".$idgrupo.",Ccriteriog (
							'".$nodo."','CODAREA'),
							'".$diaagenda."', 
							".$idMaterial.",SUM(".$cantidad." * 1)),3) AS CANTIDAD from dual";
							$this->EjecutaConsulta($sql);
						    $queryCostos = $this->data;
							//imprimir($queryCostos);
							//die;
	    return $queryCostos;
	}
	
	
	public function ConsultaActividadesOt($idagenda, $nodo, $diaagenda)
	{
	  $queryActividades = array();
	  $sql = "SELECT /*+ PARALLEL(2)*/ DISTINCT FA.IDAGENDA,
			  TT.CODIGO,
			  TT.DESCRIPCION,
			  AC.CANTIDAD,
			  MT.CODMATERIAL_SAP,
			  MT.UNIDAD,
			  MT.DESCRIPCION AS DESCMATERIAL,
			  FG.ID_GRUPOS_TT,
			  MT.IDMATERIALES,
			  FFP.PROMEDIO,
			  (AC.CANTIDAD*FFP.PROMEDIO) AS TOTAL_MT
			FROM FAO_FACTURA FA
			INNER JOIN FAO_ACTIVIDAD AC
			  ON AC.ID_FACTURA = FA.ID_FACTURA
			  AND AC.ESTADO = 'A'
			INNER JOIN FAC_TTACTIVIDAD TT
			  ON (TT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD)
			INNER JOIN MASTER_GRUPO_TT FG
			  ON (FG.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD AND FG.ESTADO='Y')
			INNER JOIN FAC_MATIN_TTAC FM
			  ON (FM.ID_GRUPO_TT = FG.ID_GRUPOS_TT)
			INNER JOIN FAC_FICHA_PROMEDIO FFP
			  ON (FFP.ID_MATERIAL = FM.IDMATERIALES
			  AND FFP.CRITERIOG   ='".$nodo."'
			  AND FM.ID_GRUPO_TT  = FFP.ID_GRUPO_TT
			  AND '$diaagenda' BETWEEN TO_CHAR(FFP.FECHAIN,'YYYY/MM/DD') AND TO_CHAR(FFP.FECHAFIN,'YYYY/MM/DD'))
			INNER JOIN FAC_MATERIALES MT
			  ON (MT.IDMATERIALES = FFP.ID_MATERIAL)
			WHERE FA.IDAGENDA   = '".$idagenda."'
			  AND FA.ESTADO = 'A'";
						$this->EjecutaConsulta($sql);
						$queryActividades = $this->data;
						//imprimir($queryActividades);
						//die;
						
						if($queryActividades<>array())
						{
						    $cantidadCostos = array();
							foreach($queryActividades as $idAc => $valueAct)
							{
							   $actividadesFac[] = $valueAct;
							}
							$queryActividades = $actividadesFac;
							//imprimir($queryActividades);
						    //die;
						}
						
		return $queryActividades;
	}
	
	public function ConsultaActividades($idagenda, $calendario)
	{
	   
	  if($calendario=='IN')
	   {
		 $value = " AND TT.CODIGO LIKE 'INS%' ";
	   }else
	   {
	     $value = " AND TT.CODIGO LIKE 'DE%' ";
	   }
	   $queryActividades1 = array();
	   $sql = " SELECT DISTINCT 
						FA.IDAGENDA,
						TT.CODIGO,
						TT.DESCRIPCION
                        FROM FAO_FACTURA FA  
						 INNER JOIN FAO_ACTIVIDAD AC ON (FA.ID_FACTURA = AC.ID_FACTURA)
						 INNER JOIN FAC_TTACTIVIDAD TT ON (AC.ID_TTACTIVIDAD = TT.ID_TTACTIVIDAD)
						where 
						FA.IDAGENDA = '".$idagenda."'
						$value
						";
						$this->EjecutaConsulta($sql);
						$queryActividades1 = $this->data;
						
						
		return $queryActividades1;
	
	}
	
	public function ConsultaInventarios($idagenda)
	{
	  $queryActividades = array();
	  $valores = array();
	  $sql = "SELECT DISTINCT AGE.IDAGENDA,
				  AGE.IDORDEN_DE_TRABAJO,
				  AGE.CODNODO,
				  AGE.CUENTA,
				  RC.NOMBRE AS CIUDAD,
				  RG.NOMBRE AS REGIONAL,
				  TT.NOMBRE_TIPO AS TIPO_TRABAJO,
				  ALI.NOMBRE AS NOMBRE_ALIADO,
				  AGE.CODRESULTADO,
				  AGE.PROGRAMACION,
				  TO_CHAR(AGE.DIAAGENDA,'YYYY/MM/DD') AS DIAAGENDA,
				  AGE.FECHA_AGENDO,
				  AGE.CALENDARIO,
				  (SELECT DISTINCT AB.CODIGO_BODEGA_SAP
				  FROM ALIADO_BODEGA_SAP AB
				  WHERE TO_NUMBER(AB.ID_ALIADO) = AGE.IDALIADO
				  AND AB.CODCIUDAD = AGE.CODCIUDAD
				  AND ROWNUM < 2
				  ) AS BODEGASAP,
				  CESTADO_INV(AGE.IDAGENDA) AS ESTADO_ALIADOS,
				  SAP.CEDULA,
				  SAPALI.CODIGO AS COD_DISTRIBUIDOR,
				  '' AS AREA,
				  '' AS CODIGO_ACTIVIDAD,
				  '' AS DESCRIPCION_ACTIVIDAD,
				  '' AS CANTIDAD,
				  CONSULTACODSAP(INV.TIPO, INV.FABRICANTE) AS CODMATERIAL_SAP,
				  '' AS UNIDAD,
				  '' AS DESCRIPCION2,
				  '' AS DESCRIPCION_ACTIVIDAD,
				  CONSULTA_INVENTARIO(INV.TIPO, INV.FABRICANTE) AS DESCMATERIAL,
				  '1' AS PROMEDIO,
				  '1' AS TOTAL_MT,
				  INV.SERIAL1,
				  INV.SERIAL2,
				  INV.TIPO,
				  INV.FABRICANTE
				FROM CO_INVENTARIO INV
				INNER JOIN INVENTARIO INVENTA
				  ON (INVENTA.TIPO = INV.TIPO
				  AND INVENTA.FABRICANTE = INV.FABRICANTE)
				INNER JOIN AGENDA AGE
				  ON (AGE.IDAGENDA = '".$idagenda."')
				INNER JOIN RR_CIUDADES RC
				  ON (RC.CODIGO = AGE.CODCIUDAD)
				INNER JOIN RR_REGIONALES RG
				  ON (RG.CODIGO = RC.CODREGIONAL)
				INNER JOIN TIPO_TRABAJO TT
				  ON (TT.ID_TT = AGE.ID_TT)
				INNER JOIN ALIADOS ALI
				  ON (ALI.IDALIADO = AGE.IDALIADO)
				INNER JOIN MOVIL MOV
				  ON (MOV.IDMOVIL = AGE.IDMOVIL)
				INNER JOIN SAPAL_CARGUE SAP
				  ON (SAP.CEDULA = MOV.NOMBRE AND SAP.ACCION = 'CARGUE')
				INNER JOIN SAPAL_ALIADOS SAPALI
				  ON (SAPALI.ID = SAP.ALIADOS_ID)
				INNER JOIN FAO_FACTURA FA
				  ON (FA.IDAGENDA = '".$idagenda."')
				WHERE INV.IDAGENDA = '".$idagenda."'";

					$this->EjecutaConsulta($sql);
					$queryInv = $this->data;
					/*
					foreach($queryInv as $id =>$inv)
					{
					   $idagenda = $inv['IDAGENDA'];
					   $calendario = $inv['CALENDARIO'];
					   $acti = $this->ConsultaActividades($idagenda, $calendario);
					   if($acti<>array())
					   {
					       foreach($acti as $id2 => $value)
						   {
						      if($idagenda == $value['IDAGENDA'])
							  {
							     $inv['CODIGO_ACTIVIDAD'] = $value['CODIGO'];
								 $inv['DESCRIPCION_ACTIVIDAD'] = $value['DESCRIPCION'];
								 $inv['DESCRIPCION2'] = $value['DESCRIPCION'];
								 
							  }
							 
						   }
					   }
					   $valores[] = $inv;
					}
					*/
		return $queryInv;
	}
	

	public function ConsultaEquipoRecogido($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta)
	{
	    $Aliado = implode(",", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);
			   
			   $sql = "SELECT AGE.IDAGENDA,
					  AGE.IDORDEN_DE_TRABAJO,
					  AGE.CODNODO,
					  AGE.CUENTA,
					  RC.NOMBRE AS CIUDAD,
					  RG.NOMBRE AS REGIONAL,
					  TT.NOMBRE_TIPO AS TIPO_TRABAJO,
					  ALI.NOMBRE AS NOMBRE_ALIADO,
					  AGE.CODRESULTADO,
					  AGE.PROGRAMACION,
					  TO_CHAR(AGE.DIAAGENDA,'YYYY/MM/DD') AS DIAAGENDA,
					  AGE.FECHA_AGENDO,
					  AGE.CALENDARIO,
					  (SELECT DISTINCT AB.CODIGO_BODEGA_SAP
					  FROM ALIADO_BODEGA_SAP AB
					  WHERE TO_NUMBER(AB.ID_ALIADO) = AGE.IDALIADO
					  AND AB.CODCIUDAD = AGE.CODCIUDAD
					  AND ROWNUM < 2
					  ) AS BODEGASAP,
					  CESTADO_INV(AGE.IDAGENDA) AS ESTADO_ALIADOS,
					  SAP.CEDULA,
					  SAPALI.CODIGO AS COD_DISTRIBUIDOR,
					  CAREA (AGE.CODNODO) AS AREA
					FROM AGENDA AGE
					INNER JOIN FAC_OPS FOP
					  ON (FOP.IDAGENDA = AGE.IDAGENDA
					  AND FOP.ESTADO  IN ('P', 'V'))
					INNER JOIN RR_CIUDADES RC
					  ON (RC.CODIGO = AGE.CODCIUDAD)
					INNER JOIN RR_REGIONALES RG
					  ON (RG.CODIGO = RC.CODREGIONAL)
					INNER JOIN TIPO_TRABAJO TT
					  ON (TT.ID_TT = AGE.ID_TT)
					INNER JOIN ALIADOS ALI
					  ON (ALI.IDALIADO = AGE.IDALIADO)
					INNER JOIN MOVIL MOV
					  ON (MOV.IDMOVIL = AGE.IDMOVIL)
					INNER JOIN SAPAL_CARGUE SAP
					  ON (SAP.CEDULA = MOV.NOMBRE AND SAP.ACCION = 'CARGUE')
					INNER JOIN SAPAL_ALIADOS SAPALI
					  ON (SAPALI.ID = SAP.ALIADOS_ID)
					INNER JOIN RR_AREAS RA
					  ON RA.CODIGO = CAREA (AGE.CODNODO)
					WHERE AGE.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial .           
					  " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .           
					  " 23:59:59','YYYY-MM-DD HH24:MI:SS')
					  AND AGE.CODRESULTADO = 'OK'
					  AND AGE.PROGRAMACION IN  ('L','O')  ";


                if ($Aliado != "TODOS") {
                    $sql.= " AND age.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND age.ID_TT IN ('" . $Carpeta . "')";
				}
				
				
				$this->EjecutaConsulta($sql);
				$queryCompleto = $this->data;
				//imprimir($queryCompleto);
				//die;
				$equipos = array();
				$valores = array();
				foreach ($queryCompleto as $id => $valorData) 
				{
				   $idagenda = $valorData['IDAGENDA'];
				   $nodo = $valorData['AREA'];
				   $diaagenda = $valorData['DIAAGENDA'];
				   $idagendaActividades = $this->ConsultaActividadesOt($idagenda, $nodo, $diaagenda);
				   //imprimir($idagendaActividades);
				   if($idagendaActividades<>array())
				   {
					   foreach($idagendaActividades as $idag => $value)
					   {
					      //imprimir($value);
						  if($value['IDAGENDA'] == $idagenda)
						  {
						        $valorData['CODIGO_ACTIVIDAD'] =  @$value['CODIGO'];
								$valorData['DESCRIPCION_ACTIVIDAD'] =  @$value['DESCRIPCION'];
								$valorData['CANTIDAD'] =  @$value['CANTIDAD'];
								$valorData['CODMATERIAL_SAP'] =  @$value['CODMATERIAL_SAP'];
								$valorData['UNIDAD'] =  @$value['UNIDAD'];
								$valorData['DESCRIPCION'] =  @$value['DESCRIPCION'];
								$valorData['DESCMATERIAL'] =  @$value['DESCMATERIAL'];
								$valorData['PROMEDIO'] =  @$value['PROMEDIO'];
								$valorData['TOTAL_MT'] =  @$value['TOTAL_MT'];

								$valorData['NOMBRE_EQUIPO'] =  @$value['NOMBRE_EQUIPO'];
								$valorData['COD_SAP'] =  @$value['COD_SAP'];
								$valorData['SERIAL1'] =  @$value['SERIAL1'];
								$valorData['SERIAL2'] =  @$value['SERIAL2'];
								$valores[] = $valorData;
						  } 
						}
					}
					$inventarios = $this->ConsultaInventarios($idagenda);
					$valores = array_merge($valores,$inventarios);
				}
				
				set_time_limit(0);				
				foreach($valores as $id => $value){
					if($value['PROMEDIO']=='0'){
						unset($valores[$id]);						
					}
				}
				
                set_time_limit(0);
                return $valores;
	
	}
	
	public function TecnicoVisita($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta)
	{
	    $Aliado = implode(",", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);
			   
			   $sql = "SELECT /*+ PARALLEL(3) */ AGE.IDAGENDA,
						  AGE.CUENTA,
						  AGE.IDORDEN_DE_TRABAJO,
						  TO_CHAR(AGE.DIAAGENDA,'YYYY/MM/DD') AS DIAAGENDA,
						  TO_CHAR(AGE.FECHA_AGENDO,'YYYY/MM/DD') AS FECHA_AGENDA,
						  RF.FINICIAL_RF|| ' - '|| RF.FFINAL_RF AS FECHA_FRANJA,
						  RH.HINICIAL_RH|| ' - '|| RH.HFINAL_RH AS HORA_FRANJA,
						  TT.NOMBRE_TIPO,
						  AGE.SUSCRIPTOR,
						  AGE.CLL
						  || ' '|| AGE.NUMDIR|| ' '|| AGE.APTO AS DIRECCION,
						  AGE.CODCIUDAD,
						  AGE.CODNODO,
						  AGE.TELEFONO1,
						  AGE.TELEFONO2,
						  MOV.NOMBRE_USU AS TECNICO,
						  MOV.NOMBRE AS IDENTIFICACION,
						  MOV.APELLIDO_USU COD_SAP,
						  AGE.CODRESULTADO,
						  AGE.PROGRAMACION,
						  AGE.CALENDARIO
						FROM AGENDA AGE
						INNER JOIN RANGO_HORA RH
						  ON (RH.ID_RH = AGE.ID_RH)
						INNER JOIN RANGO_FECHA RF
						  ON (RF.ID_RF = RH.ID_RF)
						INNER JOIN RR_CIUDADES RC
						  ON (RC.CODIGO = AGE.CODCIUDAD)
						INNER JOIN RR_REGIONALES RG
						  ON (RG.CODIGO = RC.CODREGIONAL)
						INNER JOIN TIPO_TRABAJO TT
						  ON (TT.ID_TT = AGE.ID_TT)
						INNER JOIN ALIADOS ALI
						  ON (ALI.IDALIADO = AGE.IDALIADO)
						INNER JOIN MOVIL MOV
						  ON (MOV.IDMOVIL = AGE.IDMOVIL)
						WHERE AGE.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial .           
						  " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .           
						  " 23:59:59','YYYY-MM-DD HH24:MI:SS')";
						

                if ($Aliado != "TODOS") {
                    $sql.= " AND age.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND age.ID_TT IN ('" . $Carpeta . "')";
				}
				
				$this->EjecutaConsulta($sql);
                //imprimir($this->data);
				//die;
                set_time_limit(0);
                return $this->data;
	
	}
	
	
	public function SAP($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta ,$returnSql = false){
		$Aliado = is_array($Aliado) ? implode(",", $Aliado) : $Aliado;
		$Carpeta = is_array($Carpeta) ?  implode("','", $Carpeta) : $Carpeta;
		$Ciudad = is_array($Ciudad) ? implode("','", $Ciudad) : $Ciudad;
			   $filter = $returnSql ? " AND AGE.PROGRAMACION = 'E' " : '';
			   //$a = str_replace('/','',date('y/m/d',strtotime($FechaInicial)));
			   $a = str_replace('/','',date('d/m/Yhis',strtotime($FechaInicial)));
			   $b = 'RTP0192MO'.$a;
			   $sql = " SELECT TRIM('C08') as GRUPO_DE_COMPRA,
							   '".$b."' AS FILENAME,
							   'CO' AS PAIS,
							   'CO02' AS SOCIEDAD,
							   'RF' AS OPERACION,
							   TT.CODIGO AS CODTRABAJO, 
						       TT.DESCRIPCION AS DESCRIPCION_TRABAJO,
							   CASE 
								 WHEN 
									M.CODIGOMANO_OBRA IS NULL
								  THEN 
													TT.CODIGO
								  ELSE 
													M.CODIGOMANO_OBRA
								END AS CODTRABAJO2,
								CASE 
								 WHEN 
									M.CODIGOMANO_OBRA IS NULL
								  THEN 
													TT.DESCRIPCION
								  ELSE 
													M.DESCRIPCION
								END AS DESCRIPCION_TRABAJO2,
							   to_char(age.diaagenda,'YYYY/MM/DD') AS DIAAGENDA,
							   ali.ALIADO_SAP AS NIT_PROVEEDOR,
							   Replace(ali.nombre, Chr(9), ' ') as NOMBRE_PROVEEDOR,
							   CASE WHEN MOV.CEDULA IS NULL THEN MDM.CEDULA ELSE MOV.CEDULA END AS IDMOVIL,
							   CASE WHEN MOV.CEDULA IS NULL THEN MDM.CEDULA ELSE MOV.CEDULA    END AS CEDULA_TECNICO,
							   CASE WHEN MOV.NOMBRE_USU IS NULL THEN UPPER(MDM.NOMBRES) ELSE   UPPER(MOV.NOMBRE_USU)  END AS NOMBRE_TECNICO,
							   age.cuenta,
							   CASE 
								 WHEN 
									age.TIPO_USER IS NULL
								  THEN 
													'0'
								  ELSE 
													age.TIPO_USER
								END AS TIPO_USER,
							   age.idorden_de_trabajo AS ORDEN, 
							   rg.CODIGO || '-' || Carea (age.codnodo) as regional,
							   age.CODCIUDAD,
							   age.codnodo,
							    CASE 
								WHEN 
									 SUBSTR(NDD.NOMBRE,-8,3) = 'UNI'
								THEN 
								 CASE	
									WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'BID'
								  THEN 
									 '4'
								  ELSE 
									 '2'
								  END
								ELSE
									CASE	
									WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'DTH'
									  THEN 
										 '4'
									  ELSE 
										 '1'
									  END
								END AS RED_NODO,
						       CASE 
								 WHEN 
									M.CODIGOMANO_OBRA IS NULL
								  THEN 
													AC.CANTIDAD
								  ELSE 
													I.CANTIDAD
								END AS CANTIDAD,
								CASE 
								 WHEN 
									Ccostos_New_MT (AC.ID_TTACTIVIDAD,AGE.CODNODO) IS NULL
								  THEN 
													'9999999999'
								  ELSE 
													Ccostos_New_MT (AC.ID_TTACTIVIDAD,AGE.CODNODO)
								END AS CCOSTO,
							   TO_DATE(age.diaagenda) AS ESTRATO
                        FROM agenda age 
						INNER join FAC_OPS fop on (fop.idagenda = age.idagenda and fop.ESTADO IN ('P', 'V'))
						INNER JOIN PRE_FACTURA FA  ON (FA.IDAGENDA = FOP.IDAGENDA)
						INNER JOIN PRE_ACTIVIDAD AC ON (FA.ID_FACTURA = AC.ID_FACTURA)
					    LEFT JOIN PRE_ITEM I ON (I.ID_ACTIVIDAD = AC.ID_ACTIVIDAD AND I.TIPO = 'MO')
						LEFT JOIN FAC_MANOOBRA M ON (I.ID_MOMTR = M.IDMANO_OBRA AND I.TIPO = 'MO')
						INNER JOIN FAC_TTACTIVIDAD TT ON (AC.ID_TTACTIVIDAD = TT.ID_TTACTIVIDAD)
						INNER join rr_ciudades rc on (rc.codigo = age.codciudad)
						INNER join rr_regionales rg on (rg.codigo = rc.codregional)
						LEFT JOIN RR_NODOS NDD ON (NDD.CODIGO = AGE.CODNODO AND NDD.ESTADO = 'A')
						INNER join tipo_trabajo tt on (tt.id_tt = age.id_tt)	
						INNER join aliados ali on (ali.idaliado = age.idaliado)
						LEFT join movil mov on (mov.idmovil = age.idmovil)
						LEFT JOIN GESTIONWFM.MGC_DATOS_MOVIL MDM ON MDM.ID_DATO_MOVIL = AGE.IDMOVIL
						INNER JOIN RR_AREAS RA ON RA.CODIGO = Carea (AGE.CODNODO)
						where 
						age.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial .
											" 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
											" 23:59:59','YYYY-MM-DD HH24:MI:SS')
						and age.codresultado = 'OK'  {$filter}
						and TT.HOMOLOGA_SAP = 'Y'
						AND tt.id_tt NOT IN (1685,1283)
						AND age.IDAGENDA NOT  IN
						  (SELECT DISTINCT IDAGENDA_HIJA FROM AG_OT_EXCHANGE
						  )
						";

						
				 if ($Aliado != "TODOS") {
                    $sql.= " AND age.IDALIADO IN (".$Aliado.")";
                }else
				{
				    $sql.= " AND age.idaliado not in (830053800,
												8300538004, 
												83005380042, 
												83005380044, 
												83005380043, 
												83005380045, 
												83005380046, 
												83005380047,
												900208029) ";
				}
				if ($Ciudad != "TODOS") {
					$sql.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND age.ID_TT IN ('" . $Carpeta . "')";
				}
				
				if($returnSql) return $sql;
				
                $this->EjecutaConsulta($sql);
				// $queryCompleto = $this->data;
				
				$valores = $this->calculaRecargos($this->data);
				
				return $valores;
	
	}

  public function processArray($array,$keys,$complemet = ''){
  	for ($i=0; $i < count($array); $i++) { 
  		for ($j=0; $j < count($keys); $j++) { 
  			$array[$i][$keys[$j]] = $array[$i][$keys[$j]] != '' ? $array[$i][$keys[$j]] : $array[$i][$keys[$j].$complemet];
  			unset($array[$i][$keys[$j].$complemet]);
  		}
  	}
  	return $array;
  }

  public function SAP_OPS($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta){
	    $Aliado = implode(",", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);	
		$queryCompletoAg = array();
		$queryCompletoWF = array();
		$completasActividadessinY = array();
		$ots = '';
	    $qry = "";
	    $a = str_replace('/','',date('d/m/Yhis',strtotime($FechaInicial)));
		$b = 'RTP0192MO'.$a;
	    $sql = "SELECT DISTINCT TRIM('C08') AS GRUPO_DE_COMPRA,
			    '{$b}' AS FILENAME,
			    'CO' AS PAIS,
			    'CO02' AS SOCIEDAD,
			    'RF' AS OPERACION,
			    CASE
			        WHEN OPO.ORDEN IS NOT NULL THEN OPO.ACTIVIDAD
			        ELSE FACTT.CODIGO
			    END AS CODTRABAJO,
			    CASE
			        WHEN OPO.ORDEN IS NOT NULL THEN OPACT.NOMBRE_ACTIVIDAD
			        ELSE FACTT.DESCRIPCION
			    END AS DESCRIPCION_TRABAJO,
	        	CASE
                    WHEN OPO.ORDEN IS NOT NULL THEN OPO.ACTIVIDAD
                    ELSE FACTT.CODIGO
                END
			     AS CODTRABAJO2,
	        	CASE
                    WHEN OPO.ORDEN IS NOT NULL THEN OPACT.NOMBRE_ACTIVIDAD
                    ELSE FACTT.DESCRIPCION
                END
			    AS DESCRIPCION_TRABAJO2,
			    to_char(OPO.FECHA_CIERRE_RR,'YYYY/MM/DD') AS FECHA_CIERRE,
			    ALI.ALIADO_SAP AS NIT_PROVEEDOR,
			    Replace(ALI.NOMBRE, Chr(9), ' ') AS NOMBRE_PROVEEDOR,
			    CASE
			        WHEN MOV.CEDULA IS NULL THEN MDM.CEDULA
			        ELSE MOV.CEDULA
			    END AS IDMOVIL,
			    CASE
			        WHEN MOV.CEDULA IS NULL THEN MDM.CEDULA
			        ELSE MOV.CEDULA
			    END AS CEDULA_TECNICO,
			    CASE
			        WHEN MOV.NOMBRE_USU IS NULL THEN UPPER(MDM.NOMBRES)
			        ELSE UPPER (MOV.NOMBRE_USU)
			    END AS NOMBRE_TECNICO,
			    AGE.CUENTA AS CUENTA,
			    CASE
			        WHEN AGE.TIPO_USER IS NULL THEN '0'
			        ELSE AGE.TIPO_USER
			    END AS TIPO_USER,
			    AGE.IDORDEN_DE_TRABAJO AS ORDEN,
			    rg.CODIGO || '-' || Carea (AGE.CODNODO) AS REGIONAL,
		        AGE.CODCIUDAD AS CODCIUDAD,
		        AGE.CODNODO AS CODNODO,
		        CASE
		            WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'UNI' THEN 
		            	CASE
							WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'BID' THEN '4'
							ELSE '2'
						END
		            ELSE 
		            	CASE
							WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'DTH' THEN '4'
							ELSE '1'
		                END
		        END AS RED_NODO,
		        OPO.CANTIDAD,
		        CASE
		            WHEN Ccostos_New_MT (FACTT.ID_TTACTIVIDAD,AGE.CODNODO) IS NULL THEN '9999999999'
		            ELSE Ccostos_New_MT (FACTT.ID_TTACTIVIDAD,AGE.CODNODO)
		        END AS CCOSTO,
		        AGE.DIAAGENDA ESTRATO
				FROM AGENDA AGE
				INNER JOIN OPS.OPS_ACT_ORDEN OPO 	ON AGE.IDORDEN_DE_TRABAJO = OPO.ORDEN AND OPO.ESTADO IN ('A') AND
				 OPO.ID_TIPO_ORDEN = CASE WHEN AGE.PROGRAMACION = 'O' THEN 1 WHEN AGE.PROGRAMACION = 'L' THEN 2 END 
				INNER JOIN FAC_TTACTIVIDAD FACTT 	ON OPO.ACTIVIDAD = FACTT.CODIGO 
				INNER JOIN RR_CIUDADES RC 			ON RC.CODIGO = AGE.CODCIUDAD
				INNER JOIN RR_REGIONALES RG 		ON RG.CODIGO = RC.CODREGIONAL
				LEFT  JOIN RR_NODOS NDD 			ON NDD.CODIGO = AGE.CODNODO AND NDD.ESTADO = 'A'
				INNER JOIN TIPO_TRABAJO TT 			ON TT.ID_TT = AGE.ID_TT AND TT.ID_TT NOT IN (1685,1283)	
				INNER JOIN ALIADOS ALI 				ON ALI.IDALIADO = AGE.IDALIADO
				LEFT  JOIN MOVIL MOV 				ON MOV.idmovil = AGE.idmovil
				LEFT  JOIN 
				GESTIONWFM.MGC_DATOS_MOVIL MDM 		ON MDM.ID_DATO_MOVIL = AGE.IDMOVIL
				INNER JOIN RR_AREAS RA 				ON RA.CODIGO = Carea (AGE.CODNODO)
				INNER JOIN OPS.OPS_ACTIVIDAD OPACT 	ON OPO.ACTIVIDAD = OPACT.CODIGO AND OPACT.ESTADO = 'A'
				WHERE AGE.DIAAGENDA BETWEEN ADD_MONTHS(TO_DATE('{$FechaInicial} 00:00','YYYY-MM-DD HH24:MI'),-2)  
			  AND ADD_MONTHS(TO_DATE('{$FechaFinal} 23:59','YYYY-MM-DD HH24:MI'),2)
			  AND OPO.FECHA_CIERRE_RR BETWEEN TO_DATE('{$FechaInicial} 00:00','YYYY-MM-DD HH24:MI')
			  AND TO_DATE('{$FechaFinal} 23:59','YYYY-MM-DD HH24:MI')
					AND AGE.CODRESULTADO = 'OK'
					AND FACTT.HOMOLOGA_SAP = 'Y'
				 	AND AGE.IDALIADO NOT IN (830053800,
				                          8300538004,
				                          83005380042,
				                          83005380044,
				                          83005380043,
				                          83005380045,
				                          83005380046,
				                          83005380047,
				                          900208029) 
				AND TT.ID_TT   NOT IN (1685,1283)		
		      AND AGE.IDAGENDA NOT IN(SELECT DISTINCT IDAGENDA_HIJA  FROM AG_OT_EXCHANGE)
				";
		if ($Aliado != "TODOS") 
            $sql.= " AND age.IDALIADO IN (".$Aliado.")";
		if ($Ciudad != "TODOS") 
			$sql.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";				
		if ($Carpeta != "TODOS") 
			$sql .= " AND age.ID_TT IN ('" . $Carpeta . "')";
				
	    $sql .= " UNION SELECT TRIM('C08') AS GRUPO_DE_COMPRA,
			'{$b}' AS FILENAME,
			'CO' AS PAIS,
			'CO02' AS SOCIEDAD,
			'RF' AS OPERACION,
			OPS.OPA.CODIGO AS CODTRABAJO,
			OPS.OPA.NOMBRE_ACTIVIDAD AS DESCRIPCION_TRABAJO,
			OPS.OPA.CODIGO AS CODTRABAJO2,
			OPS.OPA.NOMBRE_ACTIVIDAD AS DESCRIPCION_TRABAJO2,
			TO_CHAR(OPO.FECHA_CIERRE_RR,'DD/MM/YYYY') AS FECHA_CIERRE,
			ALI.ALIADO_SAP AS NIT_PROVEEDOR,
			REPLACE(ALI.NOMBRE, CHR(9), ' ') AS NOMBRE_PROVEEDOR,
			MDM.CEDULA AS IDMOVIL,
			MDM.CEDULA AS CEDULA_TECNICO,
			UPPER(MDM.NOMBRES) AS NOMBRE_TECNICO,
			AGE.CUENTA AS CUENTA,
			CASE
			   WHEN O.TIPO_CLIENTE IS NULL THEN '0'
			   ELSE O.TIPO_CLIENTE
			END AS TIPO_USER,
			O.ORDEN AS ORDEN,
			RG.CODIGO || '-' || CAREA (O.CODNODO) AS REGIONAL,
			AGE.CODCIUDAD AS CODCIUDAD,
			O.CODNODO AS CODNODO,
			CASE
			   WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'UNI' THEN 
			   		CASE
	                    WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'BID' THEN '4'
	                    ELSE '2'
	                END
			   ELSE CASE
			            WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'DTH' THEN '4'
			            ELSE '1'
			        END
			END AS RED_NODO,
			OPO.CANTIDAD AS CANTIDAD,
			CASE
			   WHEN CCOSTOS_NEW_MT (FACTT.ID_TTACTIVIDAD,O.CODNODO) IS NULL THEN '9999999999'
			   ELSE CCOSTOS_NEW_MT (FACTT.ID_TTACTIVIDAD,O.CODNODO)
			END AS CCOSTO,
			AGE.DIAAGENDA ESTRATO
			FROM MGW_AGENDA AGE
			INNER JOIN MGW_ORDEN O 					ON O.ID_ORDEN = AGE.ID_ORDEN
			INNER JOIN OPS.OPS_ACT_ORDEN OPO 		ON O.ORDEN = OPO.ORDEN AND OPO.ESTADO IN ('A')
			 										AND OPO.ID_TIPO_ORDEN = CASE WHEN O.ID_TIPO_ORDEN = 'O' THEN 1 
			 											WHEN O.ID_TIPO_ORDEN = 'L' THEN 2
			 											WHEN O.ID_TIPO_ORDEN = 'E' THEN 3 END 
			INNER JOIN OPS.OPS_ACTIVIDAD OPA 		ON OPO.ACTIVIDAD = OPA.CODIGO
			INNER JOIN FAC_TTACTIVIDAD FACTT 		ON OPO.ACTIVIDAD = FACTT.CODIGO
			INNER JOIN RR_CIUDADES RC 				ON AGE.CODCIUDAD = RC.CODIGO
			INNER JOIN RR_REGIONALES RG 			ON RC.CODREGIONAL = RG.CODIGO
			INNER JOIN MGW_TIPO_TRABAJO_SUBTIPO MST ON AGE.ID_TT = MST.ID_TTS
			INNER JOIN ALIADOS ALI 					ON AGE.IDALIADO = ALI.IDALIADO
			INNER JOIN MGC_DATOS_MOVIL MDM 			ON AGE.IDTECNICO = MDM.ID_DATO_MOVIL
			INNER JOIN RR_AREAS RA 					ON RA.CODIGO = CAREA (O.CODNODO)
			LEFT  JOIN RR_NODOS NDD 				ON O.CODNODO = NDD.CODIGO AND NDD.ESTADO = 'A'
			WHERE AGE.DIAAGENDA BETWEEN ADD_MONTHS(TO_DATE('{$FechaInicial} 00:00','YYYY-MM-DD HH24:MI'),-2) 
			  AND ADD_MONTHS(TO_DATE('{$FechaFinal} 23:59','YYYY-MM-DD HH24:MI'),2) 
			  AND OPO.FECHA_CIERRE_RR BETWEEN TO_DATE('{$FechaInicial} 00:00','YYYY-MM-DD HH24:MI') 
			  AND TO_DATE('{$FechaFinal} 23:59','YYYY-MM-DD HH24:MI')
			  AND AGE.ID_ESTADO_AGENDA = '44'
			  AND OPA.ESTADO = 'A'
			  AND FACTT.HOMOLOGA_SAP = 'Y'
			  AND AGE.IDALIADO NOT IN (830053800,
			                           8300538004,
			                           83005380042,
			                           83005380044,
			                           83005380043,
			                           83005380045,
			                           83005380046,
			                           83005380047,
			                           900208029)
		AND AGE.ID_AGENDA NOT IN(SELECT DISTINCT IDAGENDA_HIJA  FROM AG_OT_EXCHANGE)";
		if ($Aliado != "TODOS") 
            $sql.= " AND age.IDALIADO IN (".$Aliado.")";
		if ($Ciudad != "TODOS") 
			$sql.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";
		if ($Carpeta != "TODOS") 
			$sql .= " AND age.ID_TT IN ('" . $Carpeta . "')";
		$sql .= 'UNION '.$this->SAP($FechaInicial,$FechaFinal,$Aliado,$Ciudad,$Carpeta,true);
		
	    $this->EjecutaConsulta($sql);

		// return $this->data;
		return $this->calculaRecargos($this->data);
		/*return $this->processArray($this->data,
			array('GRUPO_DE_COMPRA','FILENAME','PAIS',
				'SOCIEDAD','OPERACION','CODTRABAJO',
				'DESCRIPCION_TRABAJO','CODTRABAJO2','DESCRIPCION_TRABAJO2',
				'FECHA_CIERRE','NIT_PROVEEDOR','NOMBRE_PROVEEDOR',
				'IDMOVIL','CEDULA_TECNICO','NOMBRE_TECNICO',
				'CUENTA','TIPO_USER',
				'ORDEN','REGIONAL','CODCIUDAD',
				'CODNODO','RED_NODO','CANTIDAD',
				'CCOSTO','ESTRATO',
				),
			'_2'
			);*/
	}

	public function SAP_OPS_OLD($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta)
	{
	    $Aliado = implode(",", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);	
		$queryCompletoAg = array();
		$queryCompletoWF = array();
		$completasActividadessinY = array();
		$ots = '';
	    $qry = "";
			   //$a = str_replace('/','',date('y/m/d',strtotime($FechaInicial)));
			   $a = str_replace('/','',date('d/m/Yhis',strtotime($FechaInicial)));
			   	$b = 'RTP0192MO'.$a;
			    $sql = " SELECT DISTINCT TRIM('C08') as GRUPO_DE_COMPRA,
							   '{$b}' AS FILENAME,
							   'CO' AS PAIS,
							   'CO02' AS SOCIEDAD,
							   'RF' AS OPERACION,
							   CASE 
								 WHEN 
									OPO.ORDEN IS NOT NULL
									THEN 
													OPO.ACTIVIDAD
									ELSE 
													FACTT.CODIGO
								END AS CODTRABAJO,
							   CASE 
								 WHEN 
									OPO.ORDEN IS NOT NULL
									THEN 
													OPACT.NOMBRE_ACTIVIDAD
									ELSE 
													FACTT.DESCRIPCION
								END AS DESCRIPCION_TRABAJO,
							   CASE 
								 WHEN 
									M.CODIGOMANO_OBRA IS NULL
								  THEN 
										
										
										CASE 
										 WHEN 
											OPO.ORDEN IS NOT NULL
											THEN 
															OPO.ACTIVIDAD
											ELSE 
															FACTT.CODIGO
										END
										
										
								  ELSE 
										M.CODIGOMANO_OBRA
								END AS CODTRABAJO2,
								CASE 
								 WHEN 
									M.CODIGOMANO_OBRA IS NULL
								  THEN 
											CASE WHEN 
												OPO.ORDEN IS NOT NULL
												THEN 
																OPACT.NOMBRE_ACTIVIDAD
												ELSE 
																FACTT.DESCRIPCION
											END
								  ELSE 
									 M.DESCRIPCION
								END AS DESCRIPCION_TRABAJO2,
							   to_char(age.diaagenda,'YYYY/MM/DD') AS DIAAGENDA,
							   ali.ALIADO_SAP AS NIT_PROVEEDOR,
							   Replace(ali.nombre, Chr(9), ' ') as NOMBRE_PROVEEDOR,
							   CASE
								WHEN mov.CEDULA IS NULL THEN
									MDM.CEDULA
								ELSE
									mov.CEDULA
								END AS IDMOVIL,
								CASE
								WHEN mov.CEDULA IS NULL THEN
									MDM.CEDULA
								ELSE
									mov.CEDULA
								END AS CEDULA_TECNICO,

								CASE
								WHEN mov.NOMBRE_USU IS NULL THEN
									UPPER(MDM.NOMBRES)
								ELSE
									UPPER (mov.NOMBRE_USU)
								END AS NOMBRE_TECNICO,
								
								CASE
								WHEN mov.NOMBRE_USU IS NULL THEN
									UPPER(MDM.NOMBRES)
								ELSE
									UPPER (mov.NOMBRE_USU)
								END AS NOMBRE_TECNICO,
							   age.cuenta,
							   CASE 
								 WHEN 
									age.TIPO_USER IS NULL
								  THEN 
													'0'
								  ELSE 
													age.TIPO_USER
								END AS TIPO_USER,
							   age.idorden_de_trabajo AS ORDEN, 
							   rg.CODIGO || '-' || Carea (age.codnodo) as regional,
							   age.CODCIUDAD,
							   age.codnodo,
							    CASE 
								WHEN 
									 SUBSTR(NDD.NOMBRE,-8,3) = 'UNI'
								THEN 
								 CASE	
									WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'BID'
								  THEN 
									 '4'
								  ELSE 
									 '2'
								  END
								ELSE
									CASE	
									WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'DTH'
									  THEN 
										 '4'
									  ELSE 
										 '1'
									  END
								END AS RED_NODO,
						       CASE 
								 WHEN 
									M.CODIGOMANO_OBRA IS NULL
								  THEN 
													
													
													CASE 
													 WHEN 
														OPO.ORDEN IS NOT NULL
														THEN 
																		OPO.CANTIDAD
														ELSE 
																		AC.CANTIDAD
													END
													
													
								  ELSE 
													I.CANTIDAD
								END AS CANTIDAD,
								CASE 
								 WHEN 
									Ccostos_New_MT (OPACT.ID_ACTIVIDAD,AGE.CODNODO) IS NULL
								  THEN 
													'9999999999'
								  ELSE 
													Ccostos_New_MT (OPACT.ID_ACTIVIDAD,AGE.CODNODO)
								END AS CCOSTO,
							   CASE 
								 WHEN 
									age.ESTRATO IS NULL
								  THEN 
													'NR'
								  ELSE 
													age.ESTRATO
								END AS ESTRATO,
								CASE 
								 WHEN 
									OPO.ORDEN IS NOT NULL
									THEN 
											OPACT.HOMOLOGA_SAP
									ELSE 
											FACTT.HOMOLOGA_SAP
								END AS HOMOLOGA
                        FROM agenda age
						INNER JOIN PRE_FACTURA FA  ON (age.IDAGENDA = FA.IDAGENDA)
						INNER JOIN PRE_ACTIVIDAD AC ON (AC.ID_FACTURA = FA.ID_FACTURA)
					    LEFT JOIN PRE_ITEM I ON (AC.ID_ACTIVIDAD = I.ID_ACTIVIDAD AND I.TIPO = 'MO')
						LEFT JOIN FAC_MANOOBRA M ON (M.IDMANO_OBRA = I.ID_MOMTR AND I.TIPO = 'MO')
						INNER JOIN FAC_TTACTIVIDAD FACTT ON (FACTT.ID_TTACTIVIDAD = AC.ID_TTACTIVIDAD)
						inner join rr_ciudades rc on (age.codciudad = rc.codigo)
						inner join rr_regionales rg on (rc.codregional = rg.codigo)
						left JOIN RR_NODOS NDD ON (AGE.CODNODO = NDD.CODIGO AND NDD.ESTADO = 'A')
						inner join tipo_trabajo tt on (age.id_tt = tt.id_tt)
						inner join aliados ali on (age.idaliado = ali.idaliado)
						left join movil mov on (age.idmovil = mov.idmovil)
						INNER JOIN RR_AREAS RA ON RA.CODIGO = Carea (AGE.CODNODO)
						LEFT JOIN GESTIONWFM.MGC_DATOS_MOVIL MDM ON (AGE.IDMOVIL = MDM.ID_DATO_MOVIL)
						LEFT JOIN OPS.OPS_ACT_ORDEN OPO ON (AGE.IDORDEN_DE_TRABAJO = OPO.ORDEN AND OPO.ESTADO IN ('A'))
					    LEFT JOIN OPS.OPS_ACTIVIDAD OPACT ON (OPO.ACTIVIDAD = OPACT.CODIGO AND OPACT.ESTADO = 'A' )
						where 
						age.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial .
											" 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
											" 23:59:59','YYYY-MM-DD HH24:MI:SS')
						and age.codresultado = 'OK'
						and age.idaliado not in (830053800,
												8300538004, 
												83005380042, 
												83005380044, 
												83005380043, 
												83005380045, 
												83005380046, 
												83005380047)
						AND tt.ID_TT NOT IN (1685,1283)	
						AND age.IDAGENDA NOT IN(SELECT DISTINCT IDAGENDA_HIJA  FROM AG_OT_EXCHANGE)
						";
						
				 if ($Aliado != "TODOS") {
                    $sql.= " AND age.IDALIADO IN (".$Aliado.")";
                }else
				{
				    $sql.= " AND age.idaliado not in (830053800,
												8300538004, 
												83005380042, 
												83005380044, 
												83005380043, 
												83005380045, 
												83005380046, 
												83005380047) ";
				}
				if ($Ciudad != "TODOS") {
					$sql.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND age.ID_TT IN ('" . $Carpeta . "')";
				}
					
						
                $this->EjecutaConsulta($sql);
				$queryCompletoAg = $this->data;
				foreach($queryCompletoAg as $idAntTotal => $okAntT)
				{
					if($okAntT['HOMOLOGA']=='Y')
					{
						unset($okAntT['HOMOLOGA']);
						$completasActividadessinY[] = $okAntT;
						
					}	
				}
				$queryCompletoAg = $completasActividadessinY;
				if($queryCompletoAg<>array())
				{
					
					foreach($queryCompletoAg as $idAnt => $okAnt)
					{
						$ots.= ',' . $okAnt['ORDEN'] . '';
						$ots1[] = $okAnt['ORDEN'];
					}
					
					if($ots<>array())
					{
					   $ots = substr($ots, 1, strlen($ots));
					   $qry = getSqlInReverse("O.ORDEN",$ots1);
					}else
					{
					 $qry = "1=1";
					}	
				}else
				{
					$qry = "1=1";
				}
				
				 $sql1 = " SELECT TRIM('C08') AS GRUPO_DE_COMPRA,
							   '{$b}' AS FILENAME, -- CONCATENAR CON EL DIA DE HOY 
							   'CO' AS PAIS,
							   'CO02' AS SOCIEDAD,
							   'RF' AS OPERACION,
							   OPS.OPA.CODIGO AS CODTRABAJO,
                               OPS.OPA.NOMBRE_ACTIVIDAD AS DESCRIPCION_TRABAJO,
                               OPS.OPA.CODIGO AS CODTRABAJO2,
                               OPS.OPA.NOMBRE_ACTIVIDAD AS DESCRIPCION_TRABAJO2,
							   TO_CHAR(AGE.DIAAGENDA,'DD/MM/YYYY') AS DIAAGENDA,
							   ALI.ALIADO_SAP AS NIT_PROVEEDOR,   
							   REPLACE(ALI.NOMBRE, CHR(9), ' ') AS NOMBRE_PROVEEDOR,
							   MDM.CEDULA AS IDMOVIL,
							   MDM.CEDULA AS CEDULA_TECNICO,
							   UPPER(MDM.NOMBRES) AS NOMBRE_TECNICO,
							   REPLACE(MDM.NOMBRES, CHR(9), ' ') AS NOMBRE_TECNICO,
							   AGE.CUENTA,
							   CASE 
								 WHEN 
									O.TIPO_CLIENTE IS NULL
								  THEN 
													'0'
								  ELSE 
													O.TIPO_CLIENTE
								END AS TIPO_USER,
							   O.ORDEN AS ORDEN, 
							   RG.CODIGO || '-' || CAREA (O.CODNODO) AS REGIONAL,
							   AGE.CODCIUDAD,
							   O.CODNODO,
							    CASE 
								WHEN 
									 SUBSTR(NDD.NOMBRE,-8,3) = 'UNI'
								THEN 
								 CASE	
									WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'BID'
								  THEN 
									 '4'
								  ELSE 
									 '2'
								  END
								ELSE
									CASE	
									WHEN SUBSTR(NDD.NOMBRE,-8,3) = 'DTH'
									  THEN 
										 '4'
									  ELSE 
										 '1'
									  END
								END AS RED_NODO,
                   OPO.CANTIDAD,
								CASE 
								 WHEN 
									CCOSTOS_NEW_MT (OPO.ID_ACT_ORDEN,O.CODNODO) IS NULL
								  THEN 
													'9999999999'
								  ELSE 
													CCOSTOS_NEW_MT (OPO.ID_ACT_ORDEN,O.CODNODO)
								END AS CCOSTO,
							   CASE 
							 WHEN 
									O.ESTRATO IS NULL
								  THEN 
													'NR'
								  ELSE 
													O.ESTRATO
								END AS ESTRATO
                       FROM MGW_AGENDA AGE
						INNER JOIN MGW_ORDEN O ON O.ID_ORDEN = AGE.ID_ORDEN
						INNER JOIN OPS.OPS_ACT_ORDEN OPO ON (O.ORDEN = OPO.ORDEN AND OPO.ESTADO IN ('A', 'C'))
						INNER JOIN OPS.OPS_ACTIVIDAD OPA ON (OPO.ACTIVIDAD = OPA.CODIGO)
						INNER JOIN RR_CIUDADES RC ON (AGE.CODCIUDAD = RC.CODIGO)
						INNER JOIN RR_REGIONALES RG ON (RC.CODREGIONAL = RG.CODIGO)
						INNER JOIN MGW_TIPO_TRABAJO_SUBTIPO MST ON (AGE.ID_TT = MST.ID_TTS)
						INNER JOIN ALIADOS ALI ON (AGE.IDALIADO = ALI.IDALIADO)
						INNER JOIN MGC_DATOS_MOVIL MDM ON (AGE.IDTECNICO = MDM.ID_DATO_MOVIL)
						INNER JOIN RR_AREAS RA ON RA.CODIGO = CAREA (O.CODNODO)
						LEFT JOIN RR_NODOS NDD ON (O.CODNODO = NDD.CODIGO AND NDD.ESTADO = 'A')
					  WHERE 
						$qry
						AND AGE.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial .
											" 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .
											" 23:59:59','YYYY-MM-DD HH24:MI:SS')
                        AND AGE.ID_ESTADO_AGENDA > '7'
                        AND OPA.HOMOLOGA_SAP = 'Y'
                        AND OPA.ESTADO = 'A'
						AND AGE.IDALIADO NOT IN (830053800,
												8300538004, 
												83005380042, 
												83005380044, 
												83005380043, 
												83005380045, 
												83005380046, 
												83005380047
												)
			AND AGE.ID_AGENDA NOT IN(SELECT DISTINCT IDAGENDA_HIJA  FROM AG_OT_EXCHANGE)";
						
				 if ($Aliado != "TODOS") {
                    $sql1.= " AND age.IDALIADO IN (".$Aliado.")";
                }else
				{
				    $sql1.= " AND age.idaliado not in (830053800,
												8300538004, 
												83005380042, 
												83005380044, 
												83005380043, 
												83005380045, 
												83005380046, 
												83005380047) ";
				}
				if ($Ciudad != "TODOS") {
					$sql1.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql1 .= " AND age.ID_TT IN ('" . $Carpeta . "')";
				}
				
				$this->EjecutaConsulta($sql1, 1);
				$queryCompletoWF = $this->data;
				
				$queryCompleto = array_merge($queryCompletoAg, $queryCompletoWF);
				//echo count($queryCompleto);
				//imprimir($queryCompleto); 
				//die;
				set_time_limit(0);
				//return $queryCompleto;
				
				//validamos sobre la orden quien opex o quien es capex
				$ORDEN1 = array();
				foreach($queryCompleto as $id => $valorData)
				{
				  if($valorData['CODTRABAJO']=='REOPEX')
				  {
				     $ORDEN1[$valorData['ORDEN']]= array("CODTRABAJO2"=>$valorData['CODTRABAJO'], "DESCRIPCION_TRABAJO2"=>$valorData['DESCRIPCION_TRABAJO2']);
				  }else if($valorData['CODTRABAJO']=='RECAPEX')
				  {
				    $ORDEN1[$valorData['ORDEN']]= array("CODTRABAJO2"=>$valorData['CODTRABAJO'], "DESCRIPCION_TRABAJO2"=>$valorData['DESCRIPCION_TRABAJO2']);
				  }else
				  {
				    //$ORDEN1[$valorData['ORDEN']]= $valorData['CODTRABAJO'];
				  }
							
				}
				//imprimir($ORDEN1);
				//die;
				
				//asociamos la orden a los regsitros
				if($ORDEN1<>array())
				{
					foreach ($queryCompleto as $id => $valorData) 
					{
					   $ORDEN= $valorData['ORDEN'];
					   $nuevosQuerys[$id][$ORDEN] = $valorData;
					}
					//imprimir($nuevosQuerys);die;
					foreach($nuevosQuerys as $id2 => $valor)
					{
					   foreach($valor as $id3 => $valor3)
					   {
						  if($valor3['CODTRABAJO']=='RECAPEX')
						  {
						  }else if($valor3['CODTRABAJO']=='REOPEX')
						  {
						  }else
						  {
							  foreach($ORDEN1 as $id4 => $valor4)
							  {
								   if($id3 == $id4)
								   {
									   $valor3['CODTRABAJO2'] = $valor4['CODTRABAJO2'];
									   $valor3['DESCRIPCION_TRABAJO2'] = $valor4['DESCRIPCION_TRABAJO2'];
									   $valor5[] = $valor3;
								   }
							  }
						  }
					   }
					   
					}
					//imprimir($valor5); die;
					$recargo = $this->ConsultaRecargos($valor5);
				    $valores = array_merge($queryCompleto,$recargo);
					
					if($valores<>array())
					{
					     foreach($valores as $v => $value)
						 {
							if($value['CANTIDAD']!='0')
							{  
							  if($value['CODTRABAJO']=='RECAPEX')
							  {
							  }else if($value['CODTRABAJO']=='REOPEX')
							  {
							  }else
							  {
								  $valor6[] = $value;
							  }
							}  
						 }
						 $valores = $valor6;
						 //imprimir($valores);
					}
					//die;
					
				}else
				{
				    if($queryCompleto<>array())
					{
					  $valuedata = array();
					  foreach($queryCompleto as $id => $valorData)
					  {
							  if($valorData['CANTIDAD']!='0')
							  {
								 $valuedata[] = $valorData;
							  }			
					  }
					  $valores =  $valuedata;
					  
					  //$valores =  $queryCompleto;
					}
				}
				//imprimir($valor5); die;
				
				//imprimir($valores); //die;
				return $valores;
	
	}
	
	public function calculaRecargos($queryCompleto)
	{	
		//validamos sobre la orden quien opex o quien es capex
				$ORDEN1 = array();
				foreach($queryCompleto as $id => $valorData)
				{
				  if($valorData['CODTRABAJO']=='REOPEX')
				  {
				     $ORDEN1[$valorData['ORDEN']]= array("CODTRABAJO2"=>$valorData['CODTRABAJO'], "DESCRIPCION_TRABAJO2"=>$valorData['DESCRIPCION_TRABAJO2']);
				  }else if($valorData['CODTRABAJO']=='RECAPEX')
				  {
				    $ORDEN1[$valorData['ORDEN']]= array("CODTRABAJO2"=>$valorData['CODTRABAJO'], "DESCRIPCION_TRABAJO2"=>$valorData['DESCRIPCION_TRABAJO2']);
				  }else
				  {
				    //$ORDEN1[$valorData['ORDEN']]= $valorData['CODTRABAJO'];
				  }
							
				}
				
				//asociamos la orden a los regsitros
				if($ORDEN1<>array())
				{
					foreach ($queryCompleto as $id => $valorData) 
					{
					   $ORDEN= $valorData['ORDEN'];
					   $nuevosQuerys[$id][$ORDEN] = $valorData;
					}
					foreach($nuevosQuerys as $id2 => $valor)
					{
					   foreach($valor as $id3 => $valor3)
					   {
						  if($valor3['CODTRABAJO']=='RECAPEX')
						  {
						  }else if($valor3['CODTRABAJO']=='REOPEX')
						  {
						  }else
						  {
							  foreach($ORDEN1 as $id4 => $valor4)
							  {
								   if($id3 == $id4)
								   {
									   $valor3['CODTRABAJO2'] = $valor4['CODTRABAJO2'];
									   $valor3['DESCRIPCION_TRABAJO2'] = $valor4['DESCRIPCION_TRABAJO2'];
									   $valor5[] = $valor3;
								   }
							  }
						  }
					   }
					   
					}
					$recargo = $this->ConsultaRecargos($valor5);
				    $valores = array_merge($queryCompleto,$recargo);
					
					if($valores<>array())
					{
					     foreach($valores as $v => $value)
						 {
							if($value['CANTIDAD']!='0')
							{  
							  if($value['CODTRABAJO']=='RECAPEX')
							  {
							  }else if($value['CODTRABAJO']=='REOPEX')
							  {
							  }else
							  {
								  $valor6[] = $value;
							  }
							}  
						 }
						 $valores = $valor6;
					}
					
				}else
				{
				    if($queryCompleto<>array())
					{
					  $valuedata = array();
					  foreach($queryCompleto as $id => $valorData)
					  {
							  if($valorData['CANTIDAD']!='0')
							  {
								 $valuedata[] = $valorData;
							  }			
					  }
					  $valores =  $valuedata;
					  
					}
				}
		return $valores;
	}
		public function ConsultaRecargos($array)
		{
				   //imprimir($array);die;
				   if($array!=array())
				   {
					   set_time_limit(0);
					   //$porcentajeValor = $this->porcentajeRecargo();
					   $porcentajeValor = 51/100;
					   //imprimir($porcentajeValor); die;
					   foreach($array as $id =>$data)
					   {
							if($data['CODTRABAJO']=='REOPEX')
							{
							   $data = array();
							}else if($data['CODTRABAJO']=='RECAPEX')
							{
							   $data = array();
							}
							else
							{
								$por = ($data['CANTIDAD'] * $porcentajeValor);
								$por1 = number_format($por, 2, ',', '');
								$data['CANTIDAD'] = $por1;
							}
							
							if($data<>array())
							{
							  $valores[] = $data;
							}  
							
					   
					   }
					   //imprimir($valores);//die;
					   return $valores;
				   }
		}	
	
		
		public function porcentajeRecargo()
		{
		    set_time_limit(0);
			$sql = "select IDPCJES, PRCJDESP from PORCENTAJESDIASESP where ROWNUM <= 1  ORDER BY IDPCJES DESC  ";
							$this->EjecutaConsulta($sql);
						    $porcentaje = $this->data;
							if($porcentaje<>array())
							{
							   $valorPorcentaje = $porcentaje[0]['PRCJDESP']/100;
							}else
							{
							   $valorPorcentaje = '0';
							}
							//imprimir($queryCostos);
							//die;
		set_time_limit(0);					
	    return $valorPorcentaje;
		
		}
		
	public function ConsultaItemFacturacionCor($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta)
	{
	    $Aliado = implode(",", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);
			   
			   $sql = "SELECT /*+ PARALLEL(3) */ DISTINCT OAI.CODCIUDAD AS CIUDAD,
						  OAI.DISPONIBILIDAD,
						  OAI.AGEID AS CONSECUTIVO,
						  AGE.CIUDAD2 AS OTPADRE,
						  AGE.IDORDEN_DE_TRABAJO AS OTHIJA,
						  OAI.VISITA,
						  AGE.suscriptor AS CLIENTE,
						  C1.DESCRIPCION AS PRODUCTO,
						  OAI.DISPONIBILIDADMAX AS FECHA_MAX_ECPC,
						  FMO.DESCRIPCION AS ITEM_FACTURACION,
						  C2.DESCRIPCION AS PRIORIDAD,
						  OAI.DISPONIBILIDAD AS DISPONIBLIDAD_FECHA,
						  OB.DESCRIPCION AS BLOQUE_DISPONIBLIDAD,
						  TO_CHAR(OAI.DISPONIBILIDAD, 'YY')|| '.'|| OB.DESCRIPCION || '.'|| OAI.CODCIUDAD || '.'|| TO_CHAR(OAI.DISPONIBILIDAD, 'MM')  AS DISPONIBILIDAD_ESTRUCTURA,
						  C3.DESCRIPCION AS ESTADO_FIN_GESTION,
						  C4.DESCRIPCION AS PNC_COD_RES,
						  OAI.FECHA_CREACION,
						  OAI.FECHA_MODIFICACION,
						  US.NOMBRE AS DILIGENCIA_FORMULARIO,
						  OAI.ESTADO
						FROM AGENDA AGE
						INNER JOIN ONIX_AGENDA_ITEMS OAI ON (OAI.IDORDEN_DE_TRABAJO = AGE.IDORDEN_DE_TRABAJO)
						INNER JOIN FAC_MANOOBRA FMO ON (FMO.IDMANO_OBRA = OAI.ITEMID AND FMO.ID_TIPO_RED = 'COR')
						INNER JOIN TIPO_TRABAJO TT ON (OAI.ID_TT = TT.ID_TT)
						LEFT JOIN ONIX_BLOQUED OB ON (OB.BLOQUEID= OAI.BLOQUEID)
						LEFT JOIN ONIX_CAMPO1 C1 ON (OAI.CAMID=C1.CAMID)
						LEFT JOIN ONIX_CAMPO2 C2 ON (OAI.CAM2ID=C2.CAM2ID)
						LEFT JOIN ONIX_CAMPO3 C3 ON (OAI.CAM3ID=C3.CAM3ID)
						LEFT JOIN ONIX_CAMPO4 C4 ON (OAI.CAM4ID=C4.CAM4ID)
						INNER JOIN USUARIOS US ON (OAI.IDUSUARIO = US.ID_USUARIO)
						WHERE AGE.DIAAGENDA BETWEEN TO_DATE('" . $FechaInicial .           
						  " 00:00:00','YYYY-MM-DD HH24:MI:SS') AND TO_DATE('" . $FechaFinal .           
						  " 23:59:59','YYYY-MM-DD HH24:MI:SS')
						AND AGE.PROGRAMACION = 'C'  
						  ";
						

                if ($Aliado != "TODOS") {
                    $sql.= " AND age.IDALIADO IN (".$Aliado.")";
                }
				if ($Ciudad != "TODOS") {
					$sql.= " AND age.CODCIUDAD IN ('" . $Ciudad . "')";
				}
				if ($Carpeta != "TODOS") {
					$sql .= " AND age.ID_TT IN ('" . $Carpeta . "')";
				}
				
				$this->EjecutaConsulta($sql);
                //imprimir($this->data);
				//die;
                set_time_limit(0);
                return $this->data;
	
	}
	
	public function ConsultaControles($FechaInicial, $FechaFinal,$Aliado, $Ciudad, $Carpeta)
	{
		
	    $Aliado = implode(",", $Aliado);
		$Carpeta = implode("','", $Carpeta);
        $Ciudad = implode("','", $Ciudad);
			   
			$this->dataRR = "";
			$sql = "SELECT 
					T02.WHACCT, 
					T03.SUDCDE, 
					T03.SUCCDE, 
					T01.PHWOÑ , 
					DIGITS(WHCRTC)||DIGITS(WHCRTY)||'/'||DIGITS(WHCRTM)||'/'||DIGITS(WHCRTD)  FECHACREOT, 
					DIGITS(WHCOMC)||DIGITS(WHCOMY)||'/'||DIGITS(WHCOMM)||'/'||DIGITS(WHCOMD),
					T01.PHITMC, 
					T01.PHMANC, 
					T01.PHIDCD, 
					T01.PHITMD, 
					T01.PHSERÑ, 
					T03.SUTYPE, 
					T03.SURSCP, 
					SUBSTR(T04.TCDTAE,47,2), 
					SUBSTR(T04.TCDTAE,1,45), 
					T02.WHNODE,
					T02.WHSTAT, 
					T02.WHDLRC, 
					T02.WHNODE,  
					T01.PHTOT$,  
					T01.PHGVRS,  
					T01.PHGVBY, 
					T02.WHCRTU, 
					T01.PHSTAT,  
					T02.WHTYPE,
				    T01.PHQTYÑ
					FROM  
					CABLEDTA.WOPUHIL1 AS T01,  
					CABLEDTA.WOMAHIL2 AS T02, 
					CABLEDTA.SUBSMSTR AS T03,  
					CABLEDTA.CONSTANT AS T04     
					WHERE  T01.PHWOÑ  = T02.WHWOÑ  
					AND   T02.WHACCT = T03.SUACCT
					AND T03.SURSCP = T04.TCARGU  
					AND T01.PHITMC  IN ('CC' )  
					AND  T04.TCCODE   =   'M!'";
					
			
			
			
			$this->EjecutaConsultaRR($sql);
			$data = $this->dataRR;
			//echo count($data);
			if($data<>array())
			{
			   foreach($data as $id => $value)
			   {
			       $sql = "SELECT US.IDALIADO, ALI.NOMBRE ,ALI2.IDALIADO AS IDALIADO2, ALI2.NOMBRE AS CAV_VISOR
							FROM ENT_CONTROLES_ENTREGADOS CE
							LEFT JOIN USUARIOS US  ON (US.ID_USUARIO=CE.IDUSUARIO)
							LEFT JOIN ALIADOS ALI ON (ALI.IDALIADO = US.IDALIADO)
							LEFT JOIN ALIADOS ALI2 ON (ALI2.IDALIADO = CE.IDALIADO)
							WHERE CE.OT = TRIM('".$value['PHWOÑ']."')
							UNION
							SELECT US.IDALIADO, ALI.NOMBRE ,ALI2.IDALIADO AS IDALIADO2, ALI2.NOMBRE AS CAV_VISOR
							FROM USUARIOS US 
							INNER JOIN ALIADOS ALI ON (ALI.IDALIADO = US.IDALIADO)
							LEFT JOIN ENT_CONTROLES_ENTREGADOS CE ON (CE.OT = TRIM('".$value['PHWOÑ']."'))
							LEFT JOIN ALIADOS ALI2 ON (ALI2.IDALIADO = CE.IDALIADO)
							WHERE US.USUARIORR = TRIM('".$value['WHCRTU']."')
							";
							//echo $sql;
							$this->EjecutaConsulta($sql);
						    $Cavs = $this->data;
							if($Cavs<>array())
							{
							   foreach($Cavs as $value2)
							   {
							       $value['IDALIADO'] = $value2['IDALIADO'];
								   $value['NOMBRE_ALIADO'] = $value2['NOMBRE']; 
								   $value['IDALIADO2'] = $value2['IDALIADO2'];
								   $value['CAV_VISOR'] = $value2['CAV_VISOR']; 
							   }
							   $valores[] =  $value;
							}
			   }
			}
			set_time_limit(0);
            return $valores;
	
	}
	


}



