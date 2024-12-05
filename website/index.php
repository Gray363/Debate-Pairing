<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'debate';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$rounds_sql = "SELECT PairingID, DateTime, Round FROM Pairing WHERE Status=1";
$rounds_result = $conn->query($rounds_sql);

$selected_round = isset($_GET['round_id']) ? $_GET['round_id'] : null;

if ($selected_round) {
    $details_sql = "SELECT * FROM Pairing WHERE PairingID = $selected_round";
    $details_result = $conn->query($details_sql);
    $round_details = $details_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debate Rounds</title>
    <style>
        body {
            display: flex;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 30%;
            border-right: 1px solid #ccc;
            padding: 20px;
            box-sizing: border-box;
            background-color: #f9f9f9;
            overflow-y: auto;
        }

        .content {
            width: 70%;
            padding: 20px;
            box-sizing: border-box;
            background-color: #fff;
            overflow-y: auto;
        }

        .round-block {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            background-color: #fff;
            transition: background-color 0.3s;
        }

        .round-block:hover {
            background-color: #f0f0f0;
        }

        .round-block.active {
            background-color: #e0e0e0;
            font-weight: bold;
        }

        h3, h4, h5 {
            color: #333;
        }

        p {
            margin: 5px 0;
        }

        a {
            text-decoration: none;
            color: blue;
        }

        a:hover {
            text-decoration: underline;
        }

        .admin-button {
        display: inline-block;
        background-color: #4CAF50;
        color: white; 
        padding: 10px 20px;
        text-align: center;
        border-radius: 5px;
        font-size: 16px;
        text-decoration: none;
        position: fixed;
        bottom: 20px;
        left: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s, transform 0.3s;
        }

        .admin-button:hover {
            background-color: #45a049; 
        }

        .admin-button:focus {
            outline: none;
        }

    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const roundBlocks = document.querySelectorAll(".round-block");

            roundBlocks.forEach(block => {
                block.addEventListener("click", function (event) {
                    event.preventDefault();
                    const roundId = this.getAttribute("data-id");

                    roundBlocks.forEach(b => b.classList.remove("active"));
                    this.classList.add("active");

                    fetch(`request.php?round_id=${roundId}`)
                        .then(response => response.text())
                        .then(html => {
                            document.querySelector(".content").innerHTML = html;
                        })
                        .catch(error => {
                            console.error("Error fetching round details:", error);
                        });
                });
            });
        });
    </script>
    </head>
    <body>

    <div class="sidebar">
        <h3>Active Debate Rounds</h3>
        <?php
        $sql = "
            SELECT 
                p.PairingID, p.DateTime, p.Round, p.RoomID, p.StyleID, 
                r.Name, r.RoomNumber 
            FROM Pairing p
            LEFT JOIN Room r ON p.RoomID = r.RoomID
            WHERE p.Status = 1
            ORDER BY p.StyleID, p.DateTime, p.Round";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $grouped_data = [
                1 => [],
                2 => [] 
            ];

            while ($row = $result->fetch_assoc()) {
                $datetime = new DateTime($row['DateTime']);
                $date_key = $datetime->format('F j, Y'); 
                $time = $datetime->format('g:i A');
                $style_id = $row['StyleID'];

                if (!isset($grouped_data[$style_id][$date_key])) {
                    $grouped_data[$style_id][$date_key] = [];
                }
                if (!isset($grouped_data[$style_id][$date_key][$row['Round']])) {
                    $grouped_data[$style_id][$date_key][$row['Round']] = [
                        'Time' => $time,
                        'Debates' => []
                    ];
                }

                $grouped_data[$style_id][$date_key][$row['Round']]['Debates'][] = [
                    'PairingID' => $row['PairingID'],
                    'RoomID' => $row['RoomID'],
                    'Name' => $row['Name'] ?: $row['RoomNumber'] 
                ];
            }

            $styles = [
                1 => 'Lincoln Douglas',
                2 => 'Team Policy'
            ];


            foreach ($grouped_data as $style_id => $dates) {
                if (empty($dates)) continue; 

                echo "<h4>" . $styles[$style_id] . "</h4>"; 

                foreach ($dates as $date => $rounds) {
                    echo "<h5>$date</h5>"; 

                    foreach ($rounds as $round => $data) {
                        $round_time = $data['Time'];
                        echo "<h5>Round $round at $round_time</h5>";

                        foreach ($data['Debates'] as $debate) {
                            echo "<div class='round-block' data-id='" . $debate['PairingID'] . "'>
                                    <a href='?round_id=" . $debate['PairingID'] . "'>
                                        Room: " . $debate['Name'] . "
                                    </a>
                                </div>";
                        }
                    }
                }
            }
            
        } else {
            echo "<p>No active rounds.</p>";
        }
        ?>
        <br>
        <a href="admin.php" class="admin-button">Admin Panel</a>
    </div>

    <div class="content">
        <h3>Round Details</h3>
        <p>Select a round to view details.</p>
    </div>

    </body>
</html>