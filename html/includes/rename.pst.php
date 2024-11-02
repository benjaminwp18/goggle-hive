<?php
	session_start();
	
	function redirect($location) { // Relative path from perspective of the file which queried this file
		echo json_encode(array('redirect' => $location));
		exit();
	}
	
	function display($type, $content) {
		echo json_encode(array('type' => $type, 'content' => $content));
		exit();
	}
	
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != TRUE || !isset($_POST['authToken']) || $_SESSION['authToken'] != $_POST['authToken']) {
		redirect('login.php');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name']) && isset($_POST['type']) && isset($_POST['itemId']) && preg_match('/[^0-9]/', $_POST['itemId']) !== 1 && preg_match('/[^0-9]/', $_POST['itemId']) !== FALSE) {
		require_once('db_connect.inc.php');
		
		try {
			if ($_POST['type'] == 'project') {
				$stmt = $link->prepare('SELECT COUNT(project_id) FROM accounts_projects WHERE project_id = :pid AND account_id = :aid');
				$stmt->execute(['pid' => $_POST['itemId'], 'aid' => $_SESSION['id']]);
				
				if ($stmt->fetchColumn(0) != 1) {
					display('error', 'That project is inaccessible or does not exist.');
				}
				else {
					$stmt = $link->prepare('UPDATE projects SET name = :name WHERE id = :pid');
					$stmt->execute(['name' => $_POST['name'], 'pid' => $_POST['itemId']]);
					
					display('success', 'Rename successful.');
				}
			}
			else {
				$stmt = $link->prepare('SELECT COUNT(ap.project_id) FROM accounts_projects ap INNER JOIN files f ON ap.project_id = f.project_id AND f.id = :fid AND ap.account_id = :aid');
				$stmt->execute(['fid' => $_POST['itemId'], 'aid' => $_SESSION['id']]);
				
				if ($stmt->fetchColumn(0) != 1) {
					display('error', 'That file/folder is inaccessible or does not exist.');
				}
				else {
					$stmt = $link->prepare('UPDATE files SET name = :name WHERE id = :fid');
					$stmt->execute(['name' => $_POST['name'], 'fid' => $_POST['itemId']]);
					
					display('success', 'Rename successful.');
				}
			}
		}
		catch (PDOException $e) {
			display('error', 'An error occurred. Please try again.');
		}
	}
	else {
		redirect('my_classes.php');
	}