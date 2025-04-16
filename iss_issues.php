<?php 
session_start();
if (!isset($_SESSION["iss_person_id"])) {
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
    <style>
        body { background-color: #f8f9fa; }
        .table-container { margin-top: 30px; }
        table { background-color: #f0f0f0; }
        .navbar { margin-bottom: 20px; }
        .navbar-nav .nav-item .nav-link { padding: 10px; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: #e9ecef; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .sortable:hover { cursor: pointer; text-decoration: underline; }
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
                <li class="nav-item"><a class="nav-link" href="iss_issues.php">Issues</a></li>
                <li class="nav-item"><a class="nav-link" href="iss_my_issues.php">My Issues</a></li>
                <li class="nav-item"><a class="nav-link" href="iss_create_issues.php">Create Issue</a></li>
                <li class="nav-item"><a class="nav-link" href="iss_per_list.php">Persons</a></li>
                <li class="nav-item"><a class="nav-link" href="iss_per.php?id=<?php echo $_SESSION['iss_person_id']; ?>">Me</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row"><h3>All Issues</h3></div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="issueTabs">
        <li class="nav-item"><a class="nav-link active" href="#" data-filter="open">Open</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-filter="closed">Closed</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-filter="all">All</a></li>
    </ul>

    <!-- Filter by Created By -->
    <div class="mb-3">
        <label for="nameFilter" class="form-label">Filter by Creator Name:</label>
        <input type="text" class="form-control" id="nameFilter" placeholder="Enter name...">
    </div>

    <div class="table-container">
        <table class="table table-striped table-bordered table-hover" id="issuesTable">
            <thead>
                <tr>
                    <th class="sortable">Issue ID</th>
                    <th class="sortable">Short Description</th>
                    <th class="sortable">Open Date</th>
                    <th class="sortable">Close Date</th>
                    <th class="sortable">Priority</th>
                    <th class="sortable">Org.</th>
                    <th class="sortable">Project</th>
                    <th class="sortable">Created By</th>
                </tr>
            </thead>
            <tbody>
            <?php 
                include '../database/database.php';
                $pdo = Database::connect();

                // Modified SQL to get user name
                $sql = 'SELECT iss_issues.id, short_description, open_date, close_date, priority, org, project, per_id,
                        iss_persons.fname, iss_persons.lname
                        FROM iss_issues
                        LEFT JOIN iss_persons ON iss_issues.per_id = iss_persons.id
                        ORDER BY iss_issues.id ASC';

                foreach ($pdo->query($sql) as $row) {
                    $fullName = htmlspecialchars($row['fname'] . ' ' . $row['lname']);
                    echo '<tr>';
                    echo '<td><a href="iss_issue_description.php?id=' . $row['id'] . '">' . $row['id'] . '</a></td>';
                    echo '<td>' . htmlspecialchars($row['short_description']) . '</td>';
                    echo '<td>' . $row['open_date'] . '</td>';
                    echo '<td>' . ($row['close_date'] != '0000-00-00' ? $row['close_date'] : 'Open') . '</td>';
                    echo '<td>' . $row['priority'] . '</td>';
                    echo '<td>' . $row['org'] . '</td>';
                    echo '<td>' . $row['project'] . '</td>';
                    echo '<td><a href="iss_per.php?id=' . $row['per_id'] . '">' . $fullName . '</a></td>';
                    echo '</tr>';
                }

                Database::disconnect();
            ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll("#issueTabs .nav-link");
    const rows = document.querySelectorAll("#issuesTable tbody tr");
    const nameFilter = document.getElementById("nameFilter");

    function applyFilters() {
        const activeTab = document.querySelector("#issueTabs .nav-link.active").getAttribute("data-filter");
        const nameQuery = nameFilter.value.toLowerCase();

        rows.forEach(row => {
            const closeDate = row.cells[3].textContent.trim();
            const createdBy = row.cells[7].textContent.trim().toLowerCase();

            const matchesTab = (
                (activeTab === "open" && closeDate === "Open") ||
                (activeTab === "closed" && closeDate !== "Open" && closeDate !== "") ||
                (activeTab === "all")
            );

            const matchesName = createdBy.includes(nameQuery);

            row.style.display = (matchesTab && matchesName) ? "" : "none";
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener("click", function (e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove("active"));
            this.classList.add("active");
            applyFilters();
        });
    });

    nameFilter.addEventListener("input", applyFilters);
    tabs[0].click();

    // Sorting logic
    document.querySelectorAll("#issuesTable th.sortable").forEach((header, index) => {
        let ascending = true;
        header.addEventListener("click", () => {
            const rowsArray = Array.from(document.querySelectorAll("#issuesTable tbody tr"));
            rowsArray.sort((a, b) => {
                const cellA = a.cells[index].textContent.trim().toLowerCase();
                const cellB = b.cells[index].textContent.trim().toLowerCase();
                return ascending ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });
            const tbody = document.querySelector("#issuesTable tbody");
            rowsArray.forEach(row => tbody.appendChild(row));
            ascending = !ascending;
        });
    });
});
</script>
</body>
</html>