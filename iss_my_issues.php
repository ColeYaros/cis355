<?php
session_start();
include '../database/database.php';

$pdo = Database::connect();

if (!isset($_SESSION["iss_person_id"])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['iss_person_id']; // Logged-in user's ID

// Handle issue deletion along with associated comments
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $delete_id = $_POST["delete_id"];

    // Start a transaction to ensure both the issue and its comments are deleted together
    try {
        $pdo->beginTransaction();

        // Delete all comments associated with the issue
        $delete_comments_sql = "DELETE FROM iss_comments WHERE iss_id = ?";
        $stmt_comments = $pdo->prepare($delete_comments_sql);
        $stmt_comments->execute([$delete_id]);

        // Delete the issue itself
        $delete_issue_sql = "DELETE FROM iss_issues WHERE id = ?";
        $stmt_issue = $pdo->prepare($delete_issue_sql);
        $stmt_issue->execute([$delete_id]);

        // Commit the transaction
        $pdo->commit();

        // Redirect to the list of issues after deletion
        Database::disconnect();
        header("Location: iss_my_issues.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $pdo->rollBack();
        echo "Failed to delete the issue and its comments: " . $e->getMessage();
    }
}

// Handle issue resolution
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["resolve_id"])) {
    $resolve_id = $_POST["resolve_id"];
    $current_date = date("Y-m-d");  // Get today's date

    // Update the close date of the issue
    $sql = "UPDATE iss_issues SET close_date = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_date, $resolve_id]);

    // Insert a comment saying "This issue has been resolved"
    $short_comment = "This issue has been resolved";
    $long_comment = "";  // Empty long comment
    $sql_comment = "INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_comment = $pdo->prepare($sql_comment);
    $stmt_comment->execute([$user_id, $resolve_id, $short_comment, $long_comment, $current_date]);

    // Redirect to refresh the page
    header("Location: iss_my_issues.php");
    exit();
}

// Handle issue reopening
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reopen_id"])) {
    $reopen_id = $_POST["reopen_id"];
    $reopen_date = "0000-00-00";

    // Set close date to 0000-00-00 to reopen
    $sql = "UPDATE iss_issues SET close_date = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reopen_date, $reopen_id]);

    // Insert a comment saying "This issue has been reopened"
    $short_comment = "This issue has been reopened";
    $long_comment = "";
    $current_date = date("Y-m-d");

    $sql_comment = "INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_comment = $pdo->prepare($sql_comment);
    $stmt_comment->execute([$user_id, $reopen_id, $short_comment, $long_comment, $current_date]);

    header("Location: iss_my_issues.php");
    exit();
}

// Fetch user's issues
$sql = "SELECT * FROM iss_issues WHERE per_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

Database::disconnect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Issues</title>
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
    <h2>My Issues</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Short Description</th>
                <th>Priority</th>
                <th>Org</th>
                <th>Project</th>
                <th>Open Date</th>
                <th>Close Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    <?php if (!empty($issues)): ?>
        <?php foreach ($issues as $issue): ?>
            <tr>
                <td><?= htmlspecialchars($issue['id']) ?></td>
                <td><?= htmlspecialchars($issue['short_description']) ?></td>
                <td><?= htmlspecialchars($issue['priority']) ?></td>
                <td><?= htmlspecialchars($issue['org']) ?></td>
                <td><?= htmlspecialchars($issue['project']) ?></td>
                <td><?= htmlspecialchars($issue['open_date']) ?></td>
                <td><?= htmlspecialchars($issue['close_date']) ?></td>
                <td>
                    <a href="iss_update_issue.php?id=<?= $issue['id'] ?>" class="btn btn-warning btn-sm">Update</a>

                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="delete_id" value="<?= $issue['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure? This will also delete all comments related to this issue.')">
                            Delete
                        </button>
                    </form>

                    <?php if ($issue['close_date'] == "0000-00-00" || empty($issue['close_date'])): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="resolve_id" value="<?= $issue['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm"
                                onclick="return confirm('Are you sure you want to resolve this issue?')">
                                Resolve
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="reopen_id" value="<?= $issue['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"
                                onclick="return confirm('Reopen this issue?')">
                                Reopen
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>

    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>