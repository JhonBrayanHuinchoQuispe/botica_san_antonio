<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bienvenido a Botica San Antonio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #D32F2F; 
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .welcome-text {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .credentials-box {
            background-color: #f9f9f9;
            border: 1px dashed #cccccc;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        .credential-item {
            margin: 10px 0;
            font-size: 16px;
        }
        .label {
            font-weight: bold;
            color: #555555;
            margin-right: 10px;
        }
        .value {
            font-family: monospace;
            font-size: 18px;
            color: #D32F2F;
            background-color: #ffffff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #eeeeee;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            background-color: #D32F2F;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
        }
        .footer {
            background-color: #eeeeee;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #777777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Botica San Antonio</h1>
        </div>
        <div class="content">
            <p class="welcome-text">
                Hola <strong>{{ $user->nombres }}</strong>,<br><br>
                ¡Bienvenido al equipo! Se ha creado tu cuenta de usuario en el sistema. A continuación encontrarás tus credenciales de acceso.
            </p>
            
            <div class="credentials-box">
                <div class="credential-item">
                    <span class="label">Usuario (Email):</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="label">Contraseña:</span>
                    <span class="value">{{ $password }}</span>
                </div>
            </div>

            <p class="welcome-text">
                Por seguridad, te recomendamos cambiar tu contraseña después de iniciar sesión por primera vez.
            </p>

            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Iniciar Sesión</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Botica San Antonio. Todos los derechos reservados.<br>
            Este es un correo automático, por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
