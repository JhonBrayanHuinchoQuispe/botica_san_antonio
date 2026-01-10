<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Recuperación - Botica San Antonio</title>
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
            padding: 24px 30px; 
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
        .code-container {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border: 2px dashed #dc3545;
        }
        .code-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }
        .verification-code {
            font-size: 36px;
            font-weight: bold;
            color: #dc3545;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
        .code-instructions {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
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
        .steps {
            background-color: #e3f2fd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps h3 {
            color: #1976d2;
            margin-top: 0;
            font-size: 16px;
        }
        .steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .steps li {
            color: #666;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px; font-weight: bold; color: white;">Botica San Antonio</h1>
        </div>

        <div class="content">
            <h2>🔐 Código de Recuperación de Contraseña</h2>
            
            <div class="message">
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
            </div>

            <div class="code-container">
                <div class="code-label">Tu código de verificación</div>
                <div class="verification-code">{{ $code }}</div>
                <div class="code-instructions">Ingresa este código en el formulario de recuperación</div>
            </div>

            

            <div class="info-box">
                <p><strong>⏰ Tiempo límite:</strong> Este código expirará en {{ $expiresInMinutes }} minutos por seguridad.</p>
            </div>

            <div class="security-notice">
                <p><strong>🛡️ Aviso de Seguridad:</strong> Si no solicitaste este código de recuperación, puedes ignorar este correo de forma segura. Tu contraseña actual permanecerá sin cambios.</p>
            </div>

            
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Botica San Antonio - Sistema de Administración</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>