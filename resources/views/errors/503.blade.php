<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wartungsarbeiten</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-size: cover;
            color: #333;
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }
        .container h1 {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .container p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .container img {
            width: 150px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        .container a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .container a:hover {
            background-color: #0056b3;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-30px); }
            60% { transform: translateY(-15px); }
        }
    </style>
</head>
<body>
<div class="container">
    <img src="{{asset('img/error503.jpg')}}" alt="Bauarbeiten">
    <h1>Wir sind bald zurück!</h1>
    <p>Unsere Website wird derzeit gewartet. Bitte haben Sie etwas Geduld und versuchen Sie es später erneut.</p>
</div>
</body>
</html>
