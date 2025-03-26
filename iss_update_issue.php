<?php
session_start();
include '../database/database.php';

$pdo = Database::connect();

if (!isset($_SESSION["iss_person_id"])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['iss_person_id'];
$issue_id = $_GET['id'] ?? null;
$error = "";

// Fetch issue details
if ($issue_id) {
    $sql = "SELECT * FROM iss_issues WHERE id = ? AND per_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$issue_id, $user_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$issue) {
        echo "<div class='alert alert-danger'>Issue not found or unauthorized.</div>";
        exit;
    }
} else {
    header("Location: iss_my_issues.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $short_description = $_POST['short_description'] ?? '';
    $long_description = $_POST['long_description'] ?? '';
    $close_date = $_POST['close_date'] ?? null;
    $priority = $_POST['priority'] ?? '';
    $org = $_POST['org'] ?? '';
    $project = $_POST['project'] ?? '';

    if (empty($short_description) || empty($priority)) {
        $error = "Short description, priority, organization, and project are required fields.";
    } else {
        $sql = "UPDATE iss_issues SET short_description=?, long_description=?, close_date=?, priority=?, org=?, project=? WHERE id=? AND per_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$short_description, $long_description, $close_date, $priority, $org, $project, $issue_id, $user_id]);
        
        header("Location: iss_my_issues.php");
        exit;
    }
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Issue</title>
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
    <h2>Update Issue</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="mb-3">
            <label for="short_description" class="form-label">Short Description</label>
            <input type="text" class="form-control" id="short_description" name="short_description" value="<?php echo htmlspecialchars($issue['short_description']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="long_description" class="form-label">Long Description</label>
            <textarea class="form-control" id="long_description" name="long_description" rows="4"><?php echo htmlspecialchars($issue['long_description']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="close_date" class="form-label">Close Date</label>
            <input type="date" class="form-control" id="close_date" name="close_date" value="<?php echo $issue['close_date']; ?>">
        </div>

        <div class="mb-3">
            <label for="priority" class="form-label">Priority</label>
            <select class="form-control" id="priority" name="priority" required>
                <option value="Low" <?php if ($issue['priority'] == 'Low') echo 'selected'; ?>>Low</option>
                <option value="Medium" <?php if ($issue['priority'] == 'Medium') echo 'selected'; ?>>Medium</option>
                <option value="High" <?php if ($issue['priority'] == 'High') echo 'selected'; ?>>High</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="org" class="form-label">Organization</label>
            <input type="text" class="form-control" id="org" name="org" value="<?php echo htmlspecialchars($issue['org']); ?>">
        </div>

        <div class="mb-3">
            <label for="project" class="form-label">Project</label>
            <input type="text" class="form-control" id="project" name="project" value="<?php echo htmlspecialchars($issue['project']); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Update Issue</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>