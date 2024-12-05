<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');  
    exit;
}

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'debate';

$conn = new mysqli($host, $username, $password, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $round_number = $_POST['round'];
    $round_date = $_POST['datetime'];
    $room_id = $_POST['room'];
    $judge_id = $_POST['judge'];
    $style_id = $_POST['style'];
    $status = 1;
    $duration = ($style_id == 1) ? 45 : ($style_id == 2 ? 90 : NULL);

    if (empty($round_number) || empty($round_date) || empty($room_id) || empty($judge_id) || empty($style_id) || $duration === NULL) {
        echo "All fields are required.";
        exit;
    }

    $conn->begin_transaction();

    try {
        $insert_pairing_sql = "
            INSERT INTO Pairing (DateTime, Duration, Round, Status, StyleID, JudgeID, RoomID)
            VALUES (
                '$round_date',
                '$duration',
                $round_number,
                $status,
                $style_id,
                $judge_id,
                $room_id
            )
        ";

        if ($conn->query($insert_pairing_sql) === TRUE) {
            $pairing_id = $conn->insert_id;

            if (isset($_POST['students'])) {
                $selected_students = $_POST['students'];

                $remove_sql = "DELETE FROM PairingAssign WHERE PairingID = $pairing_id";
                $conn->query($remove_sql);  

                foreach ($selected_students as $student_id) {
                    $insert_student_sql = "INSERT INTO PairingAssign (PairingID, StudentID) VALUES ($pairing_id, $student_id)";
                    if ($conn->query($insert_student_sql) !== TRUE) {
                        echo "Error assigning student $student_id: " . $conn->error;
                        $conn->rollback();
                        exit;
                    }
                }

                echo "Round and student assignments added successfully.";
            } else {
                echo "Error: No students selected.";
                $conn->rollback();
            }

            $conn->commit();
        } else {
            echo "Error adding round: " . $conn->error;
            $conn->rollback();
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        $conn->rollback();
    }
}

$styles_sql = "SELECT StyleID, Name FROM DebateStyle";
$styles_result = $conn->query($styles_sql);

$judges_sql = "SELECT JudgeID, CONCAT(FirstName, ' ', LastName) AS JudgeFullName FROM Judge";
$judges_result = $conn->query($judges_sql);

$rooms_sql = "SELECT Name FROM Room";
$rooms_result = $conn->query($rooms_sql);

$students_sql = "SELECT StudentID, FirstName, LastName FROM Student";
$students_result = $conn->query($students_sql);

$conn->close();

?>
