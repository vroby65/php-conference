<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

@mkdir("stream");


function deltree($path) {
	$files = scandir($path . '/*');
	foreach ($files as $file) {
		is_dir($file) ? deltree($file) : unlink($file);
	}
	rmdir($path);
	return;
}

$r=@$_GET['r'];
if (@$_GET['c']){
	$m=$_GET['m'];
	$c=$_GET['c'];
	if ($m==$c){
		setcookie('user',$m);
		@deltree("stream/$r");
		$user=$m;
	}
	else{ 
		setcookie('user',$c);
		@mkdir("stream/$r",0777);
		@mkdir("stream/$r/$c",0777);
		$user=$c;
	}			
}
$user=@$_COOKIE['user'];

if (!isset($_GET['eventSource'])){ // show HTML CSS and Javascript
    ?><!DOCTYPE html>
    <html>
    <head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
        
	<!-- EventSource polyfill for IE and Edge -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/event-source-polyfill/0.0.9/eventsource.js"></script>
	<style>
        body {
            margin: 0;
        }
        .videos {
            height: 100%;
            width: 100%;
        }
        div{
		position:absolute;
		resize: both;
		overflow: auto;
		}
        </style>
    </head>
    <body>
    <div class="videos">
        <video id="localVideo" autoplay="true" muted="muted" style="width:99%;"></video>
        <video id="remoteVideo" autoplay="true" style="display:none;width:99%;"></video>
    </div>
    <script type="text/javascript">

    var answer = 0;
    var pc=null;
	var localStream=null;
	var ws=null;

    // 'eventsource' parameter is only used to distinguish 
    // the HTML form the real eventsource calls.
    var URL = "test.php?r=<?php echo $r; ?>&eventSource=yes";
    var localVideo = document.getElementById('localVideo');
    var remoteVideo = document.getElementById('remoteVideo');
    var configuration  = {
        'iceServers': [
			//{'urls': 'stun:stun.stunprotocol.org:3478'},
			//{'urls': 'stun:stun.l.google.com:19302'},
			//{'urls': 'stun:stun1.l.google.com:19302'},
			//{'urls': 'stun:stun2.l.google.com:19302'}
        ]
    };

	// Start
    navigator.mediaDevices.getUserMedia({
            audio: true,
            video: true
        }).then(function (stream) {
            localVideo.srcObject = stream;
            localStream = stream;

            try {
                ws = new EventSource(URL);
            } catch(e) {
                console.error("Could not create eventSource ",e);
            }

			ws.send = function send(message) {
				 var xhttp = new XMLHttpRequest();
				 xhttp.onreadystatechange = function() {
					 if (this.readyState!=4) {
					   return;
					 }
					 if (this.status != 200) {
					   console.log("Error sending to "+url+ " with message: " +message);
					 }
				 };
				 xhttp.open("POST", URL, true);
				 xhttp.setRequestHeader("Content-Type","Application/X-Www-Form-Urlencoded");
				 xhttp.send(message);
			}

			ws.onmessage = function(e) {
				if (e.data.includes("_MULTIPLEVENTS_")) {
					multiple = e.data.split("_MULTIPLEVENTS_");
					for (x=0;x<multiple.length;x++) {
						onsinglemessage(multiple[x]);
					}
				} else {
					onsinglemessage(e.data);
				}
			}

            // Go show myself
            localVideo.addEventListener('loadedmetadata', 
                function () {
                    publish('client-call', null)
                }
            );
			
        }).catch(function (e) {
            console.log("Problem while getting audio video stuff ",e);
        });
		
    
    function onsinglemessage(data) {
        var package = JSON.parse(data);
        var data = package.data;
        
        console.log("received single message: " + package.event);
        switch (package.event) {
            case 'client-call':
                icecandidate(localStream);
                pc.createOffer({
                    offerToReceiveAudio: 1,
                    offerToReceiveVideo: 1
                }).then(function (desc) {
                    pc.setLocalDescription(desc).then(
                        function () {
                            publish('client-offer', pc.localDescription);
                        }
                    ).catch(function (e) {
                        console.log("Problem with publishing client offer"+e);
                    });
                }).catch(function (e) {
                    console.log("Problem while doing client-call: "+e);
                });
                break;
            case 'client-answer':
                if (pc==null) {
                    console.error('Before processing the client-answer, I need a client-offer');
                    break;
                }
                pc.setRemoteDescription(new RTCSessionDescription(data),function(){}, 
                    function(e) { console.log("Problem while doing client-answer: ",e);
                });
                break;
            case 'client-offer':
                icecandidate(localStream);
                pc.setRemoteDescription(new RTCSessionDescription(data), function(){
                    if (!answer) {
                        pc.createAnswer(function (desc) {
                                pc.setLocalDescription(desc, function () {
                                    publish('client-answer', pc.localDescription);
                                }, function(e){
                                    console.log("Problem getting client answer: ",e);
                                });
                            }
                        ,function(e){
                            console.log("Problem while doing client-offer: ",e);
                        });
                        answer = 1;
                    }
                }, function(e){
                    console.log("Problem while doing client-offer2: ",e);
                });
                break;
            case 'client-candidate':
               if (pc==null) {
                    console.error('Before processing the client-answer, I need a client-offer');
                    break;
                }
                pc.addIceCandidate(new RTCIceCandidate(data), function(){}, 
                    function(e) { console.log("Problem adding ice candidate: "+e);});
                break;
        }
    };

    function icecandidate(localStream) {
        pc = new RTCPeerConnection(configuration);
        pc.onicecandidate = function (event) {
            if (event.candidate) {
                publish('client-candidate', event.candidate);
            }
        };
        try {
            pc.addStream(localStream);
        }catch(e){
            var tracks = localStream.getTracks();
            for(var i=0;i<tracks.length;i++){
                pc.addTrack(tracks[i], localStream);
            }
        }
        pc.ontrack = function (e) {
		<?php
			if($user!=$m){
				echo"console.log('client');";
				echo"document.getElementById('remoteVideo').style.display='block';\n";
				echo"document.getElementById('localVideo').style.display='none';\n";
			}
			else{
				echo"console.log('master');";
				echo"document.getElementById('remoteVideo').style.display='none';\n";
				echo"document.getElementById('localVideo').style.display='block';\n";
			}
		?>	
            remoteVideo.srcObject = e.streams[0];
        };
    }

    function publish(event, data) {
        console.log("sending ws.send: " + event);
        ws.send(JSON.stringify({
            event:event,
            data:data
        }));
    }


    </script>
    </body>
    </html>
<?php
} 
else{ 
	if (count($_POST)!=0) { // simulated onmessage by ajax post
		if ($m==$c){	
			$posted = file_get_contents('php://input');
			
			$mainlock = fopen('test.php','r');
			flock($mainlock,LOCK_EX);
		   
			// Add the new message to file
			$d=scandir("stream/$r/");
			foreach($d as $d1){
				if (is_dir("stream/$r/$d1") && $d1[0]!='.')	$filename = "stream/$r/$d1/__file__";
				$file = fopen($filename,'ab');
				if (filesize($filename)!=0) {
					fwrite($file,'_MULTIPLEVENTS_');
				}
				fwrite($file,$posted);
				fclose($file);
			}
			// Unlock main lock
			flock($mainlock,LOCK_UN);
			fclose($mainlock);
		}		
	} 
	else { 
		header('Content-Type: text/event-stream');
		//header('Cache-Control: no-cache'); // recommended
		
		$mainlock = fopen("test.php?r=$r","r");
		flock($mainlock,LOCK_EX);

		$filename="stream/$r/$user/__file__";
		while(!file_exists($filename));
		
		if (filesize($filename)==0) {
			unlink($filename);
		}
				
		$file = fopen($filename, 'c+b');
		flock($file, LOCK_SH);
		echo 'data: ', fread($file, filesize($filename)),PHP_EOL;
		fclose($file);
		unlink($filename);
		flock($mainlock,LOCK_UN);
		fclose($mainlock);
		echo 'retry: 1000',PHP_EOL,PHP_EOL; // shorten the 3 seconds to 1 sec
	}
}
?>
