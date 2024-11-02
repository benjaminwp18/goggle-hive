<html>
	<head>
		<style>
			body {
				font-family: monospace;
			}
		</style>
	</head>
	<body>
	<?php
		session_start();
	
		function error() {
			echo '<br>Error in download procedure. Aborting.';
			exit();
		}
		
		function success() {
			echo '<br>Download successful. You shouldn\'t see this message. If you do - oops. :)';
			exit();
		}
		
		if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != TRUE) {
			echo '<br>Please login.';
		}
		else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_SESSION['downloadType']) && isset($_SESSION['downloadItems']) && $_SESSION['downloadItems'] != '' && preg_match('/[^0-9,]/', $_SESSION['downloadItems']) !== 1 && preg_match('/[^0-9],/', $_SESSION['downloadItems']) !== FALSE) {
			require_once('db_connect.inc.php');
		
			echo '<b>Event Log:</b><br>Gathering file information...';
			
			$items = explode(',', $_SESSION['downloadItems']);
			
			// Prepare basic check queries
			if ($_SESSION['downloadType'] == 'projects') $stmt = $link->prepare('SELECT p.class_id FROM projects p INNER JOIN accounts_projects ap ON p.id = ap.project_id AND p.id = :iid AND ap.account_id = :aid');
			else if ($_SESSION['downloadType'] == 'files') $stmt = $link->prepare('SELECT f.project_id FROM files f INNER JOIN accounts_projects ap ON f.project_id = ap.project_id AND f.id = :iid AND ap.account_id = :aid');
			else error();
			
			// Check each file
			foreach ($items as $i => $item) {
				$stmt->execute(['iid' => $item, 'aid' => $_SESSION['id']]);
				$results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
				
				if (sizeof($results) != 1) error();  // Error if the current item DNE/is not accessible by this user
				else if ($i == 0) $parentId = $results[0];  // If on the first item, record parentId
				else if ($parentId != $results[0]) error();  // Error if not on first item & parent is different than recorded parentId
			}
			
			echo '<br>Gathering files...';
			// Create $files to be used in ZipArchive creation
			if ($_SESSION['downloadType'] == 'projects') {
				$getProjectName = $link->prepare('SELECT `name` FROM `projects` WHERE `id` = :id');
				$getProjectChildren = $link->prepare('SELECT `id`, `name`, `type` FROM `files` WHERE `project_id` = :pid');
				
				$files = [];
				$fileNames = [];  // For renaming duplicates
				
				foreach ($items as $item) {
					// Create project entry in $files
					$getProjectName->execute(['id' => $item]);
					$files[] = [
						'name' => $getProjectName->fetchColumn(0),
						'children' => []
					];
					if ($files[count($files) - 1]['name'] === FALSE) error();
					
					// Add children
					$getProjectChildren->execute(['pid' => $item]);
					$projectChildren = $getProjectChildren->fetchAll();
					foreach ($projectChildren as $child) {
						while (in_array($child['name'], $fileNames)) $child['name'] = 'Copy of ' . $child['name'];
						$fileNames[] = $child['name'];
						$files[count($files) - 1]['children'][] = [
							'id' => $child['id'],
							'name' => $child['name'],
							'type' => $child['type']
						];
					}
				}
			}
			else if ($_SESSION['downloadType'] == 'files') {
				$folders = [];
				$files = [];
				$fileNames = [];  // For renaming duplicates
			
				// Prepare queries
				$getInfo = $link->prepare('SELECT `type` FROM `files` WHERE `id` = :id');
				$getNonFolder = $link->prepare('SELECT `id`, `name`, `type` FROM `files` WHERE `id` = :id');
				$getFolderChildren = $link->prepare('SELECT `id` FROM `files` WHERE `parent_id` = :pid AND `type` = "folder"');
				$getNonFolderChildren = $link->prepare('SELECT `id`, `name`, `type` FROM `files` WHERE `parent_id` = :pid AND `type` != "folder"');
				
				// Add all non-folders to $files and add folders to $folders
				foreach ($items as $item) {
					$getInfo->execute(['id' => $item]);
					$type = $getInfo->fetchColumn(0);
					if ($type === false) error();
					else if ($type != 'folder') {
						$getNonFolder->execute(['id' => $item]);
						$entry = $getNonFolder->fetch(PDO::FETCH_ASSOC);
						while (in_array($entry['name'], $fileNames)) $entry['name'] = 'Copy of ' . $entry['name'];
						$fileNames[] = $entry['name'];
						$files[] = [
							'id' => $entry['id'],
							'name' => $entry['name'],
							'type' => $entry['type']
						];
						if ($files[count($files) - 1]['name'] === FALSE || $files[count($files) - 1]['id'] === FALSE) error();
					}
					else $folders[] = $item;
				}
				
				// Main loop (for all subfolders...)
				for ($f = 0; $f < count($folders); $f++) {
					// Add all non-folder children to $files
					$getNonFolderChildren->execute(['pid' => $folders[$f]]);
					$children = $getNonFolderChildren->fetchAll();
					foreach ($children as $child) {
						while (in_array($child['name'], $fileNames)) $child['name'] = 'Copy of ' . $child['name'];
						$fileNames[] = $child['name'];
						$files[] = [
							'id' => $child['id'],
							'name' => $child['name'],
							'type' => $child['type']
						];
						if ($files[count($files) - 1]['name'] === FALSE || $files[count($files) - 1]['id'] === FALSE) error();
					}
					
					// Add all folder children to $folders (loop will continue until there are no folder children to add)
					$getFolderChildren->execute(['pid' => $folders[$f]]);
					$newFolders = $getFolderChildren->fetchAll(PDO::FETCH_COLUMN, 0);
					foreach ($newFolders as $newFolder)
						$folders[] = $newFolder;
				}
			}
			
			// Create ZIP
			$filename = '/var/www/html/zipDir/' . uniqid('', true) . '.zip';
		
			echo '<br>Creating zip archive...';  // echos will only be visible if something goes wrong
			$zip = new ZipArchive;
			if ($zip->open($filename, ZipArchive::CREATE) === TRUE) {
				echo '<br>Zipping files...';
			
				// Add files to the zip file
				foreach ($files as $file) {
					if (isset($file['children'])) {
						foreach ($file['children'] as $child) {
							// If name does not include extension, add it (uploading chops names off)
							$extension = (strpos($child['name'], '.' . str_replace('/', '', $child['type'])) > -1) ? '' : '.' . str_replace('/', '', $child['type']);
							
							// Find the drive the file is in
							$driveToUse = 'none';
							$childFullName = str_replace('/', '', $child['id']) . '.' . str_replace('/', '', $child['type']);
							if (file_exists('/2TBA/web/' . $childFullName)) $driveToUse = '2TBA';
							else if (file_exists('/4TBA/web/' . $childFullName)) $driveToUse = '4TBA';
							else if (file_exists('/4TBB/web/' . $childFullName)) $driveToUse = '4TBB';
							else if (file_exists('/4TBC/web/' . $childFullName)) $driveToUse = '4TBC';
							else echo 'File ' . htmlspecialchars($childFullName) . ' could not be found.';
							
							// Add file to zip if file exists
							if ($driveToUse != 'none')
								$zip->addFile('/' . $driveToUse . '/web/' . $childFullName, $file['name'] . '/' . $child['name'] . $extension);
						}
					}
					else {
						// If name does not include extension, add it (uploading chops names off)
						$extension = (strpos($file['name'], '.' . $file['type']) > -1) ? '' : '.' . $file['type'];
						
						// Find the drive the file is in
						$driveToUse = 'none';
						$fileFullName = $file['id'] . '.' . $file['type'];
						if (file_exists('/2TBA/web/' . $fileFullName)) $driveToUse = '2TBA';
						else if (file_exists('/4TBA/web/' . $fileFullName)) $driveToUse = '4TBA';
						else if (file_exists('/4TBB/web/' . $fileFullName)) $driveToUse = '4TBB';
						else if (file_exists('/4TBC/web/' . $fileFullName)) $driveToUse = '4TBC';
						else echo 'File ' . htmlspecialchars($fileFullName) . ' could not be found.';
						
						// Add file to zip if file exists
						if ($driveToUse != 'none')
							$zip->addFile('/' . $driveToUse . '/web/' . $fileFullName, $file['name'] . $extension);
					}
				}
			
				echo '<br>Number of files added: ' . htmlspecialchars($zip->numFiles);
				echo '<br>Status: ' . htmlspecialchars($zip->status);
				$zip->close();
			}
			else {
				echo '<br>Zip creation failed. Aborting.';
			}
			
			echo '<br>Downloading archive...';
			if (file_exists($filename)) {
				ob_clean();
				ob_end_flush();
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename="Goggle Hive Media Files.zip"');
				header('Content-Length: ' . filesize($filename));
				flush();
				readfile($filename);
				unlink($filename);
				
				success();
			}
			else {
				echo '<br>Zip archive not found. Are you trying to download a project or folder with no files in it?';
			}
		}
		else error();
	?>
	</body>
	</head>
</html>