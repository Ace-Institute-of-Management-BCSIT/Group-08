<?php
$username = "";
$message = "";
$messageClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = htmlspecialchars($_POST["username"]);
    $password = htmlspecialchars($_POST["password"]);

    // Static login validation
    if (!empty($username) && !empty($password)) {
        $message = "Login successful! Welcome, " . $username . ".";
        $messageClass = "success";
    } else {
        $message = "Please fill in all fields.";
        $messageClass = "error";
    }
} else {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Status</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .message-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .message-box h2 {
            margin-bottom: 20px;
            color: #132766;
        }

        .success {
            color: #28a745;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .error {
            color: #dc3545;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border-radius: 6px;
            transition: 0.3s;
        }

        .btn:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>

    <div class="message-box">

        <h2>Ghar Sathi</h2>

        <p class="<?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </p>

        <a href="login.html" class="btn">
            Back to Login
        </a>

    </div>

</body>
</html>