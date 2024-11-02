<?php
	session_start();
	if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === TRUE) {
		session_destroy();
		header('Location: index.php');
	}
	else header('Location: login.php');
	exit();
?>