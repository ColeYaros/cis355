<?php
session_start();
include '../database/database.php';

// Redirect if not logged in
if (!isset($_SESSION['iss_person_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = Database::connect();

// Check if admin status is not yet stored in session
if (!isset($_SESSION['iss_admin'])) {
    $user_id = $_SESSION["iss_person_id"];
    $admin_check_sql = "SELECT admin FROM iss_persons WHERE id = ?";
    $admin_check_stmt = $pdo->prepare($admin_check_sql);
    $admin_check_stmt->execute([$user_id]);
    $user = $admin_check_stmt->fetch(PDO::FETCH_ASSOC);

    // Store admin status in session
    $_SESSION['iss_admin'] = $user['admin'] ?? 'no';
}

// Use session variable for admin check
$is_admin = $_SESSION['iss_admin'] === 'yes';

// Fetch all people
$stmt = $pdo->query("SELECT id, fname, lname, mobile, email, admin FROM iss_persons ORDER BY lname, fname");
$people = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle admin change form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    if (isset($_POST['toggle_admin_id'])) {
        $id = $_POST['toggle_admin_id'];
        $new_status = $_POST['new_admin_status'] === 'yes' ? 'yes' : 'no';

        $stmt = $pdo->prepare("UPDATE iss_persons SET admin = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $id]);

        // If user modified their own admin status, update session too
        if ($id == $_SESSION['iss_person_id']) {
            $_SESSION['iss_admin'] = $new_status;
        }

        header("Location: iss_per_list.php");
        exit;
    }
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Person List</title>
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
    <h2>People</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <?php if ($is_admin): ?>
                    <th>Action</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($people as $person): ?>
                <tr>
                    <td><?= htmlspecialchars($person['id']) ?></td>
                    <td><?= htmlspecialchars($person['fname'] . ' ' . $person['lname']) ?></td>
                    <td><?= htmlspecialchars($person['mobile']) ?></td>
                    <td><?= htmlspecialchars($person['email']) ?></td>
                    <?php if ($is_admin): ?>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="toggle_admin_id" value="<?= $person['id'] ?>">
                                <input type="hidden" name="new_admin_status" value="<?= $person['admin'] === 'yes' ? 'no' : 'yes' ?>">
                                <button type="submit" class="btn btn-sm <?= $person['admin'] === 'yes' ? 'btn-danger' : 'btn-success' ?>">
                                    <?= $person['admin'] === 'yes' ? 'Remove Admin' : 'Make Admin' ?>
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
