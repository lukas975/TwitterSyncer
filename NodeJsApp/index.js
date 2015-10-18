var server = require('http').createServer()
  , url = require('url')
  , WebSocketServer = require('ws').Server
  , wss = new WebSocketServer({ server: server })
  , express = require('express')
  , app = express()
  , port = 4080;

//mysql
var mysql      = require('mysql');
var connection = mysql.createConnection({
  host     : 'localhost',
  user     : 'user1',
  password : 'password1',
  database : 'db_test'
});

//twitter init
var twitterAPI = require('node-twitter-api');
var twitter = new twitterAPI({
    consumerKey: 'QGypBjqk9aYf9VilCgqBPa28A',
    consumerSecret: '93qc7hXcbeFxM5xTXBqRGhCGFt1epipUDxrBfUUHsPY1jUszNS',
    callback: 'http:////localhost:4080/'
});

//twitter verify credentials
function verifyCredentials(userName, accessToken, accessTokenSecret, callback){
    twitter.verifyCredentials(accessToken, accessTokenSecret, function(error, data, response) {
        if (error) {
            //something was wrong with either accessToken or accessTokenSecret 
            console.log('error twitter credentials');
        } else { 
            console.log(userName);
            insertUserIfNotExists(userName);
        }
    });
    callback();
}

function getUsers(){
	connection.connect();
	connection.query('SELECT * from table1', function(err, rows, fields) {
		if (!err){
			console.log('The solution is: ', rows);
		}
		else{
			console.log('Error while performing Query.');
		}
	});
	connection.end();
}

// INSERT INTO table1 (name1) SELECT 'luka' FROM DUAL WHERE NOT EXISTS (SELECT name1 FROM table1 WHERE name1='luka')
function insertUserIfNotExists(user){
    connection.connect();
	connection.query('INSERT INTO table1 (name1) SELECT "' + user + '" FROM DUAL WHERE NOT EXISTS (SELECT name1 FROM table1 WHERE name1="' + user + '")', function(err, rows, fields) {
		if (!err){
			console.log('The solution is: ', rows);
		}
		else{
			console.log('Error while performing Query.');
		}
	});
	connection.end();
}

app.use(function (req, res) {
  res.send({ msg: "hello" });
});

wss.on('connection', function connection(ws) {
  var location = url.parse(ws.upgradeReq.url, true);
  // you might use location.query.access_token to authenticate or share sessions
  // or ws.upgradeReq.headers.cookie (see http://stackoverflow.com/a/16395220/151312)

  ws.on('message', function incoming(message) {
	    //var msg = JSON.stringify(JSON.parse(message));
	    var msg = JSON.parse(message);
	    //console.log('received: %s', message);
	    //console.log('received: %s', msg.username);
        //console.log('received: %s', msg.accesstoken);
        //console.log('received: %s', msg.accesstokensecret);
      
        verifyCredentials(msg.username, msg.accesstoken, msg.accesstokensecret, function(){
            ws.send('okcred');
            ws.send('sync'); 
            setInterval(function() {
	            ws.send('sync');
	            console.log("msg send");
	        }, 10000 );
        });
  });
});



server.on('request', app);
server.listen(port, function () { console.log('Listening on ' + server.address().port) });
