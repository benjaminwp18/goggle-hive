<?php
	session_start();

	function error() {
		echo json_encode(array('success' => FALSE));
		exit();
	}
	
	function success($logout) {
		echo json_encode(array('success' => TRUE, 'logout' => $logout));
		exit();
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] == TRUE && isset($_POST['authToken']) && $_SESSION['authToken'] == $_POST['authToken']
		&& $_SESSION['type'] == 'admin' && isset($_POST['id']) && isset($_POST['typeLetter'])) {
	
		if (!in_array($_POST['typeLetter'], ['S', 'T', 'A']) || preg_match('/[^0-9]/', $_POST['id']) === 1 || preg_match('/[^0-9]/', $_POST['id']) === FALSE) {
			error();
		}
		else {
			require_once('db_connect.inc.php');
			try {
				$setType = $link->prepare('UPDATE `accounts` SET `type` = :type WHERE `id` = :id');
				$setType->execute(['id' => $_POST['id'], 'type' =>
					($_POST['typeLetter'] == 'A' ? 'admin' :
						($_POST['typeLetter'] == 'T' ? 'teacher' : 'student'))
				]);
				
				success($_POST['id'] == $_SESSION['id']);
			}	
			catch (PDOException $e) {
				error();
			}
		}
	}
	else {
		header('Location: ../login.php');
		exit();
	}