<?
include("config.php");
header ("Content-Type: text/xml");
echo utf8_encode("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n\n");
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
if ($_GET['onlychecked'] == 1){
	$q = mysql_query("SELECT * FROM entries WHERE isread='0' ORDER BY feedID ASC, date DESC");
} else {
	$q = mysql_query("SELECT * FROM entries ORDER BY feedID ASC, date DESC");
}
echo utf8_encode("<feeds>");
if (mysql_num_rows($q) == 0){
	echo utf8_encode("</feeds>");
	exit;
}
$oldID = 0;
while ($r = mysql_fetch_object($q)){
	if ($oldID != $r->feedID){
		if (0 != $oldID){
			echo utf8_encode("</feed>");
		}
		$oldID = $r->feedID;
		echo utf8_encode("<feed><id>".$oldID."</id>");
	}
	if ($r->title == ""){
		$r->title="no title";
	}
	echo utf8_encode("<item><id>$r->ID</id><title>".htmlspecialchars($r->title)."</title><content>");
	foreach (split("\n", str_replace("<p>", "\n", str_replace("<br>", "\n", $r->description))) as $key => $value){
		$value = htmlspecialchars($value);
		if ($value == ""){
			$value = "&amp;nbsp;";
		}
		echo utf8_encode("<line>".$value."</line>");
	}
	echo utf8_encode("</content><link>".htmlspecialchars($r->link)."</link><isread>$r->isread</isread></item>");
}
echo utf8_encode("</feed>");
echo utf8_encode("</feeds>");
?>
