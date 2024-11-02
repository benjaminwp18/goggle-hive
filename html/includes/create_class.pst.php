<?php
	function sendBack($value, $name) {
		header('Location: ../my_classes.php?createReturn=' . urlencode($value) . '&createName=' . urlencode($name));
		exit();
	}

	session_start();
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] == TRUE && isset($_POST['submit']) && isset($_POST['name']) && isset($_POST['authToken'])
		&& $_SESSION['authToken'] == $_POST['authToken']) {
		$name = $_POST['name'];
		
		if (empty($name)) { // Check for empty fields
			sendBack('empty', '');
		}
		else if (strlen($name) >= 40 || strlen($name) <= 4 || preg_match('/[^\\w\\d\\s!@\\/$&()\\-+"\':,?]/', $name) === 1 || preg_match('/[^\\w\\d\\s!@\\/$&()\\-+"\':,?]/', $name) === FALSE) {
			// Checked for wrong code length, illegal chars in code, or if regexing generates scary error
			sendBack('badName', $name);
		}
		else {
			require_once('db_connect.inc.php');
			try {
				$codes = [];
				$ids = [];
				foreach ($link->query('SELECT `join_code`, `id` FROM `classes`') as $row) {
					$codes[] = $row['join_code'];
					$ids[] = $row['id'];
				}
				
				$code = substr(bin2hex(random_bytes(4)), 0, -1);  // random alphanumeric string with length 7
				
				// Make sure $code is not already in use
				while (in_array($code, $codes, TRUE)) {
					$code = substr(bin2hex(random_bytes(4)), 0, -1);
				}
				
				// Create class entry in DB
				$stmt = $link->prepare('INSERT INTO `classes` (`name`, `join_code`) VALUES (:name, :code)');
				$stmt->execute(['name' => $name, 'code' => $code]);
				
				// Add the teacher to class
				$stmt = $link->prepare('INSERT INTO `accounts_classes` (`account_id`, `class_id`) VALUES (:aid, :cid)');
				$stmt->execute(['aid' => $_SESSION['id'], 'cid' => max($ids) + 1]);
				$stmt = NULL;
				
				// Add every admin to class
				$stmt = $link->prepare('INSERT INTO `accounts_classes` (`account_id`, `class_id`) VALUES (:aid, :cid)');
				foreach ($link->query('SELECT `id` FROM `accounts` WHERE `type` = "admin"') as $account) {
					if ($account['id'] !== $_SESSION['id'])
						$stmt->execute(['aid' => $account['id'], 'cid' => max($ids) + 1]);
				}
				
				header('Location: ../my_classes.php');
			}
			catch (PDOException $e) {
				sendBack('error', $name);
			}
		}
	}
	else {
		header('Location: ../login.php');
		exit();
	}