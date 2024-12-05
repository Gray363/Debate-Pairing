<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "debate";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = '';

if (isset($_POST['firstname']) && isset($_POST['lastname'])) {
    $first_name = $_POST['firstname']; 
    $last_name = $_POST['lastname'];

    $query = "SELECT * FROM Student WHERE FirstName = '$first_name' AND LastName = '$last_name'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Student: " . htmlspecialchars($row['FirstName']) . " " . htmlspecialchars($row['LastName']) . "<br>";
        }
    } else {
        $error_message = "Invalid student name.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Injection Demonstration</title>
</head>
<body>
    <h1>ACME Secure University Login</h1>

    <form method="POST" action="vulnerable_index.php">
        <label for="firstname">First Name:</label>
        <input type="text" name="firstname" id="firstname" required><br><br>
        
        <label for="lastname">Last Name:</label>
        <input type="text" name="lastname" id="lastname" required><br><br>
        
        <input type="submit" value="Submit">
    </form>

    <?php
    if ($error_message) {
        echo "<p style='color:red;'>$error_message</p>";
    }
    ?>

</body>
</html>

<?php
$conn->close();
?>