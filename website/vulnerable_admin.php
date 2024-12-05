<?php
session_start();

$admin_username = 'admin';
$admin_password = 'password123'; 

if (isset($_GET['logout'])) {
    session_destroy(); 
    header("Location: vulnerable_admin.php"); 
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "debate";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username']; 
    $password = $_POST['password']; 

    $query = "SELECT * FROM User WHERE Username = '$username' AND Password = '$password'"; 

    $result = $conn->query($query);  
    
    if ($result && $result->num_rows > 0) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error_message = "Invalid username or password.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_round'])) {
    $round = $_POST['round'];
    $date_time = $_POST['datetime'];
    $style_id = $_POST['style'];
    $room_id = $_POST['room'];
    $judge_id = $_POST['judge'];
    $status = 1; 
    $students = isset($_POST['students']) ? $_POST['students'] : [];
    
    $insert_round_sql = "
        INSERT INTO Pairing (Round, DateTime, StyleID, RoomID, JudgeID, Status)
        VALUES ('$round', '$date_time', '$style_id', '$room_id', '$judge_id', '$status')";
    
    if ($conn->query($insert_round_sql) === TRUE) {
        $pairing_id = $conn->insert_id;
    
        if (!empty($students)) {
            foreach ($students as $student_id) {
                $insert_pairing_assign_sql = "
                    INSERT INTO PairingAssign (StudentID, PairingID)
                    VALUES ('$student_id', '$pairing_id')";
                if ($conn->query($insert_pairing_assign_sql) === TRUE) {
                    echo "<p>Student with ID $student_id assigned to pairing $pairing_id successfully!</p>";
                } else {
                    echo "<p>Error assigning student $student_id: " . $conn->error . "</p>";
                }
            }
        }

        echo "<p>New round added successfully!</p>";
    } else {
        echo "<p>Error: " . $conn->error . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        .login-panel, .form-container {
            padding: 20px;
        }
        input[type="text"], input[type="password"], input[type="datetime-local"], input[type="number"], input[type="checkbox"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        input[type="submit"], button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover, button:hover {
            background-color: #0056b3;
        }
        .checkbox-group label {
            display: block;
        }
        .logout {
            display: inline-block;
            margin-top: 20px;
        }
    </style>
    <!--Delete later! Emergency creds are admin:password123--> 
</head>
<body>

<div class="container">
    <?php
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
        <div class="login-panel">
            <h1>Admin Login</h1>
            <?php
            if (!empty($error_message)) {
                echo "<p style='color:red;'>$error_message</p>";
            }
            ?>
            <form method="POST">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <input type="submit" value="Login">
            </form>
        </div>
    <?php
    } else {
    ?>
        <div class="form-container">
            <h2>Add New Round</h2>
            <form action="vulnerable_admin.php" method="POST">
                <label for="round">Round Number:</label>
                <input type="number" id="round" name="round" required>

                <label for="datetime">Date & Time:</label>
                <input type="datetime-local" id="datetime" name="datetime" required>

                <label for="room">Room:</label>
                <input type="text" id="room" name="room" required>

                <label for="judge">Judge:</label>
                <input type="text" id="judge" name="judge" required>

                <label for="style">Debate Style:</label>
                <input type="text" id="style" name="style" required>

                <label>Select Students:</label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="students[]" value="1"> Student 1
                    </label><br>
                    <label>
                        <input type="checkbox" name="students[]" value="2"> Student 2
                    </label><br>
                </div>
                
                <input type="submit" name="add_round" value="Add Round">
            </form>
        </div>

        <form method="GET" style="display: inline;">
            <button type="submit" name="logout" class="logout">Logout</button>
        </form>
    <?php
    }
    ?>
</div>

</body>
</html>

<?php
$conn->close();
?>
