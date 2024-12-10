<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }

        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .header {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .content {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }

        .otp {
            font-size: 30px;
            color: #2d87f0;
            font-weight: bold;
            margin: 20px 0;
        }

        .footer {
            font-size: 14px;
            color: #999;
            margin-top: 40px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="email-container">
        <div class="header">
            Hello, {{ $userName }}!
        </div>

        <div class="content">
            You requested a password reset for your account. Please use the following OTP to reset your password:
        </div>

        <div class="otp">
            {{ $otp }}
        </div>

        <div class="footer">
            This OTP is valid for 10 minutes. If you did not request a password reset, please ignore this email.
        </div>
    </div>

</body>

</html>