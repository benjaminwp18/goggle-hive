<?php session_start(); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>Reset Password</title>
 		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<style>
			#resetForm {
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
			<div>
				<form id="resetForm" action="includes/reset_pwd.pst.php" method="POST">
					<input type="password" oninput="checkData();" name="pwd" id="pwd" autocomplete="new-password" placeholder="New Password" size="30"><br>
					<input type="password" oninput="checkData();" name="repPwd" id="repPwd" autocomplete="new-password" placeholder="Confirm New Password" size="30"><br>
					<input type="hidden" value="<?php echo $_GET['uid']; ?>" name="uid" id="uid" autocomplete="username">
					<input type="hidden" value="<?php echo $_GET['code']; ?>" name="code" id="code">
					<input type="submit" name="submit" id="submit" disabled value="Reset Password"><br>
					<p class="error" id="error">
						<?php
							if ($_GET['return'] == 'empty') {
								echo 'Please fill out all fields.';
							}
							else if ($_GET['return'] == 'badEmail') {
								echo 'Account invalid.';
							}
							else if ($_GET['return'] == 'badCode') {
								echo 'This reset link is invalid.';
							}
							else if ($_GET['return'] == 'difPwd') {
								echo 'The passwords you entered do not match. Please enter two matching passwords and try again.';
							}
							else if ($_GET['return'] == 'shortPwd') {
								echo 'The password you chose is too short (less than 8 characters). Please choose a longer password and try again';
							}
							else if ($_GET['return'] == 'badCreds') {
								echo 'This reset link is not valid for the account it was made for. Try reseting your password <a href="account_recovery.php">here</a>.';
							}
							else if ($_GET['return'] == 'linkExpired') {
								echo 'This reset link has expired. Try reseting your password <a href="account_recovery.php">here</a>.';
							}
							else if ($_GET['return'] == 'error') {
								echo 'Something went wrong. Please try again. If the problem persists, talk to your local tech wiz.';
							}
						?>
					</p><br>
					<p id="success" class="success">
						<?php
							if ($_GET['return'] == 'success') {
								echo 'Your password has been reset.';
							}
						?>
					</p>
				</form>
			</div>
		</main>
		<script>
			var uid = document.getElementById("uid");
			var code = document.getElementById("code");
			var pwd = document.getElementById("pwd");
			var repPwd = document.getElementById("repPwd");
			var submit = document.getElementById("submit");
			
			var error = document.getElementById("error");
			var success = document.getElementById("success");
			
			window.onload = checkLink;
			
			function checkData() {
				submit.disabled = true;
				if (checkLink()) {
					if (pwd.value !== "" && pwd.value.length < 8) error.innerHTML = "Password must be at least 8 characters.";
					else if (repPwd.value !== "" && repPwd.value !== pwd.value) error.innerHTML = "Passwords must match.";
					else {
						error.innerHTML = "";
						if (pwd.value !== "" && repPwd.value !== "") {
							submit.disabled = false;
						}
					}
				}
			}
			
			function checkLink() {
				if (success.innerText != "" && error.innerText != "" && (uid.value == "" || uid.value.includes("@") || code.value == "" || new RegExp("[^a-z0-9]", "g").test(code.value))) {
					error.innerHTML = "This reset link is invalid.";
					return false;
				}
				else return true;
			}
		</script>
	</body>
</html>