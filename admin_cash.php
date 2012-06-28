<?php
require_once("../../class2.php");
require_once(e_ADMIN."auth.php");
if(!getperms("P")){ header("location:".e_BASE."index.php"); exit;}
include_lan(e_PLUGIN."anteup/languages/".e_LANGUAGE.".php");
require_once(e_HANDLER."calendar/calendar_class.php");
require_once(e_PLUGIN."anteup/_class.php");
$gen = new convert();
$cal = new DHTML_Calendar(true);

$pageid = "admin_menu_02";

$text = "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' type='text/javascript'></script>
<script src='".e_PLUGIN."anteup/js/addentry.js' type='text/javascript'></script>
<style>
.addentry { display:none; }
</style>";


if(e_QUERY){
	$tmp = explode('.', e_QUERY);
	$action = $tmp[0];
	$id = $tmp[1];
	$esd = $tmp[2];
	$eed = $tmp[3];
	unset($tmp);
}

if(isset($_POST['main_delete'])){
	$delete_id = array_keys($_POST['main_delete']);
	$message = ($sql2->db_Delete("anteup_ipn", "ipn_id=".intval($delete_id[0]))) ? "Entry successfully deleted." : "Error deleting entry.";
}

if(isset($_POST['addentry'])){
	if($sql2->db_Select("user","*", "user_name='".$tp->toDB($_POST['item_name'])."'")){
		$row = $sql2->db_Fetch();
		$buyer_email = $row['user_email'];
		$user_id = $row['user_id'];
	}else{
		$buyer_email = "";
		$user_id = "";
	}
	$pd = explode("/", $_POST['payment_date']);
	$pd_ts = mktime(0, 0, 0, $pd[0], $pd[1], $pd[2]);

	$sql->db_Insert("anteup_ipn", "'', '".$tp->toDB($_POST['item_name'])."', '".$tp->toDB($_POST['payment_status'])."', '".$tp->toDB($_POST['mc_gross'])."', '".intval($_POST['mc_currency'])."', '".$tp->toDB($_POST['txn_id'])."', '".intval($user_id)."', '".$buyer_email."', '".$pd_ts."', '".$tp->toDB($_POST['mc_fee'])."', '', '".intval($_POST['type'])."', '".$tp->toDB($_POST['comment'])."', '".$tp->toDB($_POST['custom'])."'") or $message = mysql_error();
	$message = ($message ? $message : "Entry successfully added.");
}

if(isset($_POST['editentry'])){	
	$sql->db_Update("anteup_ipn", "item_name='".$tp->toDB($_POST['item_name'])."', payment_status='".$tp->toDB($_POST['payment_status'])."', mc_gross='".$tp->toDB($_POST['gross'])."', txn_id='".$tp->toDB($_POST['txn_id'])."', mc_fee='".$tp->toDB($_POST['fee'])."', type=".intval($_POST['type']).", comment='".$tp->toDB($_POST['comment'])."', custom='".$tp->toDB($_POST['custom'])."' WHERE ipn_id=".intval($_POST['id'])) or $message = mysql_error();
	$message = ($message ? $message : "Entry successfully edited.");
}

if(isset($message)){
	$action = "";
	$ns->tablerender("", "<div style='text-align:center;'><b>".$message."</b></div>");
}

if(isset($_POST['setdates'])){
	$sdt = explode("/", $_POST['sd']);
	$edt = explode("/", $_POST['ed']);
}else{
	$sdt = explode("/", date("m/d/Y", strtotime("first day of last month")));
	$edt = explode("/", date("m/d/Y", strtotime("last day of this month")));
}

$sd_ts = mktime(0, 0, 0, $sdt[0], $sdt[1], $sdt[2]);
$sd = date('m/d/Y', $sd_ts);
$ed_ts = mktime(23, 59, 59, $edt[0], $edt[1], $edt[2]);
$ed = date('m/d/Y', $ed_ts);

$total = 0;
$partial = 0;
if ($pref['anteup_ibalance'] != 0) {
   $partial = $pref['anteup_ibalance'];
   $total = $pref['anteup_ibalance'];
}
$sql -> db_Select("anteup_ipn", "*", "payment_date > '0' ORDER BY payment_date ASC, type ASC, ipn_id ASC");
while($row = $sql->db_Fetch()) {
	extract($row);
	if(empty($type) || $type==0){
		$total += ($mc_gross-$mc_fee);
		if($payment_date < $sd_ts){
			$partial += ($mc_gross-$mc_fee);
		}
	}else{
		$total -= ($mc_gross-$mc_fee);
		if ($payment_date < $sd_ts) {
			$partial -= ($mc_gross-$mc_fee);
		}
	}
}
$countl = 0;

$text .= $cal->load_files()."
<div style='text-align:center' class='fborder' >
<form method='post' action='".e_SELF."'>
<table style='width:100%;' class='fborder' cellspacing='0' cellpadding='0'>
	<tr>
		<td style='width:40%; font-size:1.4em;'>
			<b>Balance</b>: ".format_currency($total, $pref['anteup_currency'])."
			<span style='padding-left:10px;'><a href='#' class='toggleadd'><img src='".e_PLUGIN."anteup/images/admin/money_add.png' title='Add New Entry' style='border: 0px;'></a></span>
		</td>
		<td style='width:25%; text-align:right;'>
			Start Date: <input type='text' class='tbox' name='sd' id='sd' value='".$sd."' /> <a href='#' id='f-calendar-trigger-1'>".CALENDAR_IMG."</a><script type='text/javascript'>Calendar.setup({'ifFormat':'%m/%d/%Y','daFormat':'%m/%d/%Y','inputField':'sd','button':'f-calendar-trigger-1'});</script>
		</td>
		<td style='width:25%; text-align:right;'>
			End Date: <input type='text' class='tbox' name='ed' id='ed' value='".$ed."' /> <a href='#' id='f-calendar-trigger-2'>".CALENDAR_IMG."</a><script type='text/javascript'>Calendar.setup({'ifFormat':'%m/%d/%Y','daFormat':'%m/%d/%Y','inputField':'ed','button':'f-calendar-trigger-2'});</script>
		</td>
		<td style='width:10%; text-align:right;'>
			<input class='button' type='submit' name='setdates' value='Filter' />
		</td>
	</tr>
</table>
</form>

<div class='addentry'>

<form method='post' action='".e_SELF."'>
<table style='width:100%;' class='fborder' cellspacing='0' cellpadding='0'>
	<tr>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Transaction ID:</td>
		<td style='width:20%;' class='forumheader3'><input type='text' name='txn_id' class='tbox' /></td>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Payment Type:</td>
		<td style='width:20%;' class='forumheader3'>
			<select class='tbox' name='type'>
			   <option value='0'>Credit</option>
			   <option value='1'>Debit</option>
			</select>
		</td>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Payment Status:</td>
		<td style='width:20%' class='forumheader3'>
			<select class='tbox' name='payment_status'>
				<option value='Completed'>Completed</option>
				<option value='Pending'>Pending</option>
				<option value='Denied'>Denied</option>
			</select>
		</td>
	</tr>
	<tr>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Payment Date:</td>
		<td style='width:20%;' class='forumheader3'><input class='tbox' type='text' name='payment_date' id='payment_date' /> <a href='#' id='f-calendar-trigger-3'>".CALENDAR_IMG."</a><script type='text/javascript'>Calendar.setup({'ifFormat':'%m/%d/%Y','daFormat':'%m/%d/%Y','inputField':'payment_date','button':'f-calendar-trigger-3'});</script></td>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Payment Amount:</td>
		<td style='width:20%' class='forumheader3'><input class='tbox' style='width:40%;' type='text' name='mc_gross' />
			<select style='width:40%;' class='tbox' name='mc_currency'>";
			$sql->db_Select("anteup_currency", "*");
			while($row = $sql->db_Fetch()){
				$text .= "<option value='".$row['id']."'".($row['id'] == $pref['anteup_currency'] ? " selected" : "").">".$row['code']."</option>";
			}
			$text .= "</select>
		</td>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Fee:</td>
		<td style='width:20%' class='forumheader3'><input class='tbox' type='text' name='mc_fee' /></td>
	</tr>
	<tr>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Donator:</td>
		<td style='width:20%' class='forumheader3'>
		<select class='tbox' name='item_name'>";
		$sql->db_Select("user", "*", "ORDER BY user_name ASC", "no-where");
		while($row = $sql->db_Fetch()){
			$text .= "<option value='".$row['user_name']."'>".$row['user_name']."</option>";
		}
		$text .= "</select>
		</td>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Comment:</td>
		<td style='width:20%' class='forumheader3'><input class='tbox' type='text' name='comment' /></td>
		<td style='width:13%; text-align:right; vertical-align:middle;' class='forumheader3'>Custom:</td>
		<td style='width:20%' class='forumheader3'><input class='tbox' type='text' name='custom' /></td>
	</tr>
	<tr>
		<td colspan='6' style='text-align:center;' class='formheader3'>
			<input class='button' type='submit' name='addentry' value='Add Entry' />
			<input class='button toggleadd' type='button' value='Cancel' />
		</td>
	</tr>
</table>
</form>
</div>

<table style='width:100%; border:1px;' class='fborder' cellspacing='0' cellpadding='0'>
	<tr>
		<td colspan=10 class='fcaption' style='text-align: center;'><b>Donation Invoice</b></td>
	</tr>
	<tr>
		<td style='width:5; text-align:center;' class='forumheader3'>ID</td>
		<td style='width:10%; text-align:center;' class='forumheader3'>Date</td>
		<td style='width:15%; text-align:left;' class='forumheader3'>Description</td>
		<td style='width:20%; text-align:left;' class='forumheader3'>Transaction ID</td>
		<td style='width:10%; text-align:center;' class='forumheader3'>Status</td>
		<td style='width:10%; text-align:center;' class='forumheader3'>Type</td>
		<td style='width:5%; text-align:right;' class='forumheader3'>Amount</td>
		<td style='width:10%; text-align:right;' class='forumheader3'>Fee</td>
		<td style='width:10%; text-align:right;' class='forumheader3'>Total</td>
		<td style='width:5%; text-align:center;' class='forumheader3'>&nbsp;</td>
	</tr>";
 
$countl += 5;
if($pref['anteup_ibalance'] != 0){
	$text .= "<tr>
		<td colspan='8' class='fcaption' style='text-align: center;'>Initial Balance</td>
		<td style='width:10%; text-align:right;' class='fcaption'>".format_currency($partial, $pref['anteup_currency'])."</td>
		<td class='fcaption'>&nbsp;</td>
	</tr>";
	$countl += 1;
}

$flag = 0;
$bgn = 1;

// make sure we keep the date filtering active, even if we're editing an entry
if($action == "edit"){
	$sql -> db_Select("anteup_ipn", "*", "payment_date > '".$esd."' AND payment_date < '".$eed."' ORDER BY payment_date DESC");
}else if(isset($_POST['editentry'])){
	$sql -> db_Select("anteup_ipn", "*", "payment_date > '".$_POST['sd']."' AND payment_date < '".$_POST['ed']."' ORDER BY payment_date DESC");
}else{
	$sql -> db_Select("anteup_ipn", "*", "payment_date > '".$sd_ts."' AND payment_date < '".$ed_ts."' ORDER BY payment_date DESC");
}
while($row = $sql->db_Fetch()){
	extract($row);
	if(empty($type) || $type == 0){
		$partial += ($mc_gross-$mc_fee);
		$typex = "<span style='color:#009900;'>Credit</span>";
	}else{
		$partial -= ($mc_gross-$mc_fee);
		$typex = "<span style='color:#ff0000;'>Debit</span>";
	}
	
	$bgn = ($bgn == 1 ? 0 : 1);
	$bgc = ($bgn == 1 ? "#f2f2f2": "#fff");
	$ppc = ($partial < 0 ? "#009900" : "#000");
	
	if($action == "edit" && $id == $ipn_id){
		$text .= "<form method='post' action='".e_SELF."'>
		<tr>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>".$ipn_id."</td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>".$gen->convert_date(strtotime($payment_date), $pref['anteup_dformat'])."</td>
			<td style='text-align:left; background-color: ".$bgc.";' class='forumheader'><input class='tbox' type='text' name='item_name' value='".$item_name."' /><br /><input class='tbox' type='text' name='custom' value='".$custom."' /></td>
			<td style='text-align:left; background-color: ".$bgc.";' class='forumheader'><input class='tbox' type='text' name='comment' value='".trim($comment)."' /><br /><input class='tbox' type='text' name='txn_id' value='".$txn_id."' /></td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>
			<select class='tbox' name='payment_status'>
			   <option".(($payment_status == "Completed") ? " selected ='selected'" : "")." value='Completed'>Completed</option>
			   <option".(($payment_status == "Pending") ? " selected ='selected'" : "")." value='Pending'>Pending</option>
			   <option".(($payment_status == "Denied") ? " selected ='selected'" : "")." value='Denied'>Denied</option>
			</select>
			</td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>
			<select class='tbox' name='type'>
			   <option ".(($type == 0) ? " selected ='selected'" : "")." value='0'>Credit</option>
			   <option ".(($type == 1) ? " selected ='selected'" : "")." value='1'>Debit</option>
			</select>
			</td>
			<td style='text-align:right; background-color: ".$bgc.";' class='forumheader'><input class='tbox' type='text' name='gross' value='".$mc_gross."' /></td>
			<td style='text-align:right; background-color: ".$bgc.";' class='forumheader'><input class='tbox' type='text' name='fee' value='".$mc_fee."' /></td>
			<td style='text-align:right; background-color: ".$bgc."; color: ".$ppc.";' class='forumheader'>".format_currency(($mc_gross-$mc_fee), $pref['anteup_currency'])."</td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>
			<input type='hidden' name='sd' value='".$esd."' />
			<input type='hidden' name='ed' value='".$eed."' />
			<input type='hidden' name='id' value='".$id."' />
			<input class='button' type='submit' name='editentry' value='Save' />
			</td>
		</tr>
		</form>";
	}else{
		$text .= "
		<tr>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>".$ipn_id."</td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>".$gen->convert_date(strtotime($payment_date), $pref['anteup_dformat'])."</td>
			<td style='text-align:left; background-color: ".$bgc.";' class='forumheader'>".$item_name.($custom !=" "? "<br>".$custom:"")."</td>
			<td style='text-align:left; background-color: ".$bgc.";' class='forumheader'>".trim($comment." ".$txn_id)."</td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>".$payment_status."</td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>".$typex."</td>
			<td style='text-align:right; background-color: ".$bgc.";' class='forumheader'>".format_currency($mc_gross, $pref['anteup_currency'])."</td>
			<td style='text-align:right; background-color: ".$bgc.";' class='forumheader'>".format_currency($mc_fee, $pref['anteup_currency'])."</td>
			<td style='text-align:right; background-color: ".$bgc."; color: ".$ppc.";' class='forumheader'>".format_currency(($mc_gross-$mc_fee), $pref['anteup_currency'])."</td>
			<td style='text-align:center; background-color: ".$bgc.";' class='forumheader'>
			<a href='".e_SELF."?edit.".$ipn_id.".".$sd_ts.".".$ed_ts."'>".ADMIN_EDIT_ICON."</a><input type='image' title='Edit' name='main_delete[".$ipn_id."]' src='".e_PLUGIN."anteup/images/admin/delete_16.png' onclick=\"return jsconfirm('Are you sure you want to delete this entry? [ID: ".$ipn_id." ]')\"/>
			</td>
		</tr>";
	}

	$countl += 1;
	$flag++;
}
$text .= ($flag == 0  ? "<tr><td colspan='10' style='text-align:center;' class='forumheader'>There are no entries to display during the selected time periods.</td></tr>" : "")."
</table>
</div>";

$ns->tablerender("Cash Manager", $text);
require_once(e_ADMIN."footer.php");

?>