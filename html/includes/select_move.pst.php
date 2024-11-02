<?php
	session_start();
	
	function redirect($location) { // Relative path from perspective of the file which queried this file
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
		
		if ($_POST['type'] == 'projects') $stmt = $link->prepare('SELECT p.class_id FROM projects p INNER JOIN accounts_projects ap ON p.id = ap.project_id AND p.id = :iid AND ap.account_id = :aid');
		else if ($_POST['type'] == 'parentFiles') $stmt = $link->prepare('SELECT f.project_id FROM files f INNER JOIN accounts_projects ap ON f.project_id = ap.project_id AND f.id = :iid AND ap.account_id = :aid');
		else if ($_POST['type'] == 'childFiles') $stmt = $link->prepare('SELECT f.parent_id FROM files f INNER JOIN accounts_projects ap ON f.project_id = ap.project_id AND f.id = :iid AND ap.account_id = :aid');
		else error();
		
		foreach ($items as $i => $item) {
			$stmt->execute(['iid' => $item, 'aid' => $_SESSION['id']]);
			$results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
			
			if (sizeof($results) != 1) error();  // Error if the current item DNE/is not accessible by this user
			else if ($i == 0) $parentId = $results[0];  // If on the first item, record parentId
			else if ($parentId != $results[0]) error();  // Error if not on first item & parent is different than recorded parentId
		}
		
		$_SESSION['moveType'] = $_POST['type'];
		$_SESSION['moveContent'] = $items;
		$_SESSION['moveParentId'] = $parentId;
		$_SESSION['isMoving'] = TRUE;
		success();
	}
	else error();