<?php session_start(); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>Student Signup</title>
 		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<style>
			#signupForm {
				text-align: center;
			}
			input {
				font-size: 2em;
				padding: 0.5em !important;
				margin: 0.5em !important;
			}
			p {
				padding: 1em 0 1em 0;
				margin: 0.5em 0 0.5em 0;
			}
		</style>
	</head>
	<body>
		<?php include('includes/header.inc.php'); ?>
		<main>
			<div>
				<form id="signupForm" action="includes/signup.pst.php" method="POST">
 					<input type="text" oninput="checkData();" name="uid" id="uid" autocomplete="off" placeholder="Email Name" value="<?php echo $_GET['uid']; ?>" size="8" autofocus>
 					<br id="uidLineBreak">
 					<label for="uid">@example.org</label><br>
					<input type="password" oninput="checkData();" name="pwd" id="pwd" autocomplete="new-password" placeholder="Password" size="30"><br>
					<input type="password" oninput="checkData();" name="repPwd" id="repPwd" autocomplete="new-password" placeholder="Confirm Password" size="30"><br>
					<input type="submit" name="submit" id="submit" disabled value="Sign Up"><br>
					<p class="error" id="error">
						<?php
							if ($_GET['return'] == 'empty') {
								echo 'Please fill out all fields.';
							}
							else if ($_GET['return'] == 'badEmail') {
								echo 'Please enter the name of your valid ORGANIZATION email address.';
							}
							else if ($_GET['return'] == 'repeatEmail') {
								echo 'That email is already associated with an account. If you didn\'t recieve your account activation code, please visit our <a href="resend_activation.php">Resend Account Activation</a> page. If you need to recover your account information, please visit our <a href="account_recovery.php">Account Recovery</a> page.';
							}
							else if ($_GET['return'] == 'difPwd') {
								echo 'The passwords you entered do not match. Please enter two matching passwords and try again.';
							}
							else if ($_GET['return'] == 'shortPwd') {
								echo 'The password you chose is too short (less than 8 characters). Please choose a longer password and try again';
							}
							else if ($_GET['return'] == 'error') {
								echo 'Something went wrong. Please try again. If the problem persists, talk to your local tech wiz.';
							}
						?>
					</p><br>
					<p class="success">
						<?php
							if ($_GET['return'] == 'success') {
								echo 'Your account has been created! Click the link we sent to the email you entered to activate it. You might have to check your spam folder.<br>';
							}
						?>
					</p>
					<p>Already have an account?&nbsp;</p><a href="login.php">Login.</a>
				</form>
			</div>
		</main>
		<script>
			var uid = document.getElementById("uid");
			var pwd = document.getElementById("pwd");
			var repPwd = document.getElementById("repPwd");
			var submit = document.getElementById("submit");
			
			var error = document.getElementById("error");
			
			function checkData() {
				submit.disabled = true;
				if (uid.value !== "" && uid.value.includes("@")) {
					error.innerHTML = "Email not valid.";
				}
				else {
					if (pwd.value !== "" && pwd.value.length < 8) error.innerHTML = "Password must be at least 8 characters.";
					else if (repPwd.value !== "" && repPwd.value !== pwd.value) error.innerHTML = "Passwords must match.";
					else {
						error.innerHTML = "";
						if (uid.value !== "" && pwd.value !== "" && repPwd.value !== "") {
							submit.disabled = false;
						}
					}
				}
			}
		</script>
	</body>
</html>