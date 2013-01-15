SELECT endID INTO @a FROM feeds WHERE startID = 1;
UPDATE `feeds` SET endID = endID + 2 WHERE startID = 1;
INSERT INTO `feeds` (startID, endID, name, url) VALUES (@a, @a+1, "Searchresults", "SEARCHRESULTS");
