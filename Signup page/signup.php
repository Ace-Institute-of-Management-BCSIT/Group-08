<?php
$username = "";
$message = "";
$messageClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = htmlspecialchars($_POST["username"]);
    $password = htmlspecialchars($_POST["password"]);
    $confirmPassword = htmlspecialchars($_POST["confirmPassword"]);

    // Static signup validation
    if (empty($username) || empty($password) || empty($confirmPassword)) {

        $message = "Please fill in all fields.";
        $messageClass = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters long.";
        $messageClass = "error";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageClass = "error";

    } else {

        $message = "Account created successfully! Welcome to Ghar Sathi, " . $username . ".";
        $messageClass = "success";
    }

} else {

    header("Location: signup.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Status</title>

    <style>

        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body{
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .message-box{
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .message-box h2{
            color: #132766;
            margin-bottom: 20px;
        }

        .success{
            color: #28a745;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .error{
            color: #dc3545;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .btn{
            display: inline-block;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .btn:hover{
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

            Go to Login

        </a>

    </div>

</body>
</html>