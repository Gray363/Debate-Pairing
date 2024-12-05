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

if (isset($_GET['pairing_id'])) {
    $pairing_id = $_GET['pairing_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_round'])) {
        $delete_sql = "DELETE FROM PairingAssign WHERE PairingID = $pairing_id";
        $conn->query($delete_sql); 

        $delete_round_sql = "DELETE FROM Pairing WHERE PairingID = $pairing_id";  
        if ($conn->query($delete_round_sql) === TRUE) {
            header("Location: admin.php");  
            exit;
        } else {
            echo "Error deleting round: " . $conn->error;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $round_number = $_POST['round_number'];
        $round_date = $_POST['round_date'];
        $room_name = $_POST['room_name'];
        $judge_id = $_POST['judge_name'];  

        $update_sql = "UPDATE Pairing
                       SET DateTime = '$round_date', Round = '$round_number', RoomID = (SELECT RoomID FROM Room WHERE Name = '$room_name' LIMIT 1), JudgeID = $judge_id
                       WHERE PairingID = $pairing_id";

        if ($conn->query($update_sql) === TRUE) {
            if (isset($_POST['students'])) {
                $selected_students = $_POST['students'];

                $remove_sql = "DELETE FROM PairingAssign WHERE PairingID = $pairing_id";
                $conn->query($remove_sql);

                foreach ($selected_students as $student_id) {
                    $insert_sql = "INSERT INTO PairingAssign (PairingID, StudentID) VALUES ($pairing_id, $student_id)";
                    $conn->query($insert_sql);
                }
            }

            echo "Round and students updated successfully.";
            header("Location: admin.php");
            exit;
        } else {
            echo "Error updating round: " . $conn->error;
        }
    } else {
        $sql = "SELECT p.PairingID, p.DateTime, p.Round, p.Status, r.Name as RoomName, r.RoomNumber, j.JudgeID, r.RoomID,
                j.FirstName AS JudgeFirstName, j.LastName AS JudgeLastName, p.StyleID
                FROM Pairing p
                JOIN Room r ON p.RoomID = r.RoomID
                JOIN Judge j ON p.JudgeID = j.JudgeID
                WHERE p.PairingID = $pairing_id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $round = $result->fetch_assoc();
            $round_date = $round['DateTime'];
            $round_number = $round['Round'];
            $room_name = $round['RoomID'];
            $judge_name = $round['JudgeFirstName'] . ' ' . $round['JudgeLastName'];
            $style_id = $round['StyleID']; 
        } else {
            echo "Round not found.";
            exit;
        }

        $students_sql = "SELECT s.StudentID, s.FirstName, s.LastName 
                         FROM Student s
                         WHERE s.StyleID = $style_id";
        $students_result = $conn->query($students_sql);

        $assigned_sql = "SELECT s.StudentID
                         FROM PairingAssign pa
                         JOIN Student s ON pa.StudentID = s.StudentID
                         WHERE pa.PairingID = $pairing_id";
        $assigned_result = $conn->query($assigned_sql);

        $available_students = [];
        while ($student = $students_result->fetch_assoc()) {
            $available_students[] = $student;
        }

        $assigned_students = [];
        while ($assigned = $assigned_result->fetch_assoc()) {
            $assigned_students[] = $assigned['StudentID'];
        }

        $room_sql = "SELECT RoomID, RoomNumber FROM Room";
        $room_result = $conn->query($room_sql);
    }
} else {
    echo "No pairing ID provided.";
    exit;
}

$judge_sql = "SELECT JudgeID, CONCAT(FirstName, ' ', LastName) AS JudgeFullName FROM Judge";
$judge_result = $conn->query($judge_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Round</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 60%;
            margin: 50px auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"],
        input[type="datetime-local"],
        select {
            padding: 10px;
            margin-top: 5px;
            font-size: 1em;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-button {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
        .students-container {
            display: flex;
            flex-direction: column;
        }
        .students-container input[type="checkbox"] {
            margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Round</h1>

    <form method="POST" action="edit_round.php?pairing_id=<?php echo $pairing_id; ?>">
        <label for="round_number">Round Number:</label>
            <select id="round_number" name="round_number" required>
                <option value="">Select a Round</option>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $round_number) ? 'selected' : ''; ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select><br>

        <label for="round_date">Date & Time:</label>
        <input type="datetime-local" id="round_date" name="round_date" value="<?php echo $round_date; ?>" required><br>

        <label for="room_name">Room:</label>
            <select id="room_name" name="room_name" required>
                <option value="">Select a Room</option>
                <?php while ($room = $room_result->fetch_assoc()): ?>
                    <option value="<?php echo $room['RoomID']; ?>" <?php echo ($room['RoomID'] == $round['RoomID']) ? 'selected' : ''; ?>>
                        <?php echo $room['RoomID']; ?> 
                    </option>
                <?php endwhile; ?>
            </select><br>


        <label for="judge_name">Judge:</label>
        <select id="judge_name" name="judge_name" required>
            <option value="">Select a Judge</option>
            <?php while ($judge = $judge_result->fetch_assoc()): ?>
                <option value="<?php echo $judge['JudgeID']; ?>" <?php echo ($judge['JudgeID'] == $round['JudgeID']) ? 'selected' : ''; ?>>
                    <?php echo $judge['JudgeFullName']; ?>
                </option>
            <?php endwhile; ?>
        </select><br>

        <div class="students-container">
            <label>Assign Students:</label>
            <?php foreach ($available_students as $student): ?>
                <label>
                    <input type="checkbox" name="students[]" value="<?php echo $student['StudentID']; ?>" 
                        <?php echo (in_array($student['StudentID'], $assigned_students)) ? 'checked' : ''; ?>>
                    <?php echo $student['FirstName'] . ' ' . $student['LastName']; ?>
                </label><br>
            <?php endforeach; ?>
        </div><br>

        <button type="submit">Update Round</button>
    </form>

    <form method="POST" action="edit_round.php?pairing_id=<?php echo $pairing_id; ?>" style="text-align: center; margin-top: 20px;">
        <button type="submit" name="delete_round" style="background-color: #ff4d4d;">Delete Round</button>
    </form>

    <div class="back-button">
        <a href="admin.php">
            <button>Back to All Rounds</button>
        </a>
    </div>
</div>

</body>
</html>
