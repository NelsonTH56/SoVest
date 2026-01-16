<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Mode - SoVest</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .error-container {
            text-align: center;
            color: #fff;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0;
        }
        .error-title {
            font-size: 1.5rem;
            color: #a0aec0;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #718096;
            margin-bottom: 2rem;
            max-width: 400px;
        }
        .logo {
            width: 60px;
            margin-bottom: 2rem;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <img src="{{ asset('images/logo.png') }}" alt="SoVest Logo" class="logo">
        <h1 class="error-code">503</h1>
        <h2 class="error-title">Under Maintenance</h2>
        <p class="error-message">
            We're currently performing scheduled maintenance to improve your experience.
            We'll be back shortly!
        </p>
        <div class="spinner"></div>
    </div>
</body>
</html>
