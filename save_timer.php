<?php
session_start();
if (isset($_POST['time_remaining'])) {
    $_SESSION['time_remaining'] = intval($_POST['time_remaining']);
    echo "Time updated";
} else {
    echo "No data received";
}
?>
