<?php
	session_start();
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] === FALSE) {
		header('Location: login.php');
		exit();
	}
	else $_SESSION['authToken'] = uniqid(md5(microtime()), true);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>My Classes</title>
		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<style>
			#classContainer {
				text-align: center;
			}
			.classEditButton {
				font-size: 2em;
				padding: 0.5em !important;
				margin: 0.5em !important;
			}
		</style>
	</head>
	<body>
		<?php include('includes/header.inc.php'); ?>
		<main>
			<div id="flexer">
				<div id="classContainer">
					<?php
						require_once('includes/db_connect.inc.php');
						try {
							$stmt = $link->prepare('SELECT `id`, `name`, `join_code` FROM `classes` INNER JOIN `accounts_classes` ON accounts_classes.class_id = classes.id AND accounts_classes.account_id = :aid ORDER BY classes.name;');
							$stmt->execute(['aid' => $_SESSION['id']]);
							$results = $stmt->fetchAll();
							
							if (sizeof($results) == 0) echo '<h1>You do not have any classes. Click the "+ Join Class" button below to add classes.</h1><br><br><br>';
							else {
								foreach($results as $class) {
									echo '<button class="panel" onclick="location.assign(\'file_viewer.php?classId=' . htmlspecialchars($class['id']) . '\');">' . htmlspecialchars($class['name']) . '<br><span class="class_code">' . htmlspecialchars($class['join_code']) . '</span></button>';
								}
							}
						}
						catch (PDOException $e) {
							echo '<p>An error occured. Please try again.</p>';
						}
						
					?><br><br>
					<input type="button" class="classEditButton" id="addClassButton" onclick="showAddClassPopup();" value="+ Join Class">
					<?php if ($_SESSION['type'] == 'teacher' || $_SESSION['type'] == 'admin') { ?>
						<input type="button" class="classEditButton" id="createClassButton" onclick="showCreateClassPopup();" value="+ Create Class">
					<?php } ?>
				</div>
			</div>
		</main>
		<div id="overlay" class="popupOverlay"></div>
		<form class="popup" id="addClassPopup" action="includes/add_class.pst.php" method="POST">
			<input type="hidden" name="authToken" value="<?php echo $_SESSION['authToken']; ?>">
			<p>Enter Class Code</p><br>
			<input type="text" id="addClassCodeBox" name="code" placeholder="Class Code" oninput="checkAddClassCode();" autocomplete="off" spellcheck="false" maxlength="7" value="<?php echo $_GET['code']; ?>"><br>
			<input type="submit" id="addClassSubmit" name="submit" value="Join Class" disabled>
			<input type="button" onclick="hideAddClassPopup();" value="Cancel"><br>
			<p class="error">
				<?php
					if ($_GET['addReturn'] == 'empty') {
						echo 'Please enter a class code.';
					}
					else if ($_GET['addReturn'] == 'badCode') {
						echo 'Invalid code.';
					}
					else if ($_GET['addReturn'] == 'noClasses') {
						echo 'That code did not match any<br>&nbsp;&nbsp;&nbsp;classes.';
					}
					else if ($_GET['addReturn'] == 'alreadyEnrolled') {
						echo 'You are already in that class.';
					}
					else if ($_GET['addReturn'] == 'error') {
						echo 'An error occurred.';
					}
				?>
			</p>
		</form>
		<?php if ($_SESSION['type'] == 'teacher' || $_SESSION['type'] == 'admin') { ?>
			<form class="popup" id="createClassPopup" action="includes/create_class.pst.php" method="POST">
				<input type="hidden" name="authToken" value="<?php echo $_SESSION['authToken']; ?>">
				<p>Enter Your Class Name</p><br>
				<input type="text" id="createClassNameBox" name="name" placeholder="Class Name" oninput="checkCreateClassName();" autocomplete="off" maxlength="40" value="<?php echo $_GET['name']; ?>"><br>
				<input type="submit" id="createClassSubmit" name="submit" value="Create Class" disabled>
				<input type="button" onclick="hideCreateClassPopup();" value="Cancel"><br>
				<p class="error">
					<?php
						if ($_GET['createReturn'] == 'empty') {
							echo 'Please enter a class name.';
						}
						else if ($_GET['createReturn'] == 'badName') {
							echo 'Invalid name.';
						}
						else if ($_GET['createReturn'] == 'error') {
							echo 'An error occurred.';
						}
					?>
				</p>
			</form>
		<?php } ?>
		<script>
			function gId(id) { return document.getElementById(id); }
		
			var addClassPopup = gId("addClassPopup");
			var addClassCodeBox = gId("addClassCodeBox");
			var overlay = gId("overlay");
			var addClassSubmit = gId("addClassSubmit");
			
			var op = 0;
			
			window.onload = function() {
				<?php
					if (isset($_GET['addReturn'])) echo 'showAddClassPopup();';
					else if (isset($_GET['createReturn'])) echo 'showCreateClassPopup();';
				?>
			}
			
			function showAddClassPopup() {
				addClassPopup.style.display = "inline-block";
				overlay.style.display = "inline-block";
				fadePopup(addClassPopup, 0.15, 10, "inline-block");
				addClassCodeBox.focus();
			}
			
			function hideAddClassPopup() {
				addClassCodeBox.value = "";
				fadePopup(addClassPopup, -0.15, 10, "none");
			}
			
			function fadePopup(popup, tick, ms, endState) {
				let fader = setInterval(function() {
					op = Math.round(100 * (op + tick)) / 100;
					popup.style.opacity = op;
					overlay.style.opacity = op;
					if (op >= 1 || op <= 0) {
						clearInterval(fader);
						popup.style.display = endState;
						overlay.style.display = endState;
					}
				}, ms);
			}
			
			function checkAddClassCode() {
				addClassSubmit.disabled = true;
				if (!new RegExp("[^a-z0-9]", "g").test(addClassCodeBox.value) && addClassCodeBox.value != "" && addClassCodeBox.value.length == 7) addClassSubmit.disabled = false;
			}
			
			<?php if ($_SESSION['type'] == 'teacher' || $_SESSION['type'] == 'admin') { ?>
				var createClassPopup = gId("createClassPopup");
				var createClassNameBox = gId("createClassNameBox");
				var createClassSubmit = gId("createClassSubmit");
				
				function showCreateClassPopup() {
					createClassPopup.style.display = "inline-block";
					overlay.style.display = "inline-block";
					fadePopup(createClassPopup, 0.15, 10, "inline-block");
					createClassNameBox.focus();
				}
				
				function hideCreateClassPopup() {
					createClassNameBox.value = "";
					fadePopup(createClassPopup, -0.15, 10, "none");
				}
				
				function checkCreateClassName() {
					createClassSubmit.disabled = true;
					if (!new RegExp("[^\\w\\d\\s!@\\/$&()\\-+\"':,?]", "g").test(createClassNameBox.value) && createClassNameBox.value != "" && createClassNameBox.value.length < 40 && createClassNameBox.value.length > 4)
						createClassSubmit.disabled = false;
				}
			<?php } ?>
		</script>
	</body>
</html>