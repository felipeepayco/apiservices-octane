<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Comprobante de pago</title>
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
            width: 17cm;
            margin: 0 auto;
            color: #001028;
            background: #FFFFFF;
            font-family: "Roboto Condensed",Roboto,Arial,sans-serif;
            font-size: 15px;
        }

        h1 {
            color: #dd141d;
            font-size: 21px;
            font-weight: bold;
            text-align: center;
        }

        .imgFogotin {
            width: 150px;
        }

        .imgDavivienda {
            width: 120px;
        }

        .imgVigilado {
            width: 13px;
            margin-top: 20px;
            float: left;
            margin-left: -50px;
        }

        .thImageDavivienda {
            text-align: initial;
            width: 13cm
        }

        .thImageFogatin {
            width: 3cm
        }

        .value {
            padding: 0;
        }

        .tbl-content {
            width: 17cm;
            border-collapse: collapse;
        }

        .tbl-content th {
            width: 50%;
            padding: 0;
        }

        .tbl-content td {
            padding: 0;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        hr {
            border: 0.01cm red solid;
        }

        * {
            box-sizing: border-box;
        }

        .column {
            float: left;
        }

        .column_zero {
            width: 11.3cm;
        }

        footer {
            width: 100%;
            bottom: 50px;
            text-align: center;
            position: fixed;
            font-weight: lighter;
        }

        footer p {
            font-size: 10px;
            margin-top: -12px;
        }
    </style>
</head>
<body>
<div>
    <table class="tbl-title">
        <thead>
        <tr>
            <th class="thImageDavivienda" scope="col">
                <img alt="davivienda " class="imgDavivienda" src="https://multimedia-epayco.s3.amazonaws.com/vende/vende-davivienda/logo_daviplata.png" />
            </th>
            <th class="thImageFogatin" scope="col">
                <img alt="img fogafin" class="imgFogotin" src="https://multimedia-epayco.s3.amazonaws.com/vende/vende-davivienda/logo_fogafin.jpg" />
            </th>
        </tr>
        </thead>
    </table>
    <div class="column column_zero">
        <p>Cliente:&nbsp; <strong>{{ $paymentReceiptData['businessName'] }}</strong></p>
    </div>
    <div class="column column_first" >
        <p>Fecha transacción: {{ $paymentReceiptData['transactionDate'] }}</p>
    </div>
</div>

<main>
    <h1>COMPRA CON DAVIPLATA</h1>
    <hr />
    <table class="tbl-content">
        <thead>
        <tr>
            <th scope="col" class="left">Resultado de la transacción:</th>
            <th scope="col" class="right">{{ $paymentReceiptData['transactionState'] }} </th>
        </tr>
        </thead>
        <tbody>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Destino de pago:</td>
                <td class="right">{{ $paymentReceiptData['businessName'] }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;No. de DaviPlata destino:</td>
                <td class="right">{{ $paymentReceiptData['clientMobileNumber'] }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Motivo:</td>
                <td class="right">{{ $paymentReceiptData['reason'] }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;No. de aprobación:</td>
                <td class="right">{{ $paymentReceiptData['authorizationNumber'] }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Dirección IP:</td>
                <td class="right">{{ $paymentReceiptData['ip'] }}</td>
            </tr>
        </tbody>
    </table>
    <table class="tbl-content">
        <thead>
        <tr>
            <th scope="col" class="left">Datos de compra</th>
            <th scope="col" class="right"></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($paymentReceiptData['products'] as $payment)
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Productos:</td>
            <td class="right"><strong>{{ $payment['title'] }}</strong></td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cantidad:</td>
            <td class="right">{{ $payment['quantity'] }}</td>
        </tr>
        <tr>
            <td class="value">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Valor:</td>
            <td class="right value">$ {{ $payment['amount'] }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <table class="tbl-content">
        <thead>
        <tr>
            <th scope="col" class="left">Datos de envío</th>
            <th scope="col" class="right"></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nombre completo:</td>
            <td class="right"><strong>{{ $paymentReceiptData['shippingName'] }}</strong></td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Teléfono de contacto:</td>
            <td class="right">{{ $paymentReceiptData['shippingPhone'] }}</td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Dirección de entrega:</td>
            <td class="right">{{ $paymentReceiptData['shippingAddress'] }}</td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ciudad:</td>
            <td class="right">{{ $paymentReceiptData['shippingCity'] }}</td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Valor envío:</td>
            <td class="right">$ {{ $paymentReceiptData['shippingAmount'] }}</td>
        </tr>
        </tbody>
    </table>
    <table class="tbl-content">
        <thead>
        <tr>
            <th scope="col" class="left">Valor transacción:</th>
            <th scope="col" class="right">$ {{ $paymentReceiptData['transactionAmont'] }}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Costo transacción:</td>
            <td class="right">$ {{ $paymentReceiptData['transactionCost'] }} Iva incluido</td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Referencia 1:</td>
            <td class="right">{{ $paymentReceiptData['daviplataOrigin'] }}</td>
        </tr>
        </tbody>
    </table>
    <hr />
</main>
<img class="imgVigilado" alt="vigilado" src="https://multimedia-epayco.s3.amazonaws.com/vende/vende-davivienda/vigilado-sfc-B.png" />
<footer>
    <p>DaviPlata es un depósito de dinero electrónico amparado por el seguro de depósito Fogafín. Consulte condiciones, reglamento, tarifas y más información en: <a class="colorRed">www.daviplata.com</a>.Para cualquier diferencia con el saldo, comuníquese a través del botón ¿Necesita Ayuda? de la aplicación o al #688. Si requiere, puede comunicarse con nuestro Defensor del Consumidor Financiero: Carlos Mario Serna, Calle 72 No. 6-30, piso 18, Bogotá, teléfono 609-2013, fax 482-9715, correo electrónico: defensordelcliente@davivienda.com.</p>
    <p>Revisoría fiscal: KPMG Ltda. Apartado 77859, Bogotá.</p>
</footer>
<script type="text/php">
    if (isset($pdf)) {
        $x = 580;
        $y = 220;
        $text = "Banco Davivienda S.A. NIT. 860.034.313-7";
        $font = null;
        $size = 8;
        $color = array(0,0,0);
        $word_space = 0.0;
        $char_space = 0.0;
        $angle = 270.0;
        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
    }
</script>
</body></html>
