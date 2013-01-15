<?
include("config.php");
header ("Content-Type: text/plain");

$json = array();
if ($ISDEMO){
	$json["error"] = "This is just a demo page.";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	$q = mysql_query("SELECT * FROM feeds WHERE ID='".mysql_real_escape_string($_POST['id'])."'");
	$feed = mysql_fetch_object($q);
	mysql_query("DELETE FROM feeds WHERE ID = $feed->ID LIMIT 1");
	if (mysql_error()){
		$json["error"] .= mysql_error();
	}
	mysql_query("UPDATE feeds SET endID=endID-2 WHERE endID >= $feed->endID");
	if (mysql_error()){
		$json["error"] .= mysql_error();
	}
	mysql_query("UPDATE feeds SET startID=startID-2 WHERE startID >= $feed->endID"); # $feed->endID is correct!
	if (mysql_error()){
		$json["error"] .= mysql_error();
	}
}
print json_encode($json);
?>
