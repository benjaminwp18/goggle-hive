<?php session_start(); ?>
<nav>
	<a href="../index.php" id="companyName">Goggle Hive</a>
	<a href="../index.php" id="smallCompanyName">GgHve</a>
	<a href="../index.php" id="tinyCompanyName">GH</a>
	
	<div id="navLinksWrapper">
		<a id="infoLink" href="https://example.com">Info</a>
		<a id="smallInfoLink" href="../info.php">?</a>
		
		<?php if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === TRUE) { ?>
		
			<?php if ($_SESSION['type'] == 'admin') { ?>
				<a href="../account_viewer.php">Accounts</a>
			<?php } ?>
			<a href="../my_classes.php">Classes</a>
			<a href="../logout.php">Logout</a>
			
			<p id="accountName" title="
				<?php echo htmlspecialchars(strtoupper(substr($_SESSION['type'], 0, 1)) . substr($_SESSION['type'], 1) . ' ' . $_SESSION['uid']);			/* "Type name" */ ?>
			">
				<?php echo '<b>' . htmlspecialchars(strtoupper(substr($_SESSION['type'], 0, 1))) . '</b>&nbsp;' . htmlspecialchars($_SESSION['uid']); ?>	<!-- "T name" -->
			</p>
			<p id="smallAccountName" title="
				<?php echo htmlspecialchars(strtoupper(substr($_SESSION['type'], 0, 1)) . substr($_SESSION['type'], 1) . ' ' . $_SESSION['uid']);			/* "Type name" */ ?>
			">
				<?php echo htmlspecialchars(substr($_SESSION['uid'], 0, 2)); ?>																				<!-- "na" -->
			</p>
			
		<?php } else { ?>
			
			<a href="../signup.php">Signup</a>
			<a href="../login.php">Login</a>
			
		<?php } ?>
	</div>
</nav>