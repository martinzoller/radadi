<?php
  /*
  Copyright 2014-2019 Melin Software HB
  
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
  

function ConnectToDB() {
  $link = @new mysqli(getenv("MYSQL_HOST"), getenv("MYSQL_USER"), getenv("MYSQL_PASSWORD"), getenv("MYSQL_DATABASE"));
  
  if (!$link) {
    die('Not connected : ' . $link->connect_error);
  }

  return $link;
}

function query($link, $sql) {
 $result = $link->query($sql);
 if ($result === TRUE)
   return $result;
 else
   die('Invalid query: ' . $link->error);
 
 return $result;
}

function getStatusString($status) {
  switch($status) {
    case 0: 
      return "&ndash;"; //Unknown, running?
    case 1:
      return "OK";
    case 20:
      return "DNS"; // Did not start;
    case 21:
      return "CANCEL"; // Cancelled entry;
    case 3:
      return "MP"; // Missing punch
    case 4:
      return "DNF"; //Did not finish
    case 5:
      return "DQ"; // Disqualified
    case 6:      
      return "OT"; // Overtime
    case 99:
      return "NP"; //Not participating;
  }
}

function convertStatus($status){
 // to be compliant with IOF XML and backwards compatible
 //  var classifier = ['OK', 'Nicht gestartet', 'Abgebr.', 'Fehlst.', 'Disq.', 'ot'];

  switch($status) {
    case 0: 
      return 6; //Unknown, running?
    case 1:
      return 0;
    case 20:
      return 1; // Did not start;
    case 21:
      return 1; // Cancelled entry;
    case 3:
      return 3; // Missing punch
    case 4:
      return 2; //Did not finish
    case 5:
      return 4; // Disqualified
    case 6:      
      return 5; // Overtime
    case 99:
      return 1; //Not participating;
    default:
      return 7;
  }
}

function calculateResult($res, $clsname = "", $leg = 0) {
  $out = array();  
  
  $place = 0;
  $count = 0;
  $lastTime = -1;
  $bestTime = -1;
  while ($r = $res->fetch_assoc()) {
    
      
    $count++;
    $t = $r['time']/10;
    if ($bestTime == -1)
      $bestTime = $t;
    if ($lastTime != $t) {
      $place = $count;
      $lastTime = $t;
    }        
    $row = array();


    $row['name'] = $r['name'];      
    if(is_null($r['team'])) {
      $row['team'] = "";
    } else {
      $row['team'] = $r['team'];
    }
    if(is_null($r['nat'])) {
      $row['nat'] = "";
    } else {
      $row['nat'] = $r['nat'];
    }
    $row['short'] = $clsname;

    if ($leg > 0) {
      $row['short'] = $row['short']." -  Strecke $leg";
    }

    if ($r['status'] == 1) {
      $row['place'] = $place;

      if ($t > 0)
        $row['time'] = sprintf("%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
      else
        $row['time'] = "OK"; // No timing

      $row['finish'] = $r['finish'];

      if(isset($r['after']) && $r['after']!='') {
        $row['after'] = $r['after'];

      } else {
        $after = $t - $bestTime;
        if ($after > 3600)
          $row['after'] = sprintf("+%d:%02d:%02d", $after/3600, ($after/60)%60, $after%60);
        elseif ($after > 0)
          $row['after'] = sprintf("+%d:%02d", ($after/60)%60, $after%60);        
        else
          $row['after'] = "";
      }
        
      
    }
    else {
      $row['place'] = "";

      $row['time'] = getStatusString($r['status']);
      $row['after'] = "";
    }

        
    

    $row['classifier'] = convertStatus($r['status']);

          
    $out[] = $row;
  }
  
  return $out;
}

/** Format a result array as a table.*/
function formatResult($result) {
  global $lang;
  $head = false;
  print "<table>";
  foreach($result as $row) {            
    if ($head == false) {
      print "<tr>";
      foreach($row as $key => $cell) {
        print "<th>".$lang[$key]."</th>\n";  
      }
      print "</tr>";
      $head = true; 
    }      
    print "<tr>";
    foreach($row as $cell) {
      print "<td>$cell</td>";  
    }
    print "</tr>";
  }
  print "</table>";
}

function selectRadio($link, $cls) {
  global $cmpId, $PHP_SELF;
  $radio = '';
  $sql = "SELECT leg, ctrl, mopControl.name FROM mopClassControl, mopControl ".
         "WHERE mopControl.cid=? AND mopClassControl.cid=? ".
         "AND mopClassControl.id=? AND mopClassControl.ctrl=mopControl.id ORDER BY leg ASC, ord ASC";
  
  $stmt = $link->prepare($sql);
  $stmt->bind_param("iii", $cmpId, $cmpId, $cls);
  $stmt->execute();
  $stmt->store_result();
  
  if ($stmt->num_rows > 0) {
    $stmt->bind_result($leg, $ctrl, $name);
 
    if (isset($_GET['radio'])) {
      $radio = $_GET['radio'];
    }

    while ($stmt->fetch()) {
     // print '<a href="'."$PHP_SELF?cls=$cls&radio=$ctrl".'">'.$name."</a><br/>\n";      
    } 
    //print '<a href="'."$PHP_SELF?cls=$cls&radio=finish".'">'.'Finish'."</a><br/>\n";      
  }
  else {
    // Only finish   
    $radio = 'finish';
  }
  return $radio; 
}

function selectLegRadio($link, $cls, $leg, $ord) {
  global $cmpId, $PHP_SELF;
  $radio = '';
  $sql = "SELECT ctrl, mopControl.name FROM mopClassControl, mopControl ".
         "WHERE mopControl.cid=? AND mopClassControl.cid=? ".
         "AND mopClassControl.id=? AND mopClassControl.ctrl=mopControl.id AND leg=? AND ord=?";
         
  $stmt = $link->prepare($sql);
  $stmt->bind_param("iiiii", $cmpId, $cmpId, $cls, $leg, $ord);
  $stmt->execute();
  $stmt->store_result();
  
  if ($stmt->num_rows > 0) {
    $stmt->bind_result($ctrl, $name);
 
    while ($stmt->fetch()) {
      #print '<a href="'."$PHP_SELF?cls=$cls&leg=$leg&ord=$ord&radio=$ctrl".'">'.$name."</a>; \n";      
    } 
  }
  else {
    // Only finish   
    $radio = 'finish';
  }
 # print '<a href="'."$PHP_SELF?cls=$cls&leg=$leg&ord=$ord&radio=finish".'">'.'Finish'."</a><br/>\n";
  return $radio; 
}

/** Update or add a record to a table. */
function updateTable($link, $table, $cid, $id, $sqlupdate) {
  $ifc = "cid='$cid' AND id='$id'";
  $res = $link->query("SELECT id FROM `$table` WHERE $ifc");
  
  if ($res->num_rows > 0) {
    $sql = "UPDATE `$table` SET $sqlupdate WHERE $ifc";
  }
  else {
    $sql = "INSERT INTO `$table` SET cid='$cid', id='$id', $sqlupdate";  
  }
  
  //print "$sql\n";
  $link->query($sql);
}

/** Update a link with outer level over legs and other level over fieldName (controls, team members etc)*/
function updateLinkTable($link, $table, $cid, $id, $fieldName, $encoded) {
  $sql = "DELETE FROM $table WHERE cid='$cid' AND id='$id'";  
  $link->query($sql);
  $legNumber = 1;  
  $legs = explode(";", $encoded);
  
  $sql = "INSERT INTO $table SET cid='$cid', id='$id', leg=?, ord=?, $fieldName=?";  
  $stmt = $link->prepare($sql);
  $stmt->bind_param("iii", $legNumber, $key, $runner);
 
  foreach($legs as $leg) {
    if (strlen($leg) > 0) {
      $runners = explode(",", $leg);
      foreach($runners as $key => $runner) {
        $stmt->execute();  
      }
    }
    $legNumber++;
  }  
}

/** Remove all data from a table related to an event. */
function clearCompetition($link, $cid) {
   $tables = array(0=>"mopControl", "mopClass", "mopOrganization", "mopCompetitor",
                      "mopTeam", "mopTeamMember", "mopClassControl", "mopRadio");
                      
   foreach($tables as $table) {
     $sql = "DELETE FROM $table WHERE cid=$cid";
     $link->query($sql);
   } 
}

/** Update control table */
function processCompetition($link, $cid, $cmp) {
  $name = $link->real_escape_string($cmp);
  $date = $link->real_escape_string($cmp['date']);
  $organizer = $link->real_escape_string($cmp['organizer']);
  $homepage = $link->real_escape_string($cmp['homepage']);
  
  $sqlupdate = "name='$name', date='$date', organizer='$organizer', homepage='$homepage'";
  updateTable($link, "mopCompetition", $cid, 1, $sqlupdate);
}

/** Update control table */
function processControl($link, $cid, $ctrl) {
  $id = $link->real_escape_string($ctrl['id']);
  $name = $link->real_escape_string($ctrl);
  $sqlupdate = "name='$name'";
  updateTable($link, "mopControl", $cid, $id, $sqlupdate);
}

/** Update class table */
function processClass($link, $cid, $cls) {
  $id = $link->real_escape_string($cls['id']);
  $ord = $link->real_escape_string($cls['ord']);
  $name = $link->real_escape_string($cls);
  $sqlupdate = "name='$name', ord='$ord'";
  updateTable($link, "mopClass", $cid, $id, $sqlupdate);
    
  if (isset($cls['radio'])) {
    $radio = $link->real_escape_string($cls['radio']);
    updateLinkTable($link, "mopClassControl", $cid, $id, "ctrl", $radio);    
  }
}

/** Update organization table */
function processOrganization($link, $cid, $org) {
  $id = $link->real_escape_string($org['id']);
  $nat = $link->real_escape_string($org['nat']);

  if ($org['delete'] == 'true') { // MOP2.0 support
    $sql = "DELETE FROM mopOrganization WHERE cid='$cid' AND id='$id'";  
    $link->query($sql);
    return;
  }
  
  $name = $link->real_escape_string($org);
  
  $sqlupdate = "name='$name', nat='$nat'";
  updateTable($link, "mopOrganization", $cid, $id, $sqlupdate);
}

/** Update competitor table */
function processCompetitor($link, $cid, $cmp) {
  $base = $cmp->base;
  $id = $link->real_escape_string($cmp['id']);
  
  if ($cmp['delete'] == 'true') { // MOP2.0 support
    $sql = "DELETE FROM mopCompetitor WHERE cid='$cid' AND id='$id'";  
    $link->query($sql);
    return;
  }
  
  $name = $link->real_escape_string($base);
  $org = (int)$base['org'];
  $cls = (int)$base['cls'];
  $stat = (int)$base['stat'];
  $st = (int)$base['st'];
  $rt = (int)$base['rt'];
  
  $sqlupdate = "name='$name', org=$org, cls=$cls, stat=$stat, st=$st, rt=$rt";

  if (isset($cmp->input)) {
    $input = $cmp->input;
    $it = (int)$input['it'];
    $tstat = (int)$input['tstat'];
    $sqlupdate.=", it=$it, tstat=$tstat";
  }

  updateTable($link, "mopCompetitor", $cid, $id, $sqlupdate);  
  if (isset($cmp->radio)) {
    $sql = "DELETE FROM mopRadio WHERE cid='$cid' AND id='$id'";
    $link->query($sql);
    $radios = explode(";", $cmp->radio);

    $sql = "REPLACE INTO mopRadio SET cid=?, id=?, ctrl=?, rt=?"; 
    $stmt = $link->prepare($sql);
    $stmt->bind_param("iiii", $cid, $id, $radioId, $radioTime);
   
    foreach($radios as $radio) {
      $tmp = explode(",", $radio);
      $radioId = (int)$tmp[0];
      $radioTime = (int)$tmp[1];
      $stmt->execute();
    }
  }  
}

/** Update team table */
function processTeam($link, $cid, $team) {
  $base = $team->base;
  $id = $link->real_escape_string($team['id']);
  
  if ($team['delete'] == 'true') { // MOP2.0 support
    $sql = "DELETE FROM mopTeam WHERE cid='$cid' AND id='$id'";  
    $link->query($sql);
    return;
  }
  
  $name = $link->real_escape_string($base);
  
  $org = (int)$base['org'];
  $cls = (int)$base['cls'];
  $stat = (int)$base['stat'];
  $st = (int)$base['st'];
  $rt = (int)$base['rt'];
  
  $sqlupdate = "name='$name', org=$org, cls=$cls, stat=$stat, st=$st, rt=$rt";
  updateTable($link, "mopTeam", $cid, $id, $sqlupdate);
  
  if (isset($team->r)) {
    updateLinkTable($link, "mopTeamMember", $cid, $id, "rid", $team->r);
  }
}

/** MOP return code. */
function returnStatus($stat) {
  die('<?xml version="1.0"?><MOPStatus status="'.$stat.'"></MOPStatus>');
}

?>