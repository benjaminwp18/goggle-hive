<?php
	session_start();

	function redirect($location) {  // Relative path from perspective of the file which queried this file
		echo json_encode(array('redirect' => $location));
		exit();
	}
	
	function error() {
		echo json_encode(array('success' => FALSE));
		exit();
	}
	
	function success() {
		echo json_encode(array('success' => TRUE));
		exit();
	}
	
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != TRUE || !isset($_POST['authToken']) || $_SESSION['authToken'] != $_POST['authToken']) {
		redirect('login.php');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['type']) && isset($_POST['items']) && $_POST['items'] != '' && preg_match('/[^0-9,]/', $_POST['items']) !== 1 && preg_match('/[^0-9],/', $_POST['items']) !== FALSE) {
		$_SESSION['downloadItems'] = $_POST['items'];
		$_SESSION['downloadType'] = $_POST['type'];
		success();
	}
	else error();