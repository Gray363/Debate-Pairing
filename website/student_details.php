<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'debate';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['style_id'])) {
    $style_id = (int)$_GET['style_id'];

    $students_sql = "
        SELECT s.StudentID, s.FirstName, s.LastName
        FROM Student s
        WHERE s.StyleID = ?
    ";

    $stmt = $conn->prepare($students_sql);
    $stmt->bind_param("i", $style_id);
    $stmt->execute();
    $students_result = $stmt->get_result();

    $students = [];
    if ($students_result->num_rows > 0) {
        while ($student = $students_result->fetch_assoc()) {
            $students[] = $student;
        }
    }

    echo json_encode(['students' => $students]);

} else {
    $student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;  

    if ($student_id) {
        $student_sql = "
            SELECT s.StudentID, s.FirstName, s.LastName, s.Status, 
                   s.Experience, s.PartnerID, p.StyleID
            FROM Student s
            LEFT JOIN PairingAssign sa ON s.StudentID = sa.StudentID
            LEFT JOIN Pairing p ON sa.PairingID = p.PairingID
            WHERE s.StudentID = ?
        ";

        $stmt = $conn->prepare($student_sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $status_label = ($student['Status'] == 1) ? 'Active' : 'Inactive';
            $experience = ($student['Experience'] == 'V') ? 'Varsity' : 'Novice';
            $style_name = ($student['StyleID'] == 1) ? 'Lincoln Douglas' : 'Team Policy';

            $partner_name = 'N/A'; 
            if ($student['PartnerID']) {
                $partner_sql = "SELECT FirstName, LastName FROM Student WHERE StudentID = ?";
                $partner_stmt = $conn->prepare($partner_sql);
                $partner_stmt->bind_param("i", $student['PartnerID']);
                $partner_stmt->execute();
                $partner_result = $partner_stmt->get_result();
                if ($partner_result->num_rows > 0) {
                    $partner = $partner_result->fetch_assoc();
                    $partner_name = $partner['FirstName'] . ' ' . $partner['LastName'];
                }
            }

            $html = "
                <h3>Student Details</h3>
                <p><strong>Student ID:</strong> {$student['StudentID']}</p>
                <p><strong>Name:</strong> {$student['FirstName']} {$student['LastName']}</p>
                <p><strong>Experience Level:</strong> $experience</p>
                <p><strong>Status:</strong> $status_label</p>
                <p><strong>Partner:</strong> $partner_name</p>
                <p><strong>Debate Style:</strong> $style_name</p>
            ";

            echo $html;
        } else {
            echo 'No student details found.';
        }
    }
}

$conn->close();
?>
