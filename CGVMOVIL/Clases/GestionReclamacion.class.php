<?php

/**
 * Gestion Reclamacion
 * @package CGVMOVIL
 * @author rorozco
 * @version 31/10/2016
 * @access public
 */
class GestionReclamacion extends FormularioMGW
{

    public $CampoLinea = "ID";
    /**
     * Titulo del aplicativo
     * @access public
     * @see GestionReclamacion
     * @var string
     */
    public $_titulo = 'GESTION RECLAMACION';

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
     * GestionReclamacion::_construct()
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
     * GestionReclamacion::_construct_form()
     * Metodo que prepara todos los componentes graficos del CRUD
     * @return void
     * @version 1.0 - Version inicial
     * @author rorozco (V 1.0)
     */
    public function _construct_form()
    {
         $i = 0;
         $_formu = array();

         $_formu[$i++][] = $this->create_titulo($this->_titulo, '', 'style="text-align:left;"');

         $_formu[$i][] = $this->create_texto('CUENTA: ');

         $_formu[$i++][] = $this->create_input('text', 'cuenta', 'cuenta', '', '', '', 'onblur=fn_validarCuenta(this.value)');

         $_formu[$i][] = $this->create_texto('CODIGO VENDEDOR: ');

         $_formu[$i++][] = $this->create_input('text', 'vendedor', 'vendedor', '', '', '', 'onblur=fn_validarVendedor(this.value)');

         $_formu[$i][] = $this->create_texto('OT: ');

         $_formu[$i++][] = $this->create_input('text', 'ot', 'ot', '', '', '', 'onblur=fn_validarOT(this.value)');

         $_formu[$i++][] = $this->create_separador();

         $_formu[$i++][] =
                    $this->create_texto('<center>').
                    $this->create_button(true, 'Buscar', 'Buscar', 'Buscar').
                    $this->create_texto('&nbsp;&nbsp;&nbsp;').
                    $this->create_button(true, 'Limpiar', 'Limpiar', 'Limpiar').
                    $this->create_texto('</center>');

         $_formu[$i++][] = $this->create_separador() . $this->create_loading('/image/ajax-loader.gif');

         $_formu[$i++][] = "<div id='listado'>".$this->getListado()."</div>";

         $this->form_creacion = $this->print_formulario('Formulario', 'Formulario', $_formu, 4);
    }

    /**
     * GestionReclamacion ::runConsulta()
     * Metodo consulta la tabla principal y devuelve los registros contenidos en forma de grilla
     * @para
     * @return
     * @author rorozco
     * */
    public function runConsulta(){
        $i = 0;
        $_formu = array();
        $_formu[$i++][] = $this->getListado();

        print $this->print_formulario('Formulario', 'Formulario', $_formu, 1);

    }

    /**
     * GestionReclamacion ::getSqlListado()
     * Metodo consulta la tabla principal y devuelve los registros contenidos en forma de grilla
     * @param mixed $Excel
     * @return
     * @author rorozco
     * */
    function getSqlListado($Excel)
    {

        $id = 'CMR.ID_RECLAMACION';
        $gestionar =  (ValRolInterno('CGVM06') ? ', ' . " '<a style=\"text-decoration:underline;\" href=\"#\" onclick=\"javascript:fn_gestionar('||".$id."||');\">Gestionar</a>' Gestionar" : '');

        if(isset($this->_datos['cuenta']) || isset($this->_datos['ot']) || isset($this->_datos['vendedor'])){

            $condicion = 'WHERE ';
            $aux = 0;

            if(isset($this->_datos['cuenta']) && !empty($this->_datos['cuenta'])){

                $condicion .= '  CMR.CUENTA =' .$this->_datos['cuenta'];
                $aux = 1;
            }
            if(isset($this->_datos['ot']) && !empty($this->_datos['ot'])){
                if($aux == 1){
                    $condicion .= ' AND CMR.OT =' .$this->_datos['ot'];
                }else{
                    $condicion .= '  CMR.OT =' .$this->_datos['ot'];
                }
            }
            if(isset($this->_datos['vendedor']) && !empty($this->_datos['vendedor'])){
                if($aux == 1){
                    $condicion .= ' AND CMR.VENDEDOR =' .$this->_datos['vendedor'];
                }else{
                    $condicion .= '  CMR.VENDEDOR =' .$this->_datos['vendedor'];
                }
            }

            $consulta = <<<OE

                SELECT
                CMR.CUENTA,
                CMR.OT,
                CMR.NOMBRE_SERVICIO "SERVICIO",
                CMR.VENDEDOR,
                CTR.CODIGO "TIPO RECLAMACION",
                TO_CHAR(CMR.FECHA_INGRESO,'dd/mm/yyyy HH24:MI:SS') "FECHA INGRESO",
                CASE
                  WHEN CMR.ESTADO = 'P' THEN 'PENDIENTE'
                  WHEN CMR.ESTADO = 'N' THEN 'NEGADO'
                  WHEN CMR.ESTADO = 'S' THEN 'APROBADO'
                END ESTADO,
                TO_CHAR(CMR.FECHA_GESTION,'dd/mm/yyyy HH24:MI:SS') "FECHA GESTION"

                {$gestionar}

                FROM CGVM_RECLAMACION CMR
                INNER JOIN CGVM_TIPO_RECLAMO CTR ON CTR.ID_TIPO_RECLAMO = CMR.TIPO_RECLAMO

                {$condicion}

                ORDER BY CMR.FECHA_INGRESO DESC
OE;

        }else{

             $consulta = <<<OE

                SELECT
                CMR.CUENTA,
                CMR.OT,
                CMR.NOMBRE_SERVICIO "SERVICIO",
                CMR.VENDEDOR,
                CTR.CODIGO "TIPO RECLAMACION",
                TO_CHAR(CMR.FECHA_INGRESO,'dd/mm/yyyy HH24:MI:SS') "FECHA INGRESO",
                CASE
                  WHEN CMR.ESTADO = 'P' THEN 'PENDIENTE'
                  WHEN CMR.ESTADO = 'N' THEN 'NEGADO'
                  WHEN CMR.ESTADO = 'S' THEN 'APROBADO'
                END ESTADO,
                TO_CHAR(CMR.FECHA_GESTION,'dd/mm/yyyy HH24:MI:SS') "FECHA GESTION"

                {$gestionar}

                FROM CGVM_RECLAMACION CMR
                INNER JOIN CGVM_TIPO_RECLAMO CTR ON CTR.ID_TIPO_RECLAMO = CMR.TIPO_RECLAMO

                ORDER BY CMR.FECHA_INGRESO DESC
OE;
        }


        return $this->ConsultarSql($consulta);
    }

    /**
     * GestionReclamacion::_construct_script()
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

            jQuery('#Buscar').on('click',fn_buscar);
            jQuery('#Limpiar').on('click',fn_limpiar);
            fn_keypress();
            timeAjax('div_loading');
        }

        function fn_limpiar(){
            location.href= '{$this->_file}';
        }

        function fn_validarCuenta(valor){

            if($.isNumeric(valor) == false){
               fn_mensaje('El campo Cuenta debe ser numerico',{ title:'Mensaje'});
               jQuery('#cuenta').val('');
            }
        }

        function fn_validarOT(valor){

            if($.isNumeric(valor) == false){

               fn_mensaje('El campo Orden de Trabajo debe ser numerico',{ title:'Mensaje'});
               jQuery('#ot').val('');

            }
        }

        function fn_validarVendedor(valor){

            if($.isNumeric(valor) == false){
               fn_mensaje('El campo Vendedor debe ser numerico',{ title:'Mensaje'});
               jQuery('#vendedor').val('');
            }
        }

        function fn_buscar(){

           if(jQuery('#cuenta').val() != '' || jQuery('#ot').val() != '' || jQuery('#vendedor').val() != ''){

              fn_consumo('runConsulta',0,0);

           }else{
              fn_mensaje('Debe ingresar al menos un filtro de busqueda',{ title:'Mensaje'} );
           }
        }

        function fn_gestionar(id){

          window.open("/CGVMOVIL/GestionarReclamacion.php?id="+id);

        }

 	    function fn_consumo(Metodo,Id,Opc){
            var datos = (Id == 0)? jQuery('#Formulario').serializeArray() : Id;

            jQuery.post('{$this->_file}',{ 'process':Metodo, 'd':datos},function(respuesta){
   					   switch(Opc){
                        case 0:

                        	jQuery('#listado').html(respuesta);

                        break;
                       }
            });
        }

        jQuery(document).on('ready',start_formulario);
OE;
    }

}

?>
