var server = require('http').createServer()
  , url = require('url')
  , WebSocketServer = require('ws').Server
  , wss = new WebSocketServer({ server: server })
  , express = require('express')
  , app = express()
  , port = 4080;

//twitter
var Twitter = require('twitter');

var client = new Twitter({
  consumer_key: 'QGypBjqk9aYf9VilCgqBPa28A',
  consumer_secret: '93qc7hXcbeFxM5xTXBqRGhCGFt1epipUDxrBfUUHsPY1jUszNS',
  access_token_key: '175810126-zfq95juKi3LL9gd6pWZp5exa5NZxFNHuwGJKQ2xB',
  access_token_secret: 'i9TpN7rAS6AZ47XlKcmKUFdTaONo8oU5i28j985xFhPnL'
});
 
var params = {screen_name: 'nodejs'};

setInterval(getTweets, 5000);

var msg1;

function getTweets(){
	console.log('get tweets');
	client.get('statuses/user_timeline', params, function(error, tweets, response){
	//client.get('favorites/list', params, function(error, tweets, response){
		if (!error) {
			var oTweets = tweets;
			//console.log(oTweets.length);
			
			for(var i = 0, len = oTweets.length; i < len; i++){
				console.log(oTweets[i].text);
			}
			
			//console.log(oTweets[1].text);
			
			//console.log(tweets);
			//console.log(typeof tweets);
			msg1 = tweets;
		}
		else{
			console.log(error);
		}
	});
	
	//client.get('favorites/list', function(error, tweets, response){
	//	  if(error) 
	//		  {
	//		  	console.log(error);
	//		  	throw error;
	//		  }
	//	  console.log(tweets);  // The favorites. 
	//	  console.log(response);  // Raw response object. 
	//	});
	
}

function myTimer() {
    var d = new Date();
    console.log(d);
    //msg1 = d;
}

//twitter

app.use(function (req, res) {
  res.send({ msg: "hello" });
});

wss.on('connection', function connection(ws) {
  var location = url.parse(ws.upgradeReq.url, true);
  // you might use location.query.access_token to authenticate or share sessions
  // or ws.upgradeReq.headers.cookie (see http://stackoverflow.com/a/16395220/151312)

  ws.on('message', function incoming(message) {
    console.log('received: %s', message);
  });
  //twitter
  var sendmsg = msg1;
  //twitter
  ws.send(sendmsg);
});

server.on('request', app);
server.listen(port, function () { console.log('Listening on ' + server.address().port) });