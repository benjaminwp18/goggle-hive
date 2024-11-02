<?php
	function sendBack($value, $code) {
		header('Location: ../my_classes.php?addReturn=' . urlencode($value) . '&addCode=' . urlencode($code));
		exit();
	}

	session_start();
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] == TRUE && isset($_POST['authToken']) && $_SESSION['authToken'] == $_POST['authToken'] && isset($_POST['submit']) && isset($_POST['code'])) {
		$code = $_POST['code'];
		
		if (empty($code)) { // Check for empty fields
			sendBack('empty', '');
		}
		else if (strlen($code) != 7 || preg_match('/[^a-z0-9]/', $code) === 1 || preg_match('/[^a-z0-9]/', $code) === FALSE) { // Check for wrong code length, illegal (non-[lowercase or digit]) chars in code, or if regexing generates scary error
			sendBack('badCode', '');
		}
		else {
			require_once('db_connect.inc.php');
			try {
				$stmt = $link->prepare('SELECT `id` FROM `classes` WHERE `join_code` = :code');
				$stmt->execute(['code' => $_POST['code']]);
				$ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch all values in first column
				$stmt = NULL;
				
				if (sizeOf($ids) != 1) { // Make sure code is associated with 1 class
					sendBack('noClasses', $code);
				}
				else {
					$stmt = $link->prepare('SELECT COUNT(`account_id`) FROM `accounts_classes` WHERE `account_id` = :aid AND `class_id` = :cid');
					$stmt->execute(['aid' => $_SESSION['id'], 'cid' => $ids[0]]);
					$result = $stmt->fetchColumn(0);
					$stmt = NULL;
					
					if ($result > 0) { // Make sure user isn't already in class
						sendBack('alreadyEnrolled', $code);
					}
					else {
						$stmt = $link->prepare('INSERT INTO `accounts_classes` (`account_id`, `class_id`) VALUES (:aid, :cid)');
						$stmt->execute(['aid' => $_SESSION['id'], 'cid' => $ids[0]]);
						$stmt = NULL;
						
						// Add account to all projects in the class
						$stmt = $link->prepare('SELECT `id` FROM `projects` WHERE `class_id` = :cid');
						$stmt->execute(['cid' => $ids[0]]);
						$projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
						$setProjPerm = $link->prepare('INSERT INTO `accounts_projects` (account_id, project_id) VALUES (:aid, :pid)');
						foreach ($projectIds as $proj) {
							$setProjPerm->execute(['aid' => $_SESSION['id'], 'pid' => $proj]);
						}
						
						header('Location: ../my_classes.php');
					}
				}
			}
			catch (PDOException $e) {
				sendBack('error', $code);
			}
		}
	}
	else {
		header('Location: ../login.php');
		exit();
	}