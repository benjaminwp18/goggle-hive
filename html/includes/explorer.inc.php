<?php
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] === FALSE) {
		header('Location: login.php');
		exit();
	}
	else if (!isset($_GET['classId']) || preg_match('/[^0-9]/', $_GET['classId']) === 1 || preg_match('/[^0-9]/', $_GET['classId']) === FALSE) {
		header('Location: my_classes.php');
		exit();
	}
	else {
		$classId = $_GET['classId'];
	
		require_once('includes/db_connect.inc.php');
		try {
			$stmt = $link->prepare('SELECT name FROM classes INNER JOIN accounts_classes ON accounts_classes.class_id = classes.id AND accounts_classes.account_id = :aid AND classes.id = :cid');
			$stmt->execute(['aid' => $_SESSION['id'], 'cid' => $classId]);
			$results = $stmt->fetchAll();
			$stmt = NULL;
			
			if (sizeof($results) != 1) {
				header('Location: my_classes.php');
				exit();
			}
			else {
				$title = $results[0]['name'];
				$className = $title;
			}
		}
		catch (PDOException $e) {
			header('Location: my_classes.php');
			exit();
		}
		
		if (isset($_GET['projectId']) && preg_match('/[^0-9]/', $_GET['projectId']) !== 1 && preg_match('/[^0-9]/', $_GET['projectId']) !== FALSE) {
			$projectId = $_GET['projectId'];
			
			try {
				$stmt = $link->prepare('SELECT name FROM projects INNER JOIN accounts_projects ON accounts_projects.project_id = projects.id AND accounts_projects.account_id = :aid AND projects.id = :pid');
				$stmt->execute(['aid' => $_SESSION['id'], 'pid' => $projectId]);
				$results = $stmt->fetchAll();
				$stmt = NULL;
				
				if (sizeof($results) != 1) {
					header('Location: file_viewer.php?classId=' . urlencode($classId));
					exit();
				}
				else {
					$title = $results[0]['name'];
					$projectName = $title;
					
					if (isset($_GET['folderId']) && preg_match('/[^0-9]/', $_GET['folderId']) !== 1 && preg_match('/[^0-9]/', $_GET['folderId']) !== FALSE) {
						$folderId = $_GET['folderId'];
					
						$stmt = $link->prepare('SELECT name FROM files WHERE id = :fid AND files.type = "folder" AND project_id = :pid AND class_id = :cid');
						$stmt->execute(['fid' => $folderId, 'pid' => $projectId, 'cid' => $classId]);
						$results = $stmt->fetchAll();
						$stmt = NULL;
						
						if (sizeof($results) != 1) {
							header('Location: file_viewer.php?projectId=' . urlencode($projectId) . '&classId=' . urlencode($classId));
							exit();
						}
						else {
							$title = $results[0]['name'];
							$folderName = $title;
						}
					}
				}
			}
			catch (PDOException $e) {
				header('Location: file_viewer.php?classId=' . urlencode($classId));
				exit();
			}
		}
		
		
	}