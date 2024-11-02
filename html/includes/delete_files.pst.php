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
		require_once('db_connect.inc.php');
		
		$items = explode(',', $_POST['items']);
		
		// Prepare basic check queries
		if ($_POST['type'] == 'projects') $stmt = $link->prepare('SELECT p.class_id FROM projects p INNER JOIN accounts_projects ap ON p.id = ap.project_id AND p.id = :iid AND ap.account_id = :aid');
		else if ($_POST['type'] == 'files') $stmt = $link->prepare('SELECT f.project_id FROM files f INNER JOIN accounts_projects ap ON f.project_id = ap.project_id AND f.id = :iid AND ap.account_id = :aid');
		else error();
		
		// Check each file
		foreach ($items as $i => $item) {
			$stmt->execute(['iid' => $item, 'aid' => $_SESSION['id']]);
			$results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
			
			if (sizeof($results) != 1) error();  // Error if the current item DNE/is not accessible by this user
			else if ($i == 0) $parentId = $results[0];  // If on the first item, record parentId
			else if ($parentId != $results[0]) error();  // Error if not on first item & parent is different than recorded parentId
		}
		
		// Do deletion
		if ($_POST['type'] == 'projects') {
			$delProject = $link->prepare('DELETE FROM `projects` WHERE `id` = :id');
			$delChildren = $link->prepare('DELETE FROM `files` WHERE `project_id` = :id');
			$getChildren = $link->prepare('SELECT `id`, `type` FROM `files` WHERE `project_id` = :id');
			
			foreach ($items as $i => $item) {
				// Delete actual files
				$getChildren->execute(['id' => $item]);
				foreach ($getChildren->fetchAll() as $child) {
					// Find the drive the file is in
					$driveToUse = 'none';
					$childFullName = str_replace('/', '', $child['id']) . '.' . str_replace('/', '', $child['type']);
					if (file_exists('/2TBA/web/' . $childFullName)) $driveToUse = '2TBA';
					else if (file_exists('/4TBA/web/' . $childFullName)) $driveToUse = '4TBA';
					else if (file_exists('/4TBB/web/' . $childFullName)) $driveToUse = '4TBB';
					else if (file_exists('/4TBC/web/' . $childFullName)) $driveToUse = '4TBC';
					
					// Delete file file exists (if not, just ignore it & delete the database entry like normal later)
					if ($driveToUse != 'none')
						unlink('/' . $driveToUse . '/web/' . $childFullName);
				}
				
				// Delete database entries
				$delChildren->execute(['id' => $item]);
				$delProject->execute(['id' => $item]);
			}
			
			success();
		}
		else if ($_POST['type'] == 'files') {
			$folders = [];
		
			// Prepare queries
			$getInfo = $link->prepare('SELECT `id`, `type` FROM `files` WHERE `id` = :id');
			$delNonFolder = $link->prepare('DELETE FROM `files` WHERE `id` = :id AND `type` != "folder"');
			$getFolderChildren = $link->prepare('SELECT `id` FROM `files` WHERE `parent_id` = :pid AND `type` = "folder"');
			$getNonFolderChildren = $link->prepare('SELECT `id`, `type` FROM `files` WHERE `parent_id` = :pid AND `type` != "folder"');
			$delNonFolderChildren = $link->prepare('DELETE FROM `files` WHERE `parent_id` = :pid AND `type` != "folder"');
			$delFolder = $link->prepare('DELETE FROM `files` WHERE `id` = :id AND `type` = "folder"');
			
			// Delete all non-folders and add folders to $folders
			foreach ($items as $item) {
				$getInfo->execute(['id' => $item]);
				$info = $getInfo->fetch();
				if ($info['type'] === false) error();
				else if ($info['type'] != 'folder') {  // If not a folder
					// FIRST DELETE ACTUAL FILE
					// Find the drive the file is in
					$driveToUse = 'none';
					$fileFullName = str_replace('/', '', $info['id']) . '.' . str_replace('/', '', $info['type']);
					if (file_exists('/2TBA/web/' . $fileFullName)) $driveToUse = '2TBA';
					else if (file_exists('/4TBA/web/' . $fileFullName)) $driveToUse = '4TBA';
					else if (file_exists('/4TBB/web/' . $fileFullName)) $driveToUse = '4TBB';
					else if (file_exists('/4TBC/web/' . $fileFullName)) $driveToUse = '4TBC';
					
					// Delete file file exists (if not, just ignore it & delete the database entry like normal later)
					if ($driveToUse != 'none')
						unlink('/' . $driveToUse . '/web/' . $fileFullName);
					
					// THEN DELETE DB ENTRY
					$delNonFolder->execute(['id' => $item]);
				}
				else $folders[] = $item;
			}
			
			// Main loop (for all subfolders...)
			for ($f = 0; $f < count($folders); $f++) {
				// Delete all non-folder children: actual files
				$getNonFolderChildren->execute(['pid' => $folders[$f]]);
				foreach ($getNonFolderChildren->fetchAll() as $child) {
					// Find the drive the file is in
					$driveToUse = 'none';
					$childFullName = str_replace('/', '', $child['id']) . '.' . str_replace('/', '', $child['type']);
					if (file_exists('/2TBA/web/' . $childFullName)) $driveToUse = '2TBA';
					else if (file_exists('/4TBA/web/' . $childFullName)) $driveToUse = '4TBA';
					else if (file_exists('/4TBB/web/' . $childFullName)) $driveToUse = '4TBB';
					else if (file_exists('/4TBC/web/' . $childFullName)) $driveToUse = '4TBC';
					
					// Delete file file exists (if not, just ignore it & delete the database entry like normal later)
					if ($driveToUse != 'none')
						unlink('/' . $driveToUse . '/web/' . $childFullName);
				}
				
				// Delete all non-folder children: database entries
				$delNonFolderChildren->execute(['pid' => $folders[$f]]);
				
				
				// Add all folder children to $folders (loop will continue until there are no folder children to add)
				$getFolderChildren->execute(['pid' => $folders[$f]]);
				$newFolders = $getFolderChildren->fetchAll(PDO::FETCH_COLUMN, 0);
				foreach ($newFolders as $newFolder)
					$folders[] = $newFolder;
			}
			
			// Delete folders, starting with children & working back up to avoid MySQL foreign key errors
			for ($f = count($folders) - 1; $f >= 0; $f--) {
				$delFolder->execute(['id' => $folders[$f]]);
			}
			
			success();
		}
		
		
	}
	else error();
