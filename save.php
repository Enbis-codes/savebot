<?
//MySQL connection and path variables
require('config.php');

//GETs
if($_GET['u']) $username = preg_replace('/[^\w]/', '', $_GET['u']);
if($_GET['username']) $username = preg_replace('/[^\w]/', '', $_GET['username']);
if($_GET['c']) $channel = preg_replace('/[^\w]/', '', $_GET['c']);
if($_GET['channel']) $channel = preg_replace('/[^\w]/', '', $_GET['channel']);
if($_GET['q']) $q = preg_replace('/[^\w]/', '', $_GET['q']);

$result = mysqli_query($conn, "SELECT * FROM `auth_bot` WHERE `channel`='$channel' LIMIT 1");
$row = mysqli_fetch_assoc($result);
$access = $row['access_token'];

//Input
switch($q){
	case 'help':
		echo "If you like current song, you can use !save command to add it to your own list so you can find it later! You can also add comment to the song you're saving - just type it after after !save. Type !save mylist to find your list.";
	break;
	
	case 'mylist':
		echo "@$username, your list is here: $songlist_url?u=$username";
	break;
	
	case 'get':
		echo "If you're a streamer and use Nightbot queue, you can add !save command here: $auth_url";
	break;
	
	case 'unsave':
		$unsave = true;
	break;
	
	case 'edit':
		echo "Not yet implemented, but I'll make it editable soon";
	break;
	
	case 'commands':
		echo "For now special commands are: !save help, !save mylist, !save get (explains how to get !save). Edit and unsave are to be implemented.";
	break;
	
	default:
	case '':
		$do = true;
		$comment = $q;
	break;
}

if($unsave){
		$check = mysqli_query($conn, "SELECT * FROM `saved_songs` WHERE `username`='$username' ORDER BY `id` DESC LIMIT 1");
		$chrow = mysqli_fetch_assoc($check);
		$report = $chrow['songname'];
		if($chrow['id']) {
			mysqli_query($conn, "DELETE FROM `saved_songs` WHERE `username`='$username' AND `id`='".$chrow['id']."' LIMIT 1");
			echo "@$username, ok, mistakes happen! Last song you saved '$report' is deleted.";
		}
		else echo "@$username, seems like you don't have saved songs to delete yet!";
}

if($do) {
// REFRESH token (will run no more often than once a week)
	if($row['expires_in'] - time() < 2000000){
		$data = http_build_query(array('client_id' => $client_id,'client_secret' => $secret,'grant_type' => 'refresh_token','redirect_uri' => $auth_url,'refresh_token' => $row['refresh_token']));
		$options = array('http' => array('method'  => 'POST','header'  => "Content-type: application/x-www-form-urlencoded",'content' => $data));
		$context  = stream_context_create($options);
		$rt = file_get_contents('https://api.nightbot.tv/oauth2/token', false, $context);
		$ob = json_decode($rt);
		$new_token = $ob->{'access_token'};
		$refresh_token = $ob->{'refresh_token'};
		$expires = $ob->{'expires_in'} + time();
		if($new_token){
			$conn->query("UPDATE `auth_bot` SET `access_token` = '$new_token', `refresh_token` = '$refresh_token', `expires_in` = '$expires' WHERE `channel` = '$channel' LIMIT 1");
			$access = $new_token;
		}
	}
	//PARSE JSON - to check what else's in there, just copy the link below + saved access token and open it in the browser
	$results = file_get_contents('https://api.nightbot.tv/1/song_requests/queue?access_token='.$access); // gets stuff from the api 
	$obj = json_decode($results); // decodes JSON

	//FAIL - if status is wrong, most likely token has expired or wasn't there in first place
	if($obj->{'status'} != '200') echo "This channel's owner needs to authenticate or refresh the access! ".$auth_url."?r=1&c=".$channel;

	//FAIL - when queue is empty
	elseif($obj->{'_currentSong'} == NULL) echo "Queue is empty - nothing to save!";

	//NOT-FAIL
	else {
		$song_url 		= 	str_replace("'", "", $obj->{'_currentSong'}->{'track'}->{'url'});
		$songname 		= 	str_replace("'", "", $obj->{'_currentSong'}->{'track'}->{'title'});
		$artist 		= 	str_replace("'", "", $obj->{'_currentSong'}->{'track'}->{'artist'});
		$requested 		= 	str_replace("'", "", $obj->{'_currentSong'}->{'user'}->{'displayName'});
		$now = time();
		$check = mysqli_query($conn, "SELECT * FROM `saved_songs` WHERE `username`='$username' AND `url`='$song_url' LIMIT 1");
		$chrow = mysqli_fetch_assoc($check);
		
		if($chrow['id']) echo "@$username, you already had this song in your list! Type !mylist to see it."; // If song was saved before, it won't double

		else {
			// Saves the song!
			$write = mysqli_query($conn, "INSERT INTO `saved_songs`(`username`, `url`, `songname`, `artist`, `comment`, `requested`, `channel`, `date`) VALUES ('$username', '$song_url', '$songname', '$artist', '$comment', '$requested', '$channel', $now)");
			if($write) echo "@$username, song's saved to your list! Type !mylist to see it.";
			else "@$username, sorry, something went wrong WutFace"; // I dunno what else can go wrong, but let's say it might.
		}
	}
}
//Author: Enbis, 2018
//Discord: Enbis#3840	
?>