<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>CMS Backend API</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .container {
                text-align: center;
            }
            .card {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border-radius: 15px;
                padding: 3rem 4rem;
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.18);
                transform: translateY(0);
                transition: transform 0.3s ease-in-out;
            }
            .card:hover {
                transform: translateY(-10px);
            }
            h1 {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
                letter-spacing: 1px;
            }
            p {
                margin-top: 0.5rem;
                font-size: 1.25rem;
                color: rgba(255, 255, 255, 0.8);
            }
            .version-info {
                margin-top: 2rem;
                font-size: 0.9rem;
                color: rgba(255, 255, 255, 0.6);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <h1>CMS Backend API</h1>
                <p>The API is up and running.</p>
                <div class="version-info">
                    Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                </div>
            </div>
        </div>
    </body>
</html>
