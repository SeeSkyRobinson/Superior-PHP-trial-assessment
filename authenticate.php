<?php
// Database connection details
$servername = "104.210.70.61";
$username = "TestDev";
$password = "TestDev";
$dbname = "testdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare and bind
$stmt = $conn->prepare("SELECT * FROM user_info WHERE user_name = ?");
$stmt->bind_param("s", $input_username);

// Set parameters and execute
$input_username = $_POST['username'];
$input_password = $_POST['password'];
$stmt->execute();

// Store result
$stmt->store_result();

// Initialize user_id as NULL
$user_id = NULL;

// Check if the user exists
if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id, $username, $password);
    $stmt->fetch();

    // Check if the password matches
    if ($input_password == $password) {
        // Insert a new successful login
        $current_time = date("Y-m-d H:i:s");
        $stmt2 = $conn->prepare("INSERT INTO login_attempts (login_user_id, success, login_date_time) VALUES (?, 1, ?)");
        $stmt2->bind_param("is", $user_id, $current_time);
        $stmt2->execute();
        $stmt2->close();

        // Get the updated successful logins and last login time for the user
        $query = "SELECT user_name, MAX(login_date_time) as last_login_time, SUM(success) as success FROM login_attempts INNER JOIN user_info ON login_attempts.login_user_id = user_info.user_id WHERE login_user_id = ? AND success = 1";
        $stmt3 = $conn->prepare($query);
        $stmt3->bind_param("i", $user_id);
        $stmt3->execute();

        $result = $stmt3->get_result();
        echo "Past successful logins:<br>";
        while ($row = $result->fetch_assoc()) {
            echo "Username: " . $row["user_name"] . " - Last Login Time: " . $row["last_login_time"] . " - Success: " . $row["success"] . "<br>";
        }
        $stmt3->close();

    } else {
        // Insert a new unsuccessful login (wrong password)
        $current_time = date("Y-m-d H:i:s");
        $stmt2 = $conn->prepare("INSERT INTO login_attempts (login_user_id, success, login_date_time) VALUES (?, 0, ?)");
        $stmt2->bind_param("is", $user_id, $current_time);
        $stmt2->execute();
        $stmt2->close();

        echo "Invalid password.";
    }
} else {
    // Insert a new unsuccessful login (unknown user)
    $current_time = date("Y-m-d H:i:s");
    $stmt2 = $conn->prepare("INSERT INTO login_attempts (login_user_id, success, login_date_time) VALUES (NULL, 0, ?)");
    $stmt2->bind_param("s", $current_time);
    $stmt2->execute();
    $stmt2->close();

    echo "Invalid username or password.";
}

// Close statement and connection
$stmt->close();
$conn->close();
?>