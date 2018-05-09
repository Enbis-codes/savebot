<?
//MySQL connection and path variables
require('config.php');

//GETs
$auth_code = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['code']);
$r = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['r']);
$error = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['error']);
if($_GET['c']) $channel = preg_replace('/[^\w]/', '', $_GET['c']);
if($_GET['channel']) $channel = preg_replace('/[^\w]/', '', $_GET['channel']);
$q = preg_replace('/[^\w]/', '', $_GET['q']);
$auth = urlencode($auth_url);
$link = "https://api.nightbot.tv/oauth2/authorize?response_type=code&redirect_uri=$auth&client_id=$client_id&scope=$scopes"; // authentication link

//COMMANDS
if($q){
	$rt = mysqli_query($conn, "SELECT * FROM `auth_bot` WHERE `channel`='$channel' LIMIT 1");
	$row = mysqli_fetch_assoc($rt);
	$data = http_build_query(array('coolDown' => 5,'message' => "test",'name' => '!test','userLevel' => 0));
	$options = array('http' => array('method'  => 'POST','header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer ".$row['access_token'],'content' => $data));
	$context  = stream_context_create($options);
	$result = file_get_contents('https://api.nightbot.tv/1/commands', false, $context);
	$obj = json_decode($result);
	var_dump($options);
}

//CALLBACK
if($auth_code || $r == 1){	
	// SEND VIA POST - as it turns out, response cannot be sent by GET. There's some new fancy cURL thing to do this easier but since I'm ancient, I do it like I used to over a decade ago.
	
	if($r){ //REFRESH TOKEN
		$rt = mysqli_query($conn, "SELECT * FROM `auth_bot` WHERE `channel`='$channel' LIMIT 1");
		$row = mysqli_fetch_assoc($rt);
		
		$data = http_build_query(
			array(
				'client_id' => $client_id,
				'client_secret' => $secret,
				'grant_type' => 'refresh_token',
				'redirect_uri' => $auth_url,
				'refresh_token' => $row['refresh_token']
			)
		);
	}
	else { //NEW TOKEN
		$data = http_build_query(
			array(
				'client_id' => $client_id,
				'client_secret' => $secret,
				'grant_type' => 'authorization_code',
				'redirect_uri' => $auth_url,
				'code' => $auth_code
			)
		);
	}
	$options = array('http' => array('method'  => 'POST','header'  => "Content-type: application/x-www-form-urlencoded",'content' => $data));
	
	//GET TOKEN - this is how those JSON responses can be processed into variables
	$context  = stream_context_create($options);  // this does the POST thing
	$result = file_get_contents('https://api.nightbot.tv/oauth2/token', false, $context); // and this reads what nightbot has to say to us
	$obj = json_decode($result); // decodes JSON into readable object
	$new_token = $obj->{'access_token'}; // THIS IS WHERE WE GET ACTUAL TOKEN - same can be done to any JSON line if needed
	$refresh_token = $obj->{'refresh_token'}; // This is needed to refresh token later
	$expires = $obj->{'expires_in'} + time();
	$status = $obj->{'status'};
	
	//GET CHANNEL NAME
	$channel_name = file_get_contents('https://api.nightbot.tv/1/channel?access_token='.$new_token);
	$ch_name = json_decode($channel_name); // decodes JSON
	$channel = $ch_name->{'channel'}->{'name'};
	
	//FAIL
	if(!$new_token) header("Location: $auth_url?error=$status");
	
	//SUCCESS - save to DB and redirect to self
	else {
		$conn->query("DELETE FROM `auth_bot` WHERE `channel`='$channel'"); // to cut it short, just tries to delete old channel's token, whether it was there or not
		$conn->query("INSERT INTO `auth_bot`(`channel`, `access_token`, `refresh_token`, `expires_in`) 
									 VALUES ('$channel', '$new_token', '$refresh_token', '$expires')"); // writes down new token along with channel and expiration
		if($r) header("Location: $auth_url?r=2&c=$channel");
		else header("Location: $auth_url?c=$channel"); 
	}
}

//USELESS DECORATIONS TIME
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<title>SaveSong - custom api command for Nightbot</title>
	<link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<style>
	  body {
		padding-top: 54px;
	  }
	  @media (min-width: 992px) {
		body {
		  padding-top: 56px;
		}
	  }
	</style>
</head>
<body>    
<!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <a class="navbar-brand" href="<? echo $auth_url; ?>">Authentication</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="https://www.twitch.tv/enbis">PM for help</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
	
	<!-- Page Content -->
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-left">
			<h1 class="mt-5">Save song command for Nightbot</h1>
			<p class="lead">
<?
//START - runs when no GET variables are found
if(!$channel && !$auth_code && !$r){ ?>

	This Nightbot custom command allows your chat to save currently playing song to their own list that can be viewed later.<br>
	It'll ask your permission to read your queue and channel's name - nothing else is needed.</p>
	<div class="text-center">
		<p class="lead mark"><a href="<? echo $link; ?>">Get !save command for your queue</a></p>
	</div></ul>
<?
}

	
//CONTINUE - runs after the form is sent 
if($channel){
	$result = mysqli_query($conn, "SELECT * FROM `auth_bot` WHERE `channel`='$channel' LIMIT 1"); // check if there is a token saved for this channel
	$row = mysqli_fetch_assoc($result);
	
	// if db has no token 
	if(mysqli_num_rows($result) == 0 || !$row['access_token'] || !$row['refresh_token']) 
		$fail = true;
	
	else {
		$chck_name = file_get_contents('https://api.nightbot.tv/1/channel?access_token='.$row['access_token']);
		$chk_name = json_decode($chck_name); // decodes JSON
		$check_name = $chk_name->{'channel'}->{'name'};
		if($check_name != $channel) 
			$fail = true;
	}
	
	if($fail){
	?>
	<p class="lead">For some reason there's no saved token for "<? echo $channel; ?>" channel.</p>
	<div class="text-center">
		<p class="lead mark"><a href="<? echo $link; ?>">Try to authenticate again</a></p>
	</div>
	<?
	}
	//SUCCESS - token's saved
	else {
		echo "Access token for <b>".$row['channel']."</b> is ";
		if($r == 2) echo "refreshed!<br>"; else echo "saved!<br>";
		
		$exp = $row['expires_in'] - time();
		$exp_d = date("d", $exp); // gets number of days until expiration
		if($exp > 0) echo "Days until it expires: ".$exp_d;
		elseif($exp <= 0 && $exp > -2678400) echo "<b>Access token has expired</b>, but can be refreshed within ".$exp_d." days.";
		else echo "<b>But it has expired!</b> If refresh doesn't work, <a href='".$auth_url."'>try to authenticate again</a>."; ?></p>
		<div class="text-center">
			<p class="lead mark"><a href="?r=1&c=<? echo $channel; ?>" data-toggle="tooltip" data-placement="top" title="You won't need to refresh it manually unless !save command is not used for weeks.">Refresh</a></p>
		</div>
				
		<h4>What's next?</h4>
		<p class="lead"><a href='https://beta.nightbot.tv/commands/custom'><b>Go here</b></a> and create custom command <a href="#" data-toggle="tooltip" data-placement="top" title="Or you can name it anything you like, e.g. !saved, !savesong. Also you can set any permissions."><b>!save</b></a> with the following line in <b>Message</b> field:<p>
		<div class="form-group">
			<input type="text" style="color: #0069D9; background-color: #FCF8E3; border: none;" value="$(urlfetch <? echo $save_url; ?>?u=$(user)&c=$(channel)&q=$(querystring))" class="form-control" id="command">
			<button onclick="CopyText()" class="btn btn-primary">copy command</button>
		</div>
		<h4>And that's it!</h4>
		<p class="lead"> You can test it now by typing your new commands in your own twitch chat. But make sure Nightbot's there. </p><br />
		 <!-- The text field -->


<!-- The button used to copy the text -->
		<h4>Current commands:</h4><ul>
		<li><b>!save</b> - will save the current song in a personal list for whoever uses that command</li>
		<li><b>!save help</b> - explains how to use it to chat</li>
		<li><b>!save mylist</b> - will show the link to user's own list</li>
		<li><b>!save get</b> - explains how to get the command for other streamers</li>
		<li><b>!save *anything*</b> - any other text after the !save will be added as a comment to the song</li>
		</ul>
		<?
	}
	
}
?>
		</p><hr>
		<p class="small">If something doesn't work, PM me at twitch (Enbis) or discord (Enbis#3840). <a href="source/SaveSongBot.zip">Source code is here</a>, you can upload it to your own host if you have one. It's only better for me since my hosting provider is cheap and slow - not sure it'll handle it if more than a few people would use this.</p>
      </div>
    </div>
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script>
		$(document).ready(function(){
			$('[data-toggle="tooltip"]').tooltip();   
		});
		function CopyText() {
		  var copyText = document.getElementById("command");
		  copyText.select();
		  document.execCommand("Copy");
		} 
	</script>
</body>
</html>

<?
//Author: Enbis, 2018
//Discord: Enbis#3840
?>