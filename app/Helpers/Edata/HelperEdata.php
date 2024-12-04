<?php

namespace App\Helpers\Edata;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use App\Models\Clientes;
use App\Models\BblClientes;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use stdClass;

use function GuzzleHttp\Psr7\parse_query;

class HelperEdata extends HelperPago
{
    const ACTION_BLOCK = 'block';
    const ACTION_ALERT = 'alert';

    const APLICA_ALL = 'all';
    const APLICA_ONE = 'one';

    const OPERATOR_EQUAL = 'eq';
    const OPERATOR_NOT_EQUAL = 'neq';

    const STATUS_BLOCK = 'Bloqueado';
    const STATUS_ALERT = 'Alertado';
    const STATUS_ALLOW = 'Permitido';

    const ACTIVE = 1;
    const EPAYCO = 4877;
    const ALLIED_ENTITY = 15;

    const EDATA_STATE = "edata_estado";
    const EDATA_STATE_BEFORE = "edata_estado_anterior";

    /**
     * Informacion del cliente
     *
     * @var Clientes
     */
    protected $cliente;

    /**
     * Mensaje personalizado de la regla
     *
     * @var string
     */
    protected $mensaje;

    /**
     * Cuerpo del correo
     *
     * @var string
     */
    protected $correo_cuerpo = "";

    /**
     * Datos del producto
     *
     * @var string
     */
    protected $datos_producto = false;


    /**
     * Asunto del correo
     *
     * @var string
     */
    protected $correo_asunto = "";


    /**
     * Id generado por el registro de la ejecucion
     *
     * @var string
     */
    protected $id_edata;

    /**
     * Estado de la validacion
     *
     * @var string
     */
    protected $edata_estado = self::STATUS_ALLOW;

    /**
     * Reglas bloqueantes en una validacion
     *
     * @var array
     */
    protected $reglas_invalidas_bloqueantes = [];

    /**
     * Reglas alertantes en una validacion
     *
     * @var array
     */
    protected $reglas_invalidas_alertantes = [];

    /**
     * Reglas validas en una validacion
     *
     * @var array
     */
    protected $reglas_validas = [];


    /**
     * Funcion constructora
     *
     * @param Request $request el objeto request de la peticion
     * @param string $id_cliente el id del cliente que realiza la peticion
     */
    public function __construct(Request $request, string $id_cliente)
    {
        parent::__construct($request);
        $clientes = new BblClientes();
        $this->cliente = $clientes->find($id_cliente);
    }

    /**
     * Funcion para validar el nombre de un catalogo
     *
     * @param string $nombre el nombre del catalogo
     * @param int    $id     el id del catalogo si se esta modificando
     *
     * @return bool
     */
    public function validarCatalogo(string $nombre, $id = null)
    {
        return $this->validarReglas(
            'catalogo',
            ["nombre" => $nombre],
            'catalogo',
            $nombre,
            $id
        );
    }

    /**
     * Funcion para validar el nombre de una categoria
     *
     * @param string $nombre el nombre de la categoria
     * @param int    $id     el id de la categoria si se esta modificando
     *
     * @return bool
     */
    public function validarCategoria(string $nombre, $id = null)
    {
        return $this->validarReglas(
            'categoria',
            ["nombre" => $nombre],
            'categoria',
            $nombre,
            $id
        );
    }

    /**
     * Funcion para validar el nombre y la descripcion de un producto
     *
     * @param string $nombre      el titulo del producto
     * @param string $descripcion la descripcion del producto
     * @param int    $id          el id del producto si se esta modificando
     *
     * @return bool
     */
    public function validarProducto(string $nombre, string $descripcion, $id = null)
    {
        return $this->validarReglas(
            'producto',
            ["nombre" => $nombre, "descripcion" => $descripcion],
            'producto',
            $nombre,
            $id
        );
    }

    /**
     * Funcion para validar los parametros enviados un link
     *
     * @param string $link la url
     *
     * @return bool
     */
    public function validarLinkAcortado(string $link)
    {
        $parts = parse_url($link);

        if (isset($parts['query'])) {
            $query = parse_query($parts['query']);
            $values = implode(" ", $query);
            return $this->validarReglas(
                'shortlink',
                ['querystring' => $values],
                'link acortado',
                $link
            );
        } else {
            return true;
        }
    }

    /**
     * Logica de validacion para las relgas eData
     *
     * @param string $codigo   el codigo en la condicion de la regla para validar
     * @param array  $fields   los campos para validar, ejemplo ["nombre" => "balas"]
     * @param string $emailCod el tipo de objeto que se valida, ejemplo catalogo
     * @param string $emailNam nombre del objeto que se valida par enviar en el email
     * @param int    $id       el id del objeto si se modifica
     *
     * @return bool
     */
    protected function validarReglas(
        string $codigo,
        array $fields = [],
        string $emailCod = '',
        string $emailNam = '',
        $id = null
    ) {
        $fields = $this->limpiarDatos($fields);
        // Consultar las reglas asociadas
        $reglas = $this->consultarReglasAsociadas($codigo);

        // Consultar las listas asociadas a las reglas
        $listas = $this->consultarListasAsociadas($reglas, $codigo, $fields);

        // Recorro las listas para validar que se cumplan
        $this->reglas_invalidas_bloqueantes = [];
        $this->reglas_invalidas_alertantes = [];
        $this->reglas_validas = [];
        if (!empty($listas)) {


            foreach ($reglas["data"] as $regla) {
                
                $condiciones_invalidas = 0;
                foreach ($regla["condiciones"] as $condicion) {
                    $listas_encontradas = 0;

                    if(isset($listas[$condicion["campo"]]["data"]))
                    {
                      foreach ($listas[$condicion["campo"]]["data"] as $lista) {
                                        if ($condicion["lista_id"] == $lista["_id"]) {
                                            $listas_encontradas++;
                                            $condicion["lista"] = [
                                                "id" => $lista["_id"],
                                                "nombre" => $lista["_source"]["nombre"],
                                                "palabras" => $lista["inner_hits"]["palabras_claves"]["hits"]["hits"] ?? [],
                                            ];
                                            break;
                                        }
                                    }

                    if ($condicion["operador"] == self::OPERATOR_EQUAL
                        && $listas_encontradas > 0
                    ) {
                        $condiciones_invalidas++;
                    }

                    if ($condicion["operador"] == self::OPERATOR_NOT_EQUAL
                        && $listas_encontradas < 1
                    ) {
                        $condiciones_invalidas++;
                    }
                    }
                
                }

                $valid = true;
                if ($regla["aplica"] == self::APLICA_ALL
                    && $condiciones_invalidas == count($regla["condiciones"])
                    && $condiciones_invalidas > 0
                ) {
                    $valid = false;
                }

                if ($regla["aplica"] == self::APLICA_ONE
                    && $condiciones_invalidas > 0
                ) {
                    $valid = false;
                }

                // Si la regla no es valida bloqueo o alerto
                if (!$valid) {
                    if ($regla["accion"] == self::ACTION_BLOCK) {
                        $this->reglas_invalidas_bloqueantes[] = $regla;
                    }

                    if ($regla["accion"] == self::ACTION_ALERT) {
                        $this->reglas_invalidas_alertantes[] = $regla;
                    }
                } else {
                    $this->reglas_validas[] = $regla;
                }

            }
      


        }

        $this->cerrarValidacion($fields, $codigo, $emailCod, $emailNam, $id);

        return empty($this->reglas_invalidas_bloqueantes);
    }

    /**
     * Realiza el proceso de cierre de una validacion
     *
     * @param array  $fields   Datos del objeto que fue validado
     * @param string $codigo   el codigo en la condicion de la regla para validar
     * @param string $emailCod el tipo de objeto que se valida, ejemplo catalogo
     * @param string $emailNam nombre del objeto que se valida par enviar en el email
     * @param int    $id       el id del objeto si se modifica
     *
     * @return self
     */
    protected function cerrarValidacion(
        array $fields,
        string $codigo,
        string $emailCod,
        string $emailNam,
        $id = null
    ) {
        $fields['id'] = !empty($id) ? $id : '';
        $fields['codigo'] = $codigo;
        if (!empty($this->reglas_invalidas_bloqueantes)) {


            $reglasAsociada = $this->reglas_invalidas_bloqueantes[0];
            $this->edata_estado = self::STATUS_BLOCK;
            $this->mensaje = $this->reglas_invalidas_bloqueantes[0]["mensaje"];
            $this->correo_cuerpo =  isset($this->reglas_invalidas_bloqueantes[0]["correo_cuerpo"])  ? $this->reglas_invalidas_bloqueantes[0]["correo_cuerpo"] : "";
            $this->correo_asunto =  isset($this->reglas_invalidas_bloqueantes[0]["correo_asunto"])  ? $this->reglas_invalidas_bloqueantes[0]["correo_asunto"] : "Reglas";
            $this->datos_producto = isset($this->reglas_invalidas_bloqueantes[0]["datos_producto"]) ? $this->reglas_invalidas_bloqueantes[0]["datos_producto"] : "";
            $this->crearRegistro(
                $this->reglas_invalidas_bloqueantes,
                $fields,
                self::STATUS_BLOCK
            )->enviarEmail(self::ACTION_BLOCK, $emailCod, $emailNam);
        } elseif (!empty($this->reglas_invalidas_alertantes)) {


            $this->edata_estado = self::STATUS_ALERT;
            $this->mensaje = $this->reglas_invalidas_alertantes[0]["mensaje"];
            $this->correo_cuerpo  = isset($this->reglas_invalidas_alertantes[0]["correo_cuerpo"])  ? $this->reglas_invalidas_alertantes[0]["correo_cuerpo"] : "";
            $this->correo_asunto =  isset($this->reglas_invalidas_alertantes[0]["correo_asunto"])  ? $this->reglas_invalidas_alertantes[0]["correo_asunto"] : "Reglas";
            $this->datos_producto = isset($this->reglas_invalidas_alertantes[0]["datos_producto"]) ? $this->reglas_invalidas_alertantes[0]["datos_producto"] : "";
            
            $this->crearRegistro(
                $this->reglas_invalidas_alertantes,
                $fields,
                self::STATUS_ALERT
            )->enviarEmail(self::ACTION_ALERT, $emailCod, $emailNam);


        } elseif (!empty($this->reglas_validas)) {
            $this->crearRegistro($this->reglas_validas, $fields, self::STATUS_ALLOW);
        }

        return $this;
    }

    /**
     * Consulta las listas de palabras por los campos buscados
     *
     * @param array  $reglas Las reglas asociadas a las listas de palabras
     * @param string $codigo el codigo en la condicion de la regla para validar
     * @param array  $fields Las palabras a buscar
     *
     * @return array
     */
    protected function consultarListasAsociadas(
        array $reglas,
        string $codigo,
        array $fields
    ) {

        $listas_id = [];
        //CHECK IF REGLAS IS NOT EMPTY
        if(count($reglas))
        {
            foreach ($reglas["data"] as $regla) {

                foreach ($regla["condiciones"] as $idx => $condicion) {
                    if ($condicion["donde"] == $codigo
                        && $condicion["lista_id"]
                        && key_exists($condicion["campo"], $fields)
                    ) {
                        $listas_id[] = $condicion["lista_id"];
                    } else {
                        unset($regla["condiciones"][$idx]);
                    }
                }
            }
        }
        
        $total_listas = count($listas_id);
        $listas = [];
        if ($total_listas > 0) {
            foreach ($fields as $idx => $field) {
                $search = new Search();
                $search->setSize($total_listas);
                $search->setFrom(0);
                $search->setSource(['id', 'nombre']);
                $search->addQuery(new TermsQuery('id', $listas_id));
                $search->addQuery(new TermQuery('activo', true));

                $nested = new BoolQuery();
                $nested->add(new TermQuery('palabras_claves.activo', true));
                $nested->add(new MatchQuery('palabras_claves.palabra', $field));

                $nested = new NestedQuery('palabras_claves', $nested);
                /**
                 * Permite retornar las palabras que se encuentran dentro del registro enviado.
                 */
                $nested->addParameter("inner_hits", [
                    "_source" => [
                        "palabras_claves.id",
                        "palabras_claves.palabra"
                    ]
                ]);
                $search->addQuery($nested);

                $listas[$idx] = $this->consultElasticSearchConex(
                    $search->toArray(),
                    'edata_lista_palabras',
                    false
                );
            }
        }

        return $listas;
    }

    /**
     * Consulta al elastic las reglas asociadas de un codigo
     *
     * @param string $codigo codigo configurado en las condiciones de una regla
     *
     * @return array
     */
    protected function consultarReglasAsociadas(string $codigo)
    {
        if ($this->cliente->Id != self::EPAYCO) {

            $search = new Search();
            $search->setSize(5000);
            $search->setFrom(0);
            $search->addQuery(new TermQuery('condiciones.donde', $codigo));
            $search->addQuery(new TermQuery('activo', true));
            $search->addQuery(new TermQuery('entidad_id', self::EPAYCO));


            return $this->consultElasticSearchConex(
                $search->toArray(),
                'edata_regla',
                false
            );
        } else {
            return [];
        }

    }

    /**
     * Limpia los datos enviados para validar
     *
     * @param array $fields los valores a limpiar
     *
     * @return array
     */
    protected function limpiarDatos(array $fields)
    {
        $clear = [];
        foreach ($fields as $field => $value) {
            $value = trim($value);
            if (!empty($value)) {
                $clear[$field] = $value;
            }
        }
        return $clear;
    }

    /**
     * Envia un email de acuerdo a la accion generada
     *
     * @param string $accion Valor de la accion 'block' o 'alert'
     * @param string $codigo Tipo de objeto que se valido
     * @param string $name   El nombre del objeto que se valido
     *
     * @return bool
     */
    protected function enviarEmail(string $accion, string $codigo, string $name)
    {
        $vista = null;
        if ($accion == self::ACTION_BLOCK) {
            $vista = "edata_rules_block_email";
        }

        if ($accion == self::ACTION_ALERT) {
            $vista = "edata_rules_alert_email";
        }

        if ($vista && $this->cliente) {
            $this->emailEdataRest(
                $this->getCorreoAsunto(),
                $this->cliente->email,
                $vista,
                [
                    'type' => $codigo,
                    'name' => $name,
                    'message' => $this->getMensaje(),
                    'correo_cuerpo' => $this->getCorreoCuerpo(),
                    'datos_producto' => $this->getDatosProducto(),
                    'name_commerce' =>
                        $this->cliente->tipo_cliente == 'P' ?
                        $this->cliente->nombre." ".$this->cliente->apellido :
                        $this->cliente->razon_social
                ]
            );
        }

        return true;
    }

    public function emailEdataRest($subject, $toEmail, $viewName, $viewParameters = [])
    {
        $baseUrlRest = getenv("BASE_URL_REST");
        $pathPanelAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
        $pametersString = "";
        foreach ($viewParameters as $key => $parameter) {
            if (is_array($parameter) || is_object($parameter)) {
                foreach ($parameter as $key2 => $parameter2) {
                    if (is_array($parameter2) || is_object($parameter2)) {
                        foreach ($parameter2 as $key3 => $parameter3) {
                            $pametersString .= "&viewParameters[$key][$key2][$key3]=$parameter3";
                        }
                    } else {
                        $pametersString .= "&viewParameters[$key][$key2]=$parameter2";
                    }
                }
            } else {
                $pametersString .= "&viewParameters[$key]=$parameter";
            }
        }
        $url_email = "{$baseUrlRest}/{$pathPanelAppRest}/email/send?subject=$subject&toEmail=$toEmail&viewName=$viewName" . $pametersString;

        $code = $this->sendEmailEdataReglas($url_email);

        return $code;
    }

    /**
     * Crea un registro para el control de la validacion
     *
     * @param array  $reglas Las reglas por la que paso la validacion
     * @param array  $objeto La informacion del objeto que se valido
     * @param string $estado El valor de uno de los estados eData
     *
     * @return self
     */
    protected function crearRegistro(array $reglas, array $objeto, string $estado)
    {
        $listas = [];
        /**
         * Si no hay listas de palabras bloqueantes o alertantes.
         */
        if ($estado === self::STATUS_ALLOW) {
            // Consultar las listas para buscar los nombres
            $listas_id = [];
            foreach ($reglas as $regla) {
                foreach ($regla["condiciones"] as $condicion) {
                    $listas_id[] = $condicion["lista_id"];
                }
            }

            if (!empty($listas_id)) {
                $search = new Search();
                $search->setSize(count($listas_id));
                $search->setFrom(0);
                $search->addQuery(new TermsQuery('id', $listas_id));
                $search->setSource(['id', 'nombre']);

                $listas = $this->consultElasticSearchConex(
                    $search->toArray(),
                    'edata_lista_palabras',
                    false
                );
            }
        }

        // Construyo las reglas asociadas
        $reglas_asociadas = $this->construirReglasAsociadas($reglas, $listas);
        $segmento_id = null;
        $tipo_producto_id = null;
        foreach ($reglas as $regla) {
            $segmento_id = $regla["segmento_id"];
            $tipo_producto_id = $regla["tipo_producto_id"];
        }

        $this->id_edata = Uuid::uuid4()->toString();

        $edataRegistro = [
            'id' => $this->id_edata,
            'segmento_id' => $segmento_id,
            'tipo_producto_id' => $tipo_producto_id,
            'fecha_creacion' => (new \DateTime())->format(\DateTimeInterface::ATOM),
            'cliente_id' => $this->cliente ? $this->cliente->Id : null,
            'estado' => $estado,
            'objeto' => $objeto,
            'reglas_asociadas' => $reglas_asociadas,
        ];

        $this->elasticBulkUploadConex(
            ['indice' => 'edata_registro', 'data' => [$edataRegistro]]
        );

        return $this;
    }

    /**
     * Construye los datos a guardar en las reglas asociadas de un registro
     *
     * @param array $reglas las reglas por la que paso la validacion
     * @param array $listas las listas de palabras restringidas que se consultaron
     *
     * @return array
     */
    protected function construirReglasAsociadas(array $reglas, array $listas)
    {
        $reglas_asociadas = [];
        foreach ($reglas as $regla) {
            $resultados = $this->contruirResultadosReglas($regla, $listas);
            $condiciones = $this->construirCondiciones($regla["condiciones"]);
            $row = [
                'regla_id' => $regla["id"],
                'nombre' => $regla["nombre"],
                'condicion' => $resultados['condicion'],
                'resultado' => $resultados['resultado'],
                "condiciones" => $condiciones
            ];
            $reglas_asociadas[] = $row;
        }

        return $reglas_asociadas;
    }

    /**
     * Construye la estructura de almacenamiento de las condiciones con sus respectivas palabras bloqueantes.
     * @param $condiciones
     * @return array
     */
    private function construirCondiciones($condiciones): array
    {
        $result = [];

        foreach ($condiciones as $condicion) {
            $palabras = [];

            if (isset($condicion["lista"])) {
                foreach ($condicion["lista"]["palabras"] as $palabra) {
                    $palabras[] = [
                        "id" => $palabra["_source"]["id"],
                        "palabra" => $palabra["_source"]["palabra"]
                    ];
                }
            }

            $result[] = [
                "donde" => $condicion["donde"],
                "campo" => $condicion["campo"],
                "lista_id" => $condicion["lista_id"],
                "operador" => $condicion["operador"],
                "lista_nombre" => isset($condicion["lista"]) ? $condicion["lista"]["nombre"] : '',
                "palabras" => $palabras,
            ];
        }

        return $result;
    }

    /**
     * Construir los resultados de las reglas asociadas
     *
     * @param stdClass $regla  la regla a evaluar
     * @param array    $listas listado de las listas de palabras que se consultaron
     *
     * @return array
     */
    protected function contruirResultadosReglas(array $regla, array $listas)
    {
        $row = [
            'condicion' => [],
            'resultado' => []
        ];

        foreach ($regla["condiciones"] as $condicion) {
            $nombre_lista = '';

            if (isset($condicion["lista"]))
                $nombre_lista = $condicion["lista"]["nombre"];

            if (isset($listas['data']) && !isset($condicion["lista"])) {
                foreach ($listas['data'] as $lista) {
                    if ($lista["id"] == $condicion["lista_id"]) {
                        $nombre_lista = $lista["nombre"];
                        break;
                    }
                }
            }

            $resultado = sprintf(
                "Si en [%s] el campo [%s] es [%s] [%s] entonces [%s]",
                $condicion["donde"],
                $condicion["campo"],
                $condicion["operador"] == self::OPERATOR_EQUAL
                    ? "igual a" : "diferente a",
                $nombre_lista,
                $regla["accion"] == self::ACTION_BLOCK ? "bloquear" : "alertar"
            );

            $row['condicion'][] = $resultado;
            $row['resultado'][] = $resultado;
        }

        return $row;
    }

    /**
     * Retorna el mensaje personalisado de la regla que se cumpla
     *
     * @return string
     */
    public function getMensaje()
    {
        return $this->mensaje;
    }

    /**
     * Retorna el valor del id generado por el registro de la validacion
     *
     * @return string
     */
    public function getIdEdata()
    {
        return $this->id_edata;
    }

    /**
     * Retorna el valor del estado eData
     *
     * @return string
     */
    public function getEdataEstado()
    {
        return $this->edata_estado;
    }

    
    /**
     * Get cuerpo del correo
     *
     * @return string
     */ 
    public function getCorreoCuerpo()
    {
        return $this->correo_cuerpo;
    }

    /**
     * Get cuerpo del correo
     *
     * @return string
     */ 
    public function getCorreoAsunto()
    {
        return $this->correo_asunto;
    }

    /**
     * Get datos del producto
     *
     * @return bool
     */ 
    public function getDatosProducto()
    {
        return $this->datos_producto;
    }
}
