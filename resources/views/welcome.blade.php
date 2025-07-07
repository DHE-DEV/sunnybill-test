<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SunnyBill</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                text-align: center;
                padding: 2rem;
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                max-width: 500px;
                width: 90%;
            }
            .logo {
                font-size: 3rem;
                font-weight: bold;
                color: #333;
                margin-bottom: 1rem;
            }
            .login-btn {
                display: inline-block;
                padding: 15px 30px;
                background: #f53003;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-size: 1.2rem;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(245, 48, 3, 0.3);
            }
            .login-btn:hover {
                background: #d42a02;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(245, 48, 3, 0.4);
            }
            .description {
                margin-top: 1.5rem;
                color: #666;
                font-size: 1rem;
                line-height: 1.5;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">VoltMaster</div>
            <a href="https://sunnybill-test.chargedata.eu/admin" class="login-btn">
                VoltMaster Login
            </a>
            <p class="description">
                Klicken Sie hier, um sich in das VoltMaster Admin-Panel einzuloggen
            </p>
        </div>
    </body>
</html>
