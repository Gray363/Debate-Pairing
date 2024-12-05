<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'debate';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pairing_id = intval($_POST['pairing_id']);
    $current_status = intval($_POST['current_status']);
    $new_status = ($current_status === 1) ? 0 : 1;

    $update_sql = "UPDATE Pairing SET Status = $new_status WHERE PairingID = $pairing_id";
    if ($conn->query($update_sql) === TRUE) {
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
