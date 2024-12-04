<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Extracto de movimientos Daviplata</title>
    <style type="text/css">
        @font-face {
            font-family: 'Roboto Condensed Light';
            font-style: normal;
            font-weight: normal;
            src: local('Roboto Condensed Light'), local('RobotoCondensed-Light'), url(http://themes.googleusercontent.com/static/fonts/robotocondensed/v7/b9QBgL0iMZfDSpmcXcE8nL3QFSXBldIn45k5A7iXhnc.ttf) format('truetype');
        }
        @font-face {
            font-family: 'Roboto Condensed';
            font-style: normal;
            font-weight: normal;
            src: local('Roboto Condensed Regular'), local('RobotoCondensed-Regular'), url(http://themes.googleusercontent.com/static/fonts/robotocondensed/v7/Zd2E9abXLFGSr9G3YK2MsDR-eWpsHSw83BRsAQElGgc.ttf) format('truetype');
        }
        @font-face {
            font-family: 'Roboto Condensed';
            font-style: normal;
            font-weight: bold;
            src: local('Roboto Condensed Bold'), local('RobotoCondensed-Bold'), url(http://themes.googleusercontent.com/static/fonts/robotocondensed/v7/b9QBgL0iMZfDSpmcXcE8nDokq8qT6AIiNJ07Vf_NrVA.ttf) format('truetype');
        }

        body {
            width: 27cm;
            margin: 0 auto;
            color: #001028;
            background: #FFFFFF;
            font-family: "Roboto Condensed",Roboto,Arial,sans-serif;
            font-size: 12px;
        }

        h1 {
            color: #dd141d;
            font-size: 17px;
            font-weight: bold;
            text-align: center;
        }

        .imgFogotin {
            width: 140px;
        }

        .imgDaviplata {
            width: 120px;
        }

        .textAlignInitial {
            text-align: initial;
        }

        .thHeaderTitle {
            text-align: center;
        }

        .titleHeader {
            color: #dd141d;
            font-size: 17px;
            width: 20cm;
        }

        .thTitleP {
            margin-top: -28px;
            margin-left: -120px;
            font-size: 17px;
        }

        .tbl-title {
            width: 27.3cm;
            margin-top: -30px;
            border-bottom:  0.01cm solid;
        }

        .tbl-content {
            width: 27.3cm;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        .tbl-content th:nth-child(2) {
            width: 200px;
        }
        .tbl-content th:nth-child(3) {
            width: 50px;
        }
        .tbl-content th:nth-child(9), .tbl-content th:nth-child(10) {
            width: 120px;
        }

        .tbl-content th:nth-child(11) {
            width: 80px;
        }

        .tbl-content tbody {
            font-size: 8px;
        }

        .tbl-content th {
            font-size: 10px;
            padding: 5px;
            color: #FFFFFF;
            background-color: #dd141d;
            border-right: 1px white solid;
        }

        .tbl-content td {
            padding: 3px;
            border-right: 1px solid;
            border-bottom:  1px #80808066 solid;
            text-align: center;
        }


        .tbl-content td:nth-child(2) {
            text-align: left;
            padding: 3px;
        }

        .tbl-content td:nth-child(11) {
            text-align: left;
            padding: 3px;
            border-bottom:  1px #80808066 solid;
            border-right: 1px white solid;
        }

        .tbl-information tr th {
            background-color: #FFFFFF;
            color: black;
            font-size: 12px;
        }

        .tbl-information th:nth-child(1) {
            text-align: initial;
            width: 7cm;
            padding: 0;
        }

        .tbl-information th:nth-child(2) {
            text-align: initial;
            width: 10cm;
        }

        .tbl-information th:nth-child(3) {
            text-align: end;
            width: 3cm;
        }

        .tbl-information th:nth-child(4) {
            text-align: end;
            width: 0.1cm;
            padding: 0;
        }

        * {
            box-sizing: border-box;
        }

        .column {
            float: left;
        }
        .column_first {
            width: 60%;
        }

        .column p {
            margin-bottom: -16px;
        }

        .date {
            width: 0.1cm
        }

        .pBold{
            font-weight:bold;
        }
    </style>
</head>
<body>
<div>
    <table class="tbl-title">
        <thead>
        <tr>
            <th scope="col" class="textAlignInitial">
                <img class="imgDaviplata" alt="Logo Daviplata" src="https://multimedia-epayco.s3.amazonaws.com/vende/vende-davivienda/logo_daviplata.png" />
            </th>
            <th scope="col" class="thHeaderTitle">
                <p class="titleHeader">DAVIPLATA {{ $extractData['businessName'] }} / MOVIMIENTO MENSUAL DE VENTAS <br/> <p class="thTitleP">{{ $extractData['clientMobileNumber'] }}</p><p/>
            </th>
        </tr>
        </thead>
    </table>
    <div class="column column_zero">
        <p>Negocio:</p>
        <p>Tipo y No. identificación del poseedor de DaviPlata:</p>
        <p>Correo electrónico del poseedor de DaviPlata:</p>
    </div>
    <div class="column column_first">
        <p class="pBold">&nbsp;&nbsp;&nbsp;{{ $extractData['businessName'] }}</p>
        <p>&nbsp;&nbsp;&nbsp;{{ $extractData['docType'] }} - {{ $extractData['document'] }}</p>
        <p>&nbsp;&nbsp;&nbsp;{{ $extractData['email'] }}</p>
    </div>
    <div class="column column_second">
        <p>Mes: </p>
        <p>Total ingresos: </p>
        <p>Total descuentos: </p>
    </div>
    <div class="column column_three" >
        <p class="pBold">&nbsp;&nbsp;&nbsp;{{ $extractData['extractMonth'] }}</p>
        <p class="pBold">&nbsp;&nbsp;&nbsp;$ {{ $extractData['totalRevenues'] }}</p>
        <p class="pBold">&nbsp;&nbsp;&nbsp;$ {{ $extractData['totalDiscounts'] }}</p>
    </div>
</div>

<main>
    <h1>TRANSACCIONES</h1>
    <table class="tbl-content">
        <thead>
        <tr>
            <th scope="col" class="date">FECHA (DÍA/MES)</th>
            <th scope="col">DETALLE PRODUCTOS VENDIDOS Y CANTIDAD </th>
            <th scope="col">VALOR PRODUCTOS VENDIDOS</th>
            <th scope="col">VALOR ENVÍO</th>
            <th scope="col">VALOR COMISIÓN</th>
            <th scope="col">VALOR ABONO</th>
            <th scope="col">No. AUTORIZAC.</th>
            <th scope="col">MEDIO DE PAGO</th>
            <th scope="col">DATOS DEL COMPRADOR (NOMBRE Y TELÉFONO)</th>
            <th scope="col">DATOS DE ENVÍO (DIRECCIÓN Y CIUDAD DEL COMPRADOR)</th>
            <th scope="col">CANAL ORIGEN DEL PAGO</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($extractData['salesMovements'] as $sales)
            <tr>
                <td>{{ substr(str_replace(' ', '', $sales['paymentDate']),0, 7) }}</td>
                <td class="td-left">{{ $sales['productDetails'] }}</td>
                <td>
                    $ {{ $sales['totalProductsAmount']}}
                    @if ($sales['totalProductsAmount'] !== '0.00') + @endif
                </td>
                <td>
                    $ {{ $sales['shippingAmount'] }}
                    @if ($sales['shippingAmount'] !== '0.00') + @endif
                </td>
                <td>
                    $ {{ $sales['comissionAmount'] }}
                    @if ($sales['comissionAmount'] !== '0.00') - @endif
                </td>
                <td>
                    $ {{ $sales['creditAmount'] }}
                    @if ($sales['creditAmount'] !== '0.00') + @endif
                </td>
                <td>{{ $sales['authorizationNumber'] }}</td>
                <td>{{ $sales['paymentMethod'] }}</td>
                <td>{{ $sales['buyerName'] }} <br /> {{ $sales['buyerPhone'] }}</td>
                <td>{{ $sales['shippingAddress'] }} {{ $sales['shippingCity'] }}</td>
                <td class="td-left">{{ $sales['paymentChannel'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <script type="text/php">
    if (isset($pdf)) {
        $x = 750;
        $y = 5;
        $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
        $font = null;
        $size = 10;
        $color = array(0,0,0);
        $word_space = 0.0;
        $char_space = 0.0;
        $angle = 0.0;
        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);

        $x = 90;
        $y = 571;
        $text = "La información que contiene este documento es entregada por Payco, Paga y Cobra Online S.A.S., quien consolida los movimientos de las ventas realizadas en sus catálogos. Mayor información en el botón: ¿Necesita ayuda?";
        $font = null;
        $size = 7.5;
        $color = array(0,0,0);
        $word_space = 0.0;
        $char_space = 0.0;
        $angle = 0.0;
        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);

        $x = 830;
        $y = 195;
        $text = "Banco Davivienda S.A. NIT. 860.034.313-7";
        $font = null;
        $size = 7.5;
        $color = array(0,0,0);
        $word_space = 0.0;
        $char_space = 0.0;
        $angle = 270.0;
        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
    }
</script>
</main></body></html>
