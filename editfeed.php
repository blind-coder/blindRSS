<?
include("config.php");
header ("Content-Type: text/plain");

$json = array();
if ($ISDEMO){
	$json["error"] = "This is just a demo page.";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['id'])."'");
	if (!(mysql_num_rows($q) > 0)){
		$json["error"] = "Unkonwn feed ID!";
		echo json_encode($json);
		exit;
	}

	$feedToChange = mysql_fetch_object($q);
	if ($feedToChange->startID == 1){
		$json["error"] = "Refusing to change master directory!";
		echo json_encode($json);
		exit;
	}

	$query = sprintf("UPDATE `feeds` SET url='%s', name='%s', cacheimages='%s' WHERE ID='%s'",
		mysql_real_escape_string($_POST['newURL']),
		mysql_real_escape_string($_POST['newName']),
		mysql_real_escape_string($_POST['cacheimages']),
		mysql_real_escape_string($_POST['id']));

	mysql_query($query);
	if ($_POST['newURL'] != $feedToChange->url){
		$json["status"] .= "Updated URL. ";
	}

	if ($_POST['newName'] != $feedToChange->name){
		$json["status"] .= "Updated title. ";
	}

	if ($_POST['cacheimages'] != $feedToChange->cacheimages){
		$json["status"] .= "Changed caching behaviour. ";
	}

	$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['moveAbove'])."'");
	$feedToMoveTo = mysql_fetch_object($q);

	if ($feedToChange->ID != $feedToMoveTo->ID){
	if ($feedToMoveTo->url == ""){{{
		mysql_query("UPDATE feeds SET movedirection = 'moveme' WHERE startID >= $feedToChange->startID AND endID <= $feedToChange->endID");
		$difference = ($feedToChange->endID - $feedToChange->startID) + 1;

		mysql_query("UPDATE feeds SET startID = startID - $difference WHERE startID > $feedToChange->endID");
		mysql_query("UPDATE feeds SET endID = endID - $difference WHERE endID > $feedToChange->endID");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['moveAbove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['id'])."'");
		$feedToChange = mysql_fetch_object($q);

		mysql_query("UPDATE feeds SET startID = startID + $difference WHERE startID >= $feedToMoveTo->endID");
		mysql_query("UPDATE feeds SET endID = endID + $difference WHERE endID >= $feedToMoveTo->endID");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['moveAbove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['id'])."'");
		$feedToChange = mysql_fetch_object($q);

		/* Move to Top
		mysql_query("UPDATE feeds SET startID = startID - $feedToChange->startID + $feedToMoveTo->startID + 1 WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET endID = endID - $feedToChange->startID + $feedToMoveTo->startID + 1 WHERE movedirection = 'moveme'");
		 */
		mysql_query("UPDATE feeds SET startID = startID - $feedToChange->startID + $feedToMoveTo->endID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET endID = endID - $feedToChange->startID + $feedToMoveTo->endID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET movedirection = 'none'");

			/*
			   Start				End
		1  1----------+                             1  1----------+
		   |          |                                |          |
		2  |          2------------+                2  |          2------------+
		   |          |            |                   |          |            |
		3  |          |            3-------------+  3  |          |            3-------------+
		   |          | (2)Windows | (6)Appl     |     |          | (3)Linux   | (5)Strategy |
		4  |          |            4-------------+  4  |          |            4-------------+
		   |          |            |                   |          |            |
		5  |          5------------+                5  |          5------------+
		   |          |                                |          |
		6  |          6------------+                6  |          6------------+
		   |          | (3)Linux   |                   |          |            |
		7  |          7------------+                7  |          |            7------------+
		   | (1)Games |                                | (1)Games |            |            |
		8  |          8------------+                8  |          |            |            8-------------+
		   |          | (5)MIPS    |                   |          | (4)Mac     | (2)Windows | (6)Appl     |
		9  |          9------------+                9  |          |            |            9-------------+
		   |          |                                |          |            |            |
		10 |          10-----------+                10 |          |            10-----------+
		   |          | (4)Mac     |                   |          |            |
		11 |          11-----------+                11 |          11-----------+
		   |          |                                |          |
		12 12---------+                             12 12---------+ */
		$json["status"] .= "Feed moved. ";
	}}}
	else {{{
		mysql_query("UPDATE feeds SET movedirection = 'moveme' WHERE startID >= $feedToChange->startID AND endID <= $feedToChange->endID");
		$difference = ($feedToChange->endID - $feedToChange->startID) + 1; # 6

		mysql_query("UPDATE feeds SET startID = startID - $difference WHERE startID > $feedToChange->endID");
		mysql_query("UPDATE feeds SET endID = endID - $difference WHERE endID > $feedToChange->endID");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['moveAbove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['id'])."'");
		$feedToChange = mysql_fetch_object($q);
#4-5
		mysql_query("UPDATE feeds SET startID = startID + $difference WHERE startID >= $feedToMoveTo->startID AND movedirection = 'none'");
		mysql_query("UPDATE feeds SET endID = endID + $difference WHERE endID > $feedToMoveTo->startID AND movedirection = 'none'");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['moveAbove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_POST['id'])."'");
		$feedToChange = mysql_fetch_object($q);

#10-11
		mysql_query("UPDATE feeds SET startID = startID - $feedToChange->startID + $feedToMoveTo->startID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET endID = endID - $feedToChange->startID + $feedToMoveTo->startID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET movedirection = 'none'");
		$json["status"] = "Feed moved. ";
/*
$feedToChange = (                                $feedToMoveTo = (
    [ID] => 75                                       [ID] => 1
    [startID] => 154                                 [startID] => 5
    [endID] => 155                                   [endID] => 6
    [name] => NNFB                                   [name] => TechSucks
    [url] => http://feeds.feedburner.com/nnfb        [url] => http://blog.crash-override.net/rss.php
    [movedirection] => none                          [movedirection] => none
)                                                ) */

	}}}
	}
	if (!array_key_exists("filters", $_POST)){
		echo json_encode($json);
		exit;
	}

	mysql_query("DELETE FROM filter WHERE feedID = ".$feedToChange->ID);

	$filters = json_decode($_POST["filters"]);
	foreach ($filters AS $key => $value){
		$query = sprintf("INSERT INTO filter (`feedID`, `regex`, `whiteorblack`) VALUES ('%s', '%s', '%s')", $feedToChange->ID, mysql_real_escape_string($value->regex), mysql_real_escape_string($value->whiteorblack));
		mysql_query($query);
		if (mysql_error()){
			$json["error"] .= " ".mysql_error().": $query";
		}
	}
	$json["status"] .= "Updated filters.";

	echo json_encode($json);
}
?>
