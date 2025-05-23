<?php
    session_start();
    if (!isset($_SESSION["iss_person_id"])) { // If "user" not set, redirect to login
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Include database connection
    include '../database/database.php';

    // Check if 'id' is set in the URL
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo "<div class='alert alert-danger'>Invalid issue ID.</div>";
        exit;
    }

    $issue_id = $_GET['id'];

    // Connect to the database
    $pdo = Database::connect();

    // Query to fetch the issue data based on the provided ID
    $sql = "SELECT * FROM iss_issues WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$issue_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);

    // Query to fetch comments for the given issue ID, along with the commenter's first and last name
    $sql = "SELECT c.id AS comment_id, c.short_comment, c.long_comment, c.posted_date, 
        p.id AS per_id, p.fname, p.lname 
        FROM iss_comments c
        JOIN iss_persons p ON c.per_id = p.id
        WHERE c.iss_id = ?
        ORDER BY c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$issue_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if the logged-in user is an admin
    $user_id = $_SESSION["iss_person_id"];
    $admin_check_sql = "SELECT admin FROM iss_persons WHERE id = ?";
    $admin_check_stmt = $pdo->prepare($admin_check_sql);
    $admin_check_stmt->execute([$user_id]);
    $user = $admin_check_stmt->fetch(PDO::FETCH_ASSOC);
    $is_admin = $user['admin'] == 'yes';

    // Handle Issue Deletion (Only Admins)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"]) && $is_admin) {
    $delete_id = $_POST["delete_id"];
    
    try {
        $pdo = Database::connect();
        $pdo->beginTransaction(); // Start transaction
        
        // Delete all related comments first
        $delete_comments_sql = "DELETE FROM iss_comments WHERE iss_id = ?";
        $stmt = $pdo->prepare($delete_comments_sql);
        $stmt->execute([$delete_id]);

        // Delete the issue itself
        $delete_issue_sql = "DELETE FROM iss_issues WHERE id = ?";
        $stmt = $pdo->prepare($delete_issue_sql);
        $stmt->execute([$delete_id]);

        $pdo->commit(); // Commit transaction
        Database::disconnect();

        // Redirect back to issues list
        header("Location: iss_issues.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback transaction if something fails
        Database::disconnect();
        echo "<div class='alert alert-danger'>Error deleting issue: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Handle comment deletion (admins can delete all, others only their own)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_comment_id"])) {
    $comment_id = $_POST["delete_comment_id"];

    // Fetch the comment's owner
    $stmt = $pdo->prepare("SELECT per_id FROM iss_comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Allow delete if admin or if user owns the comment
    if ($comment && ($comment['per_id'] == $_SESSION['iss_person_id'] || $is_admin)) {
        $delete_stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = ?");
        $delete_stmt->execute([$comment_id]);

        // Optional: Redirect to prevent resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

    Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Details</title>
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
    <h2>Issue Details</h2>

    <?php if ($issue): ?>
        <div class="card shadow-sm p-4 mb-4 bg-white rounded">
            <table class="table">
                <tr><th>Issue ID</th><td><?php echo htmlspecialchars($issue['id']) ?? 'null'; ?></td></tr>
                <tr><th>Short Description</th><td><?php echo htmlspecialchars($issue['short_description']) ?? 'null'; ?></td></tr>
                <tr><th>Long Description</th><td><?php echo htmlspecialchars($issue['long_description']) ?? 'null'; ?></td></tr>
                <tr><th>Open Date</th><td><?php echo htmlspecialchars($issue['open_date']) ?? 'null'; ?></td></tr>
                <tr><th>Close Date</th><td><?php echo ($issue['close_date'] != '0000-00-00' ? htmlspecialchars($issue['close_date']) : 'Open') ?? 'null'; ?></td></tr>
                <tr><th>Priority</th><td><?php echo htmlspecialchars($issue['priority']) ?? 'null'; ?></td></tr>
                <tr><th>Org.</th><td><?php echo htmlspecialchars($issue['org']) ?? 'null'; ?></td></tr>
                <tr><th>Project</th><td><?php echo htmlspecialchars($issue['project']) ?? 'null'; ?></td></tr>
                <tr><th>Created By</th><td><?php echo htmlspecialchars($issue['per_id']) ?? 'null'; ?></td></tr>
            </table>
        </div>

<!-- Add Creator button before Back to Issues -->
<a href="iss_per.php?id=<?php echo htmlspecialchars($issue['per_id']); ?>" class="btn btn-info me-2">Creator</a>
<a href="iss_issues.php" class="btn btn-primary">Back to Issues</a>

        <?php if ($is_admin): ?>
            <!-- Admin Delete Button -->
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this issue?');">
                <input type="hidden" name="delete_id" value="<?= $issue['id'] ?>">
                <button type="submit" class="btn btn-danger">Delete Issue</button>
            </form>
        <?php endif; ?>

        <br></br>
<h4>Comments</h4>
<a href="iss_issue_new_comment.php?id=<?php echo $issue_id; ?>" class="btn btn-primary">Add a New Comment</a>
<br></br>
<?php if (count($comments) > 0): ?>
    <ul class="list-group">
    <?php foreach ($comments as $comment): ?>
<li class="list-group-item">
    <strong>
        <a href="iss_per.php?id=<?php echo htmlspecialchars($comment['per_id']); ?>" class="text-decoration-none">
            <?php echo htmlspecialchars($comment['fname']) . ' ' . htmlspecialchars($comment['lname']); ?>
        </a>:
    </strong>
    <p><strong>Short Comment:</strong> <?php echo htmlspecialchars($comment['short_comment']); ?></p>
    <p><strong>Long Comment:</strong> <?php echo htmlspecialchars($comment['long_comment']); ?></p>
    <p><small>Posted on: <?php echo htmlspecialchars($comment['posted_date']); ?></small></p>

    <?php if ($comment['per_id'] == $_SESSION['iss_person_id'] || $is_admin): ?>
        <!-- Edit only if the user owns the comment -->
        <?php if ($comment['per_id'] == $_SESSION['iss_person_id']): ?>
            <a href="iss_update_comment.php?id=<?php echo $comment['comment_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
        <?php endif; ?>

        <!-- Delete allowed for admins or comment owners -->
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this comment?');">
            <input type="hidden" name="delete_comment_id" value="<?php echo $comment['comment_id']; ?>">
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
    <?php endif; ?>
</li>
<?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No comments available for this issue.</p>
<?php endif; ?>

<?php else: ?>
    <div class='alert alert-danger'>Issue not found.</div>
<?php endif; ?>
</div>
<br></br>
<br></br>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>