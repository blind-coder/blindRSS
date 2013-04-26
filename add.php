<?
include("etc/config.php");
header ("Content-Type: text/plain");

function my_mysql_query($SQL){
	$q = mysql_query($SQL);
	if (mysql_error()){
		$data["status"] = "error";
		$data["msg"] = mysql_error();
		echo json_encode($data);
		exit();
	}
	return $q;
}

$data["status"] = "error";
$data["msg"] = "Unknown error!";

if ($ISDEMO){
	$data["msg"] = "This is just a demo page!";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	if ($_GET["name"] == ""){
		$f = fopen($_GET["url"], "rb");
		$stream = stream_get_contents($f);
		fclose($f);
		$stream = preg_replace("/\n/", "", $stream);
		preg_match("/<title>([^<]*)<\/title>/", $stream, $matches);
		$_GET["name"] = $matches[1];
		if ($_GET["name"] == "")
			$_GET["name"] = "Unknown feed";
	}

	foreach (Array("name", "url", "parentID") as $x)
		$_GET[$x] = mysql_real_escape_string($_GET[$x]);

	$q = my_mysql_query("SELECT * FROM feeds WHERE `url` = '".$_GET['url']."' AND `url` != ''");
	if (mysql_num_rows($q) > 0){
		$r = mysql_fetch_object($q);
		$data["msg"] = "Duplicate URL. URL in use in feed {$r->name}!";
	} else {
		$q = my_mysql_query("SELECT * FROM feeds WHERE ID = '".$_GET['parentID']."'");
		$r = mysql_fetch_object ($q);
		if ($r->url != ""){
			$data["msg"] = "Feed {$r->ID} with name {$r->name} is NOT a directory!";
		} else {
			mysql_query("UPDATE feeds SET endID=endID+2 WHERE endID >= $r->endID");
			mysql_query("UPDATE feeds SET startID=startID+2 WHERE startID >= $r->endID"); # $r->endID is correct!
			$q = mysql_query("INSERT INTO feeds (startID, endID, name, url) VALUES (".
					 "".$r->endID.", ".
					 "".($r->endID+1).", ".
					 "'".$_GET['name']."', ".
					 "'".$_GET['url']."')");
			$data["status"] = "OK";
			$data["msg"] = "Feed {$_GET['name']} added!";
		}
	}
}

echo json_encode($data);
?>
