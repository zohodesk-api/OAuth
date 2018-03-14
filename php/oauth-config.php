<?php

	/*********      START -  Fields you need to fill your own values             ***********/

	$client_id= null;  //'1000.3287tegdib2hbchbhjcbsdhbckbdc' //your client_id from https://accounts.zoho.com/developerconsole

	$client_secret= null;   //'a2gb3wihbrfugf7gefdkwjdbcsadc58c462'; //your client_secret from https://accounts.zoho.com/developerconsole

	/*********      END -  Fields you need to fill your own values             ***********/

	$current_page_url = explode('?',((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"),2)[0];

	$redirect_uri= $current_page_url; // https://localhost/php/oauth-config.php //URL of this file

	$oauth_data_file = 'oauth-token-data.json';

	$refresh_token = null;

	session_start();

	if($_SERVER['REQUEST_METHOD']=='GET' && isset($_REQUEST['accounts-server']) && $_REQUEST['accounts-server']=='https://accounts.zoho.com'){ //executed when zoho server posts information to this page
		assignNewAccessToken('authorization_code',$_REQUEST['code']);
	}
	else if($_SERVER['REQUEST_METHOD']=='GET' && isset($_REQUEST['view-mode']) && $_REQUEST['view-mode']=='home'){
		normalizeSession();
		showControlPanel();
	}
	else if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['oauth_config_post']) && $_REQUEST['oauth_config_post']=='CONFIG_CLIENT_DATA_POST'){
		storeClientDataInFile($_POST['oauth_client_id'], $_POST['oauth_client_secret'], $_POST['oauth_redirect_uri']);
		showControlPanel();
	}
	else if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['oauth_config_post']) && $_REQUEST['oauth_config_post']=='CONFIG_OAUTH_SCOPE_POST'){
		storeOAuthScopeDataInFile($_POST['oauth_scope']);
		getNewAccessCode();
		showControlPanel();
	}
	else{ //executed when included in other files
		normalizeSession();
	}

	function assignNewAccessToken($accessType,$key){

		global $oauth_data_file;

	    if(file_exists($oauth_data_file)){
			$oauth_data_json = json_decode(file_get_contents($oauth_data_file));
			$client_id = $oauth_data_json -> OAUTH_CLIENT_ID;
			$client_secret = $oauth_data_json -> OAUTH_CLIENT_SECRET;
			$redirect_uri = $oauth_data_json -> OAUTH_REDIRECT_URI;
			$refresh_token = $oauth_data_json -> OAUTH_REFRESH_TOKEN;
			$oauth_scope = $oauth_data_json -> OAUTH_SCOPE;// $oauth_data_json -> OAUTH_SCOPE;

	    $auth_query_params=array(
			'client_id'=>$client_id,
			'client_secret'=>$client_secret,
			'redirect_uri'=>$redirect_uri,
			'scope'=>$oauth_scope
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

	    $auth_response= json_decode(curl_exec($ch));

	    $info= curl_getinfo($ch);
    
    	if($info['http_code']==200){ 
    		//for example we are storing these values in session.You can store these values either in DB or a file;
			$_SESSION['OAUTH_CREATED'] = time();
			$_SESSION['OAUTH_EXPIRES_IN'] = time()+(($auth_response->expires_in_sec)-1);
			if($accessType=='authorization_code'){
				$_SESSION['OAUTH_REFRESH_TOKEN']=$auth_response->refresh_token;
				storeRefreshTokenInFile($auth_response->refresh_token);
			}
			$_SESSION['OAUTH_AUTHTOKEN']=$auth_response->access_token;

			header("location:".$redirect_uri."?view-mode=home");

		}
		else{
			echo " Error while getting OAuth Token ::::: ";print_r($auth_response);
		}

		showControlPanel();

		}

	}

	function storeOAuthScopeDataInFile($scope){
		global $oauth_data_file;
		if(file_exists($oauth_data_file)){
			$oauth_data_json = json_decode(file_get_contents($oauth_data_file));
			$oauth_data_json -> OAUTH_SCOPE = $scope;
			file_put_contents($oauth_data_file, json_encode($oauth_data_json));
		}
	}

	function storeClientDataInFile($client_id, $client_secret, $redirect_uri){
		global $oauth_data_file;
		$oauth_data_json = json_encode(
			array(
				'OAUTH_CLIENT_ID' => $client_id,
				'OAUTH_CLIENT_SECRET' => $client_secret,
				'OAUTH_REDIRECT_URI' => $redirect_uri,
				'OAUTH_REFRESH_TOKEN' => null,
				'OAUTH_SCOPE' => null
			));
		file_put_contents($oauth_data_file, $oauth_data_json);
	}

	function storeRefreshTokenInFile($refresh_token){
		global $oauth_data_file;
		if(file_exists($oauth_data_file)){
			$oauth_data_json = json_decode(file_get_contents($oauth_data_file));
			$oauth_data_json -> OAUTH_REFRESH_TOKEN = $refresh_token;
			file_put_contents($oauth_data_file, json_encode($oauth_data_json));
		}
	}

	function getNewAccessCode(){
		global $oauth_data_file;
		if(file_exists($oauth_data_file)){
			$oauth_data_json = json_decode(file_get_contents($oauth_data_file));
			$client_id = $oauth_data_json -> OAUTH_CLIENT_ID;
			$client_secret = $oauth_data_json -> OAUTH_CLIENT_SECRET;
			$redirect_uri = $oauth_data_json -> OAUTH_REDIRECT_URI;
			$refresh_token = $oauth_data_json -> OAUTH_REFRESH_TOKEN;
			$oauth_scope = $oauth_data_json -> OAUTH_SCOPE;// $oauth_data_json -> OAUTH_SCOPE;

			header("location:https://accounts.zoho.com/oauth/v2/auth?response_type=code&client_id=$client_id&scope=".strtolower($oauth_scope)."&access_type=offline&redirect_uri=$redirect_uri");

		}
	}

	function normalizeSession(){
		global $oauth_data_file;
		if(!isset($_SESSION['OAUTH_EXPIRES_IN']) || (time() > $_SESSION['OAUTH_EXPIRES_IN'])){
			if(!isset($_SESSION['OAUTH_REFRESH_TOKEN'])){
				if(file_exists($oauth_data_file)){
					$oauth_data_json = json_decode(file_get_contents($oauth_data_file));
					$_SESSION['OAUTH_REFRESH_TOKEN'] = $oauth_data_json -> OAUTH_REFRESH_TOKEN;
				}
			}
			if(isset($_SESSION['OAUTH_REFRESH_TOKEN'])){
				assignNewAccessToken('refresh_token',$_SESSION['OAUTH_REFRESH_TOKEN']);
			}
		}
	}

	?>
	<?php function showControlPanel(){
		global $current_page_url;
		global $oauth_data_file;

	$access_token = isset($_SESSION['OAUTH_AUTHTOKEN'])?$_SESSION['OAUTH_AUTHTOKEN']:null;
	$refresh_token = isset($_SESSION['OAUTH_REFRESH_TOKEN'])?$_SESSION['OAUTH_REFRESH_TOKEN']:null;
	$expires_in = isset($_SESSION['OAUTH_EXPIRES_IN'])?$_SESSION['OAUTH_EXPIRES_IN']:null;
	$created_on = isset($_SESSION['OAUTH_CREATED'])?$_SESSION['OAUTH_CREATED']:null;
	$oauth_scope_configured = null;
	$client_id = null;
	$client_secret = null;
	$redirect_uri = null;

	if(file_exists($oauth_data_file)){
		$oauth_data_json = json_decode(file_get_contents($oauth_data_file));
		$client_id = $oauth_data_json -> OAUTH_CLIENT_ID;
		$client_secret = $oauth_data_json -> OAUTH_CLIENT_SECRET;
		$redirect_uri = $oauth_data_json -> OAUTH_REDIRECT_URI;
		$refresh_token = $oauth_data_json -> OAUTH_REFRESH_TOKEN;
		$oauth_scope_configured = $oauth_data_json -> OAUTH_SCOPE;
	}
		
	?>
	<form method="POST">
		<h2> Configured Values </h2>
		<?php if(!isset($client_id) || !isset($client_secret)){ ?>
			<span class="error"> <?php if(!isset($client_id)){ ?> <b> Client ID </b> <?php } if(!isset($client_secret)){ ?> <b> Client Secret </b> is't configured  <?php } ?> </span>
			<a href="https://accounts.zoho.com/developerconsole" target="_blank"> Get Client ID and Client Secret </a>
			<div class="error-help">
				<h5 class="title">How to get new Client ID and Client Secret ? </h5>
				<ol>
					<li> Go to <b><a href="https://accounts.zoho.com/developerconsole" target="_blank"> this </a></b> link </li>
					<li> Click <button class="btn-add" disabled>Add Client ID</button> </li>
					<li> Fill <b>Client Name</b>, <b>Client Domain</b> </li>
					<li> Put <b>Authorized redirect URIs</b> = <code><?=$redirect_uri?></code></li>
				</ol>
			</div>
			<?php if(isset($client_id) && !isset($client_secret)){ ?>
			<div class="error-help">
				<h5 class="title">How to get Client Secret ? </h5>
				<ol>
					<li> Go to <b><a href="https://accounts.zoho.com/developerconsole" target="_blank"> this </a></b> link </li>
					<li> Find the row where <b>Client ID</b> = <code><?=$client_id?></code>, Click <b> 3 Vertical Dots </b> -> <b>Edit</b></li>
					<li> There you can find Client Secret </li>
				</ol>
			</div>
			<? } ?>
		<?php } ?>
		<div class="inp-item">
			<label for="oauth_client_id"> Client ID </label>
			<input id="oauth_client_id" name="oauth_client_id" type="text" placeholder=" Clien ID" value="<?=$client_id?>" required/>
			<input id="oauth_client_id" type="hidden" name="oauth_config_post" value="CONFIG_CLIENT_DATA_POST"/>
		</div>
		<div class="inp-item">
			<label for="oauth_client_secret"> Client Secret </label>
			<input id="oauth_client_secret" name="oauth_client_secret"type="text" placeholder=" Clien Secret" value="<?=$client_secret?>" required/>
		</div>
		<div class="inp-item">
			<label for="oauth_redirect_uri"> Redirect URI </label>
			<input id="oauth_redirect_uri" name="oauth_redirect_uri" type="text" placeholder=" Redirect URI" value="<?=$redirect_uri?>" required/>
		</div>
			<div class="inp-item">
			<input type="submit" value="Update">
		</div>
	</form>

	<form method="POST">
		<h2> Current OAuth Token Values </h2>
		<div class="inp-item">
			<label for="oauth_client_id"> ACCESS TOKEN </label>
			<input id="oauth_client_id" readonly type="text" placeholder=" ACCESS TOKEN" value="<?=$access_token?>" />
		</div>
		<div class="inp-item">
			<label for="oauth_client_secret"> REFRESH TOKEN </label>
			<input id="oauth_client_secret" readonly type="text" placeholder=" REFRESH TOKEN" value="<?=$refresh_token?>" />
		</div>
		<?php if($created_on!=null){ ?>
		<div class="inp-item">
			<label for="oauth_redirect_uri"> ACCESS TOKEN CREATED ON </label>
			<input id="oauth_redirect_uri" disabled type="text" placeholder=" ACCESS TOKEN CREATED ON" value="<?=date('m/d/Y H:i:s', $created_on);?>" />
		</div>
		<div class="inp-item">
			<label for="oauth_redirect_uri"> ACCESS TOKEN EXPIRES ON <b>( in <?=round(($expires_in - time())/ 60)?> mins )</label>
			<input id="oauth_redirect_uri" disabled type="text" placeholder=" ACCESS TOKEN EXPIRES ON" value="<?=date('m/d/Y H:i:s', $expires_in);?>" />
		</div>
		
		<?php } ?>
			
			<?php if(isset($client_id) && isset($client_secret)){ ?>
			<div class="inp-item">
			<label for="oauth_scopes"> SCOPE <b>( Comma Seperated OAUTH SCOPES <a href="https://desk.zoho.com/support/APIDocument.do#Authentication#OauthTokens" target="_blank"> Available Scopes</a> )</label>
			<input id="oauth_scopes" required name="oauth_scope" type="text" placeholder="DESK.TICKETS.ALL,DESK.CONTACTS.READ,..." value="<?=$oauth_scope_configured?>" />
			<input type="hidden" name="oauth_config_post" value="CONFIG_OAUTH_SCOPE_POST">
			</div>
			<div class="inp-item">
			<input type="submit" id="oauth_generate_tokens" value="Click to Generate New Access Token & Refresh Token"/> 
			</div>
			<?php } ?>
		</div>
	</form>

	<script type="text/javascript">
		/*
		var elem =document.getElementById("oauth_generate_tokens");
		var scopesElement =document.getElementById("oauth_scopes");
		elem.addEventListener('click',function(e){
			e.preventDefault();
			scopes = scopesElement.value;
			if(scopes.trim()!=""){
				window.location.assign("https://accounts.zoho.com/oauth/v2/auth?response_type=code&client_id=<?=$client_id;?>&access_type=offline&redirect_uri=<?=$redirect_uri?>&scope="+scopes);
			}else{
				alert("Please Provide OAuth Scopes. For Example DESK.TICKETS.ALL,DESK.CONTACTS.READ");
			}
		});
		*/

	</script>

	<?php 
		}

		?>
	

	<style type="text/css">

		
		form{
			 padding: 10px;
			 border: 1px solid silver;
			 box-shadow: 0px 7px 20px rgba(0,0,0,0.2);
			 font-family: sans-serif;
			 font-size:16px; 
			  margin: 5%;
			}
			.inp-item{
			  padding: 5px;
			  margin-bottom: 5px;
			}
			.inp-item label{
			  display: table-cell;
			  padding: 5px 0px;
			  color: darkcyan;
			}
			.inp-item input{
			  width: 100%;
			  padding: 5px;
			  font-size: 16px;
			  border: 1px solid silver;
			  border-width: 0px 0px 1px 0px;
			  color: grey;
			}
			.inp-item input:focus{
			  color: black;
			}
			.inp-item input[type='submit']{
			  background-color: darkcyan;
			  color: white;
			  padding: 7px 25px;
			  display: inline-block;
			  width: inherit;
			}
			.inp-item input[type='submit']:active{
			  background-color: darkorange;
			}
			.error{
				color: red;
				margin-left: 15px;
				margin-right: 15px;
			}
			.error b{
				background-color: darkred;
				margin-right: 5px;
				color:white;
				font-size: 16px;
				padding:5px 10px 3px;
				border-radius: 5px;
				font-weight: normal;
			}
			a{
				background-color: royalblue;
				padding: 5px 10px 3px;
				color: white;
				text-decoration: none;
				border-radius: 5px;
				margin: 5px;
				font-weight: normal;
				font-size: 15px;
			}
			ol li{
				margin: 10px;
				font-size: 14px;
			}
			h5.title{
				margin-top: 15px;
				color: grey;
				font-size: 15px;
				margin-left: 10px;
				margin-bottom: 5px;
			}
			code{
				background-color: whitesmoke;
				padding: 5px 15px;
				border: 1px solid silver;
				margin: 5px;
			}
			.error-help{
				padding: 5px 15px;
				background-color: whitesmoke;
				margin: 20px 10px;
			}
			.btn-add{
			  background-color: #1e85d2;
			  border: 1px solid #1e85d2;
			  padding: 3px 7px;
				color: white;
				border-radius: 5px;
				font-size: 12px;
			}
	
	</style>

	<?


	/* ******

	STEPS:
	=====

	1. Place oauth-config.php file in your server.

	2. Go to URL of this file as with view-mode=home parameter
		-> For Example : https://localhost/OAuth/php/oauth-config.php
		

	****** */



?>
