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
  '192.168.188.36' => array(
      'type'         => 'resultlist',
      'classes'      => array('M50','W35'),
      'columns'      => 2,
      'paginate'     => true,
      'displaytime'  => 15
                     ),
  '192.168.188.30' => array(
      'type'         => 'resultlist',
      'classes'      => array('M50', 'M21', 'W35'),
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
  'default' => array(
      'type'         => 'resultlist_xml',
      'classes'      => true, //true shows all classes available
      'columns'      => 1,
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
  'columns'            => array('OE0001','Stno','XStno','Chipno','Database Id','Surname','First name','YB','S','Block','nc','Start','Finish','Time','Classifier','Credit -','Penalty +','Comment','Club no.','Cl.name','City','Nat','Location','Region','Cl. no.','Short','Long','Entry cl. No','Entry class (short)','Entry class (long)','Rank','Ranking points','Num1','Num2','Num3','Text1','Text2','Text3','Addr. surname','Addr. first name','Street','Line2','Zip','Addr. city','Phone','Mobile','Fax','EMail','Rented','Start fee','Paid','Team','Course no.','Course','km','m','Course controls','extraColumn'),
  'column_separator'   => ';',
  'text_delimiter'     => '"',
  'charset'            => 'cp1251'
);


function get_most_recent_xml()
{
    $files = scandir(join(DIRECTORY_SEPARATOR, array(__DIR__, 'xml')), SCANDIR_SORT_DESCENDING);
    $newest_file = $files[0];
    return join(DIRECTORY_SEPARATOR, array(__DIR__, 'xml', $newest_file));
}
