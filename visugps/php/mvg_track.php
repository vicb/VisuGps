<?php

require('mvg_db.inc.php');

// Keep going only if an id has been provided
if (!isset($_POST['id'])) exit;

$link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
mysql_select_db(dbName) or die ('Database does not exist');

// Get the pilot id when it exists otherwise exit
$query = sprintf("SELECT id FROM pilot WHERE pseudo = '%s'", format_mysql($_POST['id']));
$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result) == 1) {
    $pilot = mysql_fetch_object($result);
    $pilotId = $pilot->id;
} else {
    exit;
}

// Create a new flight on start
if (isset($_POST['start'])) {
    $query = "INSERT INTO flight (pilotId) VALUES ($pilotId)";
    mysql_query($query) or die('Query error: ' . mysql_error());
}

// Get the current flight id
$query = "SELECT max(id) as id FROM flight WHERE pilotId = '$pilotId'";
$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result) == 1) {
    $flight = mysql_fetch_object($result);
    $flightId = $flight->id;
} else {
    exit;
}

// Should the start date be updated for that flight ?
$query = "SELECT start AS date FROM flight WHERE id = '$flightId'";
$result = mysql_query($query) or die('Query error: '. mysql_error());
if (mysql_num_rows($result) == 1) {
    $flight = mysql_fetch_object($result);
    $flightNeedDate = !isset($flight->date);
} else {
    exit;
}

// Insert the points in the database
if (isset($_POST['gps'])) {
    $insert = "INSERT INTO point (flightId, latitude, longitude, elevation, time) VALUES ";
    $points = $_POST['gps'];
    for ($i = 0; $i < count($points); $i++) {
        $data = explode(";", $points[$i]);
        $date = sprintf("20%02d-%d-%d %d:%d:%d", intval($data[8])
                                               , intval($data[7])
                                               , intval($data[6])
                                               , intval($data[3])
                                               , intval($data[4])
                                               , intval($data[5]));

        $lat = floatval($data[0]);
        $lat = (intval($lat)) + ($lat - intval($lat)) * 100 / 60;
        $lon = floatval($data[1]);
        $lon = (intval($lon)) + ($lon - intval($lon)) * 100 / 60;

        $insert .= sprintf("(%d, %f, %f, %d, '%s'),", $flightId
                                                    , $lat
                                                    , $lon
                                                    , intval($data[2])
                                                    , $date);

        if ($flightNeedDate) {
            $query = "UPDATE flight SET start = '$date' WHERE id = '$flightId'";
            mysql_query($query) or die ('Query error: ' . mysql_error());
            $flightNeedDate = false;
        }
    }
    // Remove the extra "," from the query end
    $insert = rtrim ($insert, ",");
    echo "<br>REQUEST<br>$insert";
    mysql_query($insert) or die ('Query error: ' . mysql_error());
}

// Fill the flight end date on stop
if (isset($_POST['stop'])) {
    $query = "SELECT max(time) AS date FROM point WHERE flightId = '$flightId'";;
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result) == 1) {
        $end = mysql_fetch_object($result);
        $query = "UPDATE flight SET end = '$end->date' WHERE id = '$flightId'";
        mysql_query($query) or die ('Query error: ' . mysql_error());
    } else {
        exit;
    }
}

mysql_close($link);

function format_mysql($text) {
    if(get_magic_quotes_gpc()) {
        if(ini_get('magic_quotes_sybase')) {
            $text = str_replace("''", "'", $text);
        } else {
            $text = stripslashes($text);
        }
    }
    return mysql_real_escape_string($text);
}

?>
