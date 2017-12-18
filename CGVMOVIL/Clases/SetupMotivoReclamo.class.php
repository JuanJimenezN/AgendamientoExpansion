<?php
/**
 * SetupMotivoReclamo
 * @package CGVMOVIL
 * @author rorozco
 * @Date: 2016/11/04
 * @Time: 08:50 Am
 * @access public
 */

class SetupMotivoReclamo extends FormularioMGW{

    public $CampoLinea = "ID";

    /**
     * Titulo del CRUD
     * @access public
     * @see SetupMotivoReclamo
     * @var string
     */
    public $_titulo = 'SETUP MOTIVO RECLAMO';
    /**
     * Nombre tabla principal del CRUD
     * @access public
     * @see SetupMotivoReclamo
     * @var string
     */
    public $Table = 'CGVM_MOTIVO_RECLAMO';
    /**
     * Llave primaria de la tabla principal del CRUD
     * @access public
     * @see SetupMotivoReclamo
     * @var string
     */
    public $PrimaryKey = 'ID_MOTIVO_RECLAMO';
    /**
     * Sequencia para insertar registros en la tabla principal del CRUD
     * @access public
     * @see SetupMotivoReclamo
     * @var string
     */
    public $Sequence = 'CGVM_MOTIVO_RECLAMO_SEQ';

    /**
     * Estilos aplicados al CRUD
     * @access public
     * @see SetupMotivoReclamo
     * @var Array
     */
    protected $style = array("/Template/Estilos/style.css", "../../../Template/Estilos/estilos.css",'/Javascript/Jqueryui/estilos_jquery.min.css');

    /**
     * SetupMotivoReclamo::_construct()
     * Metodo constructor en el cual se inicializa la base de datos
     * @return void
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     */
    function __construct(){
        parent::__construct('AGENDAMIENTO');
    }

    /**
     * SetupMotivoReclamo::_construct_form()
     * Metodo que prepara todos los componentes graficos del CRUD
     * @return void
     * @version 1.0 - Version inicial 2016/10/04
     * @author rorozco (V 1.0)
     */
    public function _construct_form() {
        $i = 0;
        $_formu = array();
        $_formu[$i++][] = $this->create_titulo($this->_titulo);

        $_formu[$i++][] = $this->create_input('hidden','id_motivo', 'id_motivo', '', '', '', '','', '' ,'');

        $_formu[$i][] = $this->create_texto('TIPO RECLAMO: ');

        $sql_tipo = <<<OE

            SELECT CTR.ID_TIPO_RECLAMO AS CODIGO,
            CTR.CODIGO AS MOTIVO
            FROM CGVM_TIPO_RECLAMO CTR
            WHERE CTR.ESTADO = 'A'
            ORDER BY CTR.CODIGO
OE;

        $_formu[$i++][] = $this->create_select('tipo_reclamo', 'tipo_reclamo', 'select', 'required', '', '',$sql_tipo, 'CODIGO' ,'MOTIVO');

        $_formu[$i][] = $this->create_texto('CODIGO MOTIVO: ');
        $_formu[$i++][] = $this->create_input('text', 'codigo_motivo', 'codigo_motivo', '', 'required', '', 'onblur=validarCampoC(this.value)');

        $_formu[$i][] = $this->create_texto('MOTIVO RECLAMO: ');
        $_formu[$i++][] = $this->create_input('text', 'motivo', 'motivo', '', 'required', '', 'onblur=validarCampoDes(this.value)');

        $_formu[$i++][] = $this->create_separador();
        $_formu[$i++][] =
                $this->create_button(true, 'actualizar', 'actualizar', 'actualizar').
                $this->create_texto('&nbsp;&nbsp;&nbsp;').
                $this->create_button(true, 'limpiar', 'limpiar', 'limpiar');

        $_formu[$i++][] = $this->create_separador() . $this->create_loading('/image/ajax-loader.gif');
        $_formu[$i++][] = "<div id='listado'>".$this->getListado()."</div>";

        $this->form_creacion = $this->print_formulario('Formulario', 'Formulario', $_formu, 4);
    }


    /**
     * SetupMotivoReclamo::getSqlListado()
     * Metodo consulta la tabla principal y devuelve los registros contenidos en forma de grilla
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     * */
    function getSqlListado($Excel) {

        $editar   = $this->Imagenes($this->PrimaryKey, 0);
        $deshabilitar = $this->Imagenes($this->PrimaryKey, 1);

        $consulta = <<< OE

            SELECT
            CMR.CODIGO_MOTIVO CODIGO,
            CMR.MOTIVO,
            CTR.DESCRIPCION AS "TIPO RECLAMO",
            CASE CMR.ESTADO WHEN 'A' THEN 'ACTIVO'
   			ELSE 'INACTIVO' END
			AS ESTADO,
			{$editar},
            {$deshabilitar}
            FROM CGVM_MOTIVO_RECLAMO CMR
            INNER JOIN CGVM_TIPO_RECLAMO CTR ON CTR.ID_TIPO_RECLAMO = CMR.ID_TIPO_RECLAMO
            ORDER BY CMR.MOTIVO
OE;
        return $this->ConsultarSql($consulta);

    }


    /**
     * SetupMotivoReclamo ::runActualizar()
     * Metodo que inserta o edita los registros
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     * */
    public function runActualizar() {

        $_respuesta = array('Codigo' => 0, "Mensaje" => $this->MensajeActualizacion);

        try {

            $this->_datos['ID_USUARIO'] = $this->_usuario['IDUSUARIO'];

            if ($this->_datos['id_motivo'] == 0) {

                $sql = <<< OE
                  INSERT INTO {$this->Table} ({$this->PrimaryKey},ID_TIPO_RECLAMO,MOTIVO, ESTADO, FECHA_CREACION,
                  FECHA_MODIFICACION,USUARIO_CREACION, USUARIO_MODIFICACION,CODIGO_MOTIVO)
                  VALUES ({$this->Sequence}.nextval,
                  :a,
                  UPPER(:b),
                  'A',
                  SYSDATE,
                  SYSDATE,
                  :c,
                  :d,
                  UPPER(:e))
OE;
                $textbind=<<<OE
                        \$p1 =  {$this->_datos['tipo_reclamo']};
                        \$p2 = '{$this->_datos['motivo']}';
                        \$p3 = '{$this->_datos['ID_USUARIO']}';
                        \$p4 = '{$this->_datos['ID_USUARIO']}';
                        \$p5 = '{$this->_datos['codigo_motivo']}';
                        oci_bind_by_name(\$this->resultado,':a',\$p1);
                        oci_bind_by_name(\$this->resultado,':b',\$p2);
                        oci_bind_by_name(\$this->resultado,':c',\$p3);
                        oci_bind_by_name(\$this->resultado,':d',\$p4);
                        oci_bind_by_name(\$this->resultado,':e',\$p5);
OE;

                $this->Consulta($sql,0,0,$textbind);

            }else{

                $sql = <<<OE
                  UPDATE {$this->Table} SET ESTADO = 'A', USUARIO_MODIFICACION= :a,
				  FECHA_MODIFICACION = SYSDATE, MOTIVO = UPPER(:b),
				  ID_TIPO_RECLAMO = :c,CODIGO_MOTIVO = UPPER(:d)
				  WHERE {$this->PrimaryKey} = :e

OE;
                $textbind=<<<OE
                        \$p1 =  '{$this->_datos['ID_USUARIO']}';
                        \$p2 =  '{$this->_datos['motivo']}';
                        \$p3 =  {$this->_datos['tipo_reclamo']};
                        \$p4 =  '{$this->_datos['codigo_motivo']}';
                        \$p5 =  {$this->_datos['id_motivo']};
                        oci_bind_by_name(\$this->resultado,':a',\$p1);
                        oci_bind_by_name(\$this->resultado,':b',\$p2);
                        oci_bind_by_name(\$this->resultado,':c',\$p3);
                        oci_bind_by_name(\$this->resultado,':d',\$p4);
                        oci_bind_by_name(\$this->resultado,':e',\$p5);
OE;

                $this->Consulta($sql,0,0,$textbind);
            }


        } catch (exception $e) {

            $_respuesta = array('Codigo' => 99, "Mensaje" => $e->getMessage());
        }
        print_r(json_encode($_respuesta));
    }

    /**
     * SetupMotivoReclamo ::runValidarCampo()
     * Metodo que valida los campos insertados
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
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
     * SetupMotivoReclamo::runValidarCampoDescripcion()
     * Metodo que valida los campos insertados
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     * */
    public function runValidarCampoDescripcion(){

        $_respuesta = "0";

        $datos = explode ('*',$this->_datos);

        $resp = $this->runValidarCampo($datos[0]);

        $des = strtoupper ($datos[0]);

        if($resp == "0"){

            if($datos[1]>0){

                $datos  = $this->ConsultaByIdDescripcion($des,$datos[1]);

                if(!empty($datos)){
                    $_respuesta = "2";
                }
            }else{
                $datos  = $this->ConsultaByDescripcion($des);

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
     * SetupMotivoReclamo::ConsultaByIdDescripcion()
     * Metodo que consulta la tabla CGVM_MOTIVO_RECLAMO por id y descripcion
     * @access public
     * @param $desc,$id
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     * */
    public  function ConsultaByIdDescripcion($desc,$id)
    {
        $consulta = <<<OE

        SELECT CMR.ID_MOTIVO_RECLAMO
        FROM CGVM_MOTIVO_RECLAMO CMR
        WHERE CMR.MOTIVO = '{$desc}'
        AND CMR.ID_MOTIVO_RECLAMO != {$id}
OE;

        return $this->ConsultarSql($consulta);
    }

    /**
     * SetupMotivoReclamo::ConsultaByDescripcion()
     * Metodo que consulta la tabla CGVM_MOTIVO_RECLAMO por descripcion
     * @access public
     * @param $desc
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     * */
    public  function ConsultaByDescripcion($desc)
    {
        $consulta = <<<OE

        SELECT CMR.ID_MOTIVO_RECLAMO
        FROM CGVM_MOTIVO_RECLAMO CMR
        WHERE CMR.MOTIVO = '{$desc}'
OE;

        return $this->ConsultarSql($consulta);
    }

    /**
     * SetupMotivoReclamo ::runValidarRegistro()
     * Metodo que valida los registros insertados
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2015/06/27
     * @author rorozco (V 1.0)
     * */
    public function runValidarRegistro(){

        $_respuesta = "0";

        $datos = explode ('*',$this->_datos);

        $resp = $this->runValidarCampo($datos[0]);

        if($resp == "0"){

            if($datos[1]>0){

                $datos  = $this->ConsultaByIdCodigo($datos[0],$datos[1]);

                if(!empty($datos)){
                    $_respuesta = "2";
                }
            }else{
                $datos = $this->ConsultaByCodigo($datos[0]);

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
     * SetupMotivoReclamo ::runValidarCodigoMotivo()
     * Metodo que valida los registros insertados
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2015/06/27
     * @author rorozco (V 1.0)
     * */
    public function runValidarCodigoMotivo(){

        $_respuesta = "0";

        $datos = explode ('*',$this->_datos);

        $resp = $this->runValidarCampo($datos[0]);

        if($resp == "0"){

            $codigoM = strtoupper($datos[0]);

            if($datos[1]>0){


                $sql = <<<OE

                SELECT MR.ID_MOTIVO_RECLAMO FROM CGVM_MOTIVO_RECLAMO MR WHERE MR.ID_MOTIVO_RECLAMO = {$datos[1]}
                AND MR.CODIGO_MOTIVO = '{$codigoM}'
OE;

                $datos = $this->ConsultarSql($sql);

                if(!empty($datos)){
                    $_respuesta = "2";
                }
            }else{
                $sql = <<<OE

                SELECT MR.ID_MOTIVO_RECLAMO FROM CGVM_MOTIVO_RECLAMO MR WHERE MR.CODIGO_MOTIVO = '{$codigoM}'
OE;

                $datos = $this->ConsultarSql($sql);

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
     * SetupMotivoReclamo ::runDeshabilitar()
     * Metodo que elimina logicamente un registro
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     * */
    function runDeshabilitar() {

        $_respuesta = array('Codigo' => 0, "Mensaje" => $this->MensajeActualizacion);

        $ID_USUARIO = $this->_usuario['IDUSUARIO'];
        $FECHA = 'SYSDATE';

        try {

            $sql = <<<OE
            DELETE {$this->Table} WHERE {$this->PrimaryKey} = {$this->_datos}
OE;

            $this->Consulta($sql);
        } catch (Exception $e) {
            $_respuesta = array('Codigo' => 99, "Mensaje" => $e->getMessage());
        }
        print_r(json_encode($_respuesta));
    }


    /**
     * SetupMotivoReclamo ::runEditar()
     * Metodo que consulta los datos del registro a ser editado
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
     * @author rorozco (V 1.0)
     * */
    public function runEditar(){

        $id = $_POST['d'];
        try {

            $consulta = <<<OE

            SELECT CMR.ID_MOTIVO_RECLAMO, CMR.ID_TIPO_RECLAMO, CMR.MOTIVO, CMR.ESTADO, CMR.CODIGO_MOTIVO
            FROM CGVM_MOTIVO_RECLAMO CMR
            WHERE CMR.ID_MOTIVO_RECLAMO = {$id}
OE;

            $result = $this->ConsultarSql($consulta);

            print_r(json_encode($result[0]));

        }
        catch(exception $e){
            echo "Consulta invalida!";
        }
    }


    /**
     * SetupMotivoReclamo::runConsultar()
     * Metodo para enviar los datos y filtrar la grilla segun lo solicitado
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
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
     * SetupMotivoReclamo::_construct_script()
     * Aqui se encuentra el codigo javascript, ajax y jquery para las acciones de los botones entre otras.
     * @access public
     * @return array
     * @version 1.0 - Version inicial 2016/11/04
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

        function validarCampoC(valor){

            var id  = $('#id_motivo').val();

            if(valor != '' ){
                var datos = valor+'*'+id;

                jQuery.ajax({
                type:'POST',
                dataType:'json',
                url:'{$this->_file}',
                data:{ 'process':'runValidarCodigoMotivo','d':datos },
                success:function(respuesta){

                            if(respuesta == 2){
                                    alert('Este codigo ya existe');
                                    jQuery('#codigo_motivo').val('');

                            }
                            if(respuesta == 3){
                                  alert('Formato invalido');
                                  jQuery('#codigo_motivo').val('');
                            }


                        },
                    error:function(x){

                        fn_mensaje(x.responseText,{ modal:false });
                    }
				});
            }

        }

        function validarCampoDes(valor){

			var id  = $('#id_motivo').val();

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
                                    jQuery('#motivo').val('');

                            }
                            if(respuesta == 3){
                                  alert('Formato invalido');
                                  jQuery('#motivo').val('');
                            }


                        },
                    error:function(x){

                        fn_mensaje(x.responseText,{ modal:false });
                    }
				});
            }

        }

        function validarRegistro(valor){

			var id  = $('#id_motivo').val();

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

                              jQuery('#id_motivo').val(obj['ID_MOTIVO_RECLAMO']);
                              jQuery('#motivo').val(obj['MOTIVO']);
                              jQuery('#tipo_reclamo').val(obj['ID_TIPO_RECLAMO']);
                              jQuery('#codigo_motivo').val(obj['CODIGO_MOTIVO']);

                        break;

						}

            });
        }


        jQuery(document).on('ready',start_formulario);
OE;
    }

}

?>
