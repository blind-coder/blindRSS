<?
include("config.php");
header ("Content-Type: text/plain");

$json = array();
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
$q = mysql_query("SELECT * FROM entries WHERE description LIKE '%".mysql_real_escape_string($_POST['needle'])."%' ORDER BY date DESC");
while ($r = mysql_fetch_array($q)){
	foreach (array_keys($r) as $key){
		$r[$key] = utf8_encode($r[$key]);
	}
	$json[] = $r;
}
echo json_encode($json);
?>
