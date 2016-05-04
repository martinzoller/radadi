<?php
/* This file is called by localindex.html to wait for the server to become
 * available. It has to send an "Access-Control-Allow-Origin" header due to the
 * JavaScript Same-origin policy.
 */
header('Access-Control-Allow-Origin: *');
?>pong
