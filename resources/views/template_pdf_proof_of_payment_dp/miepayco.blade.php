<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Mi ePayco - Comprobante de pago</title>
    
    <style type="text/css">
        @import url('https://fonts.cdnfonts.com/css/segoe-ui-4');
        @import url('https://fonts.googleapis.com/css2?family=Kanit');
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300');
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@500');
        @import url('https://fonts.googleapis.com/css2?family=Open+Sans');
        @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300');
        @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@600');

        body {
            color: #5C5B5C;
        }
        header {
            text-align: center;
        }
        header .title-image {
            font-family: 'Segoe UI',sans-serif;
            font-weight: 400;
            font-size: 12px;
            color: #080035;
        }
        main {
            margin-top: 25px; 
            padding: 50px;
        }
        .green-background {
            position: absolute;
            background: #67C940;
            border-radius: 10px 10px 1px 1px;
            height: 150px;
            width: 100vw;
            top: 90px;
            z-index: -9999;
        }
        .card {
            background-color: #FFFFFF;
            border: 1px solid #D3D3D3;
            box-sizing: border-box;
            border-radius: 10px;
        }
        .details {
            margin-bottom: 20px;
            padding: 50px 25px;
            text-align: center;
        }
        .details .sender {
            font-family: 'Kanit',sans-serif;
            font-size: 20px;
            text-align: center;
            color: #23272B;
            margin-bottom: 8px;
        }
        .details .requester {
            font-family: 'Open Sans',sans-serif;
            font-weight: 300;
            font-size: 16px;
            text-align: center;
            color: #23272B;
            margin-bottom: 12px;
        }
        .details .requester strong {
            font-family: 'Open Sans',sans-serif;
            font-weight: 600;
        }
        .details .state {
            font-family: 'Kanit',sans-serif;
            font-weight: 300;
            font-size: 20px;
            text-align: center;
            color: #67C940;
        }
        .details .state strong {
            font-family: 'Kanit',sans-serif;
            font-weight: 500;
        }
        .transaction-details {
            padding: 25px;
        }
        .transaction-details .title {
            font-family: 'Kanit',sans-serif;
            font-size: 20px;
            text-align: center;
            color: #23272B;
            margin-bottom: 10px;
        }
        .transaction-details table {
            font-family: 'Open Sans',sans-serif;
            font-size: 16px;
            width: 100%;
        }
        .transaction-details table th {
            font-weight: 600;
            width: 30%; 
            text-align: left; 
            color: #23272B;
        }
        .transaction-details table tr {
            font-weight: 300;
            width: 70%; 
            text-align: right; 
        }
        footer {
            width: 100vw;
            position: absolute;
            bottom: 0;
        }
        footer div {
            font-family: 'Open Sans',sans-serif;
            font-weight: 300;
            font-size: 16px;
            justify-content: center;
            text-align: center;
        }
        footer div strong {
            font-family: 'Open Sans',sans-serif;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header>
        <div class="title-image">Transacción procesada con:</div>
        <img alt="ePayco" src="https://multimedia-epayco-test.s3.amazonaws.com/panel_app_rest/vende/mi_epayco_template/group.png"/>
    </header>
    <div class="green-background"></div>
    <main>
        <div>
            <div class="card details">
                <div class="sender">Hola, {{ $paymentReceiptData['name_sender'] }}</div>
                <div class="requester">Ha realizado una transacción a favor de <strong>{{ $paymentReceiptData['name_requester'] }}</strong></div>
                <div class="state">TRANSACCIÓN ACEPTADA <strong>${{ $paymentReceiptData['total'] }} COP </strong></div>
            </div>
            <div class="card transaction-details" >
                <div class="title">Detalle de la transacción</div>
                <table aria-describedby="tabla de detalles de la transacción">
                    <tbody>
                        <tr>
                            <th id="01">Descripción</th>
                            <td>{{ $paymentReceiptData['product_description'] }}</td>
                        </tr>
                        <tr>
                            <th id="02">Referencia ePayco</th>
                            <td>{{ $paymentReceiptData['epayco_reference'] }}</td>
                        </tr>
                        <tr>
                            <th id="03">Referencia Comercio</th>
                            <td>{{ $paymentReceiptData['trade_reference'] }}</td>
                        </tr>
                        <tr>
                            <th id="04">Fecha y hora</th>
                            <td>{{ $paymentReceiptData['transactionDate'] }}</td>
                        </tr>
                        <tr>
                            <th id="05">Medio de pago</th>
                            <td>{{ $paymentReceiptData['payment_method'] }}</td>
                        </tr>
                        <tr>
                            <th id="06">Banco</th>
                            <td>{{ $paymentReceiptData['bank'] }}</td>
                        </tr>
                        <tr>
                            <th id="07">Estado</th>
                            <td>{{ $paymentReceiptData['state'] }}</td>
                        </tr>
                        <tr>
                            <th id="08">Valor</th>
                            <td>${{ $paymentReceiptData['value'] }} COP</td>
                        </tr>
                        <tr>
                            <th id="09">IVA</th>
                            <td>${{ $paymentReceiptData['iva'] }} COP</td>
                        </tr>
                        <tr>
                            <th id="10">Total</th>
                            <td>${{ $paymentReceiptData['total'] }} COP</td>
                        </tr>
                        <tr>
                            <th id="12">Nro Recibo</th>
                            <td>{{ $paymentReceiptData['receipt_number'] }}</td>
                        </tr>
                        <tr>
                            <th id="13">Autorización / CUS</th>
                            <td>{{ $paymentReceiptData['autorizacion_cus'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <footer>
        <div>
            Hemos enviado un correo de confirmación a 
            <strong>{{ $paymentReceiptData['email_sender'] }}</strong>
            en el extrato de facturación la compra se verá reflejada a nombre de
            <strong>Payco, Paga y Cobra Online S.A.S</strong>
        </div>
    </footer>
</body>
</html>