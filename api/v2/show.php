<?php
/*
  Copyright 2014-2018 Melin Software HB
  
  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at
  
      http://www.apache.org/licenses/LICENSE-2.0
  
  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
  */


include_once('functions.php');
session_start();
header('Content-type: application/json;charset=utf-8');


$PHP_SELF = $_SERVER['PHP_SELF'];
$REMOTE_IP = $_SERVER['REMOTE_ADDR'];
$link = ConnectToDB();




if (isset($_GET['cmp'])) {
  $cmpId = $_GET['competition'];
  $sql = "SELECT * FROM mopCompetition WHERE cid = '$cmpId'";
} else {
  $sql = "SELECT * FROM mopCompetition WHERE 1 ORDER BY DATE DESC LIMIT 1";
}
$res = $link->query($sql);

if ($r = $res->fetch_assoc()) {
  $cmpId = $r['cid'];

  $eventconfig = array(
    "eventname" => $r['name']
  );
}


$sql = "SELECT cc.* FROM classesClients AS cc, clients AS c WHERE cc.client_id = c.id AND c.ip='$REMOTE_IP' AND c.cid=$cmpId";
$resClasses = $link->query($sql);

if ($resClasses->num_rows > 0) {
  $exists_client = true;
} else {
  $exists_client = false;
}


if (isset($exists_client)) {
  $sql = "SELECT cls.id AS classId, cls.name AS name, cc.leg AS leg FROM clients AS c, classesClients AS cc, mopClass AS cls WHERE c.ip='$REMOTE_IP' AND c.cid=$cmpId AND cc.client_id=c.id AND cls.id=cc.class_id  ORDER BY cls.ord";
} else {
  $sql = "SELECT cls.id AS classId, cls.name AS name FROM mopClass AS cls WHERE cls.cid=$cmpId ORDER BY cls.ord";
}
$resClasses = $link->query($sql);

$results = array();

while ($rClasses = $resClasses->fetch_assoc()) {

  $cname = $rClasses['name'];
  $cls = $rClasses['classId'];


  $sql = "SELECT max(leg) AS nleg FROM mopTeamMember tm, mopTeam t WHERE tm.cid = '$cmpId' AND t.cid = '$cmpId' AND tm.id = t.id AND t.cls = $cls";
  $resTeams = $link->query($sql);
  $rTeams = $resTeams->fetch_assoc();

  if (!is_null($rTeams))
    $numlegs =  $rTeams['nleg'];


  if (is_null($rTeams) || is_null($numlegs)) {
    //No teams;        
    $sql = "SELECT cmp.id AS id, cmp.name AS name, org.name AS team,  org.nat AS nat, cmp.rt AS time, cmp.rt + cmp.st AS finish, cmp.stat AS status " .
      "FROM mopCompetitor cmp LEFT JOIN mopOrganization AS org ON cmp.org = org.id AND cmp.cid = org.cid " .
      "WHERE cmp.cls = '$cls' " .
      "AND cmp.cid = '$cmpId' AND cmp.stat>0 ORDER BY cmp.stat, cmp.rt ASC, cmp.id";
    $rname = "Finish";
    $resResults = $link->query($sql);
    $classResult = calculateResult($resResults, $cname);
    $results = array_merge($results, $classResult);
  } else {
    die("only individual results with this endpoint!");

  }
}

echo json_encode(
  array(
    'list'         =>  $results,
    'timestamp'    =>  time(),
    'time'    =>  date('H:i', time()),
    'eventconfig'  =>  $eventconfig,
    'clientconfig' =>  array(
      "columns" => 1,
      "paginate" => true,
      "displaytime" => 15
    ),
    'remote_ip' => $_SERVER['REMOTE_ADDR']
  )
);
