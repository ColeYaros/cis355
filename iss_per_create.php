<?php 
session_start();
require_once '../database/database.php';

if (!empty($_POST)) { 
    $fnameError = $lnameError = $emailError = $mobileError = $passwordError = $confirmPasswordError = null;

    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $valid = true;

    if (empty($fname)) {
        $fnameError = 'Please enter First Name';
        $valid = false;
    }
    if (empty($lname)) {
        $lnameError = 'Please enter Last Name';
        $valid = false;
    }
    if (empty($email)) {
        $emailError = 'Please enter valid Email Address (REQUIRED)';
        $valid = false;
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = 'Please enter a valid Email Address';
        $valid = false;
    }

    $pdo = Database::connect();
    $sql = "SELECT COUNT(*) FROM iss_persons WHERE email = ?";
    $q = $pdo->prepare($sql);
    $q->execute([$email]);
    $count = $q->fetchColumn();
    Database::disconnect();

    if ($count > 0) {
        $emailError = 'Email has already been registered!';
        $valid = false;
    }
    
    if (strcmp(strtolower($email), $email) != 0) {
        $emailError = 'Email address can contain only lower case letters';
        $valid = false;
    }

    if (empty($mobile)) {
        $mobileError = 'Please enter Mobile Number (or "none")';
        $valid = false;
    }
    if (!preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $mobile)) {
        $mobileError = 'Please write Mobile Number in form 000-000-0000';
        $valid = false;
    }
    if (empty($password)) {
        $passwordError = 'Please enter a valid Password';
        $valid = false;
    }
    if ($password !== $confirmPassword) {
        $confirmPasswordError = 'Passwords do not match';
        $valid = false;
    }

    if ($valid) {
        // Generate a short random salt (8 characters)
        $salt = bin2hex(random_bytes(4)); // 4 bytes = 8 hex characters

        // Append salt to password and hash using MD5
        $passwordhash = md5($password . $salt);

        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert user data into database, including salt and admin status
        $sql = "INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, pwd_salt, admin) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $q = $pdo->prepare($sql);
        $q->execute([$fname, $lname, $mobile, $email, $passwordhash, $salt, 'no']);

        // Retrieve the newly created user ID
        $sql = "SELECT id FROM iss_persons WHERE email = ? LIMIT 1";
        $q = $pdo->prepare($sql);
        $q->execute([$email]);
        $data = $q->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $_SESSION['iss_person_id'] = $data['id'];
        }

        Database::disconnect();
        header("Location: iss_issues.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create New Volunteer</title>
    <link href="path/to/bootstrap.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
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
        .controls .help-inline {
            color: red;
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
            <h3>Create New User</h3>
        </div>

        <form class="form-horizontal" action="iss_per_create.php" method="post" enctype="multipart/form-data">
            <div class="control-group <?php echo !empty($fnameError)?'error':'';?>">
                <label class="control-label">First Name</label>
                <div class="controls">
                    <input name="fname" type="text" placeholder="First Name" value="<?php echo !empty($fname)?$fname:'';?>">
                    <?php if (!empty($fnameError)): ?>
                        <span class="help-inline"><?php echo $fnameError;?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="control-group <?php echo !empty($lnameError)?'error':'';?>">
                <label class="control-label">Last Name</label>
                <div class="controls">
                    <input name="lname" type="text" placeholder="Last Name" value="<?php echo !empty($lname)?$lname:'';?>">
                    <?php if (!empty($lnameError)): ?>
                        <span class="help-inline"><?php echo $lnameError;?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="control-group <?php echo !empty($emailError)?'error':'';?>">
                <label class="control-label">Email</label>
                <div class="controls">
                    <input name="email" type="text" placeholder="Email Address" value="<?php echo !empty($email)?$email:'';?>">
                    <?php if (!empty($emailError)): ?>
                        <span class="help-inline"><?php echo $emailError;?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="control-group <?php echo !empty($mobileError)?'error':'';?>">
                <label class="control-label">Mobile Number</label>
                <div class="controls">
                    <input name="mobile" type="text" placeholder="Mobile Phone Number" value="<?php echo !empty($mobile)?$mobile:'';?>">
                    <?php if (!empty($mobileError)): ?>
                        <span class="help-inline"><?php echo $mobileError;?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="control-group <?php echo !empty($passwordError)?'error':'';?>">
    <label class="control-label">Password</label>
    <div class="controls">
        <input id="password" name="password" type="password" placeholder="Password" value="<?php echo !empty($password)?$password:'';?>">
        <?php if (!empty($passwordError)): ?>
            <span class="help-inline"><?php echo $passwordError;?></span>
        <?php endif; ?>
    </div>
</div>

<div class="control-group <?php echo !empty($confirmPasswordError)?'error':'';?>">
    <label class="control-label">Confirm Password</label>
    <div class="controls">
        <input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm Password">
        <?php if (!empty($confirmPasswordError)): ?>
            <span class="help-inline"><?php echo $confirmPasswordError;?></span>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
</script>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">Confirm</button>
                <a class="btn" href="login.php">Back</a>
            </div>

        </form>
    </div>

    <!-- <div class="footer">
        <p>&copy; 2025 Your Company. All Rights Reserved.</p>
    </div> -->
</body>
</html>