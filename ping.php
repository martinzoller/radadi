<?php
/* This file is called by localindex.html to wait for the server to become
 * available. It has to send an "Access-Control-Allow-Origin" header due to the
 * JavaScript Same-origin policy.
 */
header('Access-Control-Allow-Origin: *');

include_once('api/v2/functions.php');
session_start();

$link = ConnectToDB();
$sql = "SELECT * FROM mopCompetition WHERE 1 ORDER BY DATE DESC LIMIT 1";
$res = $link->query($sql);
 
if ($r = $res->fetch_assoc()) {
    if($r['team']) {
        echo "team";
    } else {
        echo "single";
    }
}
?>