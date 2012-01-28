<?php
//Created by the unblock-en-l dev team (test commit)
error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('session.use_cookies', '1');

require_once('../src/unblocklib.php');
require_once('../src/exceptions.php');
require_once('../src/appealObject.php');
require_once('../src/userObject.php');
require_once('../src/templateObj.php');
require_once('template.php');

// make sure user is logged in, if not, kick them out
verifyLogin('appeal.php?id=' . $_GET['id']);

$errorMessages = '';

//Template header()
skinHeader();

if (!is_numeric($_GET['id'])) {
	throw new UTRSIllegalModificationException('Appeal id is not numeric.');
}

//construct appeal object
$appeal = Appeal::getAppealByID($_GET['id']);

//construct user object
$user = User::getUserByUsername($_SESSION['user']);

//Set the handling admin
if (isset($_GET['action']) && $_GET['action'] == "reserve"){
	if (isset($_GET['user'])) {
		$appeal->setHandlingAdmin($_GET['user']);
	} else {
		$appeal->setHandlingAdmin($user->getUserId());
	}
	$appeal->update();
}

if (isset($_GET['action']) && $_GET['action'] == "release"){
	$appeal->setHandlingAdmin(null);
	$appeal->update();
}

//Status change
if (isset($_GET['action']) && $_GET['action'] == "status") {
	switch ($_GET['value']) {
		case "checkuser":
			$appeal->setStatus(Appeal::$STATUS_AWAITING_CHECKUSER);
			$appeal->setHandlingAdmin(null);
			break;
		case "user":
			$appeal->setStatus(Appeal::$STATUS_AWAITING_USER);
			break;
		case "hold":
			$appeal->setStatus(Appeal::$STATUS_ON_HOLD);
			break;
		case "proxy":
			$appeal->setStatus(Appeal::$STATUS_AWAITING_PROXY);
			break;
		case "admin":
			$appeal->setStatus(Appeal::$STATUS_AWAITING_ADMIN);
			$appeal->setHandlingAdmin(null);
			break;
		case "close":
			$appeal->setStatus(Appeal::$STATUS_CLOSED);
			break;
	}
	$appeal->update();
}
?>
<script language="Javascript" type="text/javascript">

function doClose() {
	var response = confirm("Do you want to send a response to the user?")
	if (response) {
		window.location='sendEmail.php?tid=22&id=<?php echo $_GET['id']; ?>';
	} else {
		window.location='?id=<?php echo $_GET['id']; ?>&action=status&value=close';
	}
}

</script>
<div id='appealContent'>
<h1>Details for Request #<?php echo $appeal->getID(); ?>: <a href="<?php echo getWikiLink($appeal->getUserPage(), $user->getUseSecure()); ?>" target="_new"><?php echo $appeal->getCommonName(); ?></a></h1>
<table class="appeal">
<tr>
<td valign=top class="left">
Account links: <a href="<?php echo getWikiLink($appeal->getUserPage(), $user->getUseSecure()); ?>" target="_new">User Page</a> | <a href="<?php echo getWikiLink("Special:Block/" . $appeal->getCommonName(), $user->getUseSecure()); ?>" target="_new">Block Log</a> | <a href="<?php echo getWikiLink("Special:Contributions/" . $appeal->getCommonName(), $user->getUseSecure()); ?>" target="_new">Contribs</a><br>
Request timestamp: <?php echo $appeal->getTimestamp(); ?><br>
Status: <b><?php echo $appeal->getStatus(); ?></b><br>
<?php if ($appeal->getHandlingAdmin()) {?>
Assigned: <?php $handlingAdmin = $appeal->getHandlingAdmin(); echo $handlingAdmin->getUsername(); $handlingAdmin = null; ?><br>
<?php } ?>
<?php if (verifyAccess($GLOBALS['CHECKUSER']) || verifyAccess($GLOBALS['ADMIN'])) {?>
<h3>User Agent</h3>
<div class="useragent"><?php echo $appeal->getUserAgent(); ?></div>
<?php }?>
<h3>Appeal</h3>
<div class="info"><?php echo $appeal->getAppeal(); ?></div>
<h3>Other Info</h3>
<div class="info"><?php echo $appeal->getOtherInfo(); ?></div>
<br>
</td>
<td valign=top class="right">
<h3>Actions</h3>
<div style="text-align:center;">
	<?php
	if ($appeal->getHandlingAdmin()) {
		if ($appeal->getHandlingAdmin()->getUserId() == $user->getUserId() || verifyAccess($GLOBALS['ADMIN'])) {
			$disabled = " ";
		} else {
			$disabled = " disabled = 'disabled' ";
		}
		echo "<input type=\"button\" " . $disabled . " value=\"Release\" onClick=\"window.location='?id=" . $_GET['id'] . "&action=release'\">&nbsp;";
	} else {
		if ($appeal->getStatus() != Appeal::STATUS_AWAITING_CHECKUSER && !verifyAccess("CHECKUSER")) {
			$disabled = " disabled = 'disabled' ";
		}
		if ($appeal->getStatus() != Appeal::STATUS_AWAITING_ADMIN && !verifyAccess("ADMIN")) {
			$disabled = " disabled = 'disabled' ";
		}
		echo "<input type=\"button\" " . $disabled . " value=\"Reserve\" onClick=\"window.location='?id=" . $_GET['id'] . "&action=reserve'\">&nbsp;";
	}
	$disabled = "";
	if ($appeal->getStatus() == Appeal::$STATUS_AWAITING_CHECKUSER || !($appeal->getHandlingAdmin() == $user || verifyAccess($GLOBALS['ADMIN']) || verifyAccess($GLOBALS['CHECKUSER']))) {
		$disabled = "disabled='disabled'";
	}
	echo "<input type=\"button\" value=\"Checkuser\" onClick=\"window.location='?id=" . $_GET['id'] . "&action=status&value=checkuser'\">&nbsp;";
	$disabled = "";
	if ($appeal->getStatus() == Appeal::$STATUS_AWAITING_USER || !($appeal->getHandlingAdmin() == $user || verifyAccess($GLOBALS['ADMIN']))) {
		$disabled = "disabled='disabled'";
	}
	echo "<input type=\"button\" value=\"User\" onClick=\"window.location='?id=" . $_GET['id'] . "&action=status&value=user'\">&nbsp;";
	$disabled = "";
	if ($appeal->getStatus() == Appeal::$STATUS_ON_HOLD || !($appeal->getHandlingAdmin() == $user || verifyAccess($GLOBALS['ADMIN']))) {
		$disabled = "disabled='disabled'";
	}
	echo "<input type=\"button\" value=\"Hold\" onClick=\"window.location='?id=" . $_GET['id'] . "&action=status&value=hold'\">&nbsp;";
	$disabled = "";
	if ($appeal->getStatus() == Appeal::$STATUS_AWAITING_PROXY || !($appeal->getHandlingAdmin() == $user || verifyAccess($GLOBALS['ADMIN']))) {
		$disabled = "disabled='disabled'";
	}
	echo "<input type=\"button\" value=\"Proxy\" onClick=\"window.location='?id=" . $_GET['id'] . "&action=status&value=proxy'\">&nbsp;";
	$disabled = "";
	if ($appeal->getStatus() == Appeal::$STATUS_AWAITING_ADMIN && !verifyAccess($GLOBALS['ADMIN'])) {
		$disabled = "disabled='disabled'";
	}
	echo "<input type=\"button\" value=\"Admin\" onClick=\"window.location='?id=" . $_GET['id'] . "&action=status&value=admin'\">&nbsp;";
	$disabled = "";
	if ($appeal->getHandlingAdmin() != $user && !verifyAccess($GLOBALS['ADMIN'])) {
		$disabled = "disabled='disabled'";
	}
	echo "<input type=\"button\" " . $disabled . " value=\"Close\" onClick=\"doClose();\">";
	?>
</div>
<h3>Responses</h3>
<div style="text-align:center;">
	<input type="button" value="Username" onClick="window.location='sendEmail.php?tid=7&id=<?php echo $_GET['id']; ?>'">&nbsp;
	<input type="button" value="Need Info" onClick="window.location='sendEmail.php?tid=9&id=<?php echo $_GET['id']; ?>'">&nbsp;
	<input type="button" value="School" onClick="window.location='sendEmail.php?tid=21&id=<?php echo $_GET['id']; ?>'">&nbsp;
	<input type="button" value="Rangeblock" onClick="window.location='sendEmail.php?tid=11&id=<?php echo $_GET['id']; ?>'">&nbsp;
	<SELECT onChange="if (this.selectedIndex != 0) { window.location='sendEmail.php?tid=' + this.value + '&id=<?php echo $_GET['id']; ?>'}">
		<?php 
			
			$templates = Template::getTemplateList();
			
			if (!$templates) {
				echo "<option>No templates available</option>";
			} else {
			
				echo "<option value='-1'>Please select</option>";
				
				$rows = mysql_num_rows($templates);
				
				for ($i = 0; $i < $rows; $i++) {
					$data = mysql_fetch_array($templates);
					echo "<option value='" . $data['templateID'] . "'>" . $data['name'] . "</option>";
				}
			}
		
		?>
	</SELECT>
</div>
<h3>Logs for this request (<a href="comment.php?id=<?php echo $_GET['id']; ?>">new comment</a>)</h3>
<div class="comments">
</div>
<form><input type="text" style="width:75%;"><input type="submit" style="width:20%" value="Quick Comment"></form>
</td>
</tr>
</table>


</div>







