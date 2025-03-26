<?php
    session_start();
    if (!isset($_SESSION["iss_person_id"])) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    include '../database/database.php';

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo "<div class='alert alert-danger'>Invalid user ID.</div>";
        exit;
    }

    $user_id = $_GET['id'];
    $logged_in_id = $_SESSION["iss_person_id"];

    $pdo = Database::connect();
    $sql = "SELECT * FROM iss_persons WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
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
                <li class="nav-item"><a class="nav-link" href="iss_issues.php">Issues</a></li>
                <li class="nav-item"><a class="nav-link" href="iss_my_issues.php">My Issues</a></li>
                <li class="nav-item"><a class="nav-link" href="iss_create_issues.php">Create Issue</a></li>
                <li class="nav-item"><a class="nav-link" href="iss_per.php?id=<?php echo $_SESSION['iss_person_id']; ?>">Me</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>User Profile</h2>
    
    <?php if ($user): ?>
        <div class="card shadow-sm p-4 mb-4 bg-white rounded">
            <table class="table">
                <tr><th>ID</th><td><?php echo htmlspecialchars($user['id']); ?></td></tr>
                <tr><th>First Name</th><td><?php echo htmlspecialchars($user['fname']); ?></td></tr>
                <tr><th>Last Name</th><td><?php echo htmlspecialchars($user['lname']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
                <tr><th>Phone Number</th><td><?php echo htmlspecialchars($user['mobile']); ?></td></tr>
            </table>
        </div>

        <?php if ($user_id == $logged_in_id): ?>
            <a href="iss_update_per.php?id=<?php echo $_SESSION['iss_person_id']; ?>" class="btn btn-warning">Update Profile</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        <?php endif; ?>
    <?php else: ?>
        <div class='alert alert-danger'>User not found.</div>
    <?php endif; ?>

    <br></br><a href="iss_issues.php" class="btn btn-primary mt-3">Back to Issues</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>