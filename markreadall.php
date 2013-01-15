<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";

if ($ISDEMO){
	echo "<feeds>";
	echo "</feeds>";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	$q = mysql_query("UPDATE entries SET isread='1', date=date WHERE feedID='".$_GET['feedid']."'");
	echo "<feeds>";
	echo "</feeds>";
}
?>
