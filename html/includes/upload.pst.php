<?php
	session_start();

	$finalDisplayContent = '';

	function redirect($location) { // Relative path from perspective of the file which queried this file
		echo json_encode(array('redirect' => $location));
		exit();
	}
	
	function display($content) {
		displayPart($content);
		echo json_encode(array('content' => $GLOBALS['finalDisplayContent']));
		exit();
	}
	
	function displayPart($content) {
		$GLOBALS['finalDisplayContent'] .= (empty($GLOBALS['finalDisplayContent']) ? '' : '<br>') . $content;
	}
	
	function rearrange($arr) { // Credit to "timspeelman at live dot nl" at https://www.php.net/manual/en/features.file-upload.multiple.php for this $_FILES[] rearranging function
		foreach ($arr as $key => $all) {
			foreach ($all as $i => $val) {
				$new[$i][$key] = $val;   
			}
		}
		return $new;
	}
	
	function driveHasSpace($name, $size, $fileSize) {  // ( ENUM['2TBA', '4TBA', '4TBB', '4TBC'], INT[# of terabytes], INT[# of bytes] )
		/*	Note on int sizes:
											 						64-bit (which this system is) int max = 9223372036854775807
			bytes in 4 terabytes (technically tebibytes; max sum of file sizes on one of our hard drives) = 4398046511104
			So we're good to store sum file sizes in single ints
		*/
		
		$BInTB = 1000000000000;  // # of bytes in a terabyte (originally used tebibytes (1099511627776 bytes), but overestimated drive size. this underestimates drive size, safer.)

		$drive = new DirectoryIterator('/' . $name . '/web');
		$spaceUsed = 0;
		foreach ($drive as $fileInfo) {
			if (!$fileInfo->isDot()) {
				$spaceUsed += ceil(filesize('/' . $name . '/web/' . $fileInfo->getFilename()));  // round up to avoid creating weird floats
			}
		}
		
		return ($size * $BInTB - $spaceUsed > $fileSize);  // space left in drive > fileseive + 1mb
	}
	
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != TRUE || !isset($_POST['authToken']) || $_SESSION['authToken'] != $_POST['authToken']) {
		redirect('login.php');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files']) && (isset($_POST['projectId']) || isset($_POST['folderId']))) {
		$files = rearrange($_FILES['files']);
		
		require_once('db_connect.inc.php');
		
		$success = array();
		
		$allowedTypes = array('8svx', '16svx', 'aiff', 'aif', 'aifc', 'au', 'bwf', 'cdda', 'raw', 'wav', 'ra', 'rm', 'flac', 'la', 'pac', 'ape', 'ofr', 'ofs', 'off', 'rka', 'shn', 'tak', 'thd', 'tta', 'wv', 'wma', 'brstm', 'dts', 'dtshd', 'dtsma', 'ast', 'aw', 'psf', 'ac3', 'amr', 'mp1', 'mp2', 'mp3', 'spx', 'gsm', 'wma', 'aac', 'mpc', 'vqf', 'ots', 'swa', 'vox', 'voc', 'dwd', 'smp', 'ogg', 'mod', 'mt2', 's3m', 'xm', 'it', 'nsf', 'mid', 'midi', 'ftm', 'ly', 'mus', 'musx', 'mxl', 'xml', 'mscx', 'mscz', 'sib', 'niff', 'ptb', 'asf', 'cust', 'gym', 'jam', 'mng', 'rmj', 'sid', 'spc', 'txm', 'vgm', 'ym', 'pvd', 'als', 'alc', 'alp', 'aup', 'band', 'cel', 'cpr', 'cwp', 'drm', 'dmkit', 'ens', 'flp', 'grir', 'logic', 'mmp', 'mmr', 'mx6hs', 'npr', 'omf', 'omfi', 'rin', 'ses', 'sfl', 'sng', 'stf', 'snd', 'syn', 'vcls', 'vsq', 'vsqx', 'aaf', '3gp', 'gif', 'asf', 'avchd', 'avi', 'bik', 'cam', 'collab', 'dat', 'dsh', 'flv', 'fla', 'flr', 'sol', 'm4v', 'mkv', 'wrap', 'mng', 'mov', 'mpeg', 'mpg', 'mpe', 'thp', 'mp4', 'mxf', 'roq', 'nsv', 'ogg', 'rm', 'svi', 'smi', 'smk', 'swf', 'wmv', 'wtv', 'yuv', 'webm', 'braw', 'fcp', 'mswmm', 'ppj', 'prproj', 'imovieproj', 'veg', 'veg-bak', 'suf', 'wlmp', 'kdenlive', 'vpj', 'motn', 'imoviemobile', 'wfp', 'wve', 'wlmp', 'act', 'ase', 'gpl', 'pal', 'icc', 'icm', 'art', 'blp', 'bmp', 'bti', 'cd5', 'cit', 'cpt', 'cr2', 'clip', 'cpl', 'dds', 'dib', 'djvu', 'egt', 'exif', 'gif', 'grf', 'icns', 'ico', 'iff', 'ilbm', 'lbm', 'jng', 'jpeg', 'jfif', 'jpg', 'jp2', 'jps', 'lbm', 'max', 'miff', 'mng', 'msp', 'nitf', 'otb', 'pbm', 'pc1', 'pc2', 'pc3', 'pcf', 'pcx', 'pdn', 'pgm', 'pi1', 'pi2', 'pi3', 'pict', 'pct', 'png', 'pnm', 'pns', 'ppm', 'psb', 'psd', 'pdd', 'psp', 'px', 'pxm', 'pxr', 'qfx', 'raw', 'rle', 'sct', 'sgi', 'rgb', 'int', 'bw', 'tga', 'targa', 'icb', 'vda', 'vst', 'pix', 'tif', 'tiff', 'vtf', 'xbm', 'xcf', 'xpm', 'zif', '3dv', 'amf', 'awg', 'ai', 'cgm', 'cdr', 'cmx', 'dp', 'dxf', 'e2d', 'egt', 'eps', 'fs', 'gbr', 'odg', 'svg', 'stl', 'x3d', 'sxd', 'v2d', 'vdoc', 'vsd', 'vsdx', 'vnd', 'wmf', 'emf', 'art', 'xar', '3dmf', '3dm', '3mf', '3ds', 'abc', 'ac', 'amf', 'an8', 'aoi', 'asm', 'b3d', 'blend', 'block', 'bmd3', 'bdl4', 'bdl', 'brres', 'bfres', 'c4d', 'cal3d', 'ccp4', 'cfl', 'cob', 'core3d', 'ctm', 'dae', 'dff', 'dpm', 'dts', 'egg', 'fact', 'fac', 'fbx', 'g', 'glb', 'glm', 'gltf', 'iob', 'jas', 'lwo', 'lws', 'lxf', 'lxo', 'ma', 'max', 'mb', 'md2', 'md3', 'md5', 'mdx', 'mesh', 'm', 'mesh', 'mm3d', 'mpo', 'mrc', 'nif', 'obj', 'off', 'ogex', 'ply', 'prc', 'prt', 'pov', 'r3d', 'rwx', 'sia', 'sib', 'skp', 'sldasm', 'sldprt', 'smd', 'u3d', 'usd', 'usda', 'usdc', 'usdz', 'vim', 'vimproj', 'vrml97', 'wrl', 'vue', 'vwx', 'wings', 'w3d', 'x', 'x3d', 'z3d', 'txt', 'text', 'doc', 'docx', 'pdf', 'key', 'odp', 'pps', 'ppt', 'pptx', 'ods', 'xls', 'xlsm', 'xlsx', 'tex', 'wpd', 'rtf', 'odt', 'gdoc', 'gdocx');
		
		try {
		// Start location finding
			if (isset($_POST['projectId']) && preg_match('/[^0-9]/', $_POST['projectId']) !== 1 && preg_match('/[^0-9]/', $_POST['projectId']) !== FALSE) {
				$stmt = $link->prepare('SELECT class_id FROM projects p INNER JOIN accounts_projects ap ON ap.project_id = p.id AND ap.account_id = :aid AND p.id = :pid');
				$stmt->execute(['aid' => $_SESSION['id'], 'pid' => $_POST['projectId']]);
				$results = $stmt->fetchAll();
				
				if (sizeof($results) != 1) {
					redirect('my_classes.php');
				}
				else {
					$projectId = $_POST['projectId'];
					$classId = $results[0]['class_id'];
				}
			}
			else if (isset($_POST['folderId']) && preg_match('/[^0-9]/', $_POST['folderId']) !== 1 && preg_match('/[^0-9]/', $_POST['folderId']) !== FALSE) {
				$stmt = $link->prepare('SELECT project_id, type FROM files WHERE id = :fid');
				$stmt->execute(['fid' => $_POST['folderId']]);
				$results = $stmt->fetchAll();
				
				if (sizeof($results) != 1 || $results[0]['type'] != 'folder') {
					redirect('my_classes.php');
				}
				else {
					$stmt = $link->prepare('SELECT class_id FROM projects p INNER JOIN accounts_projects ap ON ap.project_id = p.id AND ap.account_id = :aid AND p.id = :pid');
					$stmt->execute(['aid' => $_SESSION['id'], 'pid' => $results[0]['project_id']]);
					$classResults = $stmt->fetchAll();
					
					if (sizeof($classResults) != 1) {
						redirect('my_classes.php');
					}
					else {
						$projectId = $results[0]['project_id'];
						$classId = $classResults[0]['class_id'];
						$folderId = $_POST['folderId'];
					}
				}
			}
			else {
				redirect('my_classes.php');
			}
			// End location finding
			
			foreach ($files as $file) {
				
				
				if (empty($file['name'])) {
					displayPart('A file is missing. Skipping.');
				}
				else {
					
					$htmlFileName = htmlspecialchars($file['name']);
					$fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
					
					if ($file['error'] == 1 || $file['error'] == 2) {
						displayPart('"' . $htmlFileName . '" is too large. Skipping.');
					}
					else if ($file['error'] == 4) {
						displayPart('Missing "' . $htmlFileName . '." Skipping.');
					}
					else if ($file['error'] != 0 || $file['tmp_name'] == '') {
						displayPart('Error uploading "' . $htmlFileName . '." Skipping.');
					}
					else if (empty($fileType) || !in_array($fileType, $allowedTypes)) {
						displayPart('"' . $htmlFileName . '" is of an unsupported type. Skipping.');
					}
					else {
						if (strlen($file['name']) > 40) displayPart('Name "' . $htmlFileName . '" greater than max of 40 characters. Shortening to 40.');
						
						
						// Find a hard drive with space
						$driveToUse = 'none';
						if (driveHasSpace('2TBA', 1.8, $file['size'])) $driveToUse = '2TBA';
						else if (driveHasSpace('4TBA', 3.8, $file['size'])) $driveToUse = '4TBA';
						else if (driveHasSpace('4TBB', 3.8, $file['size'])) $driveToUse = '4TBB';
						else if (driveHasSpace('4TBC', 3.8, $file['size'])) $driveToUse = '4TBC';
						else displayPart('Not enough storage space to upload ' . $htmlFileName . '. Please contact administrator.');
						
						// If such a drive exists, upload file
						if ($driveToUse != 'none') {
							$link->query($startTrans);  // Start a transaction to INSERT file data, retrieve the AUTO_INCREMENT id for move_uploaded_file()
							
							$stmt = $link->prepare('INSERT INTO files (name, type, class_id, project_id, parent_id) VALUES (:name, :type, :cid, :pid, :fid)'); // INSERT file data
							$stmt->execute(['name' => $file['name'], 'type' => $fileType, 'cid' => $classId, 'pid' => $projectId, 'fid' => isset($folderId) ? $folderId : NULL]);
							$stmt = NULL;
							
							$stmt = 'SELECT MAX(id) as id FROM files';
							foreach ($link->query($stmt) as $row) {  // Get file id
								$fileId = $row['id'];
							}
							$stmt = NULL;
							
							$link->query($commitTrans);  // COMMIT the transaction
						
						
							if (move_uploaded_file($file['tmp_name'], '/' . $driveToUse . '/web/' . $fileId . '.' . $fileType)) {  // www-data only has access to web dir in drives
								array_push($success, $htmlFileName);
							}
							else {
								displayPart('Error uploading "' . $htmlFileName . '." Skipping.');
							}
						}
					}
				}
				
			}
			
			if (sizeof($success) > 1) {
				display(sizeof($success) . ' files successfully uploaded.');
			}
			else if (sizeof($success) > 0) {
				display('"' . $success[0] . '" successfully uploaded.');
			}	
			else {
				display('No files successfully uploaded.');
			}
		}
		catch(PDOException $e) {
			display('Something went wrong. Please try again.');
		}
	}
	else {
		redirect('my_classes.php');
	}