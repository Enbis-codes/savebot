<?
//MySQL
$dbhost = 'YOUR_DB_HOST';
$dbuser = 'DB_USERNAME';
$dbpass = 'DB_PASSWORD';
$dbname = 'DATABASE_NAME';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) die("MySQL's dead: ".$conn->connect_error);

//Twitch app - create it here: https://beta.nightbot.tv/account/applications
$client_id = ''; // client id can be found by pressing yellow edit button on the new app
$secret = ''; // to get secret at same popup, press New Secret, copy it here, and then *save the app*

//URLS 
$save_url = 'https://jy.feq.ru/bot/save.php'; // full link to save.php
$songlist_url = 'https://jy.feq.ru/bot/songlist.php'; // full link to songlist.php
$auth_url = 'https://jy.feq.ru/bot/auth.php'; // full link to auth.php
// files can be renamed and even separated, but keep config in same directory with each
// Note: your host MUST be https, not http. You can get it free on e.g. cloudflare.com and similar services.

// SCOPES (permissions) - you can add more (separated by space)
$scope = 'song_requests_queue%20channel'; 
// if you want to add other functions yourself, look up it up at https://api-docs.nightbot.tv/?cURL#scopes

//Author: Enbis, 2018
//Discord: Enbis#3840
?>