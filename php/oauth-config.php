<?php

	/* ******

	STEPS:
		
		1. Place this file in your server.

		2. Go https://accounts.zoho.com/developerconsole
			-> Click Add Client ID
				-> Authorized redirect URIs = URL of this file.

		3. Edit this file in your server.
			-> Replace $redirect_uri, $client_id, $client_secret values with your own obtained values in this file.
			Line 38 : $redirect_uri = URL of this file. Your own redirect url of THIS file wherever you place this file.
			Line 40 : $client_id = Your client_id from https://accounts.zoho.com/developerconsole -> Client ID
			Line 42 : $client_secret = //Your client_secret from https://accounts.zoho.com/developerconsole -> 3 dots -> edit -> Client Secret

		4. Once Goto https://accounts.zoho.com/oauth/v2/auth?response_type=code&client_id={client_id}&scope=Desk.tickets.ALL&access_type=offline&redirect_uri={redirect_uri}

	****** */

	session_start();

	if($_SERVER['REQUEST_METHOD']=='GET' && isset($_REQUEST['accounts-server']) && $_REQUEST['accounts-server']=='https://accounts.zoho.com'){ //executed when zoho server posts information to this page
		assignNewAccessToken('authorization_code',$_REQUEST['code']);
	}
	else{ //executed when included in other files
		if(time() > $_SESSION['OAUTH_EXPIRES_IN'] && isset($_SESSION['OAUTH_REFRESH_TOKEN'])){
		    assignNewAccessToken('refresh_token',$_SESSION['OAUTH_REFRESH_TOKEN']);
		}
	}

	function assignNewAccessToken($accessType,$key){

		/*********      START -  Fields you need to fill your own values             ***********/

	    $redirect_uri='https://localhost/php/oauth-config.php'; //URL of this file.

	    $client_id='1000.3287tegdib2hbchbhjcbsdhbckbdc'; //your client_id from https://accounts.zoho.com/developerconsole

	    $client_secret='a2gb3wihbrfugf7gefdkwjdbcsadc58c462'; //your client_secret from https://accounts.zoho.com/developerconsole

	    /*********      END -  Fields you need to fill your own values             ***********/

	    $auth_query_params=array(
			'client_id'=>$client_id,
			'client_secret'=>$client_secret,
			'redirect_uri'=>$redirect_uri,
			'scope'=>'Desk.tickets.READ,Desk.basic.READ'
		);

		$auth_query_params['grant_type']=$accessType;

		if($accessType=='authorization_code'){
			$auth_query_params['code']= $key;
		}else{
			$auth_query_params['refresh_token']= $key;
		}

		$auth_url="https://accounts.zoho.com/oauth/v2/token?".urldecode(http_build_query($auth_query_params));

		$ch = curl_init($auth_url);
	    curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
	    curl_setopt($ch,CURLOPT_POST,TRUE);

	    $auth_response= curl_exec($ch);

	    $info= curl_getinfo($ch);
    
    	if($info['http_code']==200){ 
    		//for example we are storing these values in session.You can store these values either in DB or a file;
			$_SESSION['OAUTH_CREATED'] = time();
			$_SESSION['OAUTH_EXPIRES_IN'] = time()+(($auth_response->expires_in_sec)-1);
			if($accessType=='authorization_code'){
				$_SESSION['OAUTH_REFRESH_TOKEN']=$auth_response->refresh_token;
			}
			$_SESSION['OAUTH_AUTHTOKEN']=$auth_response->access_token;
		}

	}



?>
