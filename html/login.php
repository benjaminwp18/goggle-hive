<?php session_start();  $_SESSION['authToken'] = uniqid(md5(microtime()), true); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>Login</title>
		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<style>
			#loginFormDiv {
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
			<div id="loginFormDiv">
				<?php
					if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === TRUE) echo '<p>You are already logged in. <a href="logout.php">Logout</a> first if you want to login to a different account.';
					else {
				?>
				<form id="loginForm" action="includes/login.pst.php" method="POST">
					<input type="hidden" name="authToken" value="<?php echo $_SESSION['authToken']; ?>">
 					<input type="text" oninput="checkData();" name="uid" id="uid" autocomplete="off" placeholder="Email Name" value="<?php echo $_GET['uid']; ?>" size="8" autofocus>
 					<br id="uidLineBreak">
 					<label for="uid">@example.org</label><br>
					<input type="password" oninput="checkData();" name="pwd" id="pwd" autocomplete="password" placeholder="Password" size="30"><br>
					<input type="submit" name="submit" id="submit" disabled value="Login"><br>
					<p class="error" id="error">
						<?php
							if ($_GET['return'] == 'empty') {
								echo 'Please fill out all fields.';
							}
							else if ($_GET['return'] == 'badEmail') {
								echo 'Email name invalid. Please enter the name of the valid ORGANIZATION email address you used to sign up.';
							}
							else if ($_GET['return'] == 'badCreds') {
								echo 'Incorrect username or password.';
							}
							else if ($_GET['return'] == 'inactive') {
								echo 'That account has not been activated. Click <a href="resend_activation.php">here</a> to resend the activation email if you didn\'t recieve it';
							}
							else if ($_GET['return'] == 'error') {
								echo 'Something went wrong. Please try again. If the problem persists, talk to your local tech wiz.';
							}
						?>
					</p><br>
					<p>Need an account?&nbsp;</p><a href="signup.php">Sign up.</a><br>
					<p>Forgot your account details?&nbsp;</p><a href="account_recovery.php">Recover them.</a>
				</form>
				<?php } ?>
			</div>
		</main>
		<script>
			var uid = document.getElementById("uid");
			var pwd = document.getElementById("pwd");
			var submit = document.getElementById("submit");
			
			var error = document.getElementById("error");
			
			function checkData() {
				submit.disabled = true;
				if (uid.value !== "" && uid.value.includes("@")) error.innerHTML = "Email not valid.";
				else {
					error.innerHTML = "";
					if (uid.value !== "" && pwd.value !== "") submit.disabled = false;
				}
			}
		</script>
	</body>
</html>