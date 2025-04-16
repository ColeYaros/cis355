<?php
session_start();
include '../database/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdo = Database::connect();

if (!isset($_SESSION["iss_person_id"])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['iss_person_id'];
$error = "";

// Fetch user details
$sql = "SELECT * FROM iss_persons WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='alert alert-danger'>User not found or unauthorized.</div>";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['fname'] ?? '';
    $last_name = $_POST['lname'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['phone'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $current_password = $_POST['current_password'] ?? '';

    // Check for required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($mobile)) {
        $error = "First name, last name, email, and phone are required fields.";
    } elseif (!preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $mobile)) {
        $error = "Phone number must be in the format XXX-XXX-XXXX.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        // Check if email has changed and is already taken
        if ($email !== $user['email']) {
            $sql = "SELECT COUNT(*) FROM iss_persons WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $email_count = $stmt->fetchColumn();
            
            if ($email_count > 0) {
                $error = "The email address is already associated with another account.";
            }
        }

        if (empty($error)) {
            // Password update logic
            if (!empty($new_password)) {
                // Verify the current password
                $stored_salt = $user['pwd_salt'];
                $stored_hash = $user['pwd_hash'];
                $current_hashed = md5($current_password . $stored_salt);

                if ($current_hashed !== $stored_hash) {
                    $error = "Current password is incorrect.";
                } else {
                    // Generate a new salt and hash the new password
                    $salt = bin2hex(random_bytes(4)); // 8-byte random salt
                    $new_hashed_password = md5($new_password . $salt);

                    $sql = "UPDATE iss_persons SET fname=?, lname=?, email=?, mobile=?, pwd_hash=?, pwd_salt=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$first_name, $last_name, $email, $mobile, $new_hashed_password, $salt, $user_id]);
                }
            } else {
                // Update user info without changing password
                $sql = "UPDATE iss_persons SET fname=?, lname=?, email=?, mobile=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$first_name, $last_name, $email, $mobile, $user_id]);
            }

            if (empty($error)) {
                header("Location: iss_per.php?id=" . $user_id);
                exit;
            }
        }
    }
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Issue Tracker</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="iss_issues.php">Issues</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="iss_my_issues.php">My Issues</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="iss_create_issues.php">Create Issue</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="iss_per_list.php">Persons</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="iss_per.php?id=<?php echo $_SESSION['iss_person_id']; ?>">Me</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">
    <h2>Update Profile</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="fname" class="form-label">First Name</label>
            <input type="text" class="form-control" id="fname" name="fname" value="<?php echo htmlspecialchars($user['fname']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="lname" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lname" name="lname" value="<?php echo htmlspecialchars($user['lname']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
            <input type="password" class="form-control" id="new_password" name="new_password">
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
        </div>

        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>