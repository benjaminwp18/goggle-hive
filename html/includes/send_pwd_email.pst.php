<?php
	session_start();

	require('website_address.php');

	function sendBack($value, $uid) {
		header('Location: ../account_recovery.php?return=' . urlencode($value) . '&uid=' . urlencode($uid));
		exit();
	}

	require('PHPMailer/PHPMailerCustomLoad.php');
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isset($_POST['uid']) /*&& isset($_POST['recapToken'])*/) {
		require_once('db_connect.inc.php');
		try {
			$uid = $_POST['uid'];
			
			if (empty($uid)) { // Check for empty fields
				sendBack('error');
			}
        	else if (!filter_var($uid . '@example.org', FILTER_VALIDATE_EMAIL)) { // Check for valid email
				sendBack('badEmail', '');
			}
			else {
				$stmt = $link->prepare('SELECT COUNT(uid) FROM accounts WHERE uid = :uid');
				$stmt->execute(['uid' => $uid]);
				$result = $stmt->fetchColumn(0);
				$stmt = NULL;
				
				if ($result != 1) { // Make sure account exists
					sendBack('emailNotFound', $uid);
				}
				else {
					$emailCode = substr(md5(uniqid(mt_rand(0, strlen($uid)), true)), 0, 10);
					$stmt = $link->prepare('UPDATE accounts SET pwd_reset_code = :pwd_code, pwd_reset_time = NOW() WHERE uid = :uid');
					$stmt->execute(['uid' => $uid, 'pwd_code' => $emailCode]);
					
					require('PHPMailer/PHPMailerCustomActivate.php');
					$mail->Subject = 'Password Recovery';
					$mail->Body = 'Hello ' . htmlspecialchars($uid) . ', <br><br> Click <a href="' . $websiteAddress . '/reset_password.php?uid=' . htmlspecialchars(urlencode($uid)) . '&code=' . htmlspecialchars(urlencode($emailCode)) . '">here</a> to reset your password.<br><br>Cheers,<br>Your Friendly Neighbourhood File Server';
					$mail->AddAddress($uid . '@example.org');
					
					if ($mail->send()) {
						sendBack('success', '');
					}
					else {
						sendBack('error', $uid);
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