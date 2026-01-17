<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Success - User Input</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            width: 100%;
        }
        .success-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 24px;
            animation: scaleIn 0.5s ease-out;
        }
        .success-icon svg {
            width: 48px;
            height: 48px;
            stroke: white;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }
        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        h1 {
            color: #1f2937;
            font-size: 28px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .message {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .user-input {
            background: #f3f4f6;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
            text-align: left;
        }
        .user-input-label {
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .user-input-text {
            color: #1f2937;
            font-size: 18px;
            word-wrap: break-word;
            line-height: 1.5;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #4b5563;
        }
        .btn-secondary:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }
        @media (max-width: 640px) {
            .success-card {
                padding: 30px 20px;
            }
            h1 {
                font-size: 24px;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <svg viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            
            <h1>Success!</h1>
            
            <p class="message">
                Your input has been successfully saved to the database.
            </p>
            
            @if(isset($userInput) && $userInput)
            <div class="user-input">
                <div class="user-input-label">Your Submitted Text:</div>
                <div class="user-input-text">{{ $userInput->user_input }}</div>
            </div>
            @endif
            
            <div class="actions">
                <a href="{{ route('user-inputs.create') }}" class="btn btn-primary">
                    Submit Another
                </a>
                <a href="/" class="btn btn-secondary">
                    Go to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>