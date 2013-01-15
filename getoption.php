<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

mysql_query("INSERT IGNORE INTO options (`key`, `value`) VALUES ('".mysql_real_escape_string($_GET['key'])."', '".mysql_real_escape_string($_GET['default'])."')");
$q = mysql_query("SELECT * FROM options WHERE `key`='".mysql_real_escape_string($_GET['key'])."'");
echo "<option>";
if (mysql_error()){
	echo "<key>error</key>";
	echo "<value>";
	echo mysql_error();
	echo "</value>";
} elseif (mysql_num_rows($q) == 0){
	echo "<key>error</key>";
	echo "<value>No such option '".$_GET['key']."' (huh?!)</value>";
} else {
	$option = mysql_fetch_object($q);
	echo "<key>".$option->key."</key>";
	echo "<value>".$option->value."</value>";
}
echo "</option>";
?>
