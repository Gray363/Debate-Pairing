<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'debate';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['round_id'])) {
    $selected_round = intval($_GET['round_id']);
    $details_sql = "SELECT * FROM Pairing WHERE PairingID = $selected_round";
    $details_result = $conn->query($details_sql);
    $round_details = $details_result->fetch_assoc();

    if ($round_details) {
        $datetime = new DateTime($round_details['DateTime']);
        $formatted_date = $datetime->format('F j, Y');
        $formatted_time = $datetime->format('g:i A');

        $styles = [
            1 => 'Lincoln Douglas',
            2 => 'Team Policy'
        ];
        $style_name = isset($styles[$round_details['StyleID']]) ? $styles[$round_details['StyleID']] : 'Unknown Style';

        $judge_sql = "SELECT FirstName, LastName FROM Judge WHERE JudgeID = " . $round_details['JudgeID'];
        $judge_result = $conn->query($judge_sql);
        $judge_name = '';
        if ($judge_result->num_rows > 0) {
            $judge_row = $judge_result->fetch_assoc();
            $judge_name = $judge_row['FirstName'] . ' ' . $judge_row['LastName'];
        }

        $room_sql = "SELECT Name, RoomNumber FROM Room WHERE RoomID = " . $round_details['RoomID'];
        $room_result = $conn->query($room_sql);
        $room_name = $round_details['RoomID'];
        if ($room_result->num_rows > 0) {
            $room_row = $room_result->fetch_assoc();
            $room_name = $room_row['Name'] ?: $room_row['RoomNumber'];
        }

        $students_sql = "
            SELECT s.FirstName, s.LastName 
            FROM PairingAssign pa 
            JOIN Student s ON pa.StudentID = s.StudentID 
            WHERE pa.PairingID = $selected_round";
        $students_result = $conn->query($students_sql);
        $students = [];
        while ($student_row = $students_result->fetch_assoc()) {
            $students[] = $student_row['FirstName'] . ' ' . $student_row['LastName'];
        }

        $status_label = ($round_details['Status'] == 1) ? 'Active' : 'Inactive';

        echo "
            <h4>$formatted_date</h4>
            <h5>$style_name Round: " . $round_details['Round'] . " at $formatted_time</h5>
            <p><strong>Room:</strong> $room_name</p>
            <p><strong>Judge:</strong> $judge_name</p>
            <p><strong>Students:</strong> " . implode(', ', $students) . "</p>
            <p><strong>Duration:</strong> " . $round_details['Duration'] . " minutes</p>
            <p><strong>Status:</strong> $status_label</p>
        ";
    } else {
        echo "<p>No details available for the selected round.</p>";
    }
} else {
    echo "<p>Select a round to view details.</p>";
}
?>
