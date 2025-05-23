<?php
// Start or resume session, and create: $_SESSION[] array
session_start();

require '../database/database.php';

if (!empty($_POST)) { // If $_POST is filled, process the form

    // Initialize $_POST variables
    $username = $_POST['username']; // username is email address
    $password = $_POST['password'];

    // Connect to the database
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retrieve the salt for the given username
    $sql = "SELECT id, pwd_hash, pwd_salt FROM iss_persons WHERE email = ? LIMIT 1";
    $q = $pdo->prepare($sql);
    $q->execute(array($username));
    $data = $q->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $salt = $data['pwd_salt']; // Retrieve the salt from the database
        $passwordhash = md5($password . $salt); // Append salt and hash

        // Verify if the hashed password matches the stored hash
        if ($passwordhash === $data['pwd_hash']) {
            // Successful login
            $_SESSION['iss_person_id'] = $data['id'];
            header("Location: iss_issues.php");
            exit();
        }
    }

    // If login fails, redirect to error page
    header("Location: login_error.html");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-horizontal {
            display: flex;
            flex-direction: column;
        }
        .control-group {
            margin-bottom: 20px;
        }
        .control-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .controls input {
            padding: 10px;
            font-size: 14px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .form-actions button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        .form-actions .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
        }
        .form-actions .btn:hover {
            background-color: #0056b3;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            background-color: #f1f1f1;
            color: #333;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h3>Login</h3>
        </div>

        <form class="form-horizontal" action="login.php" method="post">
            <div class="control-group">
                <label class="control-label">Username (Email)</label>
                <div class="controls">
                    <input name="username" type="text" placeholder="me@email.com" required> 
                </div>    
            </div> 

            <div class="control-group">
                <label class="control-label">Password</label>
                <div class="controls">
                    <input name="password" type="password" required> 
                </div>    
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">Sign in</button>
                <a class="btn btn-primary" href="iss_per_create.php">Join</a>
            </div>
        </form>
    </div>

    <!-- <div class="footer">
        <p>&copy; 2025 Your Company. All Rights Reserved.</p>
    </div> -->

</body>
</html>