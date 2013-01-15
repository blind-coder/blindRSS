<?
include("config.php");
header ("Content-Type: text/plain");

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
if (!$ISDEMO){
	mysql_query("UPDATE entries SET isread='1', date=date WHERE ID='".$_POST['id']."'");
}
$q = mysql_query("SELECT feeds.* , (SELECT COUNT(ID) FROM entries WHERE entries.feedID = feeds.ID AND entries.isread = '0') AS entries
	FROM feeds
	WHERE feeds.ID = ".$_POST['feed']."
	ORDER BY startID ASC");
$json = array();
while ($x = mysql_fetch_array($q)){
	$json[] = $x;
}
echo json_encode($json);
?>
