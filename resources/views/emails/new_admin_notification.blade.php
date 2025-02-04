<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Marasem</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        .content {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
        }

        .button {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            background: #007bff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
        }

        .footer {
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">Welcome to Marasem</div>
        <div class="content">
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
            <p>Your admin account has been successfully created in Marasem.</p>
            <p>Here are your login details:</p>
            <ul>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>Temporary Password:</strong> {{ $password }}</li>
            </ul>
            <p>Please log in and update your password as soon as possible.</p>
            <div class="button">
                <a href="{{ url('/login') }}" class="btn">Log in to Your Account</a>
            </div>
        </div>
        <div class="footer">
            <p>Need assistance? Contact our support team.</p>
        </div>
    </div>
</body>

</html>