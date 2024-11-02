<?php
	session_start();
	
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != TRUE && isset($_POST['authToken']) && $_SESSION['authToken'] == $_POST['authToken']) {
		redirect('login.php');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$_SESSION['isMoving'] = FALSE;
	}