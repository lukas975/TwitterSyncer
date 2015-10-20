var server = require('http').createServer(), 
	url = require('url'), 
	WebSocketServer = require('ws').Server, 
	wss = new WebSocketServer({ server: server }), 
	express = require('express'), 
	app = express(), 
	port = 4080;

//mysql
/*
var mysql      = require('mysql');
var connection = mysql.createConnection({
	host     : 'localhost',
	user     : 'tsuser',
	password : 'psw123',
	database : 'db_twittersyncer'
});
*/
var mysql      = require('mysql');
var pool      =    mysql.createPool({
    connectionLimit : 100, //important
    host     : 'localhost',
    user     : 'tsuser',
    password : 'psw123',
    database : 'db_twittersyncer',
    debug    :  false
});


//twitter init
var twitterAPI = require('node-twitter-api');
var twitter = new twitterAPI({
    consumerKey: 'QGypBjqk9aYf9VilCgqBPa28A',
    consumerSecret: '93qc7hXcbeFxM5xTXBqRGhCGFt1epipUDxrBfUUHsPY1jUszNS',
    callback: 'http:////localhost:4080/'
});

//crypto js
var CryptoJS = require("crypto-js");

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

//sync interval
//var intSync = 5000;
var intSync = 50000;
var timers = {};

//clients
var count = 0;
var id = 0;
var clients = {};

function verifyCredentials(userName, accessToken, accessTokenSecret, ws, id){

    twitter.verifyCredentials(accessToken, accessTokenSecret, function(error, data, response) {
        if (error) {            
        	ws.send("errcred");
        } else {
        	
        	insertUserIfNotExists(userName);

            ws.send("okcred");
            ws.send('sync'); 
            timers[id] = setInterval(function() {
            	ws.send('sync');
	        }, intSync );
        }
    });
}

// INSERT INTO table1 (name1) SELECT 'luka' FROM DUAL WHERE NOT EXISTS (SELECT name1 FROM table1 WHERE name1='luka')
/*
function insertUserIfNotExists(user){
    connection.connect();
	connection.query('INSERT INTO users (username) SELECT "' + user + '" FROM DUAL WHERE NOT EXISTS (SELECT username FROM users WHERE username="' + user + '")', function(err, rows, fields) {
		if (!err){
			console.log('The solution is: ', rows);
		}
		else{
			console.log('Error while performing Query.: ' + err);
		}
	});
	connection.end();
}
*/
function insertUserIfNotExists(user){
	
    pool.getConnection(function(err,connection){
        if (err) {
          connection.release();
          //res.json({"code" : 100, "status" : "Error in connection database"});
          return;
        }   

        connection.query('INSERT INTO users (username) SELECT "' + user + '" FROM DUAL WHERE NOT EXISTS (SELECT username FROM users WHERE username="' + user + '")',function(err,rows){
            connection.release();
            if(!err) {
                //res.json(rows);
            }           
        });

        connection.on('error', function(err) {      
              //res.json({"code" : 100, "status" : "Error in connection database"});
              return;     
        });
  });
}


app.use(function (req, res) {
	res.send({ msg: "hello" });
});


wss.on('connection', function connection(ws) {

	var location = url.parse(ws.upgradeReq.url, true);
	
	id = count++;
	clients[id] = ws;
	
	clients[id].on('message', function incoming(message) {
		var msg = CryptoJS.AES.decrypt(message, "facility", { format: JsonFormatter });
	    msg = msg.toString(CryptoJS.enc.Utf8);
	    msg = JSON.parse(msg);
	    
	    verifyCredentials(msg.username, msg.accesstoken, msg.accesstokensecret, clients[id], id);

	});
	clients[id].on('close', function(reasonCode, description) {
		clearInterval(timers[id]);
	    delete clients[id];
	    delete timers[id];
	});
});



server.on('request', app);
server.listen(port, function () { console.log('Listening on ' + server.address().port) });
