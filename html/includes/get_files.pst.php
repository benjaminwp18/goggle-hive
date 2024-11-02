<?php
	session_start();
	
	function redirect($location) { // Relative path from perspective of the file which queried this file
		echo json_encode(array('redirect' => $location));
		exit();
	}
	
	function display($content) {
		echo json_encode(array('content' => $content));
		exit();
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] == TRUE && isset($_POST['authToken']) && $_SESSION['authToken'] == $_POST['authToken']) {
		$uid = $_SESSION['id'];
		
		require_once('db_connect.inc.php');
		try {
			if (isset($_POST['folderId'])) {
				if (preg_match('/[^0-9]/', $_POST['folderId']) === 1 || preg_match('/[^0-9]/', $_POST['folderId']) === FALSE) {
					redirect('my_classes.php');
				}
				else {
					$dirId = $_POST['folderId'];
					
					// Check if account has access to folder & folder exists
					$stmt = $link->prepare('SELECT `id`, `name` FROM `files` f INNER JOIN `accounts_projects` ap ON ap.project_id = f.project_id AND ap.account_id = :aid AND f.id = :fid AND f.type = "folder"');
					$stmt->execute(['aid' => $uid, 'fid' => $dirId]);
					$result = $stmt->fetchAll();
					$stmt = NULL;
					
					if (count($result) != 1) {
						redirect('my_classes.php');
					}
					else {
						if (isset($_POST['searchTerm'])) {
							$queryFolders = [];
							$folders = [['id' => $dirId, 'name' => $result[0]['name'], 'type' => 'folder']];
							$nonFolders = [];
							
							$getFolders = $link->prepare('
								SELECT `id`, `name`, `type`
								FROM `files`
								WHERE `parent_id` = :pid AND `type` = "folder"
							');
							$getQueryFolders = $link->prepare('
								SELECT `id`, `name`, `type`, `project_id`, `class_id`
								FROM `files`
								WHERE `parent_id` = :pid AND `type` = "folder" AND `name` LIKE :pstp
								ORDER BY
									CASE
										WHEN `name` LIKE :stp
											THEN 1
											ELSE 2
										END,
									`name` ASC
							');
							$getNonFolders = $link->prepare('
								SELECT `id`, `name`, `type`, `project_id`, `class_id`
								FROM `files`
								WHERE `parent_id` = :pid AND `type` != "folder" AND `name` LIKE :pstp
								ORDER BY
									CASE
									WHEN `name` LIKE :stp
										THEN 1
										ELSE 2
									END,
									`name` ASC
							');
							
							$pstp = '%' . $_POST['searchTerm'] . '%';
							$stp = $_POST['searchTerm'] . '%';
							
							// Look through folder & all children, collecting them in $files
							for ($i = 0; $i < count($folders); $i++) {
								$getFolders->execute(['pid' => $folders[$i]['id']]);
								$getQueryFolders->execute(['pid' => $folders[$i]['id'], 'pstp' => $pstp, 'stp' => $stp]);
								$getNonFolders->execute(['pid' => $folders[$i]['id'], 'pstp' => $pstp, 'stp' => $stp]);
								
								$folders = array_merge($folders, $getFolders->fetchAll());
								$queryFolders = array_merge($queryFolders, $getQueryFolders->fetchAll());
								$nonFolders = array_merge($nonFolders, $getNonFolders->fetchAll());
							}
							
							$files = array_merge($queryFolders, $nonFolders);
							
							if (sizeof($files) < 1) {
								display('<p>Your search returned no results.</p>');
							}
						}
						else {
							$stmt = $link->prepare('SELECT id, class_id, project_id, name, type FROM files WHERE parent_id = :fid ORDER BY CASE WHEN type = "folder" THEN 1 ELSE 2 END, name ASC');
							$stmt->execute(['fid' => $dirId]);
							$results = $stmt->fetchAll();
							$stmt = NULL;
							
							if (sizeof($results) < 1) {
								display('<p>This folder is empty. Click the Upload button to add files.</p>');
							}
							else $files = $results;
						}
					}
				}
			}
			else if (isset($_POST['projectId'])) {
				if (preg_match('/[^0-9]/', $_POST['projectId']) === 1 || preg_match('/[^0-9]/', $_POST['projectId']) === FALSE) {
					redirect('my_classes.php');
				}
				else {
					$projId = $_POST['projectId'];
					
					// Check if account has access to project & project exists
					$stmt = $link->prepare('SELECT COUNT(id) FROM projects p INNER JOIN accounts_projects ap ON ap.project_id = p.id AND ap.account_id = :aid AND p.id = :pid');
					$stmt->execute(['aid' => $uid, 'pid' => $projId]);
					$result = $stmt->fetchColumn(0);
					$stmt = NULL;
					
					if ($result != 1) {
						redirect('my_classes.php');
					}
					else {
						/* If searching:
							look thru ALL files in project,
							only take responses that have search term in them,
							and order responses like so:
								folders w/search term first,
								other folders,
								files w/search term first,
								other files
						*/
						if (isset($_POST['searchTerm'])) {
							$stmt = $link->prepare('
								SELECT `id`, `class_id`, `project_id`, `name`, `type`, `parent_id`
								FROM `files`
								WHERE `project_id` = :pid AND `name` LIKE :pstp
								ORDER BY
									CASE
									WHEN `type` = "folder"
										THEN
											CASE
											WHEN `name` LIKE :stp
												THEN 1
												ELSE 2
											END
										ELSE
											CASE
											WHEN `name` LIKE :stp
												THEN 3
												ELSE 4
											END
									END,
									`name` ASC
							');
							$stmt->execute(['pid' => $projId, 'pstp' => '%' . $_POST['searchTerm'] . '%', 'stp' => $_POST['searchTerm'] . '%']);
						}
						// Else only order by folder vs. file, don't care about searchTerm, only look at top level files
						else {
							$stmt = $link->prepare('
								SELECT `id`, `class_id`, `project_id`, `name`, `type`
								FROM `files`
								WHERE `project_id` = :pid AND `parent_id` IS NULL
								ORDER BY
									CASE
									WHEN `type` = "folder"
										THEN 1
										ELSE 2
									END,
									`name` ASC
							');
							$stmt->execute(['pid' => $projId]);
						}
						
						$results = $stmt->fetchAll();
						$stmt = NULL;
						
						if (sizeof($results) < 1) {
							if (!isset($_POST['searchTerm'])) display('<p>There are no files in this project yet. Click the Upload button to add files.</p>');
							else display('<p>Your search returned no results.</p>');
						}
						else {
							$files = $results;
						}
					}
				}
			}
			else if (isset($_POST['classId'])) {
				if (preg_match('/[^0-9]/', $_POST['classId']) === 1 || preg_match('/[^0-9]/', $_POST['classId']) === FALSE) {
					redirect('my_classes.php');
				}
				else {
					$classId = $_POST['classId'];
					
					$stmt = $link->prepare('SELECT COUNT(`id`) FROM `classes` c INNER JOIN `accounts_classes` ac ON ac.class_id = c.id AND ac.account_id = :aid AND c.id = :cid');
					$stmt->execute(['aid' => $uid, 'cid' => $classId]);
					$result = $stmt->fetchColumn(0);
					$stmt = NULL;
					
					if ($result != 1) {
						redirect('my_classes.php');
					} 
					else {
						if (isset($_POST['searchTerm'])) {
							$getFiles = $link->prepare('
								SELECT `id`, `name`, `type`, `project_id`, `class_id`
								FROM `files`
								WHERE `class_id` = :cid AND `name` LIKE :pstp
								ORDER BY
									CASE
									WHEN `type` = "folder"
										THEN
											CASE
											WHEN `name` LIKE :stp
												THEN 1
												ELSE 2
											END
										ELSE
											CASE
											WHEN `name` LIKE :stp
												THEN 3
												ELSE 4
											END
									END,
									`name` ASC
							');
							
							$getProjects = $link->prepare('
								SELECT `id`, `class_id`, `name`
								FROM `projects` p
								INNER JOIN `accounts_projects` ap
								ON p.id = ap.project_id AND ap.account_id = :aid AND p.class_id = :cid AND `name` LIKE :pstp
								ORDER BY
									CASE
									WHEN `name` LIKE :stp
										THEN 1
										ELSE 2
									END,
									`name` ASC
							');
							
							$returnProjects = isset($_POST['returnOnly']) && $_POST['returnOnly'] == 'projects';
							
							if ($returnProjects) {
								$getProjects->execute(['aid' => $uid, 'cid' => $classId, 'pstp' => '%' . $_POST['searchTerm'] . '%', 'stp' => $_POST['searchTerm'] . '%']);
								foreach ($getProjects->fetchAll() as $project) {
									$project['type'] = 'project';
									$results[] = $project;
								}
							}
							else {
								$getFiles->execute(['cid' => $classId, 'pstp' => '%' . $_POST['searchTerm'] . '%', 'stp' => $_POST['searchTerm'] . '%']);
								$results = $getFiles->fetchAll();
							}
						}
						else {
							$getProjects = $link->prepare('
								SELECT `id`, `class_id`, `name`
								FROM `projects` p
								INNER JOIN `accounts_projects` ap
								ON p.id = ap.project_id AND ap.account_id = :aid AND p.class_id = :cid
							');
							$getProjects->execute(['aid' => $uid, 'cid' => $classId]);
							foreach ($getProjects->fetchAll() as $project) {
								$project['type'] = 'project';
								$results[] = $project;
							}
						}
						
						$stmt = NULL;
						
						if (sizeof($results) < 1) {
							if (isset($_POST['searchTerm'])) display('<p>Your search returned no results.</p>');
							else display('<p>You are not in any projects in this class yet. Click the New Project button to create on.</p>');
						}
						else {
							$files = $results;
						}
					}
				}
			}
			else {
				redirect('my_classes.php');
			}
			
			$out = "";
			
			if (isset($files)) {
				for ($i = 0; $i < sizeof($files); $i++) {
					$file = $files[$i];
					$out .=
						'<div class="filePanel"
							data-position="' . htmlspecialchars($i) .
							'" id="file' . htmlspecialchars($file['id']) .
							'" onclick="select(event, ' . htmlspecialchars($file['id']) .
							');" ondblclick="' .
							($file['type'] == 'folder' || $file['type'] == 'project'
							?	'location.assign(\'file_viewer.php?classId=' .
								htmlspecialchars(urlencode($file['class_id'])) .
								'&projectId=' . htmlspecialchars(urlencode($file[ ($file['type'] == 'project' ? 'id' : 'project_id') ])) .
								($file['type'] == 'folder' ? '&folderId=' . htmlspecialchars(urlencode($file['id'])) : '')
							:	'') .
							'\');"><img src="../assets/' .
							($file['type'] == 'project'
							?	'project'
							:	($file['type'] == 'folder'
								? 'folder'
								: 'file'
								)
							) .'.png"></img><p>' . htmlspecialchars($file['name']) /*. ' (' . htmlspecialchars($file['id']) . ')'*/ . '</p>' . 
						'</div>';
				}
			}
			
			display($out);
		}
		catch (PDOException $e) {
			display('<p>An error occurred. Please try again. ' . $e->getMessage() . ' </p>');
		}
	}
	else {
		header('Location: ../login.php');
		redirect('login.php');
	}