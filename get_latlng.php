/*get_latlng.php
This script selects sites from the local database that are waiting to be geocoded, sends a geocode request for them, and updates the database accordingly
I chose for the http request to respond with a CSV output becasue it's the simplest.  It responds with the latitude and longitude seperated by a comma
If the request went through and returned a latitude/longitude, the script seperates the data and adds updates the site in the local database
The corresponding site is identified by the tables primary key.  The new latitude/longitude is added and the geo_state column is updated to 'success'
If the request fails, the geo_state column for the row is updated to 'failed'
The current Google API policy is to only allow 2,500 http geocode requests per 24 hours (Unless you have Google Maps API Premier.. They get 100,000)
I worked around this by allocating a new elastic IP for the instance and requesting a new API KEY whenever I needed to geocode large amounts (For example when you need to rebuild the map and the local database)
*/
<?php

define("MAPS_HOST", "maps.google.com");
define("KEY", "YOURKEYHERE");

// Opens a connection to a MySQL server
$connection = mysql_connect("localhost", "map", "map707");
if (!$connection) {
  die("Not connected : " . mysql_error());
}

// Set the active MySQL database
$db_selected = mysql_select_db("marker_data", $connection);
if (!$db_selected) {
  die("Can\'t use db : " . mysql_error());
}

// Select all the rows in the markers table
$query = "SELECT * FROM profiles WHERE geo_state = 'waiting'";
$result = mysql_query($query);
if (!$result) {
  die("Invalid query: " . mysql_error());
}

// Initialize delay in geocode speed
$delay = 0;
$base_url = "http://" . MAPS_HOST . "/maps/geo?output=csv&key=" . KEY;

// Iterate through the rows, geocoding each address
while ($row = @mysql_fetch_assoc($result)) {
  $geocode_pending = true;

  while ($geocode_pending) {
    $address = $row["zip_code"];
    $P_Id = $row["P_Id"];
    $request_url = $base_url . "&q=" . urlencode($address);
    $csv = file_get_contents($request_url) or die("url not loading");

    $csvSplit = split(",", $csv);
    $status = $csvSplit[0];
    $lat = $csvSplit[2];
    $lng = $csvSplit[3];
    if (strcmp($status, "200") == 0) {
      // successful geocode
      $geocode_pending = false;
      $lat = $csvSplit[2];
      $lng = $csvSplit[3];

      $query = sprintf("UPDATE profiles " .
             " SET lat = '%s', lng = '%s',geo_state = 'success' " .
             " WHERE P_Id = %s LIMIT 1;",
             mysql_real_escape_string($lat),
             mysql_real_escape_string($lng),
             mysql_real_escape_string($P_Id));
      $update_result = mysql_query($query);
      if (!$update_result) {
        die("Invalid query: " . mysql_error());
      }
    } else if (strcmp($status, "620") == 0) {
      // sent geocodes too fast
      $delay += 100000;
    } else {
      // failure to geocode
      $query = sprintf("UPDATE profiles " .
             " SET geo_state = 'failed' " .
             " WHERE P_Id = '%s' LIMIT 1;",
             mysql_real_escape_string($P_Id));
      $update_result = mysql_query($query);
      if (!$update_result) {
        die("Invalid fail query: " . mysql_error());
      }

      $geocode_pending = false;
      echo "Address " . $address . " failed to geocoded. ";
      echo "Received status " . $status . "<br>";
    }
    usleep($delay);
  }
}
?>
