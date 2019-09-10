<?php
// This will go into DB
$eventconfig = array(
  'eventname' => 'Hamburg Sprint',
  'stagename' => 'E1',
  'zerotime' => '10:00:00',
);

// This will come from the DB associated with REMOTE_ADDR
// 192.168.1.109,110: Men
// 192.168.1.108,111: Women
$clientconfig = array(
  '10.0.1.5' => array(
      'type'         => 'resultlist_xml',
      'classes'      => array('H19L', 'H19K', 'H35', 'H45', 'H55', 'H65', 'H75'),
      'columns'      => 1,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
  '10.0.1.3' => array(
      'type'         => 'resultlist_xml',
      'classes'      => array('D19L', 'D19K', 'D35', 'D45', 'D55', 'D65'),
      'columns'      => 1,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
  '10.0.1.6' => array(
      'type'         => 'resultlist_xml',
      'classes'      => array('D/H10', 'D/H10b', 'D12', 'H12', 'D14', 'H14', 'D16', 'H16', 'D18', 'H18', 'OL', 'OM'),
      'columns'      => 1,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
  'default' => array(
      'type'         => 'resultlist_xml',
      'classes'      => true, //true shows all classes available
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
);



$iof_xml = array(
'filename' => get_most_recent_xml()
);

$oe11_resultlist_csv = array(
  'filename'           => join(DIRECTORY_SEPARATOR, array(__DIR__, 'resultlist.csv')),
  /* CSV column headers in English (the actual headers depend on OE's current language, but the filetype ID in the first column is reliable) */
  'columns'            => array('OE0012','Stno','XStno','Chipno','Database Id','Surname','First name','YB','S','Block','nc','Start','Finish','Time','Classifier','Credit -','Penalty +','Comment','Club no.','Cl.name','City','Nat','Location','Region','Cl. no.','Short','Long','Entry cl. No','Entry class (short)','Entry class (long)','Rank','Ranking points','Num1','Num2','Num3','Text1','Text2','Text3','Addr. surname','Addr. first name','Street','Line2','Zip','Addr. city','Phone','Mobile','Fax','EMail','Rented','Start fee','Paid','Team','Course no.','Course','km','m','Course controls','Place','extraColumn'),
  'column_separator'   => ';',
  'text_delimiter'     => '"',
  'charset'            => 'cp1251'
);

// TODO: We don't really need two formats, we can also read a startlist from a resultlist probably!!
$oe11_startlist_csv = array(
  'filename'           => join(DIRECTORY_SEPARATOR, array(__DIR__, 'startlist.csv')),
  //OE0001;Stnr;Chip;Datenbank Id;Nachname;Vorname;Jg;G;Block;AK;Start;Ziel;Zeit;Wertung;Club-Nr.;Abk;Ort;Nat;Katnr;Kurz;Lang;Num1;Num2;Num3;Text1;Text2;Text3;Adr. Name;Stra�e;Zeile2;PLZ;Ort;Tel;Fax;EMail;Id/Verein;Gemietet;Startgeld;Bezahlt;Bahnnummer;Bahn;km;Hm;Bahn Posten;Pl;Startstempel;Zielstempel;Posten1;Stempel1;Posten2;Stempel2;Posten3;Stempel3;Posten4;Stempel4;Posten5;Stempel5;Posten6;Stempel6;Posten7;Stempel7;Posten8;Stempel8;Posten9;Stempel9;Posten10;Stempel10;(und weitere)...

  'columns'            => array('OE0001','Stno','Chipno','Database Id','Surname','First name','YB','S','Block','nc','Start','Finish','Time','Classifier','Club no.','Cl.name','City','Nat','Cl. no.','Short','Long','Course','km','m','Course controls'),
  'column_separator'   => ',',
  'text_delimiter'     => '"',
  'charset'            => 'cp1251'
);


function get_most_recent_xml()
{

    $files = scan_dir(join(DIRECTORY_SEPARATOR, array(__DIR__, 'xml')));
    $newest_file = $files[0];
    return join(DIRECTORY_SEPARATOR, array(__DIR__, 'xml', $newest_file));
}

function scan_dir($dir) {
    $ignored = array('.', '..', '.svn', '.htaccess');

    $files = array();
    foreach (scandir($dir) as $file) {
        if (in_array($file, $ignored)) continue;
        $files[$file] = filemtime($dir . '/' . $file);
    }

    arsort($files);
    $files = array_keys($files);

    return ($files) ? $files : false;
}
