<!-- index.php
This is the main page on the web server which displays the map.  It's written almost entirely in html/javascript using Google Maps custom API tools.  Three source files are needed for reference on the page.  
First the Google maps reference allows for use of their custom functions and allows for quick customization of the map.  To see the map/use these tools, you must acquire an API key for your domain(It's free) Sign up here.  For example:
<script src="http://maps.google.com/maps?file=api&amp;v=3&amp;key=YOURAPIKEYHERE&sensor=false" ></script>
Second the markerClusterer class AUTOMATICAlLY clusters markers on the map.  This is crucial in speeding up how fast the map renders.  And it looks pretty cool.  The appearance of the clusters can also be changed. Example: 
 <script type = "text/javascript" src="http://107.20.200.123/markercluster.js"></script>
I added the copied and pasted the markerClusterer class into the /var/www directory
Newer versions of this class did not work for me.  Here's the one that did... MarkerClusterer
When the page is first loaded the "Initialize()" function is called.  Everything in this function is subsequently loaded/executed.
The data for the markers is read from an xml file by a function "GDownloadURL".  The script reads data for each individual marker and creates its icon and its infowindow before moving on to the next.
-->

<!DoCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Collab!!</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=3&amp;key=
    YOURKEYHERE
    &sensor=false" ></script>

    <script type = "text/javascript" src="http://107.20.200.123/markercluster.js"></script>

    <script type = "text/javascript">

    var iconBlue = new GIcon();
    iconBlue.image = 'http://labs.google.com/ridefinder/images/mm_20_blue.png';
    iconBlue.shadow = 'http://labs.google.com/ridefinder/images/mm_20_shadow.png';
    iconBlue.iconSize = new GSize(12, 20);
    iconBlue.shadowSize = new GSize(22, 20);
    iconBlue.iconAnchor = new GPoint(6, 20);
    iconBlue.infoWindowAnchor = new GPoint(5, 1);

    //starts loading the map
    function initialize() {
      if (GBrowserIsCompatible()) {
        //creates base map
        var map = new GMap2(document.getElementById("map"));
        //array that will hold all of the markers
        var theGoods = [];
        //adds zoom and pan controls
        map.addControl(new GLargeMapControl3D());
        //adds map type controls
        map.addControl(new GHierarchicalMapTypeControl());
        //sets center latitude/longitude and default zoom
        map.setCenter(new GLatLng(0,0), 2);
        //enables mouse wheel zooming
        map.enableScrollWheelZoom();
        //holds value of previous latitude
        var oldlat;
        //holds value of previous longitude
        var oldlng;
        //counter for lat/lng duplicates in a row
        var count = 0;
        var counter = 1;
        var colcount = 0;
        //gets information from xml page with all marker data
        GDownloadUrl("/mapxml.php", function(data) {
          var xml = GXml.parse(data);
          var markers = xml.documentElement.getElementsByTagName("marker");

          //this loop creates markers and adds them the the marker array
          for (var i = 0; i < markers.length; i++) {
            var name = markers[i].getAttribute("name");
            var site = markers[i].getAttribute("site");
            var address = markers[i].getAttribute("zip");
            var lat = parseFloat(markers[i].getAttribute("lat"));
            var lng = parseFloat(markers[i].getAttribute("lng"));
            var point = new GLatLng(lat,lng);

            //**********************************************************
            //if lat/lng is unique to those before it
            if(lat != oldlat && lng != oldlng) {
            count = 0;
            //creates marker
            var marker = createMarker(point,name,address,site);
            //adds marker to array
            theGoods.push(marker);
            //set current lat/lng to prev lat/lng
            oldlat = lat;
            oldlng = lng;
            }

            //***********************************************************
            //if lat/lng is NOT unique to those before it
            else {
                 if(count == 0) {
                 counter = 1;
                 colcount = 0;}

                 else if(count % 5 == 0) {
                 counter = 0;
                 colcount++;}

            lat = lat + (0.0008 * counter);
            lng = lng + (0.0008 * (colcount));
            counter++;
            count++;

            //redefine point with altered lat/lng
            point = new GLatLng(lat,lng);
            var marker = createMarker(point,name,address,site);
            theGoods.push(marker);
            }
          }
            //*******************************************************
            //markerClusterer options are set
            var mcOptions = {gridSize: 50, maxZoom: 9};
            //the markerClusterer is called using its options
            //and the array of markers
            var mc = new MarkerClusterer(map,theGoods,mcOptions);

        });
            //this function creates a panel
            //which displays site totals
            function MyPane() {}
            MyPane.prototype = new GControl;
            MyPane.prototype.initialize = function(map) {
            var me = this;
            me.panel = document.createElement("div");
            //sets the dimensions,border and background color
            me.panel.style.width = "145px";
            me.panel.style.height = "80px";
            me.panel.style.border = "1px solid gray";
            me.panel.style.background = "white";
            //the panel output is written in html..
            me.panel.innerHTML = "<?php
               $con = mysql_connect("localhost","map","map707");
               mysql_select_db("marker_data",$con);
               $query = mysql_query("SELECT zip_code FROM profiles",$con);
               $total = mysql_num_rows($query); echo "<center> Total Sites: ". $total."<br>";
               $query = mysql_query("SELECT zip_code FROM profiles WHERE geo_state='success'",$con);
               $success = mysql_num_rows($query); echo " Sites mapped: ". $success."<br>";
               $query = mysql_query("SELECT zip_code FROM profiles WHERE geo_state='nullzip'",$con);
               $null = mysql_num_rows($query); echo " Sites w/out zips: ". $null."<br>";
               $query = mysql_query("SELECT zip_code FROM profiles WHERE geo_state='failed' or geo_state='waiting'",$con);
               $fail = mysql_num_rows($query); echo " Failed to map: ". $fail ."</center>";
                                 ?>";

            map.getContainer().appendChild(me.panel);
            return me.panel;
            };
            //places the panel in the upper right corner of page
            MyPane.prototype.getDefaultPosition = function() {
            return new GControlPosition(
            G_ANCHOR_TOP_RIGHT, new GSize(10, 50));
            //Should be _ and not &#95;
            };

            MyPane.prototype.getPanel = function() {
            return me.panel;
            }
            map.addControl(new MyPane());
       }
     }


    //This function create the markers and their info windows
    function createMarker(point, name, address,site) {
      //the marker is made from lat/lng and our custom blue icon
      var marker = new GMarker(point, iconBlue);
      //contents of info window are written here...
      var html = "<b> School: </b><i>" + name + "</i><br/><b>Site: </b><i>"
                 + site + "</i><br/><b> Zip: </b><i>" + address + "</i>";
      GEvent.addListener(marker, 'click', function() {
        marker.openInfoWindowHtml(html);
      });
      return marker;
    }
    //]]>
  </script>

  </head>

  <body onload="initialize()" onunload="GUnload()">
    <!-- The map position and size are defined here-->
    <div id="map" style="position: absolute; top: 0px; left:0px; width: 100%; height: 100%"></div>
  </body>
</html>
