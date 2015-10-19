<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Twitter Syncer</title>
	<link rel="stylesheet" href="<?php echo base_url("assets/css/bootstrap.css"); ?>" />
</head>
<body>
	<nav class="navbar navbar-default">
  		<div class="container-fluid">
    		<!-- Brand and toggle get grouped for better mobile display -->
    		<div class="navbar-header">
      			<a class="navbar-brand" href="/">Twitter Syncer</a>
    		</div>
    		<!-- Collect the nav links, forms, and other content for toggling -->
    		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      			<ul class="nav navbar-nav navbar-right">
      				<?php
      					if($authUser){
      						?>
      							<li><?php echo $screenName?></li>		
      						<?php
      					}
      					else{
      						?>
      							<li><a href="<?php echo base_url("twitter/auth")?>">Sign in</a></li>
      						<?php
      					}
      				?>
        			
      			</ul>
    		</div><!-- /.navbar-collapse -->
  		</div><!-- /.container-fluid -->
	</nav>
    <div class="container">
    <div class="row">
      		<div class="col-sm-4">
      			<div id="sync">Time sync:
	      		</div>
      		</div>
      		<div class="col-sm-8">
	      		<div id="tweets">not connected
	      		</div>
      		</div>
    	</div>
	</div>
<script type="text/javascript" src="<?php echo base_url("assets/js/jquery-1.11.3.js"); ?>"></script>
<script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap.js"); ?>"></script>
<script>
$( document ).ready(function() {

var authUser = "<?php echo $authUser?>";
//console.log(authUser);
if(authUser == "1"){
	//console.log("authUser");
	connect();	
}
else{
	//console.log("!authUser");
}

function connect() {			
    var ws = new WebSocket("ws://localhost:4080/");
    var msg;
    			
    ws.onopen = function (event) {
		$.when(
    	$.get("twitter/user", function(data){
    		//console.log('Load was performed -> getuser');
    		msg = {
    		    	type: "message",
    		    	username: $.parseJSON(data).screen_name,
    		    	accesstoken: $.parseJSON(data).access_token,
    		    	accesstokensecret: $.parseJSON(data).access_token_secret
    		  	};
    		//screenName = $.parseJSON(data).screen_name;
    		//accessToken = $.parseJSON(data).access_token;
    		//console.log(screenName);
    		//console.log(accessToken);
    		
    	})
      	).then(function(){
          	//console.log(msg);
      		ws.send(JSON.stringify(msg));
          	})
    };
    			
    ws.onmessage = function (event) {
      	//console.log(event.data);
      	switch(event.data){
      		case "sync":
          		//console.log('case sync');
      			getTweets();
          		break;
      		case "okcred":
          		//console.log('case sync');
          		$('#sync').append("<br />" + "credentials ok");
          		break;   		
      	}
      	
    };
    ws.onerror = function (event) {
    	//$('#sync').empty();
		$('#sync').append("<br />Error websocket");
    };
}

function getUser(){
	$.get("twitter/user", function(data){
		//console.log('Load was performed -> getuser');
		var screenName = $.parseJSON(data).screen_name;
		var accessToken = $.parseJSON(data).access_token;
		//console.log(screenName);
		//console.log(accessToken);
	})
}

function getTweets(){
	$.get("twitter/tweets", function(data){
		var now = new Date();
		var time = now.getHours() + ":" + now.getMinutes() + ":" + now.getSeconds();
		$('#tweets').empty();
		$('#sync').append("<br />" + time);
		$('#tweets').append("<ul class='list-group'>");
		//console.log('Load was performed -> gettweets');
		
		// [{"message":"Rate limit exceeded","code":88}]		
		$.each($.parseJSON(data), function(idx, obj){
			if (typeof obj.message != undefined){
				//console.log(obj.message);
				$('#tweets').append("<li class='list-group-item'>" + obj.message + "</li>");
			}
			else if(typeof obj.message != undefined){
				$('#tweets').append("<li class='list-group-item'>" + obj.text + "</li>");
				//console.log(obj.text);
			}
		});
		$('#tweets').append("</ul>");
	})
}
});
</script>
</body>
</html>