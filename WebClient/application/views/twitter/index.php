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
      			<div>
      				<h4>Status:</h4>
      				<div id="sync">
	      			</div>
      			</div>
      		</div>
      		<div class="col-sm-8">
	      		<div id="tweets">
	      		</div>
      		</div>
    	</div>
	</div>
<script type="text/javascript" src="<?php echo base_url("assets/js/jquery-1.11.3.js"); ?>"></script>
<script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap.js"); ?>"></script>
<script src="http://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/aes.js"></script>
<script>
$( document ).ready(function() {

//aes
var JsonFormatter = {
	stringify: function (cipherParams) {
        // create json object with ciphertext
     	var jsonObj = {
            ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)
        };

        // optionally add iv and salt
        if (cipherParams.iv) {
            jsonObj.iv = cipherParams.iv.toString();
        } 
        if (cipherParams.salt) {
            jsonObj.s = cipherParams.salt.toString();
        }

        // stringify json object
        return JSON.stringify(jsonObj);
        },

    parse: function (jsonStr) {
        // parse json string
        var jsonObj = JSON.parse(jsonStr);

        // extract ciphertext from json object, and create cipher params object
        var cipherParams = CryptoJS.lib.CipherParams.create({
            ciphertext: CryptoJS.enc.Base64.parse(jsonObj.ct)
        });

        // optionally extract iv and salt
        if (jsonObj.iv) {
            cipherParams.iv = CryptoJS.enc.Hex.parse(jsonObj.iv)
        }
        if (jsonObj.s) {
            cipherParams.salt = CryptoJS.enc.Hex.parse(jsonObj.s)
        }

        return cipherParams;
    }
};

var authUser = "<?php echo $authUser?>";

if(authUser == "1"){
	connect();	
}

function connect() {			
    //var ws = new WebSocket("ws://localhost:4080/");
    var ws = new WebSocket("<?php echo $ws?>");

    var msg;
    			
    ws.onopen = function (event) {
		$.when(
    	$.get("twitter/user", function(data){
    		
    		msg = {
    		    	type: "message",
    		    	username: $.parseJSON(data).screen_name,
    		    	accesstoken: $.parseJSON(data).access_token,
    		    	accesstokensecret: $.parseJSON(data).access_token_secret
    		  	};
    	})
      	).then(function(){
      		ws.send(CryptoJS.AES.encrypt(JSON.stringify(msg), "facility", { format: JsonFormatter }));
     	})
    };
    			
    ws.onmessage = function (event) {
        
      	switch(event.data){
      		case "sync":
      			getTweets();
          		break;
      		case "okcred":
          		$('#sync').append("<div class='alert alert-success' role='alert'><a href='#' class='alert-link'>Credentials ok</a></div>");
          		break;  
      		case "errcred":
          		$('#sync').append("<div class='alert alert-danger' role='alert'><a href='#' class='alert-link'>Credentials error</a></div>");
          		break;  		
      	}
    };
    
    ws.onerror = function (event) {
    	
		$('#sync').append("<div class='alert alert-danger' role='alert'><a href='#' class='alert-link'>Error websocket</a></div>");
    };
}

function getUser(){
	$.get("twitter/user", function(data){
		var screenName = $.parseJSON(data).screen_name;
		var accessToken = $.parseJSON(data).access_token;
	})
}

function getTweets(){
	$.get("twitter/tweets", function(data){
		var now = new Date();
		var time = now.getHours() + ":" + now.getMinutes() + ":" + now.getSeconds();
		$('#tweets').empty();
		$('#tweets').append("<ul class='list-group'>");

		// [{"message":"Rate limit exceeded","code":88}]		
		$.each($.parseJSON(data), function(idx, obj){
			if (typeof obj.message !== "undefined"){
				$('#sync').empty().append("<div class='alert alert-danger' role='alert'><a href='#' class='alert-link'>Sync: " + time + " error: " + obj.message + "</a></div>");				
			}else{
				$('#tweets').append("<li class='list-group-item'><div  class='media'><div class='media-left'><a href='#'><img class='media-object' src='" + obj.user.profile_image_url + "' alt='...'></a></div><div class='media-body'><h4 class='media-heading'>" + obj.user.name + "</h4>" + obj.text + "</div></div></li>");
				$('#sync').empty().append("<div class='alert alert-success' role='alert'><a href='#' class='alert-link'>Sync: " + time + "</a></div>");
			}
		});
		$('#tweets').append("</ul>");
	})
}
});
</script>
</body>
</html>