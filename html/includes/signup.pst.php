<?php
	session_start();

	require('website_address.php');

	function sendBack($value, $uid) {
		header('Location: ../signup.php?return=' . urlencode($value) . '&uid=' . urlencode($uid));
		exit();
	}

	require('PHPMailer/PHPMailerCustomLoad.php');
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isset($_POST['uid']) && isset($_POST['pwd']) && isset($_POST['repPwd']) /*&& isset($_POST['recapToken'])*/) {
		require_once('db_connect.inc.php');
		try {
			$uid = $_POST['uid'];
			$pwd = $_POST['pwd'];
			$repPwd = $_POST['repPwd'];
			
			if (empty($uid) || empty($pwd) || empty($repPwd)) { //Check for empty fields
				sendBack('empty', $uid);
			}
        	else if (!filter_var($uid . '@example.org', FILTER_VALIDATE_EMAIL)) { //Check for valid email
				sendBack('badEmail', '');
			}
			else {
				$stmt = $link->prepare('SELECT COUNT(uid) FROM accounts WHERE uid = :uid');
				$stmt->execute(['uid' => $uid]);
				$result = $stmt->fetchColumn(0);
				$stmt = NULL;
				
				if ($result > 0) { //Check for repeat usernames/emails
					sendBack('repeatEmail', '');
				}
				else if ($pwd != $repPwd){ //Make sure passwords match
					sendBack('difPwd', $uid);
				}
				else if (strlen($pwd) < 8) { //Make sure password is not really short
					sendBack('shortPwd', $uid);
				}
				else {
					$hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
					$emailCode = substr(md5(uniqid(mt_rand(0, strlen($uid)), true)), 0, 10);
					$stmt = $link->prepare('INSERT INTO accounts (uid, pwd, activate_code) VALUES (:uid, :pwd, :activate_code)');
					$stmt->execute(['uid' => $uid, 'pwd' => $hashedPwd, 'activate_code' => $emailCode]);
					
					require('PHPMailer/PHPMailerCustomActivate.php');
					$mail->Subject = 'Activate Your Account';
					$mail->Body = 'Hello ' . htmlspecialchars($uid) . ', <br><br> Click <a href="' . $websiteAddress . '/activate_account.php?uid=' . htmlspecialchars(urlencode($uid)) . '&code=' . htmlspecialchars(urlencode($emailCode)) . '">here</a> to activate your account.<br><br>Cheers,<br>Your Friendly Neighbourhood File Server';
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
		header('Location: ../signup.php');
		exit();
	}