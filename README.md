ccmap
=====

CC Map V2
his map was created using PHP, MYSQL and Google Maps API V2/V3 and you can find it here:  http://107.20.200.123/
The map scripts are located on an Amazon Web Services (AWS) UBUNTU EC2 Instance.
Google provides most of the help you need for making custom maps. Here's some links I used:

Using PHP/MYSQL with Google Maps
Geocoding addresses with PHP/MYSQL
MarkerClusterer Reference
Handling Large Amounts of Markers
PHP Manual (Lots of examples)

It is recommended to use Google Maps API V3 since V2 is no longer supported.. I could not get the GDownloadURL() function or markerClusterer class to work on V3 YET.. so I stuck with V2 for now. 
V3 also has cooler map controls.

There are FIVE main scripts that support the map.
They are located in the /var/www directory..

1. Database Set-up Script - initial database set-up

2. add_new_schools script - adds new schools from democrasoft database to local database

3. get latitude/longitude script - acquires latitude/longitude of sites

4. map xml script - outputs xml of map data

5. Index script - displays map

A cronjob is necessary to keep the map updating with new schools automatically.  Cron was already installed on the instance when i started.
Heres some reference I used on cronjobs... php and cron  cron tutorial
I used two methods for a cronjob.. The first method worked great until I changed some of the cron files .. I then implemented the second method which works fine..
Method 1:  On the command line enter "crontab -e" . This takes you to an editor to make cron commands.
The format is >> minute    hour    day-of-month month    day-of-week      command


I added 2 lines to the crontab...       */5   *    *    *    *    php  /var/www/add_new_schools.php   -runs every  5 minutes
                                                    */6   *    *    *    *    php  /var/www/get_latlng.php               -runs every 6 minutes


Just save the edits and your good to go.  It's easy to tell if it's working because the map will show the changes


Method 2:  Edit the file /etc/crontab with the command "sudo vi /etc/crontab"


I added 2 lines ...                            *   *   *   *   *       root    lynx -dump http://107.20.200.123/add_new_schools.php
                                                     *   *   *   *   *       root    lynx -dump http://107.20.200.123/get_latlng.php
"107.20.200.123" is the instance's elastic IP
These lines specify a user (root) , run every minute, and run the page using http request instead of a php command

