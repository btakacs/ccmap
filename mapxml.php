/*mapxml.php
This script outputs selected site data in xml format so that it may be read by the index page to create markers on the map
The information output in this xml is what goes on the markers for the map
The xml data is ordered in ascending order by latitude.  This makes it much much easier to see and deal with sites with the same coordinates.  It also makes the initial marker clustering faster
*/

<?php

function parseToXML($htmlStr)
{
$xmlStr=str_replace('<','&lt;',$htmlStr);
$xmlStr=str_replace('>','&gt;',$xmlStr);
$xmlStr=str_replace('"','&quot;',$xmlStr);
$xmlStr=str_replace("'",'&#39;',$xmlStr);
$xmlStr=str_replace("&",'&amp;',$xmlStr);
return $xmlStr;
}

// Opens a connection to a MySQL server
$connection=mysql_connect (loacl_db,"local_db_user_name","local_db_pwd");
if (!$connection) {
  die('Not connected : ' . mysql_error());
}

// Set the active MySQL database
$db_selected = mysql_select_db("marker_data", $connection);
if (!$db_selected) {
  die ('Can\'t use db : ' . mysql_error());
}

// Select all the rows in the markers table
$query = "SELECT * FROM profiles ORDER BY lat";
$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

header("Content-type: text/xml");

// Start XML file, echo parent node
echo '<markers>';

// Iterate through the rows, printing XML nodes for each
while ($row = @mysql_fetch_assoc($result)){
  // ADD TO XML DOCUMENT NODE
  if($row['geo_state'] == "success") {
  echo '<marker ';
  echo 'name="' . parseToXML($row['school_name']) . '" ';
  echo 'zip="' . parseToXML($row['zip_code']) . '" ';
  echo 'site="' . parseToXML(htmlentities($row['site_name'],ENT_QUOTES,'UTF-8'))   . '" ';
  echo 'lat="' . $row['lat'] . '" ';
  echo 'lng="' . $row['lng'] . '" ';
  //echo 'type="' . $row['site_name'] . '" ';
  echo '/>';
  }
}

// End XML file
echo '</markers>';

?>
