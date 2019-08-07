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
set_time_limit(60);
error_reporting(-1);
require_once "config.php";




$needed_cols = array('Stno','Chipno','First name','Surname','YB','S','Start','Time','Classifier','City','Nat','Short','km','m','Course controls','Place');

$error_message = ''; // Global variable for error message



// If the client already has data, wait for new data to be available
if (!isset($_GET['timestamp'])) {
    $_GET['timestamp']=0;
}

if (isset($clientconfig[$_SERVER['REMOTE_ADDR']])) {
    $clientconfig = $clientconfig[$_SERVER['REMOTE_ADDR']];
} else {
    $clientconfig = $clientconfig['default'];
}


$xml=false;

if ($clientconfig["type"] == "startlist") {
    $csvconfig = $oe11_startlist_csv;
} elseif ($clientconfig["type"] == "resultlist_xml") {
    $csvconfig = $iof_xml;
    $xml=true;
} else {
    $csvconfig = $oe11_resultlist_csv;
}





// Run this loop for up to 30 seconds
for ($i=0;$i<30;$i++) {
    $file_tstamp=filemtime($csvconfig['filename']);
    if ($file_tstamp==0) {
        $error_message='Invalid CSV file timestamp, check path';
        jsonerr();
    }
    if ($file_tstamp > $_GET['timestamp']) {
        break;
    }
    sleep(1);
}


if ($xml) {
    $csv= read_iof_xml($csvconfig) or jsonerr();
    $output=array();
    foreach ($csv as $idx=>$line) {
        // Filter CSV by classes
        if ($clientconfig['classes']!==true && !in_array($line['Short'], $clientconfig['classes'])) {
            continue;
        }

        $output[]=$line;
    }
} else {
    $csv=read_oe11_csv($csvconfig) or jsonerr();

    $output=array();
    foreach ($csv as $idx=>$line) {
        // Filter CSV by classes
        if ($clientconfig['classes']!==true && !in_array($line['Short'], $clientconfig['classes'])) {
            continue;
        }
        // Results: remove 'did not start'
        if ($clientconfig['type']=='resultlist' && $line['Classifier']==1) {
            continue;
        }

        $output[]=array_intersect_key($line, array_flip($needed_cols));
    }
}



echo json_encode(
    array(
    'list'         =>  $output,
    'timestamp'    =>  $file_tstamp,
    'eventconfig'  =>  $eventconfig,
    'clientconfig' =>  $clientconfig
  )
);



function read_iof_xml($config)
{
    $xml = simplexml_load_file($config['filename']);
    $results = [];

    // overwrite
    $GLOBALS['eventconfig'] = array(
    'eventname' => (string) $xml->Event->Name,
    'stagename' => (string) $xml->Event->Race->RaceNumber,
    'zerotime' => '10:00:00',
  );

    foreach ($xml->ClassResult as $empl) {
        foreach ($empl->PersonResult as $pr) {
            $seconds = $pr->Result->Time;

            $hours = floor($seconds / 3600);
            $mins = floor($seconds / 60 % 60);
            $secs = floor($seconds % 60);

            $secondsOverall = $pr->Result->OverallResult->Time;

            $hoursOverall = floor($secondsOverall / 3600);
            $minsOverall = floor($secondsOverall / 60 % 60);
            $secsOverall = floor($secondsOverall % 60);

            if ($pr->Result->Status == "OK") {
                $classifier = 0;
            } elseif ($pr->Result->Status == "DidNotStart") {
                $classifier = 1;
            } elseif ($pr->Result->Status == "DidNotFinish") {
                $classifier = 2;
            } elseif ($pr->Result->Status == "MissingPunch") {
                $classifier = 3;
            } else {
                $classifier = 4;
            }

            if ($pr->Result->OverallResult->Status == "OK") {
                $classifierOverall = 0;
            } elseif ($pr->Result->OverallResult->Status == "DidNotStart") {
                $classifierOverall = 1;
            } elseif ($pr->Result->OverallResult->Status == "DidNotFinish") {
                $classifierOverall = 2;
            } elseif ($pr->Result->OverallResult->Status == "MissingPunch") {
                $classifierOverall = 3;
            } elseif ($pr->Result->OverallResult->Status == "Disqualified") {
                $classifierOverall = 4;
            } else {
                $classifierOverall = 0;
            }

            if ($classifier != 1) {
                $results[] = array(
           "Stno" => (string) $pr->Result->BibNumber,
           "Chipno" => (string) $pr->Result->ControlCard,
           "Surname" => (string) $pr->Person->Name->Family,
           "First name" => (string) $pr->Person->Name->Given,
           "Nat" => (string) $pr->Organisation->Country['code'],
           "YB" => "",
           "S" => "",
           "Start" => substr($pr->Result->StartTime, 11, 8),
           "FinishTimestamp" => strtotime(substr($pr->Result->FinishTime, 11, 8)),
           "Time" =>  sprintf('%01d:%02d:%02d', $hours, $mins, $secs),
           "TimeOverall" =>  sprintf('%01d:%02d:%02d', $hoursOverall, $minsOverall, $secsOverall),
           "Classifier" => $classifier,
           "ClassifierOverall" => $classifierOverall,
           "City" => (string) $pr->Organisation->Name,
           "Short" => (string) $empl->Class->Name,
           "km" => round((float)$empl->Course->Length / 1000.0, 2),
           "m" => (string) $empl->Course->Climb,
           "Course controls" => "",
           "Place" => (string) $pr->Result->Position,
           "PlaceOverall" => (string) $pr->Result->OverallResult->Position
         );
            }
        }


        //   print_r($empl->Result);
    }


    return $results;
}

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
function read_oe11_csv($csv_config)
{
    global $error_message;

    // Get the file contents
    if (! $csv = file_get_contents($csv_config['filename'])) {
        $error_message = 'Error reading CSV file: '.$csv_config['filename'];
        return false;
    }

    // Charset conversion first
    // Note that the dash in 'utf-8' is mandatory on Windows (only)
    if ($csv_config['charset']!='utf-8') {
        $csv=iconv($csv_config['charset'], 'utf-8', $csv);
    }

    // Break up the file into lines
    //$arr=preg_split('/$\R?^/m', $csv);
    $arr=explode("\n", str_replace(array("\r\n","\n\r","\r"), "\n", $csv)); //Apparently faster

    // Check and strip off header line
    $head=explode($csv_config['column_separator'], array_shift($arr));
    if ($head[0]!=$csv_config['columns'][0]) {
        $error_message = 'CSV import error: Invalid OE file type, check export settings';
        return false;
    }

    // Process the file
    foreach ($arr as $no=>$line) {
        // Ignore empty line at end
        if ($line=='') {
            unset($arr[$no]);
            continue;
        }

        $cols = str_getcsv($line, $csv_config['column_separator'], $csv_config['text_delimiter']);

        // Check line length
        if (count($cols)!=count($csv_config['columns'])) {
            $error_message = 'CSV import error: Line '.($no+1).' has a wrong column count';
            return false;
        }

        // Format data as an associative array
        $arr[$no] = array_combine($csv_config['columns'], $cols);
    }

    return $arr;
}

/**
  * Outputs the last message stored in the global error variable as JSON and exits.
  */
function jsonerr()
{
    global $error_message;

    if (! $error_message) {
        $error_message = 'Unknown error';
    }
    echo json_encode(array('error' => $error_message));
    exit;
}
