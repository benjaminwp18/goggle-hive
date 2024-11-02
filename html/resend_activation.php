<?php session_start(); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>Resend Account Activation</title>
 		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<style>
			#resendDiv {
				text-align: center;
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
				<h1>To resend your activation code, enter the ORGANIZATION email name you used to sign up below and click <strong>Send</strong>.</h1>
				<form id="resendForm" action="includes/resend.pst.php" method="POST">
 					<input type="text" oninput="checkData();" name="uid" id="uid" autocomplete="off" placeholder="Email Name" value="<?php echo $_GET['uid']; ?>" size="8" autofocus>
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
							else if ($_GET['return'] == 'alreadyActivated') {
								echo 'That account is already activated. If you need to recover your account information, please visit our <a href="account_recovery.php">Account Recovery</a> page.';
							}
							else if ($_GET['return'] == 'error') {
								echo 'Something went wrong. Please try again. If the problem persists, talk to your local tech wiz.';
							}
						?>
					</p><br>
					<p class="success">
						<?php
							if ($_GET['return'] == 'success') {
								echo 'We have sent you an email with a new activation link.';
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