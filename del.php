<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";

if ($ISDEMO){
	echo "<status>This is just a demo page.</status>";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	$q = mysql_query("SELECT * FROM feeds WHERE ID='".mysql_real_escape_string($_GET['id'])."'");
	$feed = mysql_fetch_object($q);
	if ($feed->url == "SPECIAL"){
		echo "<status>Refusing to delete special feed!</status>";
		exit;
	}
	mysql_query("DELETE FROM feeds WHERE ID = $feed->ID LIMIT 1");
	mysql_query("UPDATE feeds SET endID=endID-2 WHERE endID >= $feed->endID");
	mysql_query("UPDATE feeds SET startID=startID-2 WHERE startID >= $feed->endID"); # $feed->endID is correct!
	echo "<status>";
	if (mysql_error()){
		echo mysql_error();
	} else {
		echo "Feed removed";
	}
	echo "</status>";
}
?>
