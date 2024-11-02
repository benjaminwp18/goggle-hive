<?php session_start(); $_SESSION['authToken'] = uniqid(md5(microtime()), true); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>Account Recovery</title>
 		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<style>
			#resendDiv {
				text-align: center;
			}
			
			@media only screen and (max-width: 530px) {
				#resendDiv h1 {
					word-break: break-all;
				}
			}
			
			input {
				font-size: 2em;
				padding: 0.5em !important;
				margin: 0.5em !important;
			}
		</style>
	</head>
	<body>
		<?php include('includes/header.inc.php'); ?>
		<main>
			<div id="resendDiv">
				<h1><strong>Recovering Username/Email:</strong> Your username is the same as your name in your ORGANIZATION email. It should be in the format [first initial + last name]. For example, Bilbo Baggins would have the email "bbaggins@example.org" and thus the username "bbaggins". If you don't have access to the email you signed up with anymore, contact the ORGANIZATION tech expert for help.</h1>
				<h1><strong>Recovering Passwords:</strong> Enter your username/email below and we'll send you an email with a password reset link.</h1>
				<form id="resendForm" action="includes/send_pwd_email.pst.php" method="POST">
					<input type="hidden" name="authToken" value="<?php echo $_SESSION['authToken']; ?>">
 					<input type="text" oninput="checkData();" name="uid" id="uid" autocomplete="off" placeholder="Email Name" value="<?php echo $_GET['uid']; ?>" size="8">
 					<br id="uidLineBreak">
 					<label for="uid">@example.org</label><br>
					<input type="submit" name="submit" id="submit" disabled value="Send"><br>
					<p class="error" id="error">
						<?php
							if ($_GET['return'] == 'empty') {
								echo 'Please enter your email name.';
							}
							else if ($_GET['return'] == 'badEmail') {
								echo 'Please enter the valid ORGANIZATION email address you used to create your account.';
							}
							else if ($_GET['return'] == 'emailNotFound') {
								echo 'That email is not associated with an account. To create an account, visit our <a href="signup.php">Signup</a> page.';
							}
							else if ($_GET['return'] == 'error') {
								echo 'Something went wrong. Please try again. If the problem persists, talk to your local tech wiz.';
							}
						?>
					</p><br>
					<p class="success">
						<?php
							if ($_GET['return'] == 'success') {
								echo 'We have sent you an email with a password reset link. You may have to check your spam folder to view it.';
							}
						?>
					</p>
				</form>
			</div>
		</main>
		<script>
			var uid = document.getElementById("uid");
			var submit = document.getElementById("submit");
			
			var error = document.getElementById("error");
			
			function checkData() {
				submit.disabled = true;
				if (uid.value !== "" && uid.value.includes("@")) error.innerHTML = "Email not valid.";
				else {
					error.innerHTML = "";
					if (uid.value !== "") submit.disabled = false;
				}
			}
		</script>
	</body>
</html>