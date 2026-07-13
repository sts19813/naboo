<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Error') | Videre</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #ffffff;
            color: #1e1e2d;
        }

        .error-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .error-header {
            padding: 20px 40px;
            border-bottom: 1px solid #eef0f4;
            text-align: center;
        }

        .error-header img {
            height: 36px;
        }

        .error-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .error-box {
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 40px;
        }

        .error-code {
            font-size: 180px;
            font-weight: 800;
            color: #eef2f6;
            line-height: 1;
        }

        .error-text h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        .error-text p {
            color: #6c757d;
            margin-bottom: 24px;
        }

        .error-text a {
            display: inline-block;
            padding: 10px 18px;
            background: #000;
            color: #fff;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
        }

        .error-image img {
            max-width: 100%;
        }

        @media (max-width: 768px) {
            .error-box {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .error-code {
                font-size: 120px;
            }
        }
    </style>
</head>

<body>

    <div class="error-wrapper">

        <div class="error-header">
            <img src="{{ asset('assets/img/videre-logo.png') }}" alt="Videre">
        </div>

        <div class="error-content">
            @yield('content')
        </div>

    </div>

</body>

</html>