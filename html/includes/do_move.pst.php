<?php
	session_start();
		
	function redirect($location) { // Relative path from perspective of the file which queried this file
		echo json_encode(array('redirect' => $location));
		exit();
	}
	
	function error($message) {
		$_SESSION['isMoving'] = FALSE;
		echo json_encode(array('success' => FALSE, 'message' => $message));
		exit();
	}
	
	function success() {
		$_SESSION['isMoving'] = FALSE;
		echo json_encode(array('success' => TRUE));
		exit();
	}
	
	// Recursively navigates through all children of original $parent (id). error()s on seeing $illegalId in children, and updates children to have project = $newProject & class = $newClass, if they are not set to ''
	function filterFolderContents($parent, $newProject, $newClass, $illegalId, $link) {
		try {
			// Check for $illegalId
			$stmt = $link->prepare('SELECT COUNT(id) FROM files WHERE parent_id = :pid AND id = :iid');
			$stmt->execute(['pid' => $parent, 'iid' => $illegalId]);
			if ($stmt->fetchColumn(0) != 0) error(1);
			
			// Set class and/or project
			if ($newProject != '' && $newClass != '') {
				$stmt = $link->prepare('UPDATE files SET class_id = :cid, project_id = :pid WHERE parent_id = :paid');
				$stmt->execute(['cid' => $newClass, 'pid' => $newProject, 'paid' => $parent]);
			}
			else if ($newProject != '') {
				$stmt = $link->prepare('UPDATE files SET project_id = :pid WHERE parent_id = :paid');
				$stmt->execute(['pid' => $newProject, 'paid' => $parent]);
			}
			
			// Select & store in $results any folder children
			$stmt = $link->prepare('SELECT id FROM files WHERE parent_id = :pid AND type = "folder"');
			$stmt->execute(['pid' => $parent]);
			$results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
			
			// filterFolderContents() on $results
			foreach ($results as $resultId) filterFolderContents($resultId, $newProject, $newClass, $illegalId, $link);
		}
		catch (PDOException $a) {
			error($a);
		}
	}
	
	
	
	
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != TRUE || !isset($_POST['authToken']) || $_SESSION['authToken'] != $_POST['authToken']) {
		redirect('login.php');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['moveType']) && isset($_SESSION['moveContent']) && isset($_SESSION['moveParentId']) && isset($_SESSION['isMoving']) && $_SESSION['isMoving'] == TRUE && isset($_POST['classId']) && preg_match('/[^0-9]/', $_POST['classId']) !== 1 && preg_match('/[^0-9]/', $_POST['classId']) !== FALSE) {
		require_once('db_connect.inc.php');
		
		try {
			// Check if user has access to class
			$stmt = $link->prepare('SELECT COUNT(class_id) FROM accounts_classes WHERE class_id = :cid AND account_id = :aid');
			$stmt->execute(['cid' => $_POST['classId'], 'aid' => $_SESSION['id']]);
			if ($stmt->fetchColumn(0) != 1) error(3);
			
			// Move projects
			if ($_SESSION['moveType'] == 'projects') {  // ---------------------------------------------------------------------------------------- TODO: Check if user has access to projects?
				if ($_SESSION['moveParentId'] == $_POST['classId']) success();  // If projects are already located at new location
				else {
					$setItemClass = $link->prepare('UPDATE projects SET class_id = :cid WHERE id = :pid');
					$setChildrenClass = $link->prepare('UPDATE files SET class_id = :cid WHERE project_id = :pid');
					
					// Move each project
					foreach ($_SESSION['moveContent'] as $item) {
						$setItemClass->execute(['cid' => $_POST['classId'], 'pid' => $item]);
						$setChildrenClass->execute(['cid' => $_POST['classId'], 'pid' => $item]);
					}
					
					success();
				}
			}
			else if (isset($_POST['projectId']) && preg_match('/[^0-9]/', $_POST['projectId']) !== 1 && preg_match('/[^0-9]/', $_POST['projectId']) !== FALSE) {
				// Check if user has access to parent project & that parent project is in parent class
				$stmt = $link->prepare('SELECT COUNT(p.id) FROM projects p INNER JOIN accounts_projects ap ON p.id = ap.project_id AND p.class_id = :cid AND p.id = :pid AND ap.account_id = :aid');
				$stmt->execute(['cid' => $_POST['classId'], 'pid' => $_POST['projectId'], 'aid' => $_SESSION['id']]);
				if ($stmt->fetchColumn(0) != 1) error(4);
				
				// If there is no parent folder & file already located at current project
				if (!isset($_POST['folderId']) && $_SESSION['moveParentId'] == $_POST['projectId']) success();
				
				$goodFolder = FALSE;
				
				// Otherwise, if there is a parent folder, error if it is bad/inaccessible, set $goodFolder = true otherwise
				if (isset($_POST['folderId'])) {
					if (preg_match('/[^0-9]/', $_POST['folderId']) !== 1 && preg_match('/[^0-9]/', $_POST['folderId']) !== FALSE && !in_array($_POST['folderId'], $_SESSION['moveContent'])) {
						$stmt = $link->prepare('SELECT COUNT(f.id) FROM files f INNER JOIN accounts_projects ap ON f.project_id = ap.project_id AND f.project_id = :pid AND f.id = :fid AND ap.account_id = :aid AND f.type = "folder"');
						$stmt->execute(['pid' => $_POST['projectId'], 'fid' => $_POST['folderId'], 'aid' => $_SESSION['id']]);
						if ($stmt->fetchColumn(0) != 1) error(5);
						else $goodFolder = TRUE;
					}
					else error(6);
				}
				
				// Check the first file to determine what of (class, project, folder) we will have to change to complete the move
				$stmt = $link->prepare('SELECT class_id, project_id, parent_id FROM files WHERE id = :fid');
				$stmt->execute(['fid' => $_SESSION['moveContent'][0]]);
				$results = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if (!$results) error(7);
				else if ($results['class_id'] != $_POST['classId']) $setClass = ($setProject = ($setFolder = TRUE));
				else if (isset($results['project_id']) != isset($_POST['projectId']) || ($results['project_id'] == 'NULL' && isset($_POST['projectId'])) || $results['project_id'] != $_POST['projectId']) $setClass = !($setProject = ($setFolder = TRUE));
				else if (isset($results['parent_id']) != isset($_POST['folderId']) || ($results['parent_id'] == 'NULL' && isset($_POST['folderId'])) || $results['parent_id'] != $_POST['folderId']) $setClass = ($setProject = !($setFolder = TRUE));
				else $setClass = ($setProject = ($setFolder = FALSE));
				
				// For every item to move...
				foreach ($_SESSION['moveContent'] as $item) {
					if ($setFolder) { // If we should set ANY of (class, project, folder)
						// Build move query
						$data = array('fid' => $item);
						$query = 'UPDATE files SET ';
						if ($setClass) {
							$query .= 'class_id = :cid, '; // We can use a comma b/c we know we will have to set project
							$data['cid'] = $_POST['classId'];
						}
						if ($setProject) {
							$query .= 'project_id = :pid, ';
							$data['pid'] = $_POST['projectId'];
						}
						if ($goodFolder && $setFolder) {
							$query .= 'parent_id = :paid ';
							$data['paid'] = $_POST['folderId'];
						}
						else if (!isset($_POST['folderId'])) {
							$query .= 'parent_id = NULL ';
						}
						$query .= 'WHERE id = :fid';
						
						// Execute move query
						$stmt = $link->prepare($query);
						$stmt->execute($data);
					}
					
					// Use filterFolderContents() to set the class & project of the contents of the current item if it is a folder (we don't need to set the parent of the contents)
					$stmt = $link->prepare('SELECT COUNT(id) FROM files WHERE id = :fid AND type = "folder"');
					$stmt->execute(['fid' => $item]);
					if ($stmt->fetchColumn(0) == 1) filterFolderContents($item, $setProject ? $_POST['projectId'] : '', $setClass ? $_POST['classId'] : '', $setFolder ? (isset($_POST['folderId']) ? 'NULL' : $_POST['folderId']) : '', $link);
				}
				
				success();
			}
			else error(8);
		}
		catch (PDOException $e) {
			error($e);
		}
	}
	else error(10);