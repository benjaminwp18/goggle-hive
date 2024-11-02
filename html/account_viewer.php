<?php
	session_start(); $_SESSION['authToken'] = uniqid(md5(microtime()), true);
	if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] === FALSE) {
		header('Location: login.php');
		exit();
	}
	else if ($_SESSION['type'] != 'admin') {
		header('Location: my_classes.php');
		exit();
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" href="assets/favicon.png">
		<title>Account Management</title>
		<link href="styles/style.css" rel="stylesheet" type="text/css"/>
		<style>
			#tableContainer {
				text-align: center;
			}
		</style>
	</head>
	<body>
		<?php include('includes/header.inc.php'); ?>
		<main>
			<div id="flexer">
				<div id="tableContainer">
					<p>Reload this page to update the table below.</p>
					<table class="adminAccountsTable">
						<tr>
							<th title="i.e. &quot;dvader&quot; for Darth Vader">Username/Email</th>
							<th title="Has this account's email been activated?">Activated?</th>
							<th title="Admin, Teacher, or Student">Type</th>
							<th title="Which classes has this account joined?">Classes</th>
						</tr>
						<?php
							require_once('includes/db_connect.inc.php');
							try {
								foreach ($link->query('SELECT `id`, `uid`, `activated`, `type` FROM `accounts`') as $account) {
									$typeLetter = htmlspecialchars(strtoupper(substr($account['type'], 0, 1)));
									if ($typeLetter != 'S' && $typeLetter != 'T' && $typeLetter != 'A') $typeLetter = 'S';
								
									echo '<tr>';
									echo '<td>' . htmlspecialchars($account['uid']) . '</td>';
									echo '<td>' . ($account['activated'] === '1' ? 'Yes' : 'No') . '</td>';
									echo '<td>' .
										'<span class="adminTypeSwitch adminTypeSwitch' .
											($typeLetter != 'S' ?
												'Clickable" onclick="setAccountType(\'' . $account['id'] . '\', \'S\')"' :
												'Unclickable"'
											)
										. '>S</span>' .
										'<span class="adminTypeSwitch adminTypeSwitch' .
											($typeLetter != 'T' ?
												'Clickable" onclick="setAccountType(\'' . $account['id'] . '\', \'T\')"' :
												'Unclickable"'
											)
										. '>T</span>' .
										'<span class="adminTypeSwitch adminTypeSwitch' .
											($typeLetter != 'A' ?
												'Clickable" onclick="setAccountType(\'' . $account['id'] . '\', \'A\')"' :
												'Unclickable"'
											)
										. '>A</span>' .
									'</td>';
									
									$getClasses = $link->prepare('SELECT `name` FROM `classes` INNER JOIN `accounts_classes` ON accounts_classes.class_id = classes.id AND accounts_classes.account_id = :aid ORDER BY classes.name');
									$getClasses->execute(['aid' => $account['id']]);
									echo '<td>';
									$classes = $getClasses->fetchAll(PDO::FETCH_COLUMN, 0);
									foreach ($classes as $i => $class) {
										echo htmlspecialchars($class) . ($i >= count($classes) - 1 ? '' : ', ');
									}
									echo '</td>';
								}
							}
							catch (PDOException $e) {
								echo '<p>An error occured. Please try again.</p>';
							}
							
						?>	
					</table>
				</div>
			</div>
		</main>
		<div id="overlay" class="popupOverlay"></div>
		<form class="popup" id="addClassPopup" action="includes/add_class.pst.php" method="POST">
			<input type="hidden" name="authToken" value="<?php echo $_SESSION['authToken']; ?>">
			<p>Enter Class Code</p><br>
			<input type="text" id="addClassCodeBox" name="code" placeholder="Class Code" oninput="checkAddClassCode();" autocomplete="off" spellcheck="false" maxlength="7" value="<?php echo $_GET['code']; ?>"><br>
			<input type="submit" id="addClassSubmit" name="submit" value="Add Class" disabled>
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
			</script>
			<script src="includes/jquery-3.5.1.min.js"></script>  <!-- Need jQuery for Ajax in following script -->
			<script>
				function setAccountType(id, typeLetter) {
					if (confirm("Are you sure you want to change this account's type? (If you accidentally change your own account type you will need another admin to reset it.)")) {
						$.ajax({
							dataType: "json",
							url: "includes/change_account_type.pst.php",
							type: "POST",
							data: {id: id, typeLetter: typeLetter, authToken: "<?php echo $_SESSION['authToken']; ?>"},
							success: function(data) {
								if (data.success == true) {
									if (data.logout == true) window.location.replace('logout.php');
									else location.reload();
								}
								else {
									alert("Type change failed. Please try again.");
								}
							},
							error: function(jqXHR, textStatus, errorThrown) {
								alert("Type change failed. Please try again.");
								location.reload();
							}
						});
					}
				}
			<?php } ?>
		</script>
	</body>
</html>