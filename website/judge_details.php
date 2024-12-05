<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'debate';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$judge_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($judge_id) {
    $judge_sql = "
        SELECT j.JudgeID, j.FirstName, j.LastName 
        FROM Judge j
        WHERE j.JudgeID = $judge_id";
    $result = $conn->query($judge_sql);

    if ($result->num_rows > 0) {
        $judge = $result->fetch_assoc();

        $children_sql = "
            SELECT s.FirstName, s.LastName 
            FROM Parent p 
            JOIN Student s ON p.StudentID = s.StudentID 
            WHERE p.JudgeID = $judge_id";
        $children_result = $conn->query($children_sql);
        $children = [];
        while ($child = $children_result->fetch_assoc()) {
            $children[] = $child['FirstName'] . ' ' . $child['LastName'];
        }

        $children_list = $children ? implode(', ', $children) : 'No children';

        $html = "
            <h3>Judge Details</h3>
            <p><strong>Judge ID:</strong> {$judge['JudgeID']}</p>
            <p><strong>Full Name:</strong> {$judge['FirstName']} {$judge['LastName']}</p>
            <p><strong>Children:</strong> $children_list</p>
        ";

        echo $html;
    } else {
        echo 'No judge details found.';
    }
}

$conn->close();
?>
