<?php
	session_start();
	
	require('website_address.php');
	
	function sendBack($value, $uid) {
		header('Location: ../resend_activation.php?return=' . urlencode($value) . '&uid=' . urlencode($uid));
		exit();
	}

	require('PHPMailer/PHPMailerCustomLoad.php');
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isset($_POST['uid']) /*&& isset($_POST['recapToken'])*/) {
		require_once('db_connect.inc.php');
		try {
			$uid = $_POST['uid'];
			
			if (empty($uid)) { // Check for empty fields
				sendBack('empty', $uid);
			}
        	else if (!filter_var($uid . '@example.org', FILTER_VALIDATE_EMAIL)) { // Check for valid email
				sendBack('badEmail', '');
			}
			else {
				$stmt = $link->prepare('SELECT COUNT(uid) FROM accounts WHERE uid = :uid AND activated = 1');
				$stmt->execute(['uid' => $uid]);
				$result = $stmt->fetchColumn(0);
				$stmt = NULL;
				
				if ($result > 0) { // Make sure account isn't already activated
					sendBack('alreadyActivated', $uid);
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
						$stmt = $link->prepare('UPDATE accounts SET activate_code = :activate_code, activate_time = NOW() WHERE uid = :uid');
						$stmt->execute(['uid' => $uid, 'activate_code' => $emailCode]);
						
						require('PHPMailer/PHPMailerCustomActivate.php');
						$mail->Subject = 'Account Activation Resend';
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
		}
		catch(PDOException $e) {
			sendBack('error', $uid);
		}
	}
	else {
		header('Location: ../resend_activation.php');
		exit();
	}