<?
include("config.php");
header ("Content-Type: text/plain");
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

$where = "";
if (array_key_exists("feedID", $_POST)){
	$where = "WHERE feeds.ID = ".$_POST["feedID"];
}
$q = mysql_query("SELECT feeds.*,
	(SELECT COUNT(*) FROM entries WHERE feedID = feeds.ID AND isread='0') AS entries
	FROM feeds
	$where
	ORDER BY startID ASC");
$json = array();
while ($r = mysql_fetch_array($q)){
	foreach (array_keys($r) as $key){
		$r[$key] = utf8_encode($r[$key]);
	}
	$json[] = $r;
}
echo json_encode($json);
?>
