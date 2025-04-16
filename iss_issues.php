<?php 
session_start();
if (!isset($_SESSION["iss_person_id"])) { // If "user" not set, redirect to login
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="cardinal_logo.png" type="image/png" />
    <script>
        // Function to filter issues based on the checkbox selection
        function filterIssues() {
            var notCompleted = document.getElementById('not_completed').checked;
            var completed = document.getElementById('completed').checked;
            var rows = document.querySelectorAll('#issuesTable tbody tr');

            rows.forEach(function(row) {
                var closeDate = row.cells[3].textContent.trim(); // Get the Close Date (4th column)

                if ((notCompleted && closeDate === 'Open') || (completed && closeDate !== 'Open' && closeDate !== '')) 
                {
                    row.style.display = ''; // Show the row
                } 
                else 
                {
                    row.style.display = 'none'; // Hide the row
                }
            });
        }
    </script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .table-container {
            margin-top: 30px;
        }

        table {
            background-color: #f0f0f0;
        }

        .navbar {
            margin-bottom: 20px;
        }

        .navbar-nav .nav-item .nav-link {
            padding: 10px;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #e9ecef;
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
        }
    </style>
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
    <div class="row">
        <h3>All Issues</h3>
    </div>

    <!-- Checkboxes for filtering -->
    <div class="mb-3">
        <input type="checkbox" id="not_completed" checked onclick="filterIssues()"> Not Completed
        <input type="checkbox" id="completed" checked onclick="filterIssues()"> Completed
    </div>

    <div class="table-container">
        <table class="table table-striped table-bordered table-hover" id="issuesTable">
            <thead>
                <tr>
                    <th>Issue ID</th>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Close Date</th>
                    <th>Priority</th>
                    <th>Org.</th>
                    <th>Project</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
            <?php 
                include '../database/database.php';
                $pdo = Database::connect();

                // Fetch all issues from the database
                $sql = 'SELECT id, short_description, open_date, close_date, priority, org, project, per_id FROM iss_issues ORDER BY id ASC';

                foreach ($pdo->query($sql) as $row) {
                    echo '<tr>';
                    // Issue ID with a link
                    echo '<td><a href="iss_issue_description.php?id=' . $row['id'] . '">' . ($row['id'] ?? 'null') . '</a></td>';
                    echo '<td>' . (!empty($row['short_description']) ? $row['short_description'] : '') . '</td>';
                    echo '<td>' . (!empty($row['open_date']) ? $row['open_date'] : 'null') . '</td>';
                    echo '<td>' . ($row['close_date'] != '0000-00-00' ? $row['close_date'] : 'Open') . '</td>';
                    echo '<td>' . (!empty($row['priority']) ? $row['priority'] : '') . '</td>';
                    echo '<td>' . (!empty($row['org']) ? $row['org'] : '') . '</td>';
                    echo '<td>' . (!empty($row['project']) ? $row['project'] : '') . '</td>';
                    echo '<td>';
                    if ($row['per_id'] >= 0) {
                        echo '<a href="iss_per.php?id=' . $row['per_id'] . '">' . $row['per_id'] . '</a>';
                    } else {
                        echo 'null';
                    }
                    echo '</td>';
                    echo '</tr>';
                }

                Database::disconnect();
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>