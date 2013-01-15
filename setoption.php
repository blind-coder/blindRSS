<?
include("config.php");

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

mysql_query("INSERT IGNORE INTO options (value, key) VALUES ('".mysql_real_escape_string($_GET['value'])."', '".mysql_real_escape_string($_GET['key'])."')");
mysql_query("UPDATE IGNORE options SET value='".mysql_real_escape_string($_GET['value'])."' WHERE `key`='".mysql_real_escape_string($_GET['key'])."'");
header ("Location: getoption.php?key=".$_GET['key']);
?>
