<?
include("config.php");
header ("Content-Type: text/plain");

$json = array();
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

if ($ISDEMO){
	$json["error"] = "This is a demo page!";
} else {
	mysql_query("UPDATE entries SET isread='1', date=date WHERE feedID='".$_POST['feedID']."' AND entries.isread = '0'");
}
echo json_encode($json);
?>
