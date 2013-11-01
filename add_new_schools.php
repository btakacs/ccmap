/*add_new_schools.php
This page adds site data from the target database to a local database on the AWS instance.  The script's query (by Kyle Faulkner) filters out most test sites.

The script inserts zeros into the latitude and longitude columns( these 2 columns will be updated later..)

If the site selected has a null zip code, or the zip code is equal to 0, 'nullzip' is inserted into the geo_state column
Else, 'waiting' is inserted

If run in a web browser the script outputs information from sites that were inserted successfully and those that were not, along with the mysql error 

The mysql_real_escape_string() function is necessary for dealing with special characters
In order to make a connection to the target database, a hole had to be punched in the security for the specific elastic IP
*/

 <?php


//opens connection to target db and local db
$connection1 = mysql_connect("target_db_ip_addr","target_db_login",'target_db_pwd');
$connection2 = mysql_connect(local_db_ip,"local_db_login","local_db_pwd");

if (!$connection1 || !$connection2) {
  die("Not connected : " . mysql_error());
}
//selects relevent db from both dbs
$db_selected1 = mysql_select_db("target_db", $connection1);
$db_selected2 = mysql_select_db("local_db", $connection2);


if (!$db_selected1 || !$db_selected2) {
  die("Can\'t use db : " . mysql_error());
}

//selects..
//site name, published url, site id, date created, zipcode and school name
//from target db
//it filters sites created like test
$query1 = "SELECT
  s.site_name,
  s.publish_url,
  s.site_id,
  s.created_dtm AS site_created,
  ui.zip_code,
  ui.school_name

 FROM
  target_db.site s,
  target_db.user_info ui,
  target_db.account_login al,
  target_db.login l

  WHERE
   al.account_id=s.account_id
  AND
   ui.user_id=al.user_id
  AND
   l.user_id=ui.user_id
  AND
   s.site_status=2
  /* And if you want to not filter out the test accounts move this comment */
  AND l.email_id NOT LIKE '%test%'
  AND l.first_name NOT LIKE '%test%'
  AND l.last_name NOT LIKE '%test%'
  AND l.company_name NOT LIKE '%test%'
  AND s.site_name NOT LIKE 'delete_%'


ORDER BY s.created_dtm ";
$result = mysql_query($query1, $connection1);
if (!$result) {
  die("Invalid demo query: " . mysql_error());
}


while ($row = @mysql_fetch_assoc($result)) {
      //vairables to use in escape string
      $school = $row['school_name'];
      $site = $row['site_name'];
      $url = $row['publish_url'];
      $zip = $row['zip_code'];

      //this query is used for sites other than those with null zipcodes
      $query3 = sprintf("INSERT INTO profiles ".
      "(site_id,created_dtm, school_name,site_name,publish_url,zip_code,lat,lng,geo_state)".
                " VALUES ( \"".$row['site_id'].
                "\",\"".$row['site_created']."\",\"".mysql_real_escape_string($school)."\",\"".mysql_real_escape_string($site).
                "\",\"".mysql_real_escape_string($url)."\",\"".mysql_real_escape_string($zip)."\",0,0,'waiting');");

      //this query is used for sites with null zipcodes
       $query2 = sprintf("INSERT INTO profiles ".
      "(site_id,created_dtm, school_name,site_name,publish_url,zip_code,lat,lng,geo_state)".
                " VALUES ( \"".$row['site_id'].
                "\",\"".$row['site_created']."\",\"".mysql_real_escape_string($school)."\",\"".mysql_real_escape_string($site).
                "\",\"".mysql_real_escape_string($url)."\",\"".mysql_real_escape_string($zip)."\",0,0,'nullzip');");

      if($row['zip_code'] == "" || $row['zip_code'] == '0') {
      $insert_result = mysql_query($query2,$connection2); }
      else {
      $insert_result = mysql_query($query3,$connection2); }
      //outputs schools that were not read into local db
     if (!$insert_result) {
      echo "Query Fail: ".mysql_error().' '. $row['school_name'].' , '.$row['site_name']. '<br>';
      }
      //outputs the new schools added
      if ($insert_result) {
      echo "New school added: " .$row['school_name'].' , '.$row['site_name'].' , '.$row['zip_code']. '<br>';
      }
}
?>
