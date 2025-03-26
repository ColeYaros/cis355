<?php
session_start();
include '../database/database.php';

$pdo = Database::connect();

if (!isset($_SESSION["iss_person_id"])) { // If "user" not set, redirect to login
    session_destroy();
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['iss_person_id']; // Assign logged-in user's ID

// Initialize variables
$short_description = $long_description = $priority = $org = $project = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $short_description = $_POST['short_description'] ?? '';
    $long_description = $_POST['long_description'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $org = $_POST['org'] ?? '';
    $project = $_POST['project'] ?? '';
    $open_date = date("Y-m-d"); // Auto-set open date to today

    if (empty($short_description) || empty($long_description) || empty($priority)) {
        $error = "Short and Long description and Priority are needed!";
    } else {
        // Insert into database
        $sql = "INSERT INTO iss_issues (short_description, long_description, open_date, priority, org, project, per_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$short_description, $long_description, $open_date, $priority, $org, $project, $user_id]);

        // Redirect to issues page after creation
        header("Location: iss_issues.php");
        exit();
    }
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Issue</title>
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
                    <a class="nav-link" href="iss_per.php?id=<?php echo $_SESSION['iss_person_id']; ?>">Me</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">
    <h2>Create a New Issue</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="short_description" class="form-label">Short Description</label>
            <input type="text" class="form-control" id="short_description" name="short_description" required>
        </div>

        <div class="mb-3">
            <label for="long_description" class="form-label">Long Description</label>
            <textarea class="form-control" id="long_description" name="long_description" rows="4" required></textarea>
        </div>

        <div class="mb-3">
            <label for="priority" class="form-label">Priority</label>
            <select class="form-control" id="priority" name="priority" required>
                <option value="">Select Priority</option>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="org" class="form-label">Organization</label>
            <input type="text" class="form-control" id="org" name="org">
        </div>

        <div class="mb-3">
            <label for="project" class="form-label">Project</label>
            <input type="text" class="form-control" id="project" name="project">
        </div>

        <button type="submit" class="btn btn-primary">Create Issue</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>