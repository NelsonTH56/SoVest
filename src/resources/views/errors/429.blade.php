<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Too Many Requests - SoVest</title>
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
            background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
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
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: #fff;
        }
        .logo {
            width: 60px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <img src="{{ asset('images/logo.png') }}" alt="SoVest Logo" class="logo">
        <h1 class="error-code">429</h1>
        <h2 class="error-title">Too Many Requests</h2>
        <p class="error-message">
            You've made too many requests in a short period. Please wait a moment and try again.
        </p>
        <a href="{{ url('/') }}" class="btn-home">Back to Home</a>
    </div>
</body>
</html>
