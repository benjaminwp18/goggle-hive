<?php session_start();  $_SESSION['authToken'] = uniqid(md5(microtime()), true); require('includes/explorer.inc.php'); ?>
<?php function setLength($str, $len) { return strlen($str) > $len ? substr($str, 0, $len) . '&hellip;' : $str; } ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title><?php echo $title; ?></title>
		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<link href="styles/explorerStyles.css" rel="stylesheet" type="text/css"/>
	</head>
	<body>
		<?php include('includes/header.inc.php'); ?>
		<div id="fileExplorerNav">
			<script> const authToken = "<?php echo $_SESSION['authToken']; ?>"; </script>
			<?php if (isset($_SESSION['isMoving']) && $_SESSION['isMoving'] == TRUE) { ?>
				<button id="cancelMoveButton" onclick="cancelMove();">Cancel</button>
				<button id="doMoveButton" onclick="doMove();" disabled>Move Here</button>
				<input type="hidden" id="moveType" value="<?php echo $_SESSION['moveType']; ?>">
				<button class="spacer">e</button>
				<button class="spacer">New Project</button>
				<button class="spacer">Rename</button>
				<button class="spacer">Search</button>
				<button class="spacer">Download</button>
			<?php } else { ?>
				<button id="selectMoveButton" onclick="selectMove();" disabled>Move</button>
				<button id="deleteButton" onclick="deleteFiles();" disabled>Delete</button>
				<button id="newItemButton" onclick="newItem();">New&nbsp;<?php echo isset($projectId) ? '&nbsp;Folder' : 'Project'; ?></button>
				<button id="renameButton" onclick="showRenamePopup();" disabled>Rename</button>
				<button id="toggleSearchButton" onclick="toggleSearchBar();">Search</button>
				<button id="downloadButton" onclick="downloadFiles();" disabled>Download</button>
				<?php if (isset($projectId)) { ?>
					<button onclick="showUploadPopup();">Upload</button>
				<?php } else { ?>
					<button class="spacer">Upload</button>
				<?php } ?>
			<?php } ?>
			<p></p>
			<button onclick="location.replace('<?php
				if (!isset($projectId)) echo 'my_classes.php';
				else if (!isset($folderId)) echo 'file_viewer.php?classId=' . $classId;
				else {
					try {
						$stmt = $link->prepare('SELECT parent_id FROM files WHERE id = :fid');
						$stmt->execute(['fid' => $folderId]);
						$results = $stmt->fetchColumn(0);
						
						if (sizeof($results) == 0) {
							echo 'file_viewer.php?classId=' . $classId . '&projectId=' . $projectId;
						}
						else if (sizeof($results) == 1) {
							echo 'file_viewer.php?classId=' . $classId . '&projectId=' . $projectId . '&folderId=' . $results;
						}
						else {
							echo 'file_viewer.php?classId=' . $classId . '&projectId=' . $projectId;
						}
					}
					catch (PDOException $e) {
						echo 'file_viewer.php?classId=' . $classId . '&projectId=' . $projectId;
					}
				}
			?>');" id="backArrowButton">&#9650;</button>
			
			<a id="explorerNavMyClasses" href="my_classes.php">My Classes</a>
			<p id="explorerNavSlashes">//</p>
			<a id="explorerNavClassLink" href="file_viewer.php?classId=<?php echo $classId ?>"><?php echo setLength($className, isset($projectId) ? 7 : 21); ?></a>
			<?php
				if (isset($projectId)) {
					echo
					'<p id="explorerNavProjectArrow"> > </p>' .
					'<a id="explorerNavProjectLink" href="file_viewer.php?classId=' . $classId . '&projectId=' . $projectId . '">' .
						setLength($projectName, isset($folderId) ? 7 : 14) .
					'</a>';
					if (isset($folderId)) {
						echo
						'<p id="explorerNavFolderArrow"> > </p>' .
						'<a id="explorerNavFolderLink" href="file_viewer.php?classId=' . $classId . '&projectId=' . $projectId . '&folderId=' . $folderId . '">' .
							setLength($folderName, 7) .
						'</a>';
					}
				}
			?>
		</div>
		<div id="searchBar">
			<input type="text" id="searchInput" placeholder="Search for Files" onkeypress="tryKeyedSearch(event);">
			<button onclick="searchFiles(false);">Search<?php if (!isset($_GET['projectId'])) echo ' Files'; ?></button>
			<?php if (!isset($_GET['projectId'])) echo '<button onclick="searchFiles(true);">Search Projects</button>'; ?>
		</div>
		<div id="fileContainer"></div>
		<div id="overlay" class="popupOverlay"></div>
		<form class="popup" id="renamePopup" onsubmit="doRename(event);">
			<input type="hidden" name="authToken" value="<?php echo $_SESSION['authToken']; ?>">
			<p id="renameTitle">Rename </p><br>
			<input type="text" id="renameInput" name="name" autocomplete="off" placeholder="Enter New Name"><br>
			<input type="submit" id="renameSubmit" name="submit" value="Rename">
			<input type="button" onclick="closeRenamePopup();" value="Cancel"><br>
			<input type="hidden" name="type" id="renameType" value="normal">
			<input type="hidden" name="renameId" id="renameId">
			<p id="renameResponse"></p>
		</form>
		<form class="popup" id="uploadPopup" onsubmit="uploadFiles(event);" enctype="multipart/form-data">
			<input type="hidden" name="authToken" value="<?php echo $_SESSION['authToken']; ?>">
			<p>Select Files</p><br>
			<input type="file" id="fileInput" name="files[]" multiple><br>
			<p>Upload Progress: </p><progress id="uploadProgress" value="0" max="100"></progress><br>
			<input type="submit" name="submit" value="Upload Files">
			<input type="button" onclick="hideUploadPopup();" value="Close"><br>
			<input type="hidden" id="location" name="<?php echo isset($folderId) ? 'folderId' : (isset($projectId) ? 'projectId' : 'notAllowed') ?>" value="<?php echo isset($folderId) ? $folderId : (isset($projectId) ? $projectId : 'notAllowed') ?>">
			<p id="uploadResponse"></p>
		</form>
		<div class="popup" id="downloadPopup">
			<p>Processing download. This might take awhile.<br>Once you see a ZIP file start downloading, you may close this popup.<br>Folder structure will not be kept in downloaded ZIP.</p>
			<iframe id="downloadIframe"></iframe>
			<input type="button" onclick="hideDownloadPopup();" value="Close/Cancel">
		</div>
		<script src="includes/jquery-3.5.1.min.js"></script>
		<script src="js/explorer.js"></script>
	</body>
</html>