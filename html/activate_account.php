<?php
	session_start(); $_SESSION['authToken'] = uniqid(md5(microtime()), true);
	if (isset($_GET['code']) && isset($_GET['uid'])) {
		require('includes/db_connect.inc.php');
		$status = 0; // 0 = failure, 1 = success, 2 = expired code
		try {
			$code = $_GET['code'];
			$uid = $_GET['uid'];
			if (preg_match('/[^a-z0-9]/', $code) === 1 || preg_match('/[^a-z0-9]/', $code) === FALSE) { // Check for illegal (uppercase or digit) chars in code or if regexing generates error
				$status = 0;
			}
			else {
				$stmt = $link->prepare('SELECT COUNT(activate_code) FROM accounts WHERE activate_code = :code AND uid = :uid AND activated = 0 AND TIMESTAMPDIFF(MINUTE, activate_time, NOW()) < 5');
				$stmt->execute(['code' => $code, 'uid' => $uid]);
				if ($stmt->fetchColumn(0) == 1) {
					$stmt = NULL;
					$stmt = $link->prepare('UPDATE accounts SET activate_code = \'\', activated = 1 WHERE uid = :uid');
					$stmt->execute(['uid' => $uid]);
					$stmt = NULL;
					$status = 1;
				}
				$stmt = NULL;
				if ($status == 0) {
					$stmt = $link->prepare('SELECT COUNT(activate_code) FROM accounts WHERE activate_code = :code AND uid = :uid AND activated = 0');
					$stmt->execute(['code' => $code, 'uid' => $uid]);
					if ($stmt->fetchColumn(0) == 1) {
						$status = 2;
					}	
				}
				$stmt = NULL;
			}
		}
		catch (PDOException $e) {
			//success is already 0
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>Activate Account</title>
		<link rel="stylesheet" type="text/css" href="styles/style.css">
		<style>
			#responseDiv {
				text-align: center;
			}
		</style>
	</head>
	<body>
		<?php include('includes/header.inc.php'); ?>
		<main>
			<div id="responseDiv">
				<?php
					if ($status == 1) {
						echo '<p>Account successfully activated! <a href="login.php">Login</a>.</p>';
					}
		 			else if ($status == 2) {
		 				echo '<p>This activation link is expired. Click <a href="resend_activation.php">here</a> to resend your activation link.</p>';
		 			}
					else {
						echo '<p>Account verification failed. Please make sure you have clicked the correct link and try again. If this problem persists, contact the nearest tech warlock.</p>';
					}
				?>
			</div>
		</main>
	</body>
</html>