<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') - Botica San Antonio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            margin: 0; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
            color: #1f2937;
            overflow-x: hidden;
        }
        
        
        .animated-bg {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
            overflow: hidden;
            z-index: 0;
        }
        
        .bubble {
            position: absolute;
            bottom: -100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: rise 15s infinite ease-in;
            backdrop-filter: blur(5px);
        }
        
        .bubble:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .bubble:nth-child(2) { width: 60px; height: 60px; left: 20%; animation-delay: 2s; animation-duration: 12s; }
        .bubble:nth-child(3) { width: 100px; height: 100px; left: 35%; animation-delay: 4s; }
        .bubble:nth-child(4) { width: 70px; height: 70px; left: 50%; animation-delay: 0s; animation-duration: 18s; }
        .bubble:nth-child(5) { width: 90px; height: 90px; left: 65%; animation-delay: 3s; }
        .bubble:nth-child(6) { width: 75px; height: 75px; left: 80%; animation-delay: 1s; animation-duration: 14s; }
        .bubble:nth-child(7) { width: 85px; height: 85px; left: 90%; animation-delay: 5s; }
        
        @keyframes rise {
            0% {
                bottom: -100px;
                transform: translateX(0) scale(1);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                bottom: 110vh;
                transform: translateX(100px) scale(1.2);
                opacity: 0;
            }
        }
        
        .container { 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 24px; 
            position: relative; 
            z-index: 1; 
        }
        
        .card { 
            max-width: 600px; 
            width: 100%; 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px; 
            padding: 48px 40px; 
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .illust { 
            margin: 0 auto 24px; 
            width: 140px; 
            height: 140px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .code-big { 
            font-size: 72px; 
            font-weight: 800; 
            letter-spacing: -2px; 
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 16px 0 12px;
            line-height: 1;
        }
        
        h1 { 
            margin: 0 0 12px; 
            font-size: 28px; 
            font-weight: 700;
            color: #1f2937;
            line-height: 1.3;
        }
        
        p { 
            margin: 8px 0 0; 
            font-size: 16px; 
            color: #6b7280;
            line-height: 1.6;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .meta-info {
            margin-top: 20px;
            padding: 16px;
            background: rgba(59, 130, 246, 0.08);
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.15);
        }
        
        .meta-info p {
            margin: 4px 0;
            font-size: 14px;
            color: #4b5563;
        }
        
        .meta-info strong {
            color: #3b82f6;
            font-weight: 600;
        }
        
        .actions { 
            margin-top: 32px; 
            display: flex; 
            gap: 12px; 
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn { 
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
            color: #fff; 
            border: none; 
            border-radius: 12px; 
            padding: 14px 28px; 
            font-weight: 600;
            font-size: 15px;
            cursor: pointer; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: white;
            color: #3b82f6;
            border: 2px solid #3b82f6;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15);
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.25);
        }
        
        .footer { 
            margin-top: 32px; 
            padding-top: 24px;
            border-top: 1px solid rgba(107, 114, 128, 0.15);
            font-size: 13px; 
            color: #9ca3af;
        }
        
        .footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        
        @media (max-width: 640px) {
            .card {
                padding: 36px 24px;
            }
            
            .code-big {
                font-size: 56px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            p {
                font-size: 15px;
            }
            
            .btn {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
    @yield('head')
</head>
<body>
    <div class="animated-bg">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="illust">
                @yield('illustration')
            </div>
            <div class="code-big">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            
            @hasSection('meta-info')
                <div class="meta-info">
                    @yield('meta-info')
                </div>
            @endif
            
            <div class="actions">
                @yield('actions')
            </div>
            
            @hasSection('footer')
                <div class="footer">
                    @yield('footer')
                </div>
            @endif
        </div>
    </div>
</body>
</html>