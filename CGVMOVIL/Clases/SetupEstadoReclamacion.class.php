<?php
/**
 * Created by PhpStorm.
 * User: rorozco
 * Date: 27/12/16
 * Time: 02:34 PM
 */

class SetupEstadoReclamacion extends FormularioMGW{

    public $CampoLinea = "ID";

    /**
     * Titulo del CRUD
     * @access public
     * @see SetupEstadoReclamacion
     * @var string
     */
    public $_titulo = 'SETUP ESTADO RECLAMACION';
    /**
     * Nombre tabla principal del CRUD
     * @access public
     * @see SetupEstadoReclamacion
     * @var string
     */
    public $Table = 'CGVM_ESTADO_RECLAMACION';
    /**
     * Llave primaria de la tabla principal del CRUD
     * @access public
     * @see SetupTipoReclamo
     * @var string
     */
    public $PrimaryKey = 'ID_ESTADO_RECLAMACION';
    /**
     * Sequencia para insertar registros en la tabla principal del CRUD
     * @access public
     * @see SetupEstadoReclamacion
     * @var string
     */
    public $Sequence = 'CGVM_ESTADO_RECLAMACION_SEQ';

    /**
     * Estilos aplicados al CRUD
     * @access public
     * @see SetupEstadoReclamacion
     * @var Array
     */
    protected $style = array("/Template/Estilos/style.css", "../../../Template/Estilos/estilos.css",'/Javascript/Jqueryui/estilos_jquery.min.css');

    /**
     * SetupEstadoReclamacion::_construct()
     * Metodo constructor en el cual se inicializa la base de datos
     * @return void
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     */
    function __construct(){
        parent::__construct('AGENDAMIENTO');
    }

    /**
     * SetupEstadoReclamacion::_construct_form()
     * Metodo que prepara todos los componentes graficos del CRUD
     * @return void
     * @version 1.0 - Version inicial 2016/10/03
     * @author rorozco (V 1.0)
     */
    public function _construct_form() {
        $i = 0;
        $_formu = array();
        $_formu[$i++][] = $this->create_titulo($this->_titulo);

        $_formu[$i++][] = $this->create_input('hidden','id_estado', 'id_estado', '', '', '', '','', '' ,'');

        $_formu[$i][] = $this->create_texto('CODIGO ESTADO: ');

        $_formu[$i++][] = $this->create_input('text', 'codigo', 'codigo', '', 'required', '', 'onblur=validarRegistro(this.value)');

        $_formu[$i][] = $this->create_texto('NOMBRE ESTADO: ');

        $_formu[$i++][] = $this->create_input('text', 'nombre', 'nombre', '', 'required', '', 'onblur=validarCampoDes(this.value)');

        $_formu[$i++][] = $this->create_separador();
        $_formu[$i++][] =
                $this->create_button(true, 'actualizar', 'actualizar', 'Actualizar').
                $this->create_texto('&nbsp;&nbsp;&nbsp;').
                $this->create_button(true, 'limpiar', 'limpiar', 'Limpiar');

        $_formu[$i++][] = $this->create_separador() . $this->create_loading('/image/ajax-loader.gif');
        $_formu[$i++][] = "<div id='listado'>".$this->getListado()."</div>";

        $this->form_creacion = $this->print_formulario('Formulario', 'Formulario', $_formu, 4);
    }


    /**
     * SetupEstadoReclamacion::getSqlListado()
     * Metodo consulta la tabla principal y devuelve los registros contenidos en forma de grilla
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    function getSqlListado($Excel) {

        $editar   = $this->Imagenes($this->PrimaryKey, 0);
        $deshabilitar = $this->Imagenes($this->PrimaryKey, 1);

        $consulta = <<< OE

            SELECT
            CER.CODIGO_ESTADO AS "CODIGO ESTADO",
            CER.NOMBRE_ESTADO AS "NOMBRE ESTADO",
            CASE CER.ESTADO WHEN 'A' THEN 'ACTIVO'
   			ELSE 'INACTIVO' END
			AS ESTADO,
			{$editar},
            {$deshabilitar}
            FROM {$this->Table} CER
            ORDER BY CER.CODIGO_ESTADO
OE;
        return $this->ConsultarSql($consulta);

    }


    /**
     * SetupEstadoReclamacion ::runActualizar()
     * Metodo que inserta o edita los registros
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public function runActualizar() {

        $_respuesta = array('Codigo' => 0, "Mensaje" => $this->MensajeActualizacion);

        try {

            $this->_datos['ID_USUARIO'] = $this->_usuario['IDUSUARIO'];

            if ($this->_datos['id_estado'] == 0) {

                $sql = <<< OE
                  INSERT INTO {$this->Table} ({$this->PrimaryKey},CODIGO_ESTADO,NOMBRE_ESTADO, ESTADO, FECHA_GRABACION,
                  FECHA_MODIFICACION,USUARIO_GRABACION, USUARIO_MODIFICACION)
                  VALUES ({$this->Sequence}.nextval,
                  UPPER(:a),
                  UPPER(:b),
                  'A',
                  SYSDATE,
                  SYSDATE,
                  :c,
                  :d )
OE;

                $textbind=<<<OE
                        \$p1 = '{$this->_datos['codigo']}';
                        \$p2 = '{$this->_datos['nombre']}';
                        \$p3 = '{$this->_datos['ID_USUARIO']}';
                        \$p4 = '{$this->_datos['ID_USUARIO']}';
                        oci_bind_by_name(\$this->resultado,':a',\$p1);
                        oci_bind_by_name(\$this->resultado,':b',\$p2);
                        oci_bind_by_name(\$this->resultado,':c',\$p3);
                        oci_bind_by_name(\$this->resultado,':d',\$p4);

OE;

                $this->Consulta($sql,0,0,$textbind);


            }else{

                $sql = <<<OE
                  UPDATE {$this->Table} SET ESTADO = 'A', USUARIO_MODIFICACION= :a,
				  FECHA_MODIFICACION = SYSDATE, NOMBRE_ESTADO = UPPER(:b),
				  CODIGO_ESTADO = UPPER(:c)
				  WHERE {$this->PrimaryKey} = :d

OE;
                $textbind=<<<OE
                        \$p1 = '{$this->_datos['ID_USUARIO']}';
                        \$p2 = '{$this->_datos['nombre']}';
                        \$p3 = '{$this->_datos['codigo']}';
                        \$p4 = {$this->_datos['id_estado']};
                        oci_bind_by_name(\$this->resultado,':a',\$p1);
                        oci_bind_by_name(\$this->resultado,':b',\$p2);
                        oci_bind_by_name(\$this->resultado,':c',\$p3);
                        oci_bind_by_name(\$this->resultado,':d',\$p4);
OE;

                $this->Consulta($sql,0,0,$textbind);

            }


        } catch (exception $e) {

            $_respuesta = array('Codigo' => 99, "Mensaje" => $e->getMessage());
        }
        print_r(json_encode($_respuesta));
    }

    /**
     * SetupEstadoReclamacion::runValidarCampo()
     * Metodo que valida los campos insertados
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    function runValidarCampo($campo) {

        $_respuesta = "1";

        if(preg_match("/^[A-Z0-9 a-z _\-]*$/",$campo)){
            $_respuesta = "0";
        }

        return $_respuesta;

    }

    /**
     * SetupEstadoReclamacion::runValidarCampoDescripcion()
     * Metodo que valida los campos insertados
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public function runValidarCampoDescripcion(){

        $_respuesta = "0";

        $datos = explode ('*',$this->_datos);

        $resp = $this->runValidarCampo($datos[0]);

        $des = strtoupper ($datos[0]);

        if($resp == "0"){

                if($datos[1]>0){

                    $datos  = $this->ConsultaByIdNombre($des,$datos[1]);

                    if(!empty($datos)){
                        $_respuesta = "2";
                    }
                }else{
                    $datos  = $this->ConsultaByNombre($des);

                    if(!empty($datos)){
                        $_respuesta = "2";
                    }

                }
        }else{

                $_respuesta = "3";
        }

        echo $_respuesta;

    }

    /**
     * SetupEstadoReclamacion::ConsultaByIdDescripcion()
     * Metodo que consulta la tabla CGVM_ESTADO_RECLAMACION por id y descripcion
     * @access public
     * @param $desc,$id
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public  function ConsultaByIdNombre($desc,$id)
    {
        $consulta = <<<OE

        SELECT CER.ID_ESTADO_RECLAMACION
        FROM {$this->Table} CER
        WHERE CER.NOMBRE_ESTADO = '{$desc}'
        AND CER.ID_ESTADO_RECLAMACION != {$id}
OE;

        return $this->ConsultarSql($consulta);
    }

    /**
     * SetupEstadoReclamacion::ConsultaByDescripcion()
     * Metodo que consulta la tabla CGVM_ESTADO_RECLAMACION por descripcion
     * @access public
     * @param $desc
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public  function ConsultaByNombre($desc)
    {
        $consulta = <<<OE

        SELECT CER.ID_ESTADO_RECLAMACION
        FROM {$this->Table} CER
        WHERE CER.NOMBRE_ESTADO = '{$desc}'
OE;

        return $this->ConsultarSql($consulta);
    }

    /**
     * SetupEstadoReclamacion::ConsultaByIdCodigo()
     * Metodo que consulta la tabla CGVM_ESTADO_RECLAMACION por codigo y id
     * @access public
     * @param $cod,$id
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public  function ConsultaByIdCodigoEstado($cod,$id)
    {
        $consulta = <<<OE

        SELECT CER.ID_ESTADO_RECLAMACION
        FROM {$this->Table} CER
        WHERE CER.CODIGO_ESTADO = '{$cod}'
        AND CER.ID_ESTADO_RECLAMACION != {$id}
OE;

        return $this->ConsultarSql($consulta);
    }

    /**
     * SetupEstadoReclamacion::ConsultaByCodigo()
     * Metodo que consulta la tabla CGVM_ESTADO_RECLAMACION por codigo
     * @access public
     * @param $cod
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public  function ConsultaByCodigoEstado($cod)
    {
        $consulta = <<<OE

        SELECT CER.ID_ESTADO_RECLAMACION
        FROM {$this->Table} CER
        WHERE CER.CODIGO_ESTADO = '{$cod}'
OE;
        return $this->ConsultarSql($consulta);
    }

    /**
     * SetupEstadoReclamacion ::runValidarRegistro()
     * Metodo que valida los registros insertados
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2015/06/27
     * @author rorozco (V 1.0)
     * */
    public function runValidarRegistro(){

        $_respuesta = "0";

        $datos = explode ('*',$this->_datos);

        $longitudCodigo = strlen($datos[0]);

        if($longitudCodigo == 1){

            $resp = $this->runValidarCampo($datos[0]);

            if($resp == "0"){

                if($datos[1]>0){

                    $datos  = $this->ConsultaByIdCodigoEstado($datos[0],$datos[1]);

                    if(!empty($datos)){
                        $_respuesta = "2";
                    }
                }else{
                    $datos = $this->ConsultaByCodigoEstado($datos[0]);

                    if(!empty($datos)){
                        $_respuesta = "2";
                    }

                }
            }else{

                $_respuesta = "3";
            }

        }else{

            $_respuesta = "4";

        }

        echo $_respuesta;

    }

    /**
     * SetupEstadoReclamacion ::runDeshabilitar()
     * Metodo que elimina logicamente un registro
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    function runDeshabilitar() {

        $_respuesta = array('Codigo' => 0, "Mensaje" => $this->MensajeActualizacion);

        $ID_USUARIO = $this->_usuario['IDUSUARIO'];
        $FECHA = 'SYSDATE';

        try {

            $sql = <<<OE
            DELETE FROM {$this->Table}  WHERE {$this->PrimaryKey} = {$this->_datos}
OE;

            $this->Consulta($sql);
        } catch (Exception $e) {
            $_respuesta = array('Codigo' => 99, "Mensaje" => $e->getMessage());
        }
        print_r(json_encode($_respuesta));
    }

    /**
     * SetupEstadoReclamacion ::runEditar()
     * Metodo que consulta los datos del registro a ser editado
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public function runEditar(){

        $id = $_POST['d'];
        try {

            $consulta = <<<OE

            SELECT CER.ID_ESTADO_RECLAMACION, CER.CODIGO_ESTADO, CER.NOMBRE_ESTADO, CER.ESTADO
            FROM {$this->Table} CER
            WHERE CER.ID_ESTADO_RECLAMACION = {$id}
OE;

            $result = $this->ConsultarSql($consulta);

            print_r(json_encode($result[0]));

        }
        catch(exception $e){
            echo "Consulta invalida!";
        }
    }

    /**
     * SetupEstadoReclamacion::runConsultar()
     * Metodo para enviar los datos y filtrar la grilla segun lo solicitado
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public function runConsultar()
    {

        $j = 0;
        $_formu2 = array();
        $aux = $this->getListado();
        $_formu2[$j++][] = $aux;
        echo $this->print_formulario('Tabla1', 'Tabla1', $_formu2, 5);

    }

    /**
     * SetupEstadoReclamacion::_construct_script()
     * Aqui se encuentra el codigo javascript, ajax y jquery para las acciones de los botones entre otras.
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/03
     * @author rorozco (V 1.0)
     * */
    public function _construct_script()
    {
        return <<< OE
        function start_formulario(){
            jQuery('#actualizar').on('click',fn_guardar);
            jQuery('#limpiar').on('click',fn_limpiar);
            fn_keypress();
            timeAjax('div_loading');

        }
        function fn_guardar(){
            if( validar_all('required') ){

                fn_consumo('runActualizar',0,1);

    		}else
                fn_mensaje('{$this->MensajeCamposRequeridos}',{ title:'Mensaje'} );
        }

        function fn_limpiar(){
            location.href= '{$this->_file}';
        }


		function fn_consultar(){
    		fn_consumo('runConsultar',0,2);

    	}

    	function fn_editar(id){
            	fn_consumo('runEditar',id,3);
        }

        function fn_eliminar(valor){
        	if(confirm('Esta seguro de eliminar del registro?'))
				fn_consumo('runDeshabilitar',valor,0);
    	}

        function validarCampoDes(valor){

			var id  = $('#id_estado').val();

            if(valor != '' ){
                var datos = valor+'*'+id;

                jQuery.ajax({
                type:'POST',
                dataType:'json',
                url:'{$this->_file}',
                data:{ 'process':'runValidarCampoDescripcion','d':datos },
                success:function(respuesta){

                            if(respuesta == 2){
                                    alert('Esta descripcion ya existe');
                                    jQuery('#nombre').val('');

                            }
                            if(respuesta == 3){
                                  alert('Formato invalido');
                                  jQuery('#nombre').val('');
                            }


                        },
                    error:function(x){

                        fn_mensaje(x.responseText,{ modal:false });
                    }
				});
            }

        }

        function validarRegistro(valor){

			var id  = $('#id_estado').val();

            if(valor != '' ){
                var datos = valor+'*'+id;

                jQuery.ajax({
                type:'POST',
                dataType:'json',
                url:'{$this->_file}',
                data:{ 'process':'runValidarRegistro','d':datos },
                success:function(respuesta){

                            if(respuesta == 2){
                                    alert('Este codigo ya existe');
                                    jQuery('#codigo').val('');

                            }
                            if(respuesta == 3){
                                  alert('Formato invalido');
                                  jQuery('#codigo').val('');
                            }
                            if(respuesta == 4){

                                alert('Solo se admite un caracter');
                                jQuery('#codigo').val('');
                            }


                        },
                    error:function(x){

                        fn_mensaje(x.responseText,{ modal:false });
                    }
				});
            }

        }

 	    function fn_consumo(Metodo,Id,Opc){
            var datos = (Id == 0)? jQuery('#Formulario').serializeArray() : Id;
            jQuery.post('{$this->_file}',{ 'process':Metodo, 'd':datos},function(respuesta){
   					   switch(Opc){
                        case 0:
                        	 var obj = JSON.parse(respuesta);
                             if( obj.Codigo == 0 )
                                fn_limpiar();
                        break;
                        case 1:
                            var obj = JSON.parse(respuesta);
                            alert( obj.Mensaje );
                            if( obj.Codigo == 0 ){
                                fn_limpiar();
                            }
                        break;
                        case 2:
                             jQuery('#listado').html(respuesta);
                        break;
                        case 3:
                              var obj = JSON.parse(respuesta);

                              jQuery('#id_estado').val(obj['ID_ESTADO_RECLAMACION']);
                              jQuery('#nombre').val(obj['NOMBRE_ESTADO']);
                              jQuery('#codigo').val(obj['CODIGO_ESTADO']);

                        break;

						}

            });
        }


        jQuery(document).on('ready',start_formulario);
OE;
    }

}

?>