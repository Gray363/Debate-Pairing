<?php

session_start();

if (isset($_GET['logout'])) {
    session_destroy(); 
    $_SESSION['admin_logged_in'] = false;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['admin_logged_in'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $host = 'localhost';
    $db_username = 'root';
    $db_password = '';
    $db_name = 'debate';

    $conn = new mysqli($host, $db_username, $db_password, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT Password FROM User WHERE Username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $hashed_password = $row['Password'];

            if (password_verify($password, $hashed_password)) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['username'] = $username; 
                header('Location: admin.php');
                exit;
            } else {
                $error_message = "Invalid credentials. Please try again.";
            }
        } else {
            $error_message = "Invalid credentials. Please try again.";
        }

        $stmt->close();
    } else {
        $error_message = "Something went wrong. Please try again later.";
    }

    $conn->close();
}



if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        table th {
            background-color: #007bff;
            color: white;
        }
        .section-title {
            font-size: 1.5em;
            margin-top: 30px;
            color: #333;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: red;
            font-weight: bold;
        }

        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }

        .form-container h2 {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #333;
        }

        .form-container label {
            font-size: 1em;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .form-container select,
        .form-container input[type="text"],
        .form-container input[type="datetime-local"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }

        .form-container input[type="text"]:focus,
        .form-container select:focus,
        .form-container input[type="datetime-local"]:focus {
            border-color: #007bff;
            outline: none;
        }

        .form-container input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1em;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .form-container input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const judgeElements = document.querySelectorAll(".judge-name");
            const studentElements = document.querySelectorAll(".student-name");

            function showDetails(type, id) {
                fetch(`${type}details.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const modal = document.getElementById('modal');
                            const modalBody = document.getElementById('modal-body');
                            modalBody.innerHTML = `
                                <h4>${type.charAt(0).toUpperCase() + type.slice(1)} Details</h4>
                                <p><strong>Full Name:</strong> ${data.details.FullName}</p>
                                <p><strong>Children:</strong> ${data.details.Children}</p>
                            `;
                            modal.style.display = 'block';  
                        } else {
                            alert(`Error fetching ${type} details: ${data.error}`);
                        }
                    })
                    .catch(error => console.error('Error fetching data:', error));
            }


            judgeElements.forEach(judge => {
                judge.addEventListener("click", function () {
                    const judgeId = this.getAttribute("data-id");
                    showDetails('judge', judgeId);  
                });
            });

            studentElements.forEach(student => {
                student.addEventListener("click", function () {
                    const studentId = this.getAttribute("data-id");
                    showDetails('student', studentId); 
                });
            });
        });
    </script>
</head>
<body>
    <h1>Admin Control Panel</h1>

    <?php
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'debate';

    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $rounds_sql = "
        SELECT 
            p.PairingID, p.DateTime, p.Round, p.Status, r.Name as RoomName, r.RoomNumber, 
            j.FirstName AS JudgeFirstName, j.LastName AS JudgeLastName, s.StyleID, j.JudgeID
        FROM Pairing p
        JOIN Room r ON p.RoomID = r.RoomID
        JOIN Judge j ON p.JudgeID = j.JudgeID
        JOIN DebateStyle s ON p.StyleID = s.StyleID
        ORDER BY s.StyleID, p.Round";
    $rounds_result = $conn->query($rounds_sql);

    $rounds_by_style = ['1' => [], '2' => []];
    while ($row = $rounds_result->fetch_assoc()) {
        $rounds_by_style[$row['StyleID']][] = $row;
    }

    $styles = [
        '1' => 'Lincoln Douglas',
        '2' => 'Team Policy'
    ];

    foreach ($styles as $style_id => $style_name) {
        echo "<div class='section-title'>$style_name</div>";
        if (!empty($rounds_by_style[$style_id])) {
            echo "<table>
                    <tr>
                        <th>Round</th>
                        <th>Date & Time</th>
                        <th>Room</th>
                        <th>Judge</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>";
            foreach ($rounds_by_style[$style_id] as $round) {
                $pairing_id = $round['PairingID'];
                $status_label = ($round['Status'] == 1) ? 'Active' : 'Inactive';
                $status_class = ($round['Status'] == 1) ? 'status-active' : 'status-inactive';

                $students_sql = "
                    SELECT s.StudentID, s.FirstName, s.LastName 
                    FROM PairingAssign pa 
                    JOIN Student s ON pa.StudentID = s.StudentID 
                    WHERE pa.PairingID = $pairing_id";
                $students_result = $conn->query($students_sql);
                $students = [];
                while ($student_row = $students_result->fetch_assoc()) {
                    $students[] = [
                        'StudentID' => $student_row['StudentID'],
                        'FullName' => $student_row['FirstName'] . ' ' . $student_row['LastName']
                    ];
                }

                echo "<tr>
                        <td>Round " . $round['Round'] . "</td>
                        <td>" . $round['DateTime'] . "</td>
                        <td>" . ($round['RoomName'] ?: $round['RoomNumber']) . "</td>
                        <td>
                            <a href='#' class='judge-name' data-id='" . $round['JudgeID'] . "'>
                                " . $round['JudgeFirstName'] . " " . $round['JudgeLastName'] . "
                            </a>
                        </td>
                        <td>";
                foreach ($students as $index => $student) {
                    echo "<a href='#' class='student-name' data-id='{$student['StudentID']}'>
                            {$student['FullName']}
                        </a>";
                    if ($index < count($students) - 1) {
                        echo ", ";
                    }
                }
                echo "</td>
                        <td id='status-$pairing_id' class='$status_class'>$status_label</td>
                        <td>
                            <button 
                                class='toggle-status' 
                                id='button-$pairing_id'
                                data-pairing-id='$pairing_id'
                                data-current-status='" . $round['Status'] . "'>
                                " . ($round['Status'] == 1 ? 'Deactivate' : 'Activate') . "
                            </button>
                            </button>
                            <!-- Edit Button -->
                            <a href='edit_round.php?pairing_id=$pairing_id'>
                                <button class='edit-round'>Edit</button>
                            </a>
                        </td>
                    </tr>";
                
            }
            echo "</table>";
        } else {
            echo "<p>No rounds found for $style_name.</p>";
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

        $insert_round_sql = "INSERT INTO Pairing (Round, DateTime, StyleID, RoomID, JudgeID, Status) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_round_sql);
        $stmt->bind_param("isisii", $round, $date_time, $style_id, $room_id, $judge_id, $status);

        if ($stmt->execute()) {
            $pairing_id = $stmt->insert_id;

            if (!empty($students)) {
                foreach ($students as $student_id) {
                    $insert_pairing_assign_sql = "INSERT INTO PairingAssign (StudentID, PairingID) VALUES (?, ?)";
                    $stmt2 = $conn->prepare($insert_pairing_assign_sql);
                    $stmt2->bind_param("ii", $student_id, $pairing_id);

                    if ($stmt2->execute()) {
                        echo "<p>Student with ID $student_id assigned to pairing $pairing_id successfully!</p>";
                    } else {
                        echo "<p>Error assigning student $student_id: " . $stmt2->error . "</p>";
                    }
                }
            }

            echo "<p>New round added successfully!</p>";
        } else {
            echo "<p>Error: " . $stmt->error . "</p>";
        }

    }

    $rooms_sql = "SELECT RoomID, RoomNumber FROM Room";
    $rooms_result = $conn->query($rooms_sql);

    $judges_sql = "SELECT JudgeID, FirstName, LastName FROM Judge";
    $judges_result = $conn->query($judges_sql);

    $style_id = isset($_POST['style']) ? $_POST['style'] : null;
    $students_sql = "
        SELECT StudentID, FirstName, LastName
        FROM Student" . ($style_id ? " WHERE StyleID = $style_id" : "");
    $students_result = $conn->query($students_sql);

    ?>

    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>


    <div class="form-container">
        <h2>Add New Round</h2>
        <form action="add_round.php" method="POST">
            <label for="round_number">Round Number:</label>
                <select id="round_number" name="round_number" required>
                    <option value="">Select a Round</option>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == $round) ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select><br>

            <label for="datetime">Date & Time:</label>
            <input type="datetime-local" id="datetime" name="datetime" required>

            <label for="room">Room:</label>
            <select id="room" name="room" required>
                <?php
                while ($room = $rooms_result->fetch_assoc()) {
                    echo "<option value='{$room['RoomID']}'>{$room['RoomID']}</option>";
                }
                ?>
            </select>

            <label for="judge">Judge:</label>
            <select id="judge" name="judge" required>
                <?php
                while ($judge = $judges_result->fetch_assoc()) {
                    echo "<option value='{$judge['JudgeID']}'>
                    {$judge['FirstName']} {$judge['LastName']}
                    </option>";
                }
                ?>
            </select>

            <label for="style">Debate Style:</label>
            <select id="style" name="style" required>
                <option value="1" <?php echo (isset($_POST['style']) && $_POST['style'] == 1) ? 'selected' : ''; ?>>Lincoln Douglas</option>
                <option value="2" <?php echo (isset($_POST['style']) && $_POST['style'] == 2) ? 'selected' : ''; ?>>Team Policy</option>
            </select>

            <label>Select Students:</label>
            <div class="checkbox-group">
                <?php
                if (isset($students_result) && $students_result->num_rows > 0) {
                    while ($student = $students_result->fetch_assoc()) {
                        echo "<label>
                                <input type='checkbox' name='students[]' value='{$student['StudentID']}'>
                                {$student['FirstName']} {$student['LastName']}
                            </label><br>";
                    }
                } else {
                    echo "<p>No students available for the selected debate style.</p>";
                }
                ?>
            </div>
            
            <input type="submit" name="add_round" value="Add Round">
        </form>
    </div>

<?php
    $conn->close();
?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modal');
            const modalBody = document.getElementById('modal-body');
            const closeBtn = document.getElementsByClassName('close-btn')[0];

            const links = document.querySelectorAll('.judge-name, .student-name');
            
            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const type = this.classList.contains('judge-name') ? 'judge' : 'student';

                    fetch(type + '_details.php?id=' + id)
                        .then(response => response.text())
                        .then(data => {
                            modalBody.innerHTML = data;
                            modal.style.display = 'block';
                        })
                        .catch(error => {
                            modalBody.innerHTML = '<p>Error loading details.</p>';
                        });
                });
            });

            closeBtn.addEventListener('click', function () {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function (event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });

        document.getElementById('style').addEventListener('change', function () {
            const styleId = this.value;
            fetch(`student_details.php?style=${styleId}`)
                .then(response => response.json())
                .then(data => {
                    const studentDiv = document.querySelector('.checkbox-group');
                    studentDiv.innerHTML = ''; 
                    if (data.students.length > 0) {
                        data.students.forEach(student => {
                            const label = document.createElement('label');
                            label.innerHTML = `<input type='checkbox' name='students[]' value='${student.StudentID}'> ${student.FirstName} ${student.LastName}`;
                            studentDiv.appendChild(label);
                        });
                    } else {
                        studentDiv.innerHTML = '<p>No students available for the selected debate style.</p>';
                    }
                });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const toggleButtons = document.querySelectorAll(".toggle-status");

            toggleButtons.forEach(button => {
                button.addEventListener("click", function (event) {
                    event.preventDefault();

                    const pairingId = this.getAttribute("data-pairing-id");
                    const currentStatus = this.getAttribute("data-current-status");

                    fetch('update_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `pairing_id=${pairingId}&current_status=${currentStatus}`,
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const newStatus = data.new_status;
                                const statusCell = document.querySelector(`#status-${pairingId}`);
                                const button = document.querySelector(`#button-${pairingId}`);

                                if (newStatus === 1) {
                                    statusCell.textContent = "Active";
                                    statusCell.className = "status-active";
                                    button.textContent = "Deactivate";
                                    button.setAttribute("data-current-status", "1");
                                } else {
                                    statusCell.textContent = "Inactive";
                                    statusCell.className = "status-inactive";
                                    button.textContent = "Activate";
                                    button.setAttribute("data-current-status", "0");
                                }
                            } else {
                                alert(`Error updating status: ${data.error}`);
                            }
                        })
                        .catch(error => console.error("Error:", error));
                });
            });
        });

        document.getElementById('style').addEventListener('change', function() {
            const styleId = this.value;

            fetch(`students_details.php?style_id=${styleId}`)
                .then(response => response.json())
                .then(data => {
                    const studentDiv = document.querySelector('.checkbox-group');
                    studentDiv.innerHTML = ''; 

                    if (data.students.length > 0) {
                        data.students.forEach(student => {
                            const label = document.createElement('label');
                            label.innerHTML = `
                                <input type="checkbox" name="students[]" value="${student.StudentID}">
                                ${student.FirstName} ${student.LastName}
                            `;
                            studentDiv.appendChild(label);
                        });
                    } else {
                        studentDiv.innerHTML = '<p>No students available for the selected debate style.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>

<form method="GET" style="display: inline;">
        <button type="submit" name="logout" class="logout">Logout</button>
</form>
<a href="index.php" class="back"><button>Back to Main Page</button></a>

</body>
</html>

    <?php
} else {
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
            height: 100vh; 
            display: flex; 
            justify-content: center;
            align-items: center;
        }

        .login-panel {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            width: 300px;
            text-align: center;
        }

        h1, h2 {
            text-align: center;
            color: #333;
        }

        input[type="text"], input[type="password"] {
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
    </head>
    <body>
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
    </body>
    </html>

    <?php
}
?>

