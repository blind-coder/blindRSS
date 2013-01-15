<?
include("config.php");

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

mysql_query("INSERT IGNORE INTO options (value, key) VALUES ('".mysql_real_escape_string($_POST['value'])."', '".mysql_real_escape_string($_POST['key'])."')");
mysql_query("UPDATE IGNORE options SET value='".mysql_real_escape_string($_POST['value'])."' WHERE `key`='".mysql_real_escape_string($_POST['key'])."'");
?>
