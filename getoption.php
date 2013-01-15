<?
include("config.php");
header ("Content-Type: text/plain");

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

mysql_query("INSERT IGNORE INTO options (`key`, `value`) VALUES ('".mysql_real_escape_string($_POST['key'])."', '".mysql_real_escape_string($_POST['default'])."')");
$q = mysql_query("SELECT * FROM options WHERE `key`='".mysql_real_escape_string($_POST['key'])."'");
$json = array();
if (mysql_error()){
	$json["error"] = mysql_error();
} elseif (mysql_num_rows($q) == 0){
	$json["error"] = "No such option '".$_POST['key']."' (huh?!)";
} else {
	$option = mysql_fetch_object($q);
	$json[$option->key] = $option->value;
}
echo json_encode($json);
?>
