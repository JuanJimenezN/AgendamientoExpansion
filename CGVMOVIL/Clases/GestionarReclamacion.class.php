<?php

/**
 * Gestionar Reclamacion
 * @package CGVMOVIL
 * @author rorozco
 * @version 31/10/2016
 * @access public
 */
class GestionarReclamacion extends FormularioMGW
{

    /**
     * Titulo del aplicativo
     * @access public
     * @see GestionarReclamacion
     * @var string
     */
    public $_titulo = 'FORMULARIO DE GESTION RECLAMACION';

    /**
     * Nombre tabla principal gestion reclamacion
     * @access public
     * @see GestionReclamacion
     * @var string
     */
    public $Table_reclamacion = 'CGVM_RECLAMACION';

    /**
     * Nombre tabla de soportes gestion reclamacion
     * @access public
     * @see GestionReclamacion
     * @var string
     */
    public $Table_soportes = 'CGVM_RECLAMACION_SOPORTES';

    /**
     * Estilos aplicados
     * @access public
     * @see GestionReclamacion
     * @var Array
     */
    protected $style = array("/Template/Estilos/style.css", "../../../Template/Estilos/estilos.css", '/Javascript/Jqueryui/estilos_jquery.min.css');

    /**
     * GestionarReclamacion::_construct()
     * Metodo constructor en el cual se inicializa la base de datos
     * @return void
     * @version 1.0 - Version inicial
     * @author rorozco (V 1.0)
     */
    function __construct()
    {
        parent::__construct('AGENDAMIENTO');

    }

    /**
     * GestionarReclamacion::_construct_form()
     * Metodo que prepara todos los componentes graficos del CRUD
     * @return void
     * @version 1.0 - Version inicial
     * @author rorozco (V 1.0)
     */
    public function _construct_form()
    {

       $consulta = <<<OE

        SELECT
        CMR.CUENTA,
        CMR.OT,
        CMR.SERVICIO CODIGO_SERVICIO,
        CMR.NOMBRE_SERVICIO "SERVICIO",
        CMR.VENDEDOR,
        CTR.CODIGO "TIPO RECLAMACION",
        CMR.FECHA_INGRESO "FECHA INGRESO",
        CASE
          WHEN CMR.ESTADO = 'P' THEN 'PENDIENTE'
          WHEN CMR.ESTADO = 'N' THEN 'NEGADO'
          WHEN CMR.ESTADO = 'S' THEN 'APROBADO'
        END ESTADO,
        CMR.FECHA_GESTION "FECHA GESTION",
        CMR.JUSTIFICACION,
        CMR.USUARIO_RR

        FROM CGVM_RECLAMACION CMR
        INNER JOIN CGVM_TIPO_RECLAMO CTR ON CTR.ID_TIPO_RECLAMO = CMR.TIPO_RECLAMO

        WHERE CMR.ID_RECLAMACION = {$_GET['id']}

OE;

        $datos = $this->ConsultarSql($consulta);

        if($datos[0]['ESTADO'] == 'APROBADO'){

            $estado = 'S';

        }elseif($datos[0]['ESTADO'] == 'NEGADO'){

            $estado = 'N';

        }else{
            $estado = '';
        }

        $justificacion = $datos[0]['JUSTIFICACION'] == NULL ? '':  $datos[0]['JUSTIFICACION'];

        $sql_soportes = "

        SELECT
        '<a style=\"text-decoration:underline;\" href=".SERVERCGVMOVIL.PATHTEMPLATEPORTALCGVMOVIL. "'||CMS.NOMBRE_ARCHIVO||' \" target=\"_blank\">Soporte</a>' SOPORTE FROM CGVM_RECLAMACION_SOPORTES CMS
        WHERE CMS.ID_RECLAMACION = ". $_GET['id'];

        $result_soportes = $this->ConsultarSql($sql_soportes);

        $i = 0;
        $_formu = array();

        $_formu[$i++][] = $this->create_titulo($this->_titulo, '', 'style="text-align:left;"');

        $_formu[$i][] = $this->create_texto('CUENTA: ');

        $_formu[$i++][] = $this->create_input('text', 'cuenta', 'cuenta', $datos[0]['CUENTA'], '', '', 'readonly');

        $_formu[$i][] = $this->create_texto('ORDEN DE TRABAJO: ');

        $_formu[$i++][] = $this->create_input('text', 'ot', 'ot', $datos[0]['OT'], '', '', 'readonly');

        $_formu[$i][] = $this->create_texto('VENDEDOR VENTAS: ');

        $_formu[$i++][] = $this->create_input('text', 'vendedor', 'vendedor', $datos[0]['VENDEDOR'], '', '', 'readonly');

        $_formu[$i][] = $this->create_texto('SERVICIO: ');

        $_formu[$i++][] = $this->create_input('text', 'servicio', 'servicio', $datos[0]['SERVICIO'], '', '', 'readonly');

        $_formu[$i][] = $this->create_texto('TIPO RECLAMACION: ');

        $_formu[$i++][] = $this->create_input('text', 'reclamacion', 'reclamacion', $datos[0]['TIPO RECLAMACION'], '', '', 'readonly');

        $_formu[$i][] = $this->create_texto('AUTORIZA RECLAMACION: ');

        $_formu[$i++][] = $this->create_select('estado', 'estado', $estado, 'required', '', '', $this->getEstados(), 'CODIGO_ESTADO', 'CODIGO_ESTADO');

        $_formu[$i++][] =  $this->create_texto(isset($result_soportes[0]['SOPORTE']) ? $result_soportes[0]['SOPORTE'].' 1' : ''). $this->create_texto('&nbsp;&nbsp;').

        $this->create_texto(isset($result_soportes[1]['SOPORTE']) ? $result_soportes[1]['SOPORTE'] .' 2' : ''). $this->create_texto('&nbsp;&nbsp;'). $this->create_texto(isset($result_soportes[2]['SOPORTE']) ? $result_soportes[2]['SOPORTE'] .' 3' : '');

        $_formu[$i][] = $this->create_texto('JUSTIFICACION: ');

        $_formu[$i++][] = $this->create_input('textarea', 'justificacion', 'justificacion', $justificacion, '', '', '');

        $_formu[$i++][] = $this->create_input('hidden', 'id_reclamacion', 'id_reclamacion', $_GET['id'], '', '', '');

        $_formu[$i++][] = $this->create_input('hidden', 'codigo_servicio', 'codigo_servicio', $datos[0]['CODIGO_SERVICIO'], '', '', '');

        $_formu[$i++][] = $this->create_input('hidden', 'usuario_rr', 'usuario_rr', $datos[0]['USUARIO_RR'], '', '', '');

        $_formu[$i++][] = $this->create_separador();

        if($estado != "S" && $estado != "N"){

            $_formu[$i++][] =
                $this->create_texto('<center>').
                $this->create_button(true, 'Aceptar', 'Aceptar', 'Aceptar').
                $this->create_texto('&nbsp;&nbsp;&nbsp;').
                $this->create_button(true, 'Limpiar', 'Limpiar', 'Limpiar').
                $this->create_texto('</center>');
        }

        $_formu[$i++][] = $this->create_separador() . $this->create_loading('/image/ajax-loader.gif');

        $_formu[$i++][] = "<div id='listado'></div>";

        $this->form_creacion = $this->print_formulario('Formulario', 'Formulario', $_formu, 4);
    }

    /**
     * GestionarReclamacion::getEstados()
     * Metodo que arma un array de los estados a usar en el filtro Autoriza Reclamacion.
     * @access public
     * @return array
     * @version 1.0
     * @author rorozco
     **/
    public function getEstados()
    {

       $consultaEstados = <<<OE

       SELECT CER.CODIGO_ESTADO FROM CGVM_ESTADO_RECLAMACION CER WHERE CER.NOMBRE_ESTADO = 'APROBADO' OR  CER.NOMBRE_ESTADO = 'NEGADO'
       AND CER.ESTADO = 'A'

OE;
       $estados = $this->ConsultarSql($consultaEstados);

       return $estados;
    }


    /**
     * GestionarReclamacion ::getSqlListado()
     * Metodo consulta la tabla principal y devuelve los registros contenidos en forma de grilla
     * @param mixed $Excel
     * @return
     * @author rorozco
     * */
    function getSqlListado($Excel)
    {
        return null;
    }


    /**
     * GestionarReclamacion ::obtenerUrlWebService()
     * Metodo consulta la tabla webservices y ws_metodos para traer datos del webservice
     * @param $nombre
     * @return
     * @author rorozco
     * */
    public function obtenerUrlWebService($nombre){

        $sql_consulta = <<<OE

               SELECT
					 W.IDWS
					,W.URL
					,W.DESCRIPCION
					,W.ESTADO
					,W.IDUSUARIO
					,W.FECHA
					,W.IDUSUARIO_MOD
					,W.FECHA_MOD
					,WM.IDWSM
					,WM.IDWS
					,WM.NAME
					,WM.DESCRIPCION
					,WM.ESTADO
					,WM.IDUSUARIO
					,WM.FECHA
					,WM.IDUSUARIO_MOD
					,WM.FECHA_MOD
                FROM WEBSERVICES W
                INNER JOIN WS_METODOS WM ON WM.IDWS = W.IDWS
                WHERE WM.DESCRIPCION = '{$nombre}'
OE;

        $result = $this->ConsultarSql($sql_consulta);

        return $result;
    }

    /**
     * GestionarReclamacion::modificarReclamacion()
     * Metodo que consume servicio compensaciones - modificarReclamacion
     * @access public
     * @return array
     * @version 1.0
     * @author rorozco
     **/
    public function modificarReclamacion(){



        $usuarioRR = $this->_datos['usuario_rr'];
        $pcml = 'SI';

        try{

            $serviciosOrden = array();
            $client_ws = new client_ws();

            $servicio = $this->obtenerUrlWebService('modificarReclamacion');

            $method = $servicio[0]['NAME'];
            $client_ws->initializeClient($servicio[0]['URL']);

            $_structure = $client_ws->print_structure($method);

            $_structure[$method]['PCML'] = $pcml;
            $_structure[$method]['PUSERID'] = $usuarioRR;
            $_structure[$method]['SUSCRIP_CMP'] = $this->_datos['cuenta'];
            $_structure[$method]['OT_CMP'] = $this->_datos['ot'];
            $_structure[$method]['SERVIC_CMP'] = $this->_datos['codigo_servicio'];
            $_structure[$method]['PRFAUTZ'] = $this->_datos['estado'];
            $_structure[$method]['PRFJUST'] = $this->_datos['justificacion'];
            $_structure[$method]['RESULTADO'] = "";
            $_structure[$method]['MENSAJE'] = "";

            $client_ws->run_method($method,$_structure[$method]);

            if( isset($client_ws->_array_response->return->RESULTADO) &&  $client_ws->_array_response->return->RESULTADO == "I"){

                return "I";

            }else{
                if( isset($client_ws->_array_response->return->RESULTADO) &&  $client_ws->_array_response->return->RESULTADO == "E"){
                    return $client_ws->_array_response->return->MENSAJE;
                }else{
                    return "E";
                }
            }


        }catch (Exception $e) {
            echo 'Error: ', $e->getMessage(), "\n";
        }

    }

    /**
     * GestionarReclamacion::runGestionar()
     * Metodo que guarda los cambios realizados sobre la reclamacion
     * @access public
     * @return array
     * @version 1.0
     * @author rorozco
     **/
    public function runGestionar() {

        $textbind = '';
        $sql = '';

        $this->_datos['id_usuario'] = $this->_usuario['IDUSUARIO'];

        $_respuesta = array('Codigo' => 0, "Mensaje" => $this->MensajeActualizacion);

        //Se actualiza Reclamacion en rr

        $result = $this->modificarReclamacion();

        if($result == "I"){

            try {

            $sql = <<<OE

            UPDATE {$this->Table_reclamacion} SET
            ESTADO = :a,
            JUSTIFICACION = :b,
            FECHA_GESTION = SYSDATE,
            USUARIO_SOLUCIONA = :c

            WHERE ID_RECLAMACION = :d
OE;

            $textbind=<<<OE
                        \$p1 = '{$this->_datos['estado']}';
                        \$p2 = '{$this->_datos['justificacion']}';
                        \$p3 = {$this->_datos['id_usuario']};
                        \$p4 = {$this->_datos['id_reclamacion']};
                        oci_bind_by_name(\$this->resultado,':a',\$p1);
                        oci_bind_by_name(\$this->resultado,':b',\$p2);
                        oci_bind_by_name(\$this->resultado,':c',\$p3);
                        oci_bind_by_name(\$this->resultado,':d',\$p4);
                        
OE;

            $this->Consulta($sql,0,0,$textbind);
        
          } catch (exception $e) {

                $_respuesta = array('Codigo' => 99, "Mensaje" => $e->getMessage());
          }



        }else{

            if($result != "E"){

                $_respuesta = array('Codigo' => 99, "Mensaje" => $result);

            }else{

                $_respuesta = array('Codigo' => 99, "Mensaje" => "Error al consumir servicio modificarReclamacion");

            }
        }

        print_r(json_encode($_respuesta));

    }

    /**
     * GestionarReclamacion::_construct_script()
     * Aqui se encuentra el codigo javascript, ajax y jquery para las acciones de los botones entre otras.
     * @access public
     * @return array
     * @version 1.0
     * @author rorozco (V 1.0)
     * */
    public function _construct_script()
    {
        return <<< OE

        function start_formulario(){

            jQuery('#Aceptar').on('click',fn_gestionar);
            jQuery('#Limpiar').on('click',fn_limpiar);
            fn_keypress();
            timeAjax('div_loading');
        }

        function fn_limpiar(){
			$("#justificacion").val('');
			$("#estado").val('');
        }

        function fn_gestionar(){

            if( validar_all('required') ){

                if(jQuery('#estado').val() == 'N' && jQuery('#justificacion').val() == ''){
                   fn_mensaje('Debe ingresar una justificacion!',{ title:'Mensaje'} );
                }else{

                   fn_consumo('runGestionar',0,0);
                }

            }else
                fn_mensaje('Los campos marcados son requeridos!',{ title:'Mensaje'} );
        }

 	    function fn_consumo(Metodo,Id,Opc){

            var datos = (Id == 0)? jQuery('#Formulario').serializeArray() : Id;

            jQuery.post('{$this->_file}',{ 'process':Metodo, 'd':datos},function(respuesta){
   					   switch(Opc){
                        case 0:

                            var obj = JSON.parse(respuesta);

                            if(obj.Codigo == 0){
                             alert("Transaccion Exitosa");
                             window.close();
                            }else{

                              alert(obj.Mensaje);

                            }

                        break;

					   }

            });
        }


        jQuery(document).on('ready',start_formulario);
OE;
    }

}

?>
