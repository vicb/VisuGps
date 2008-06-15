<?php

require('mvg_db.inc.php');

$link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
mysql_select_db(dbName) or die ('Database does not exist');

$query = "SELECT name, start, end, flightId, COUNT(latitude) as points ".
         "FROM pilot, flight, point " .
         "WHERE flightId = flight.id AND pilotId = pilot.id GROUP BY flightId " .
         "HAVING points > 5 " .
         "ORDER BY start DESC";
$result = mysql_query($query)  or die('Query error: ' . mysql_error());

$tracks['tracks'] = array();

if (mysql_num_rows($result)) {
    while ($row = mysql_fetch_assoc($result)) {
        $tracks['tracks'][] = $row;
    }
}

echo @json_encode($tracks);


?>
