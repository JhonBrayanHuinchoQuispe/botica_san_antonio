<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña - Botica San Antonio</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 40px 30px;
        }
        .icon {
            text-align: center;
            margin-bottom: 20px;
        }
        .icon i {
            font-size: 48px;
            color: #dc3545;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
        }
        .message {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .info-box p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .footer p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        .security-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .security-notice p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .link-fallback {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            word-break: break-all;
        }
        .link-fallback p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        .link-fallback a {
            color: #dc3545;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px; font-weight: bold; color: white;">Botica San Antonio</h1>
        </div>

        <div class="content">
            <h2>Recuperación de Contraseña</h2>
            
            <div class="message">
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
            </div>

            <div class="button-container">
                <a href="{{ $actionUrl }}" class="reset-button" style="display: inline-block; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 18px 35px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 18px; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);">
                    🔐 Restablecer mi Contraseña
                </a>
            </div>

            <div class="info-box">
                <p><strong>⏰ Tiempo límite:</strong> Este enlace expirará en {{ config('auth.passwords.users.expire') }} minutos por seguridad.</p>
            </div>

            <div class="security-notice">
                <p><strong>🛡️ Aviso de Seguridad:</strong> Si no solicitaste este restablecimiento de contraseña, puedes ignorar este correo de forma segura. Tu contraseña actual permanecerá sin cambios.</p>
            </div>

            <div class="link-fallback">
                <p><strong>¿No funciona el botón?</strong> Copia y pega este enlace en tu navegador:</p>
                <a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Botica San Antonio - Sistema de Administración</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>