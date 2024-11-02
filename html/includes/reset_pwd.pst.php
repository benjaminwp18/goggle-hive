<?php
	session_start();
	function sendBack($value, $uid, $code) {
		header('Location: ../reset_password.php?return=' . urlencode($value) . '&uid=' . urlencode($uid) . '&code=' . urlencode($code));
		exit();
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isset($_POST['uid']) && isset($_POST['code']) && isset($_POST['pwd']) && isset($_POST['repPwd'])  /*&& isset($_POST['recapToken'])*/) {
		require_once('db_connect.inc.php');
		try {
			$uid = $_POST['uid'];
			$code = $_POST['code'];
			$pwd = $_POST['pwd'];
			$repPwd = $_POST['repPwd'];
			
			if (empty($uid) || empty($code) || empty($pwd) || empty($repPwd)) { // Check for empty fields
				sendBack('empty', $uid, $code);
			}
        	else if (!filter_var($uid . '@example.org', FILTER_VALIDATE_EMAIL)) { // Check for valid email
				sendBack('badEmail', '', $code);
			}
			else if (preg_match('/[^a-z0-9]/', $code) === 1 || preg_match('/[^a-z0-9]/', $code) === FALSE) { // Check for illegal (uppercase or digit) chars in code or if regexing generates scary error
				sendBack('badCode', $uid, '');
			}
			else {
				$stmt = $link->prepare('SELECT COUNT(pwd_reset_code) FROM accounts WHERE pwd_reset_code = :code AND uid = :uid');
				$stmt->execute(['code' => $code, 'uid' => $uid]);
				if ($stmt->fetchColumn(0) != 1) {
					sendBack('badCreds', '', '');
				}
				else {
					$stmt = NULL;
					$stmt = $link->prepare('SELECT COUNT(pwd_reset_code) FROM accounts WHERE pwd_reset_code = :code AND uid = :uid AND TIMESTAMPDIFF(MINUTE, pwd_reset_time, NOW()) < 5');
					$stmt->execute(['code' => $code, 'uid' => $uid]);
					if ($stmt->fetchColumn(0) != 1) {
						sendBack('linkExpired', $uid, $code);
					}
					else if ($pwd != $repPwd){ // Make sure passwords match
						sendBack('difPwd', $uid);
					}
					else if (strlen($pwd) < 8) { // Make sure password is not really short
						sendBack('shortPwd', $uid);
					}
					else {
						$hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
						$stmt = NULL;
						$stmt = $link->prepare('UPDATE accounts SET pwd = :pwd, pwd_reset_code = NULL WHERE uid = :uid AND pwd_reset_code = :code');
						$stmt->execute(['uid' => $uid, 'pwd' => $hashedPwd, 'code' => $code]);
						sendBack('success', '', '');
					}
				}
			}
		}
		catch(PDOException $e) {
			sendBack('error', $uid);
		}
	}
	else {
		header('Location: ../account_recovery.php');
		exit();
	}