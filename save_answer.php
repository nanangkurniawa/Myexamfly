<?php
session_start();
if (isset($_POST['question_id'], $_POST['selected_option'])) {
    $questionId = $_POST['question_id'];
    $selectedOption = $_POST['selected_option'];

    if (!isset($_SESSION['user_answers'])) {
        $_SESSION['user_answers'] = [];
    }

    $_SESSION['user_answers'][$questionId] = $selectedOption;
    echo "Answer saved";
} else {
    echo "Invalid data";
}
?>
