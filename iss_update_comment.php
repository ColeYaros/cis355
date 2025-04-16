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
$comment_id = $_GET['id'] ?? null;
$error = "";

// Fetch the comment
if ($comment_id) {
    $sql = "SELECT * FROM iss_comments WHERE id = ? AND per_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$comment_id, $user_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        echo "<div class='alert alert-danger'>Comment not found or unauthorized.</div>";
        exit;
    }
} else {
    header("Location: iss_my_issues.php");
    exit;
}

// Handle update submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $short_comment = trim($_POST['short_comment'] ?? '');
    $long_comment = trim($_POST['long_comment'] ?? '');

    if (empty($short_comment) && empty($long_comment)) {
        $error = "At least one field must be filled out.";
    } else {
        // Get the issue ID associated with this comment
        $stmt = $pdo->prepare("SELECT iss_id FROM iss_comments WHERE id = ? AND per_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $issue_id = $row['iss_id'] ?? null;

        if ($issue_id) {
            $sql = "UPDATE iss_comments SET short_comment = ?, long_comment = ?, posted_date = NOW() WHERE id = ? AND per_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$short_comment, $long_comment, $comment_id, $user_id]);

            header("Location: iss_issue_description.php?id=" . $issue_id);
            exit;
        } else {
            $error = "Unable to find associated issue for redirect.";
        }
    }
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Comment</title>
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
    <h2>Update Comment</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="short_comment" class="form-label">Short Comment</label>
            <input type="text" class="form-control" id="short_comment" name="short_comment" value="<?php echo htmlspecialchars($comment['short_comment']); ?>">
        </div>

        <div class="mb-3">
            <label for="long_comment" class="form-label">Long Comment</label>
            <textarea class="form-control" id="long_comment" name="long_comment" rows="4"><?php echo htmlspecialchars($comment['long_comment']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Comment</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
