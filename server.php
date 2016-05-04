<?php
/*
radadi - Race Data Display
This program allows the user to display start lists and result lists of orienteering races on any client devices that support HTML5.
The result lists can be updated automatically through a periodic data export from the OE2010 event software.

Copyright (C) 2016 Martin Zoller

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses>.

See the README file for more information.

*/

$oe11_resultlist_csv = array(
  'filename'           => 'C:\xampp\htdocs\radadi\resultlist.csv',
  /* CSV column headers in English (the actual headers depend on OE's current language, but the filetype ID in the first column is reliable) */
  'columns'            => array('OE0012','Stno','XStno','Chipno','Database Id','Surname','First name','YB','S','Block','nc','Start','Finish','Time','Classifier','Credit -','Penalty +','Comment','Club no.','Cl.name','City','Nat','Location','Region','Cl. no.','Short','Long','Entry cl. No','Entry class (short)','Entry class (long)','Rank','Ranking points','Num1','Num2','Num3','Text1','Text2','Text3','Addr. surname','Addr. first name','Street','Line2','Zip','Addr. city','Phone','Mobile','Fax','EMail','Rented','Start fee','Paid','Team','Course no.','Course','km','m','Course controls','Place','extraColumn'),
  'column_separator'   => ';',
  'text_delimiter'     => '"',
  'charset'            => 'cp1251'
);  

// TODO: We don't really need two formats, we can also read a startlist from a resultlist probably!!
$oe11_startlist_csv = array(
  'filename'           => 'C:\xampp\htdocs\radadi\startlist.csv',
  'columns'            => array('OE0001','Stno','XStno','Chipno','Database Id','Surname','First name','YB','S','Block','nc','Start','Finish','Time','Classifier','Credit -','Penalty +','Comment','Club no.','Cl.name','City','Nat','Location','Region','Cl. no.','Short','Long','Entry cl. No','Entry class (short)','Entry class (long)','Rank','Ranking points','Num1','Num2','Num3','Text1','Text2','Text3','Addr. surname','Addr. first name','Street','Line2','Zip','Addr. city','Phone','Mobile','Fax','EMail','Rented','Start fee','Paid','Team','Course no.','Course','km','m','Course controls','extraColumn'),
  'column_separator'   => ';',
  'text_delimiter'     => '"',
  'charset'            => 'cp1251'
);

$needed_cols = array('Stno','Chipno','First name','Surname','YB','S','Start','Time','Classifier','City','Nat','Short','km','m','Course controls','Place');

$error_message = ''; // Global variable for error message

// This will go into DB
$eventconfig = array(
  'eventname' => 'Velikden Cup 2016',
  'stagename' => 'Long Distance',
  'zerotime' => '10:00:00',
);

// This will come from the DB associated with REMOTE_ADDR
// 192.168.1.109,110: Men
// 192.168.1.108,111: Women
$clientconfig = array(
  '192.168.1.5' => array(
      'type'         => 'resultlist',
      'classes'      => array('M10','M12','M14','W10'),
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
  '192.168.1.107' => array(
      'type'         => 'resultlist',
      'classes'      => array('W10','W12','W14','W16','W18','W20','W21E','W21B','W35','W40','W45','W50','W55','W60','W65','OS','OM','OL'),
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
  '192.168.1.109' => array(
      'type'         => 'resultlist',
      'classes'      => array('W10','W12','W14','W16','W18','W20','W21E','W21B','W35','W40','W45','W50','W55','W60','W65','OS','OM','OL'),
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),            
  '192.168.1.108' => array(
      'type'         => 'resultlist',
      'classes'      => array('M10','M12','M14','M16','M18','M20','M21E','M21B','M35','M40','M45','M50','M55','M60','M65','M70','M75'),
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
  '192.168.1.110' => array(
      'type'         => 'resultlist',
      'classes'      => array('M10','M12','M14','M16','M18','M20','M21E','M21B','M35','M40','M45','M50','M55','M60','M65','M70','M75'),
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),                     
  'default' => array(
      'type'         => 'resultlist',
      'classes'      => array('M10','W10','M12','W12','M14','W14','M16','W16','M18','W18','M20','W20','M21E','W21E','M21B','W21B','M35','W35','M40','W40','M45','W45','M50','W50','M55','W55','M60','W60','M65','W65','M70','M75','OS','OM','OL'),
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
);

// --------------------------------------------------------------------------
// ACTION
// --------------------------------------------------------------------------

set_time_limit(60);
error_reporting(0);

// If the client already has data, wait for new data to be available
if(!isset($_GET['timestamp'])){
  $_GET['timestamp']=0;
}

if(isset($clientconfig[$_SERVER['REMOTE_ADDR']])){
  $clientconfig = $clientconfig[$_SERVER['REMOTE_ADDR']];
} else {
  $clientconfig = $clientconfig['default'];
}

if($clientconfig['type']=='resultlist'){
  $csvconfig = $oe11_resultlist_csv;
} else {
  $csvconfig = $oe11_startlist_csv;
}

// Run this loop for up to 30 seconds
for($i=0;$i<30;$i++){
  $file_tstamp=filemtime($csvconfig['filename']);
  if($file_tstamp==0){
    $error_message='Invalid CSV file timestamp, check path';
    jsonerr();
  }
  if($file_tstamp > $_GET['timestamp']){
    break;
  }
  sleep(1);
}
  
$csv=read_oe11_csv($csvconfig) or jsonerr();

$output=array();
foreach($csv as $idx=>$line){
  // Filter CSV by classes
  if(!in_array($line['Short'],$clientconfig['classes'])){
    continue;
  }
  // Results: remove 'did not start'
  if($clientconfig['type']=='resultlist' && $line['Classifier']==1){
    continue;
  }
  
  $output[]=array_intersect_key($line,array_flip($needed_cols));
}

echo json_encode(
  array(
    'list'         =>  $output,
    'timestamp'    =>  $file_tstamp,
    'eventconfig'  =>  $eventconfig,
    'clientconfig' =>  $clientconfig
  )
);
  

/** Reads the given CSV file into an array, with the first key being the line number and the second key the column name according
  * to the given set of headers. Checks the file type stored in the first column header.
  * 
  * @param csv_config An array of configuration parameters about the CSV file, with the following entries:
  *   filename         - The name of the file to read
  *   columns          - An array of all the column headers to be used as array keys
  *   column_separator - Character that separates CSV columns
  *   text_delimiter   - Character that may enclose strings in column values
  *   charset          - Character encoding of the source file
  *
  * @return A data array, or false if an error occurred.
  */
function read_oe11_csv($csv_config){
  global $error_message;
  
  // Get the file contents
  if(! $csv = file_get_contents($csv_config['filename'])){
    $error_message = 'Error reading CSV file: '.$csv_config['filename'];
    return false;
  }

	// Charset conversion first
  // Note that the dash in 'utf-8' is mandatory on Windows (only)
	if($csv_config['charset']!='utf-8')
	  $csv=iconv($csv_config['charset'],'utf-8',$csv);
	
	// Break up the file into lines
	//$arr=preg_split('/$\R?^/m', $csv);
	$arr=explode("\n",str_replace(array("\r\n","\n\r","\r"),"\n",$csv)); //Apparently faster
    
	// Check and strip off header line
  $head=explode($csv_config['column_separator'],array_shift($arr));
	if($head[0]!=$csv_config['columns'][0]){
    $error_message = 'CSV import error: Invalid OE file type, check export settings';
		return false;
	}
  
  // Process the file
  foreach($arr as $no=>$line){
    // Ignore empty line at end
    if($line==''){
      unset($arr[$no]);
      continue;
    }
    
    $cols = str_getcsv($line, $csv_config['column_separator'], $csv_config['text_delimiter']);
    
    // Check line length
    if(count($cols)!=count($csv_config['columns'])){
      $error_message = 'CSV import error: Line '.($no+1).' has a wrong column count';
      return false;
    }
    
    // Format data as an associative array
    $arr[$no] = array_combine($csv_config['columns'],$cols);
    
  }
    
  return $arr;
}

/**
  * Outputs the last message stored in the global error variable as JSON and exits.
  */
function jsonerr(){
  global $error_message;
  
  if(! $error_message){
    $error_message = 'Unknown error';
  }
  echo json_encode(array('error' => $error_message));
  exit;
}

?>