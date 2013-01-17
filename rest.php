<?
include("etc/config.php");
function mres($x){
	return mysql_real_escape_string($x);
}

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
mysql_query("SET NAMES 'utf8';");

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
			$q = mysql_query("SELECT * FROM feeds ORDER BY startID ASC");
			$data = Array();
			while ($r = mysql_fetch_object($q)){
				$data[] = $r;
			}
		}
		break;
		// }}}
	case "feed": // {{{
		if ($method == "GET"){{{
			# GET /feed/1
			$q = mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
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
						$q = mysql_query("SELECT * FROM feeds WHERE startID > ".mres($r->startID)." AND endID < ".mres($r->endID)." ORDER BY startID ASC");
						while ($r = mysql_fetch_object($q)){
							$data[] = $r;
						}
					} else {
						header("Location: ../../unreadcount/".$r->ID);
					}
					break;
				# GET /feed/1/entries/25
				case "entries":
					$data = Array();
					$limit = "";
					if (count($path) >= 4){
						$limit = "LIMIT ".mres($path[3]).", 25";
					}
					$q = mysql_query("SELECT * FROM entries WHERE feedID = ".mres($r->ID)." ORDER BY `date` DESC $limit");
					while ($r = mysql_fetch_object($q)){
						$data[] = $r;
					}
					break;
				# GET /feed/1/filter
				case "filter":
					$data = Array();
					$q = mysql_query("SELECT * FROM filter WHERE feedID = ".mres($r->ID)." ORDER BY ID ASC");
					while ($r = mysql_fetch_object($q)){
						$data[] = $r;
					}
					break;
			}
		}}}
		elseif ($method == "POST"){{{
			switch ($path[2]){
				case "markAllRead":
					# POST /feed/1/markAllRead
					$maxID = "";
					if ($_POST["maxID"] > 0){
						$maxID = "AND ID <= ".mres($_POST["maxID"]);
					}
					$q = mysql_query("SELECT startID, endID FROM feeds WHERE ID = ".mres($path[1]));
					$feed = mysql_fetch_object($q);

					$q = mysql_query("UPDATE entries SET date=date, isread='1'
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
					$query = json_decode(file_get_contents("php://input"));
					if ($query->moveAfterFeed){
						$q = mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
						$feedToChange = mysql_fetch_object($q);
						$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($query->moveAfterFeed)."'");
						$feedToMoveTo = mysql_fetch_object($q);

						mysql_query("UPDATE feeds SET movedirection = 'moveme' WHERE startID >= {$feedToChange->startID} AND endID <= {$feedToChange->endID}");
						$difference = ($feedToChange->endID - $feedToChange->startID) + 1;

						/* Take the feed we want to move OUT of the structure and move everything AFTER it UPWARDS to close the gap */
						mysql_query("UPDATE feeds SET startID = startID - $difference WHERE startID > $feedToChange->endID");
						mysql_query("UPDATE feeds SET endID = endID - $difference WHERE endID > $feedToChange->endID");

						$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($query->moveAfterFeed)."'");
						$feedToMoveTo = mysql_fetch_object($q);

						/* Make a gap for the feed we want to move */
						mysql_query("UPDATE feeds SET startID = startID + $difference WHERE startID > $feedToMoveTo->endID AND movedirection = 'none'");
						mysql_query("UPDATE feeds SET endID = endID + $difference WHERE endID > $feedToMoveTo->endID AND movedirection = 'none'");

						$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($query->moveAfterFeed)."'");
						$feedToMoveTo = mysql_fetch_object($q);
						$q = mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
						$feedToChange = mysql_fetch_object($q);

						/* Move the feed to the newly created room */
						mysql_query("UPDATE feeds SET startID = startID - $feedToChange->startID + $feedToMoveTo->endID + 1 WHERE movedirection = 'moveme'");
						mysql_query("UPDATE feeds SET endID = endID - $feedToChange->startID + $feedToMoveTo->endID + 1 WHERE movedirection = 'moveme'");
						mysql_query("UPDATE feeds SET movedirection = 'none'");
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
			# PUT /feed/1
			$UPDATE = "ID = ID";
			$update = json_decode(file_get_contents("php://input"));
			foreach (array("name", "url", "cacheimages") as $k){
				if ("".$update->$k != ""){
					$UPDATE .= ", `$k` = \"".mres($update->$k)."\"";
				}
			}
			mysql_query("UPDATE feeds SET $UPDATE WHERE ID = ".mres($path[1])." LIMIT 1");
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}
			foreach ($update->filter as $k => $v){
				if ($v->delete == "true"){
					mysql_query("DELETE FROM filter WHERE ID = ".mres($v->ID)." LIMIT 1");
				} else {
					if ($v->ID == "0" && strlen($v->regex) > 0){
						mysql_query("INSERT INTO filter (regex, whiteorblack, feedID) VALUES (\"".mres($v->regex)."\", \"".mres($v->whiteorblack)."\", ".mres($path[1]).")");
					} else {
						mysql_query("UPDATE filter SET regex = \"".mres($v->regex)."\", whiteorblack = \"".mres($v->whiteorblack)."\" WHERE ID = ".mres($v->ID)." LIMIT 1");
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
			# DELETE /feed/1
			$q = mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			$feed = mysql_fetch_object($q);
			mysql_query("DELETE FROM feeds WHERE ID = $feed->ID LIMIT 1");

			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
		}

		mysql_query("UPDATE feeds SET endID=endID-2 WHERE endID >= {$feed->endID}");
		if (mysql_error()){
			$data["status"] = "error";
			$data["msg"] = mysql_error();
			break;
		}

		mysql_query("UPDATE feeds SET startID=startID-2 WHERE startID >= $feed->endID"); # $feed->endID is correct!
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
		if ($method == "PUT"){
			# PUT /entry/1
			$UPDATE = "date = date";
			$update = json_decode(file_get_contents("php://input"));
			foreach (array("isread") as $k){
				if ("".$update->$k != ""){
					$UPDATE .= ", `$k` = \"".mres($update->$k)."\"";
				}
			}
			if ($UPDATE == "date = date"){
				$data["status"] = "error";
				$data["msg"] = "No valid data to update given!";
			} else {
				mysql_query("UPDATE entries SET $UPDATE WHERE ID = ".mres($path[1])." LIMIT 1");
				if (mysql_error() == ""){
					$data["status"] = "OK";
					$data["msg"] = "";
				} else {
					$data["status"] = "error";
					$data["msg"] = "Update failed: ".mysql_error();
				}
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
				$q = mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			} else {
				$q = mysql_query("SELECT * FROM feeds WHERE startID = 1");
			}
			$r = mysql_fetch_object($q);
			$qc = mysql_query("SELECT feeds.ID, feeds.startID, COUNT(entries.ID) AS unread, MAX(entries.ID) AS maxID
				FROM feeds
				LEFT JOIN entries ON entries.feedID IN (SELECT f.ID FROM feeds AS f WHERE f.startID >= feeds.startID AND f.endID <= feeds.endID) AND isread='0'
				WHERE feeds.startID >= ".$r->startID." AND feeds.endID <= ".$r->endID."
				GROUP BY feeds.ID");
			while ($qr = mysql_fetch_object($qc)){
				$data[] = $qr;
			}
		}
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
