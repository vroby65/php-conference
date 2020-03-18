<html>
<head>
<style>
</style>

<link rel='shortcut icon' href='php-conference.png' type='image/gif'>
<link rel="stylesheet" href="style.css" />	
<meta charset='utf-8'>
<meta name='viewport' content='width=device-width'>		

<script>
function $(el){return document.getElementById(el);}	
var server='server.php';
function parseGetVars(){
  var args = new Array();
  var query = window.location.search.substring(1);
  if (query){
    var strList = query.split('&');
    for(str in strList){
      var parts = strList[str].split('=');
      args[unescape(parts[0])] = unescape(parts[1]);
    }
  }
  return args;
}
var get=parseGetVars();

function ahah(url,target){
  var req = new XMLHttpRequest();
  if (req) {
	req.onreadystatechange = function(){ahahDone(req,url, target);};
	req.open('post', url, true);
	req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	req.send('');
  }
}

function ahahDone(req,url,target){
  if (req.readyState == 4){ 
    if (req.status == 200){ 
      	$(target).innerHTML = req.responseText;
    }else{
      	$(target).innerHTML="ahah error:\n"+req.statusText;
    }
  }
}
</script>
</head>
<body>
<div style='float:left;width:30%;border:1px solid black;'>
<br>
room &nbsp;<input type='text' name='room' id='room' value="conference" style="width:50%;" onchange="localStorage.setItem('room',room.value);">
 <button  onclick="location=server+'?delete=yes&room='+room.value" target='display' style="background:#FF4444;">cancella</button> <br> 
 <textarea name='listusers' id='listusers' style="width:100%;height:20%;"></textarea>
 <div name='display' id='display' style="width:100%;height:50%;border:1px solid black;overflow-y:scroll;"></div>
 <div id='last' value="" style="display:block;width:50%;border:1px solid black;"></div>
 <br>
user &nbsp;<input type='text' name='user' id='user' value="" onchange="localStorage.setItem('user',user.value);"><br>
 <input type='text' name='message' id='message' style="width:70%;" onkeydown="if(event.keyCode==13){ahah(server+'?scope=html&room='+room.value+'&user='+user.value+'&message='+message.value,'display');message.value='';display.scrollTop = display.scrollHeight;}">
 <button onclick="ahah(server+'?scope=text&room='+room.value+'&user='+user.value+'&message='+message.value,'display');message.value='';display.scrollTop = display.scrollHeight;" >invia</button><br>
<button onclick="document.getElementById('message').value='setme';ahah(server+'?scope=text&room='+room.value+'&user='+user.value+'&message='+message.value,'display');message.value='';display.scrollTop = display.scrollHeight;">dammi la parola</button>
</div>

<div style='float:right;width:68%;border:1px solid black;'>
	<iframe id='browser' style='width:90%;height:80%;'></iframe>
</div>




 </body>
<script>
	var m;
	setInterval(function(){
		ahah(server+'?scope=html&room='+room.value,'display');
		display.scrollTop = display.scrollHeight;
		ahah(server+'?scope=last&room='+room.value,'last');
		var last=$('last').innerHTML.split(":");
		if (last[1]!=undefined){
			if (last[1].trim()=="setme"){
				if ( m != last[0].trim()){
					m=last[0].trim();
					var u=(user.value).split(":");
					$('browser').src="test.php?r="+room.value+"&m="+m+"&c="+u[0];
					alert('la parola a '+ m); 
				}	
			}
		}
	},
	1000);
	setInterval(function(){ahah(server+'?scope=list&room='+room.value,'listusers');},5000);
	room.value=get['room'];
	user.value=get['user'];
	if (room.value=='undefined')room.value=localStorage.getItem("room");
	if (user.value=='undefined')user.value=localStorage.getItem("user");	
	if (room.value=='')room.value=prompt("please enter room name");
	localStorage.setItem('room',room.value);
	if (user.value=='')user.value=prompt("please enter username");
	localStorage.setItem('user',user.value);
	ahah(server+'?scope=html&room='+room.value+'&user='+user.value,'display');
	ahah(server+'?scope=list&room='+room.value+'&user='+user.value,'listusers');
	display.scrollTop = display.scrollHeight;
</script>
</html>
	
