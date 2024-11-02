<?php
	session_start();
	function sendBack($value, $uid) {
		header('Location: ../login.php?return=' . urlencode($value) . '&uid=' . urlencode($uid));
		exit();
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isset($_POST['uid']) && isset($_POST['pwd']) && !isset($_SESSION['loggedIn'])) {
		require_once('db_connect.inc.php');
		try {
			$uid = $_POST['uid'];
			$pwd = $_POST['pwd'];
			
			if (empty($uid) || empty($pwd)) { // Check for empty fields
				sendBack('empty', $uid);
			}
        	else if (!filter_var($uid . '@example.org', FILTER_VALIDATE_EMAIL)) { // Check for valid email
				sendBack('badEmail', '');
			}
			else {
				$stmt = $link->prepare('SELECT id, uid, pwd, type, activated FROM accounts WHERE uid = :uid');
				$stmt->execute(['uid' => $uid]);
				$result = $stmt->fetchAll();
				$stmt = NULL;
				
				if (sizeOf($result) != 1 ) { // Check for bad uid
					sendBack('badCreds', $uid);
				}
				else if (!password_verify($pwd, $result[0]['pwd'])) { // Check for bad pwd
					sendBack('badCreds', $uid);
				}
				else if ($result[0]['activated'] == 0) {
					sendBack('inactive', $uid);
				}
				else {
					$stmt = $link->prepare('UPDATE accounts SET pwd_reset_code = NULL WHERE uid = :uid');
					$stmt->execute(['uid' => $uid]);
					$_SESSION['id'] = $result[0]['id'];
					$_SESSION['uid'] = $result[0]['uid'];
					$_SESSION['type'] = $result[0]['type'];
					$_SESSION['loggedIn'] = TRUE;
					$_SESSION['authToken'] = uniqid(md5(microtime()), true);
					session_regenerate_id();
					header('Location: ../my_classes.php');
					exit();
				}
			}
			
		}
		catch(PDOException $e) {
			sendBack('error', $uid);
		}
	}
	else {
		header('Location: ../login.php');
		exit();
	}