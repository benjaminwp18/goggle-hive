<?php
	session_start();
	
	function redirect($location) { // Relative path from perspective of the file which queried this file
		echo json_encode(array('redirect' => $location));
		exit();
	}
	
	function error($content) {
		echo json_encode(array('success' => FALSE, 'content' => $content));
		exit();
	}
	
	function success($id, $content) {
		echo json_encode(array('success' => TRUE, 'id' => urlencode($id), 'content' => $content));
		exit();
	}
	
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != TRUE || !isset($_POST['authToken']) || $_SESSION['authToken'] != $_POST['authToken']) {
		redirect('login.php');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name']) && isset($_POST['type']) && isset($_POST['classId']) && preg_match('/[^0-9]/', $_POST['classId']) !== 1 && preg_match('/[^0-9]/', $_POST['classId']) !== FALSE) {
		require('db_connect.inc.php');
		
		try {
			// Check that class is accessible
			$stmt = $link->prepare('SELECT COUNT(`class_id`) FROM `accounts_classes` WHERE `class_id` = :cid AND `account_id` = :aid');
			$stmt->execute(['cid' => $_POST['classId'], 'aid' => $_SESSION['id']]);
			
			if ($stmt->fetchColumn(0) != 1) {
				error('Class is inaccessible or does not exist.');
			}
			else { 
				// If within a project...
				if (isset($_POST['projectId']) && preg_match('/[^0-9]/', $_POST['projectId']) !== 1 && preg_match('/[^0-9]/', $_POST['projectId']) !== FALSE) {
					// ...make sure we're creating a folder
					if ($_POST['type'] != 'folder') {
						error('You can\'t create that here.');
					}
					else {
						$stmt = $link->prepare('SELECT COUNT(`id`) FROM `projects` p INNER JOIN `accounts_projects` ap ON p.id = ap.project_id AND p.id = :pid AND ap.account_id = :aid AND p.class_id = :cid');
						$stmt->execute(['pid' => $_POST['projectId'], 'aid' => $_SESSION['id'], 'cid' => $_POST['classId']]);
						
						if ($stmt->fetchColumn(0) != 1) {
							error('Project is inaccessible or does not exist.');
						}
						else {
							if (isset($_POST['folderId'])) {
								if (preg_match('/[^0-9]/', $_POST['folderId']) === 1 || preg_match('/[^0-9]/', $_POST['folderId']) === FALSE) {
									error('Bad location data.');
								}
								else {
									$stmt = NULL;
									$stmt = $link->prepare('SELECT COUNT(`id`) FROM `files` WHERE `id` = :fid AND `project_id` = :pid AND `type` = "folder"');
									$stmt->execute(['fid' => $_POST['folderId'], 'pid' => $_POST['projectId']]);
									
									if ($stmt->fetchColumn(0) != 1) {
										error('Parent folder is inaccessible or does not exist.');
									}
								}
							}
							
							$stmt = NULL;
							
							$link->query($startTrans); // Start a transaction to INSERT folder data and return folder id
							
							$stmt = $link->prepare('INSERT INTO `files` (`name`, `project_id`, `class_id`, `parent_id`, `type`) VALUES (:name, :pid, :cid, :paid, "folder")');
							$stmt->execute(['name' => $_POST['name'], 'pid' => $_POST['projectId'], 'cid' => $_POST['classId'], 'paid' => isset($_POST['folderId']) ? $_POST['folderId'] : NULL]);
							$stmt = NULL;
							
							$stmt = 'SELECT MAX(`id`) AS `id` FROM `files` WHERE `type` = "folder"';
							foreach ($link->query($stmt) as $row) { // Get project id
								$folderId = $row['id'];
							}
							
							$link->query($commitTrans);
							
							success($folderId, 'Folder successfully created.');
						}
					}
				}
				else {
					if ($_POST['type'] != 'project') {
						error('You cannot create that here.');
					}
					else {
					
						$stmt = NULL;
						
						// Get permissions for project - currently inherited from parent class - could be expanded in the future to an editable, project-by-project sharing system.
						$setProjPerms = $link->prepare('INSERT INTO `accounts_projects` (`account_id`, `project_id`) VALUES (:aid, :pid)');
						$getClassPerms = $link->prepare('SELECT `account_id` FROM `accounts_classes` WHERE `class_id` = :cid');
						$getClassPerms->execute(['cid' => $_POST['classId']]);
						$classPerms = $getClassPerms->fetchAll(PDO::FETCH_COLUMN, 0);  // Get all zeroth-column values
						
						$link->query($startTrans); // Start a transaction to INSERT project data and INSERT project permissions data
						
						$stmt = $link->prepare('INSERT INTO `projects` (`name`, `class_id`) VALUES (:name, :cid)'); // INSERT project data
						$stmt->execute(['name' => $_POST['name'], 'cid' => $_POST['classId']]);
						$stmt = NULL;
						
						$stmt = 'SELECT MAX(`id`) AS `id` FROM `projects`';
						foreach ($link->query($stmt) as $row) { // Get project id
							$projectId = $row['id'];
						}
						$stmt = NULL;
						
						foreach ($classPerms as $perm) {
							$setProjPerms->execute(['aid' => $perm, 'pid' => $projectId]);
						}
						
						$link->query($commitTrans); // COMMIT the transaction
						
						success($projectId, 'Project successfully created.');
					}
				}
			}
		}
		catch (PDOException $e) {
			error('An error occurred. Please try again.');
		}
	}
	else {
		redirect('my_classes.php');
	}