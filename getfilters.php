<?
include("config.php");
header ("Content-Type: text/plain");
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

$feedID = mysql_real_escape_string($_POST["id"]);

$q = mysql_query("SELECT * FROM filter WHERE feedID=$feedID ORDER BY ID ASC");
$json = array();
while ($r = mysql_fetch_array($q)){
	$json[] = $r;
}
echo json_encode($json);
?>
