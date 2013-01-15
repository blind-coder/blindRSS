<?
include("config.php");
header ("Content-Type: text/plain");
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

$where = "";
if ($_POST["showOnlyUnread"] == "true"){
	$where = "AND isread='0'";
}
$q = mysql_query("SELECT entries.*
	FROM entries
	WHERE feedID = ".mysql_real_escape_string($_POST['id'])."
	$where");
$json = array();
while ($r = mysql_fetch_array($q)){
	foreach (array_keys($r) as $key){
		$r[$key] = utf8_encode($r[$key]);
	}
	$json[] = $r;
}
echo json_encode($json);
?>
