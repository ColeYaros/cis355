<?php
session_start();
if (!isset($_SESSION["iss_person_id"])) { // If "user" not set, redirect to login
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check if issue_id is passed in the URL
if (!isset($_GET['id'])) {
    die("Issue ID is required.");
}

$issue_id = $_GET['id'];

include '../database/database.php';
$pdo = Database::connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $short_comment = isset($_POST['short_comment']) ? trim($_POST['short_comment']) : null;
    $long_comment = isset($_POST['long_comment']) ? trim($_POST['long_comment']) : null;
    $person_id = $_SESSION['iss_person_id']; // User ID from session
    $posted_date = date('Y-m-d H:i:s'); // Current date and time

    // Ensure at least one comment field is filled
    if (empty($short_comment) && empty($long_comment)) {
        $error = "Please fill out at least one of the comment fields.";
    } else {
        // Prepare SQL to insert comment into database
        $sql = "INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date) 
                VALUES (:per_id, :iss_id, :short_comment, :long_comment, :posted_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':iss_id', $issue_id);
        $stmt->bindParam(':short_comment', $short_comment);
        $stmt->bindParam(':long_comment', $long_comment);
        $stmt->bindParam(':per_id', $person_id);
        $stmt->bindParam(':posted_date', $posted_date);

        // Execute the query
        if ($stmt->execute()) {
            // Redirect to issue description page after successful comment submission
            header("Location: iss_issue_description.php?id=" . $issue_id);
            exit;
        } else {
            $error = "Failed to add the comment. Please try again.";
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
    <title>Create Comment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="cardinal_logo.png" type="image/png" />
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
    <h2>Create a New Comment</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="iss_issue_new_comment.php?id=<?php echo $issue_id; ?>" method="post">
        <div class="mb-3">
            <label for="short_comment" class="form-label">Short Description</label>
            <textarea class="form-control" id="short_comment" name="short_comment" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="long_comment" class="form-label">Long Description</label>
            <textarea class="form-control" id="long_comment" name="long_comment" rows="5"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit Comment</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>