<?php

namespace App\Http\Controllers;

use App\Helpers\Pago\HelperPago;
use Illuminate\Http\Request;


class ApiProviderDeliveryController extends HelperPago
{

    public function listProviders(Request $request)
    {
      return $this->elogisticaRequest(null, "/api/v1/operadores", 0,"GET");
    }

    public function listDepartaments(Request $request)
    {
      return $this->elogisticaRequest(null, "/api/v1/departamentos", 0,"GET");
    }

    public function listCities(Request $request)
    {
      return $this->elogisticaRequest(null, "/api/v1/ciudades", 0,"GET");
    }

    public function quote(Request $request)
    {
      $data = $request->request->all();
      return $this->elogisticaRequest($data, "/api/v1/cotizacion", 0, "POST", false);
    }

    public function guide(Request $request)
    {
      $data = $request->request->all();
      return $this->elogisticaRequest($data, "/api/v1/guia", 0, "POST", false);
    }

    public function pickup(Request $request)
    {
      $data = $request->request->all();
      return $this->elogisticaRequest($data, "/api/v1/recogida", 0, "POST", false);
    }

    public function createConfiguration(Request $request)
    {
      $data = $request->request->all();
      return $this->elogisticaRequest($data, "/api/v1/configuracion");
    }

    public function updateConfiguration(Request $request)
    {
      $data = $request->request->all();
      return $this->elogisticaRequest($data, "/api/v1/configuracion",0,"PUT");
    }
  }