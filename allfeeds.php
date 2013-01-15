<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n\n";
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
if ($_GET['onlychecked'] == 1){
	$q = mysql_query("SELECT * FROM entries WHERE isread='0' ORDER BY feedID ASC, date DESC");
} else {
	$q = mysql_query("SELECT * FROM entries ORDER BY feedID ASC, date DESC");
}
if (mysql_num_rows($q) == 0){
	exit;
}
echo "<feeds>";
$oldID = 0;
while ($r = mysql_fetch_object($q)){
	if ($oldID != $r->feedID){
		if (0 != $oldID){
			echo "</feed>";
		}
		$oldID = $r->feedID;
		echo "<feed><id>".$oldID."</id>";
	}
	if ($r->title == ""){
		$r->title="no title";
	}
	echo "<item><id>$r->ID</id><title>".htmlspecialchars($r->title)."</title><content>";
	foreach (split("\n", str_replace("<p>", "\n", str_replace("<br>", "\n", $r->description))) as $key => $value){
		$value = htmlspecialchars($value);
		if ($value == ""){
			$value = "&amp;nbsp;";
		}
		echo "<line>".$value."</line>";
	}
	echo "</content><link>".htmlspecialchars($r->link)."</link><isread>$r->isread</isread></item>";
}
echo "</feed>";
echo "</feeds>";
?>
