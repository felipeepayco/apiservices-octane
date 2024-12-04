<?php

namespace App\Helpers\Messages;

class CommonText{

    public const TITLE = "title";
    public const PRICE = "price";
    public const CHANNEL = "channel";
    public const PROCEED = "procede";
    public const PROGRESS = "progreso";
    public const PARAMETER = "parameter";
    public const COLOR = "color";
    public const DATE = "fecha";
    public const ACTIVE = "activo";
    public const SCRIPT = "script";
    public const ENABLED_ENG = "enabled";
    public const ACTIVE_ENG = "active";
    public const LANDING_IDENTIFIER = "landingIdentifier";
    public const CONTACT_NAME = "contactName";
    public const CONTACT_PHONE = "contactPhone";
    public const DOCUMENT_TYPE = "documentType";
    public const DOCUMENT_NUMBER = "documentNumber";
    public const IMAGEN_PERFIL = "imagen_perfil";
    public const IMAGEN_FONDO = "imagen_fondo";
    public const EMAIL = "email";
    public const FRANCHISE = "franchise";
    public const FIELD = "field";
    public const CLIENT_ID = "cliente_id";
    public const CLIENTID = "clientId";
    public const PRODUCT = "producto";
    public const PRODUCTS = "productos";
    public const STATE = "estado";
    public const BANNERS = "banners";
    public const EMPTY = "empty";
    public const POSICION = "posicion";
    public const TITULO = "titulo";
    public const VALOR = "valor";
    public const FIELDS = "fields";
    public const CATALOGUE = "catalogo";
    public const CATALOGUE_ID = "catalogo_id";
    public const INDEX = "indice";
    public const COMPANY_NAME = "nombre_empresa";
    public const OWNDOMAIN = "dominio_propio";
    public const OWNDOMAINVALUE = "valor_dominio_propio";
    public const OWNSUBDOMAINVALUE = "valor_subdominio_propio";
    public const DELETE_OWNDOMAINVALUE = "eliminado_valor_dominio_propio";
    public const DELETE_OWNSUBDOMAINVALUE = "eliminado_valor_subdominio_propio";
    public const FILES_EXEEDED = 'number of files exceeded';
    public const CATALOGUE_NAME_EXIST = 'Catalogue name already exist';
    public const FORMAT_NOT_ALLOWED = 'file format not allowed';
    public const ORIGIN_EPAYCO = 'epayco';
    public const PLAN_EXCEEDED = 'Plan limit was exceeded';
    public const PLAN_CANCEL = 'su plan debe ser renovado';
    public const TIPO_PLAN = 12;
    public const CATEGORIES = "categorias";
    public const INLINE = "inline";
    public const STRING = "string";
    public const SOCIAL_SELLER_DUPLICATE_VALIDATION = "SOCIAL_SELLER_DUPLICATE_VALIDATION";
    public const CONTACT_PHONE_ES = "telefono_contacto";
    public const PHONE_ES = "telefono";
    public const PHONE = "phone";
    public const CONTACT_EMAIL = "correo_contacto";
    public const WHATSAPP_ACTIVE = "whatsapp_activo";
    public const COUNTRY_CODE = "indicativo_pais";
    public const SPACE_IS_INVALID = " is invalid";
    public const IND_PAIS = "ind_pais";
    public const INTEGER = "integer";
    public const IMAGE_BASE_64 = "imageBase64";
    public const ENTIDAD_ALIADA = "entidad_aliada";
    public const FECHA_CREACION_CLIENTE = "fecha_creacion_cliente";
    public const TRANSACTIONS = "transacciones_rest";
    PUBLIC CONST CURRENCY = "moneda";
    PUBLIC CONST DEFAULT_LANGUAGE = "idioma";
    PUBLIC CONST CURRENCY_ENG = "currency";
    public const ANALYTICS = "analiticas";
    public const ANALYTICS_ENG = "analytics";
    public const IVA = "IVA";
    public const BBL_SUBSCRIPTIONS = "bbl_suscripciones";

    ///RESPONSE
    public const SUCCESS = 'success';
    public const TRUE = true;
    public const FALSE = false;
    public const ERROR = 'Error';
    public const ERRORS = 'errors';
    public const COD_ERROR = 'codError';
    public const DATA = 'data';
    public const ERROR_MESSAGE = 'errorMessage';
    public const TITLE_RESPONSE = 'titleResponse';
    public const TEXT_RESPONSE = 'textResponse';
    public const LAST_ACTION = 'lastAction';
    public const COD_AE100 = 'AE100';
    public const TOTAL_ERRORS = 'totalerrors';
    public const FETCH_DATABASE = 'fetch data from database';

    ///DNS
    public const SUBDOMAIN_NOT_AVAILABLE = 'Subdomain not available';
    PUBLIC CONST CREATE_SUBDOMAIN = 'create subdomain';
    PUBLIC CONST DOMAIN = 'domain';
    PUBLIC CONST SUBDOMAIN_CREATE_CORRECT = 'Subdomain create correct';
    PUBLIC CONST SUBDOMAIN_COULD_NOT_BE_CREATE = 'Subdomain could not be created';
    PUBLIC CONST ERROR_CONSULT_SUB_PARAMETERS = 'Error inesperado al crear subdomain con los parametros datos';
    const COUNTRY_CODE_CO = 'CO';
    const COUNTRY_CODE_CO_ID = 1;
    const CONF_CLIENTES_COUNTRY_CONFIG_ID=54;
    PUBLIC CONST CONF_CLIENTES_CURRENCY = 15;

    PUBLIC CONST COP_CURRENCY_CODE = "COP";
    PUBLIC CONST STRING_CURRENCY_CODES = [
        self::COP_CURRENCY_CODE,
        "USD"
    ];

    //proveedores de envios
    PUBLIC const DEFAULT_LAST_TRANSACTION_SHOPPINGCART_STATUS = "No Aplica";
    PUBLIC const DEFAULT_SHOPPINGCART_STATUS_DELIVERY = "No Aplica";
    PUBLIC const UPDATE_STATE_DELIVERY_SHOPPINGCART ="update state delivery shoppingcart";
    PUBLIC const PROVIDER_DELIVERY = "proveedor_envios";
    PUBLIC const EPAYCO_LOGISTIC = "epayco_logistica";
    PUBLIC const SENDER_TYPE = "tipo_remitente";
    PUBLIC const SENDER_FIRSTNAME = "nombre_remitente";
    PUBLIC const SENDER_LASTNAME = "apellido_remitente";
    PUBLIC const SENDER_DOC_TYPE = "tipo_documento_remitente";
    PUBLIC const SENDER_DOC = "documento_remitente";
    PUBLIC const SENDER_PHONE = "telefono_remitente";
    PUBLIC const SENDER_BUSINESS = "razon_social_remitente";
    PUBLIC const EPAYCO_DELIVERY_PROVIDER_VALUES = "lista_proveedores";
    PUBLIC const PICKUP_CITY = "ciudad_recogida";
    PUBLIC const PICKUP_DEPARTAMENT = "departamento_recogida";
    PUBLIC const PICKUP_ADDRESS = "direccion_recogida";
    PUBLIC const PICKUP_CONFIGURATION_ID = "configuracion_recogida_id";
    PUBLIC const AUTOMATIC_PICKUP = "recogida_automatica";
    PUBLIC const FREE_DELIVERY = "envio_gratis";
    PUBLIC CONST REAL_WEIGHT = "peso_real";
    PUBLIC CONST HIGH = "alto";        
    PUBLIC CONST LONG = "largo";
    PUBLIC CONST WIDTH = "ancho"; 
    PUBLIC CONST DECLARED_VALUE = "valor_declarado";
    PUBLIC CONST QUOTE = "cotizacion";
    PUBLIC CONST QUOTE_EN = "quote";
    PUBLIC CONST CODEDANE = "codigo_dane";
    PUBLIC CONST CODEDANE_EN = "codeDane";
    PUBLIC CONST MADELLIN = "Medellin";
    public CONST PATH_TEXT_CODE = "/printqr?txtcodigo=";


    public const INTENTS_CERTIFICATION = "intentos_certificacion";
    public const NEXT_ATTEMPT = "proximo_inteto";
    public const POSSESSES_CERTIFICATE = "posee_certificado";

}

