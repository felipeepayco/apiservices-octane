<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PayPal</title>

    <script src='https://www.paypalobjects.com/js/external/connect/api.js'></script>
    <script>
        const host = window.location.host;

        paypal.use(['login'], function (login) {
            login.render({
                "appid": "AUzsKDLrRwvM4qyJpQE_WZ4JASuC3c3-H9tWyBp1d-xgNtPhs2iOfMpYwooMeqXJQwH7uenehAPaUKyd",
                //"appid": "AWepK2X73tY83HufVN5hlz405Cj--amWehizH4buCmIuTL9YJUlYRX00uXA_cYQtYC_eVohAndQj7Rea",
                "authend": "sandbox",
                "scopes": "openid profile email address https://uri.paypal.com/services/paypalattributes https://uri.paypal.com/services/wallet/balance-accounts/read https://uri.paypal.com/transfers/withdrawals https://uri.paypal.com/transfers/deposits",
                "containerid": "lippButton",
                "responseType": "code",
                "locale": "es-es",
                "buttonType": "CWP",
                "buttonShape": "pill",
                "buttonSize": "lg",
                "fullPage": "false",
                "returnurl": "https://127.0.0.1:8000/api/paypal/login/return"
            });
        });
    </script>
</head>
<body>

<span id='lippButton'></span>

</body>
</html>