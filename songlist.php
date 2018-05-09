<? 
//MySQL connection and path variables
require('config.php');
$username = preg_replace('/[^\w]/', '', $_GET['u']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<title>Saved songlist - <? echo $username; ?></title>
	<!-- Bootstrap core CSS -->
	<link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<!-- Custom styles for this template -->
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
        <a class="navbar-brand" href="#">Song list by <? echo $username; ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="https://jy.feq.ru/bot/auth.php">Get !save command for your Nightbot's queue</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
	
	<!-- Page Content -->
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-left">
			<div class="table-responsive">
			<table class="table"><tr>
			<th>Added on</th>
			<th>Link</th>
			<th>Artist</th>
			<th>Requested by</th>
			<th>On channel</th>
			<th>Comment</th>
			</tr>
<?
if(!$username) echo "Username's missing! You can try typing it in the url like this: ".$songlist_url."?u=TWITCH_NAME";
$results = mysqli_query($conn, "SELECT * FROM `saved_songs` WHERE `username`='$username' order by `date` DESC");


while ($row = mysqli_fetch_array($results)) {
	$when = date("d.m.y G:i", $row['date']);
	echo '<tr><td>'.$when.'</td><td><a href='.$row['url'].'>'.$row['songname'].'</td><td>'.$row['artist'].'</a></td><td>'.$row['requested'].'</td><td>'.$row['channel'].'</td><td>'.$row['comment'].'</td></tr>';
}
?>
          </table>
<? if(mysqli_num_rows($results) == 0) echo '<div class="text-center"><br /><br /><p class="lead">Your list is empty! You can add songs by typing !save to add current.</p>'; ?>
        </div>
      </div>
    </div>
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?
//Author: Enbis, 2018
//Discord: Enbis#3840
?>