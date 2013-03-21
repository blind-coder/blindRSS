<?
include("etc/config.php");
function mres($x){
	return mysql_real_escape_string($x);
}
function my_mysql_query($x){
	//error_log($x);
	return mysql_query($x);
}

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
my_mysql_query("SET NAMES 'utf8';");

$method = $_SERVER["REQUEST_METHOD"];
$path = preg_replace(";^/;", "", $_SERVER["PATH_INFO"]);
preg_match(";^(.*?)(\.(.*))?$;", $path, $match);
if (count($match) > 3){
	$format = $match[3];
} else {
	$format = "json";
}
$path = explode("/", $match[1]);

$data = Array();
$data["status"] = "error";
$data["msg"] = "Unknown query / method!";

switch ($path[0]){
	case "feeds": // {{{
		if ($method == "GET"){
			# GET /feeds
			$q = my_mysql_query("SELECT * FROM feeds ORDER BY startID ASC");
			$data = Array();
			while ($r = mysql_fetch_object($q)){
				$data[] = $r;
			}
		}
		elseif ($method == "POST"){
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			# PUT /feeds
			$q = my_mysql_query("SELECT * FROM feeds WHERE `url` = '".$_GET['url']."' AND `url` != ''");
			if (mysql_num_rows($q) > 0){
				$r = mysql_fetch_object($q);
				$data["status"] = "Error";
				$data["msg"] = "Duplicate URL! URL already in use in Feed {$r->name}!";
				break;
			}

			$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($_REQUEST["parent"]));
			if (mysql_error()){
				$data["status"] = "Error";
				$data["msg"] = "Unknown Parent Feed";
				break;
			}

			$r = mysql_fetch_object($q);
			my_mysql_query("UPDATE feeds SET endID=endID+2 WHERE endID >= $r->endID");
			my_mysql_query("UPDATE feeds SET startID=startID+2 WHERE startID >= $r->endID"); # $r->endID is correct!
			$q = my_mysql_query("INSERT INTO feeds (startID, endID, cacheimages, name, url)
				VALUES ({$r->endID}, {$r->endID}+1, '".mres($_REQUEST["cacheimages"])."', '".mres($_REQUEST["name"])."', '".mres($_REQUEST["url"])."')");
			if (mysql_error()){
				$data["status"] = "Error";
				$data["msg"] = mysql_error();
				break;
			}
			$data["status"] = "OK";
			$data["msg"] = "";
		}
		break;
		// }}}
	case "feed": // {{{
		if ($method == "GET"){{{
			# GET /feed/1
			$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			$r = mysql_fetch_object($q);
			if (count($path) < 3){
				$data = $r;
				break;
			}
			switch ($path[2]){
				# GET /feed/1/children
				case "children":
					if ("x".$r->url == "x"){
						$data = Array();
						$q = my_mysql_query("SELECT * FROM feeds WHERE startID > ".mres($r->startID)." AND endID < ".mres($r->endID)." ORDER BY startID ASC");
						while ($r = mysql_fetch_object($q)){
							$data[] = $r;
						}
					} else {
						header("Location: ../../unreadcount/".$r->ID);
					}
					break;
				# GET /feed/1/entries/2013-02-02
				case "entries":
					$data = Array();
					$date = "";
					$q = my_mysql_query("SELECT startID, endID FROM feeds WHERE ID = ".mres($r->ID));
					$r = mysql_fetch_object($q);
					if (count($path) >= 4){
						/* We need to get the first date with entries after one day before $path[4] */
						$q = my_mysql_query($SQL = "SELECT DISTINCT `date` FROM `entries`
							WHERE feedID IN (
								SELECT ID FROM feeds WHERE startID >= {$r->{startID}} AND endID <= {$r->{endID}}
							)
							AND `date` <= '".mres($path[3])." 23:59:59'
							ORDER BY `date` DESC
							LIMIT 1");
						if (!($d = mysql_fetch_object($q))){
							/* no more entries */
							break;
						}
						$date = "AND `date` >= '".substr($d->{"date"}, 0, 10)."' AND `date` < '".mres($path[3])." 23:59:59'";
					}
					$q = my_mysql_query($SQL = "SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE feedID IN (
						SELECT ID FROM feeds WHERE startID >= {$r->{startID}} AND endID <= {$r->{endID}}
					) $date ORDER BY `date` DESC");
					while ($r = mysql_fetch_object($q)){
						$data[] = $r;
					}
					break;
				# GET /feed/1/filter
				case "filter":
					$data = Array();
					$q = my_mysql_query("SELECT * FROM filter WHERE feedID = ".mres($r->ID)." ORDER BY ID ASC");
					while ($r = mysql_fetch_object($q)){
						$data[] = $r;
					}
					break;
			}
		}}}
		elseif ($method == "POST"){{{
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			switch ($path[2]){
				case "markAllRead":
					# POST /feed/1/markAllRead
					$maxID = "";
					if ($_POST["maxID"] > 0){
						$maxID = "AND ID <= ".mres($_POST["maxID"]);
					}
					$q = my_mysql_query("SELECT startID, endID FROM feeds WHERE ID = ".mres($path[1]));
					$feed = mysql_fetch_object($q);

					$q = my_mysql_query("UPDATE entries SET date=date, isread='1'
						WHERE feedID IN (SELECT ID FROM feeds WHERE startID >= {$feed->startID} AND endID <= {$feed->endID})
						AND isread='0' $maxID");
					if (mysql_error()){
						$data["status"] = "error";
						$data["msg"] = mysql_error();
						break;
					}
					$data["status"] = "OK";
					$data["msg"] = "";
					break;
				case "move":
					# POST /feed/1/move
					if (array_key_exists("moveAfterFeed", $_POST)){
						$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
						$feedToChange = mysql_fetch_object($q);
						$gap = ($feedToChange->endID - $feedToChange->startID) + 1;

						/* Take the feed we want to move OUT of the structure */
						my_mysql_query("UPDATE feeds SET startID = startID - {$feedToChange->startID}, endID = endID - {$feedToChange->startID}, movedirection = 'moveme' WHERE startID >= {$feedToChange->startID} AND endID <= {$feedToChange->endID}");

						/* Move everything AFTER it UPWARDS to close the gap */
						my_mysql_query("UPDATE feeds SET startID = startID - $gap WHERE startID > $feedToChange->endID AND movedirection = 'none'");
						my_mysql_query("UPDATE feeds SET endID = endID - $gap WHERE endID > $feedToChange->endID AND movedirection = 'none'");

						$q = my_mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($_POST["moveAfterFeed"])."'");
						$feedToMoveAfter = mysql_fetch_object($q);

						/* Make a gap for the feed we want to move */
						my_mysql_query("UPDATE feeds SET endID=endID+$gap WHERE endID > {$feedToMoveAfter->endID} AND movedirection = 'none'");
						my_mysql_query("UPDATE feeds SET startID=startID+$gap WHERE startID >= {$feedToMoveAfter->endID} AND movedirection = 'none'"); # endID is correct!

						/* Move the feed to the newly created room */
						my_mysql_query("UPDATE feeds SET startID = startID + {$feedToMoveAfter->endID} + 1, endID = endID + {$feedToMoveAfter->endID} + 1, movedirection = 'none' WHERE movedirection = 'moveme'");
						$data["status"] = "OK";
						$data["msg"] = "Feed moved.";
					} elseif (array_key_exists("moveIntoCategory", $_POST)){
						$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
						$feedToChange = mysql_fetch_object($q);
						$gap = ($feedToChange->endID - $feedToChange->startID) + 1;

						/* Take the feed we want to move OUT of the structure */
						my_mysql_query("UPDATE feeds SET startID = startID - {$feedToChange->startID}, endID = endID - {$feedToChange->startID}, movedirection = 'moveme' WHERE startID >= {$feedToChange->startID} AND endID <= {$feedToChange->endID}");

						/* Move everything AFTER it UPWARDS to close the gap */
						my_mysql_query("UPDATE feeds SET startID = startID - $gap WHERE startID > $feedToChange->endID AND movedirection = 'none'");
						my_mysql_query("UPDATE feeds SET endID = endID - $gap WHERE endID > $feedToChange->endID AND movedirection = 'none'");

						$q = my_mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($_POST["moveIntoCategory"])."'");
						$feedToMoveInto = mysql_fetch_object($q);

						/* Make a gap for the feed we want to move */
						my_mysql_query("UPDATE feeds SET endID=endID+$gap WHERE endID >= {$feedToMoveInto->endID} AND movedirection = 'none'");
						my_mysql_query("UPDATE feeds SET startID=startID+$gap WHERE startID >= {$feedToMoveInto->endID} AND movedirection = 'none'"); # endID is correct!

						/* Move the feed to the newly created room */
						my_mysql_query("UPDATE feeds SET startID = startID + {$feedToMoveInto->endID}, endID = endID + {$feedToMoveInto->endID}, movedirection = 'none' WHERE movedirection = 'moveme'");

						$data["status"] = "OK";
						$data["msg"] = "Feed moved.";
					} else {
						$data["status"] = "error";
						$data["msg"] = "Invalid query";
						break;
					}
					break;
			}
		}}}
		elseif ($method == "PUT"){ # {{{
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			# PUT /feed/1
			$UPDATE = "ID = ID";
			$update = json_decode(file_get_contents("php://input"));
			foreach (array("name", "url", "cacheimages", "collapsed") as $k){
				if ("".$update->$k != ""){
					$UPDATE .= ", `$k` = \"".mres($update->$k)."\"";
				}
			}
			my_mysql_query("UPDATE feeds SET $UPDATE WHERE ID = ".mres($path[1])." LIMIT 1");
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}
			foreach ($update->filter as $k => $v){
				if ($v->delete == "true"){
					my_mysql_query("DELETE FROM filter WHERE ID = ".mres($v->ID)." LIMIT 1");
				} else {
					if ($v->ID == "0" && strlen($v->regex) > 0){
						my_mysql_query("INSERT INTO filter (regex, whiteorblack, feedID) VALUES (\"".mres($v->regex)."\", \"".mres($v->whiteorblack)."\", ".mres($path[1]).")");
					} else {
						my_mysql_query("UPDATE filter SET regex = \"".mres($v->regex)."\", whiteorblack = \"".mres($v->whiteorblack)."\" WHERE ID = ".mres($v->ID)." LIMIT 1");
					}
				}
				if (mysql_errno()){
					$data["status"] = "error";
					$data["msg"] = mysql_error();
					break;
				}
			}
			$data["status"] = "OK";
			$data["msg"] = "";
		} # }}}
		elseif ($method == "DELETE"){{{
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			# DELETE /feed/1
			$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			$feed = mysql_fetch_object($q);
			my_mysql_query("DELETE FROM feeds WHERE ID = $feed->ID LIMIT 1");

			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}

			my_mysql_query("UPDATE feeds SET endID=endID-2 WHERE endID >= {$feed->endID}");
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}

			my_mysql_query("UPDATE feeds SET startID=startID-2 WHERE startID >= $feed->endID"); # $feed->endID is correct!
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}

			$data["status"] = "OK";
			$data["msg"] = "";
		}}}
		break;
		// }}}
	case "entry": // {{{
		if ($method == "GET"){
			# GET /entry/1
			$data = Array();
			$q = my_mysql_query("SELECT * FROM entries WHERE ID = ".mres($path[1]));
			if ($r = mysql_fetch_object($q)){
				$data = $r;
				$q = my_mysql_query("SELECT * FROM tags WHERE ID IN (SELECT tagID FROM entries_tags WHERE entryID = {$r->{"ID"}})");
				$data->{"tags"} = Array();
				while ($r = mysql_fetch_object($q)){
					$data->{"tags"}[] = $r;
				}
			} else {
				$data["status"] = "error";
				$data["msg"] = "Unknown ID: ".mres($path[1]);
			}
		}
		elseif ($method == "PUT"){
			if ($ISDEMO){
				$data["status"] = "OK";
				$data["msg"] = "";
				break;
			}
			# PUT /entry/1
			if (count($path) == 2){
				$UPDATE = "date = date";
				$update = json_decode(file_get_contents("php://input"));
				foreach (array("isread", "favorite") as $k){
					if ("".$update->$k != ""){
						$UPDATE .= ", `$k` = \"".mres($update->$k)."\"";
					}
				}
				if ($UPDATE == "date = date"){
					$data["status"] = "error";
					$data["msg"] = "No valid data to update given!";
				} else {
					my_mysql_query("UPDATE entries SET $UPDATE WHERE ID = ".mres($path[1])." LIMIT 1");
					if (mysql_error() == ""){
						$data["status"] = "OK";
						$data["msg"] = "";
					} else {
						$data["status"] = "error";
						$data["msg"] = "Update failed: ".mysql_error();
					}
				}
			} elseif (count($path) == 3){
				# PUT /entry/1/tags
				$data["status"] = "OK";
				$data["msg"] = "";
				$tags = json_decode(file_get_contents("php://input"));
				foreach (explode(",", $tags->{"tags"}) as $t){
					my_mysql_query("INSERT IGNORE INTO tags (tag) VALUES (\"".mres($t)."\")");
					if (mysql_error()){
						$data["status"] = "error";
						$data["msg"] .= "Could not add tag '".mres($t)."': ".mysql_error();
						break;
					}
					my_mysql_query("INSERT INTO entries_tags (entryID, tagID) VALUES (".mres($path[1]).",
						(SELECT ID FROM tags WHERE tag = \"".mres($t)."\"))");
					if (mysql_error()){
						$data["status"] = "error";
						$data["msg"] .= "Could not add tag '".mres($t)."': ".mysql_error();
						break;
					}
				}
				break;
			}
		}
		elseif ($method == "DELETE"){
			if (count($path) == 4){
				# DELETE /entry/1/tags/foobar
				$data["status"] = "OK";
				$data["msg"] = "";
				my_mysql_query("DELETE FROM entries_tags WHERE entryID = \"".mres($path[1])."\" AND tagID = (SELECT ID FROM tags WHERE tag = \"".mres($path[3])."\")");
				break;
			}
		}
		break;
		// }}}
	case "unreadcount": // {{{
		# GET /unreadcount
		# GET /unreadcount/1
		if ($method == "GET"){
			$data = Array();
			$r = Array();
			if (count($path) >= 2){
				$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			} else {
				$q = my_mysql_query("SELECT * FROM feeds WHERE startID = 1");
			}
			$r = mysql_fetch_object($q);
			$qc = my_mysql_query("SELECT feeds.ID, feeds.startID, COUNT(entries.ID) AS unread, MAX(entries.ID) AS maxID
				FROM feeds
				LEFT JOIN entries ON entries.feedID IN (SELECT f.ID FROM feeds AS f WHERE f.startID >= feeds.startID AND f.endID <= feeds.endID) AND isread='0'
				WHERE feeds.startID >= ".$r->startID." AND feeds.endID <= ".$r->endID."
				GROUP BY feeds.ID");
			while ($qr = mysql_fetch_object($qc)){
				$data[] = $qr;
			}
		}
		break;
		// }}}
	case "favorites": // {{{
		# GET /favorites
		if (count($path) == 1){
			$data = Array();
			$q = my_mysql_query("SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE favorite = 'yes' ORDER BY `date` DESC");
			while ($r = mysql_fetch_object($q)){
				$data[] = $r;
			}
			break;
		}
		break;
		// }}}
	case "unread": // {{{
		# GET /unread
		if (count($path) == 1){
			$data = Array();
			$q = my_mysql_query("SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE isread = '0' ORDER BY `date` DESC");
			while ($r = mysql_fetch_object($q)){
				$data[] = $r;
			}
			break;
		}
		break;
		// }}}
	case "tags": // {{{
		if ($method == "GET"){
			# GET /tags
			if (count($path) == 1){
				$data = Array();
				$q = my_mysql_query("SELECT * FROM tags ORDER BY tag ASC");
				if ($r = mysql_fetch_object($q)){
					$data = $r;
				} else {
					$data["status"] = "error";
					$data["msg"] = "Unknown Error! ".mysql_error();
				}
				break;
			}
			if (count($path) > 1){
				# GET /tags/1
				$data = Array();
				$q = my_mysql_query("SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE ID IN (
					SELECT entryID FROM entries_tags WHERE tagID = ".mres($path[1])."
				) ORDER BY `date` DESC");
				while ($r = mysql_fetch_object($q)){
					$data[] = $r;
				}
				break;
			}
		}
		break;
		// }}}
	case "options": // {{{
		# GET /options
		if ($method == "GET"){
			$q = my_mysql_query("SELECT * FROM options");
			$data = Array();
			while ($r = mysql_fetch_object($q)){
				$data[$r->key] = $r;
			}
		}
		# POST /options/unreadOnChange
		elseif ($method == "POST"){
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			my_mysql_query("INSERT INTO options (`key`, `value`) VALUES ('".mres($path[1])."', '".mres($_POST["value"])."')
				ON DUPLICATE KEY UPDATE value=VALUES(value)");
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
			}
			$data["status"] = "OK";
			$data["msg"] = "";
		}
		break;
		// }}}
}

switch ($format){
case "json":
default:
	header ("Content-Type: text/plain;charset=utf8");
	echo json_encode($data);
}
exit;
?>
