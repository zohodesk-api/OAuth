<?php

	session_start();

	if(!isset($_SESSION['OAUTH_USER_ID'])){
		header("location:login.php");
	}

	require 'database_con.php';
        
        $oauth_data = getUserClientDetails($_SESSION['OAUTH_USER_ID']);

        $stored_client_id = $oauth_data['CLIENT_ID'];
        $stored_client_secret = $oauth_data['CLIENT_SECRET'];
        $stored_redirect_uri = $oauth_data['OAUTH_REDIRECT_URI'];
        $stored_refresh_token = $oauth_data['OAUTH_REFRESH_TOKEN'];
        $stored_oauth_scope = $oauth_data['OAUTH_SCOPE'];
        $stored_auth_uri = $oauth_data['AUTH_URL'];
        $stored_access_token_uri = $oauth_data['ACCESS_TOKEN_URL'];

	$current_page_url = explode('?',((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"),2)[0];

	$redirect_uri= $current_page_url; // https://localhost/php/oauth-config.php //URL of this file

	$refresh_token = null;

	if($_SERVER['REQUEST_METHOD']=='GET' && isset($_REQUEST['code'])){ //executed when zoho server posts information to this page
		assignNewAccessToken('authorization_code',$_REQUEST['code']);
	}
	else if($_SERVER['REQUEST_METHOD']=='GET' && isset($_REQUEST['view-mode']) && $_REQUEST['view-mode']=='home'){
		normalizeSession();
		showControlPanel();
	}
	else if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['oauth_config_post']) && $_REQUEST['oauth_config_post']=='CONFIG_CLIENT_DATA_POST'){
		storeClientDataInFile($_POST['oauth_auth_uri'], $_POST['oauth_access_token_uri'], $_POST['oauth_client_id'], $_POST['oauth_client_secret'], $_POST['oauth_redirect_uri']);
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
            
            global $stored_client_id;
            global $stored_client_secret;
            global $stored_redirect_uri;
            global $stored_refresh_token;
            global $stored_oauth_scope;
            global $stored_auth_uri;
            global $stored_access_token_uri;


	    $auth_query_params=array(
			'client_id'=>$stored_client_id,
			'client_secret'=>$stored_client_secret,
			'redirect_uri'=>$stored_redirect_uri,
			'scope'=>$stored_oauth_scope
		);

		$headers=array(
            "Content-Type: application/x-www-form-urlencoded"
    	);

		$auth_query_params['grant_type']=$accessType;

		if($accessType=='authorization_code'){
			$auth_query_params['code']= $key;
		}else{
			$auth_query_params['refresh_token']= $key;
		}

		$auth_url=$stored_access_token_uri;
		//$auth_url=$stored_access_token_uri."?".urldecode(http_build_query($auth_query_params));

		$ch = curl_init($auth_url);
	    curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
	    curl_setopt($ch,CURLOPT_POST,TRUE);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_query_params));
	    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);

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
			else{
				$_SESSION['OAUTH_REFRESH_TOKEN'] = $stored_refresh_token;
			}
			$_SESSION['OAUTH_AUTHTOKEN']=$auth_response->access_token;

			header("location:".$stored_redirect_uri."?view-mode=home");

		}
		else{
			echo " Error while getting OAuth Token ::::: ";print_r($auth_response);print_r($info);
		}

		showControlPanel();

	}

	function storeOAuthScopeDataInFile($scope){
            global $stored_client_id;
            updateClientScope($scope, $stored_client_id);
	}

	function storeClientDataInFile($auth_uri, $access_token_uri, $client_id, $client_secret, $redirect_uri){
            insertClientData($auth_uri, $access_token_uri, $client_id, $client_secret, $redirect_uri, $_SESSION['OAUTH_USER_ID']);
	}

	function storeRefreshTokenInFile($refresh_token){
            global $stored_client_id;
            updateRefreshToken($refresh_token, $stored_client_id);
	}

	function getNewAccessCode(){
            global $stored_client_id;
            global $stored_redirect_uri;
            global $stored_oauth_scope;
            global $stored_auth_uri;

            header("location:".$stored_auth_uri."?response_type=code&client_id=$stored_client_id&scope=".strtolower($stored_oauth_scope)."&access_type=offline&redirect_uri=$stored_redirect_uri");

	}

	function normalizeSession(){
		if(!isset($_SESSION['OAUTH_EXPIRES_IN']) || (time() > $_SESSION['OAUTH_EXPIRES_IN'])){
			if(!isset($_SESSION['OAUTH_REFRESH_TOKEN'])){
				if(isset($_SESSION['OAUTH_USER_ID'])){
                                        $oauth_data = getUserClientDetails($_SESSION['OAUTH_USER_ID']);
					$_SESSION['OAUTH_REFRESH_TOKEN'] = $oauth_data['OAUTH_REFRESH_TOKEN'];
				}
			}
			if(isset($_SESSION['OAUTH_REFRESH_TOKEN'])){
				assignNewAccessToken('refresh_token',$_SESSION['OAUTH_REFRESH_TOKEN']);
			}
		}
	}

	/*function setClientSession(){
		$client_data = getClientDetails('client_id_3');
		$_SESSION['OAUTH_CLIENT_ID'] = $client_data -> CLIENT_ID;
		$_SESSION['OAUTH_CLIENT_SECRET'] = $client_data -> CLIENT_SECRET;
		$_SESSION['OAUTH_REDIRECT_URI'] = $client_data -> OAUTH_REDIRECT_URI;
	    $_SESSION['OAUTH_REFRESH_TOKEN'] = $client_data -> OAUTH_REFRESH_TOKEN;
		$_SESSION['OAUTH_SCOPE'] = $client_data -> OAUTH_SCOPE;
	} */

	?>
	<?php 
        
        function showControlPanel(){
	
        global $current_page_url;
        global $stored_client_id;
        global $stored_client_secret;
        global $stored_redirect_uri;
        global $stored_refresh_token;
        global $stored_oauth_scope;
        global $stored_auth_uri;
        global $stored_access_token_uri;

	$access_token = isset($_SESSION['OAUTH_AUTHTOKEN'])?$_SESSION['OAUTH_AUTHTOKEN']:null;
	$refresh_token = isset($_SESSION['OAUTH_REFRESH_TOKEN'])?$_SESSION['OAUTH_REFRESH_TOKEN']:null;
	$expires_in = isset($_SESSION['OAUTH_EXPIRES_IN'])?$_SESSION['OAUTH_EXPIRES_IN']:null;
	$created_on = isset($_SESSION['OAUTH_CREATED'])?$_SESSION['OAUTH_CREATED']:null;

	$redirect_uri = (!isset($stored_redirect_uri))? $current_page_url : $stored_redirect_uri;

	$auth_uri = (!isset($stored_auth_uri))? "https://accounts.zoho.com/oauth/v2/auth" : $stored_auth_uri;

	$access_token_uri = (!isset($stored_access_token_uri))? "https://accounts.zoho.com/oauth/v2/token" : $stored_access_token_uri;
		
	?>
	<h1 class="main_title"> Hi <?=explode('@',$_SESSION["OAUTH_USER_EMAIL"])[0]?> </h1>
	<form method="POST">
		<h2> STEP 1 :  Configured Values </h2>
		<?php if(!isset($stored_client_id) || !isset($stored_client_secret)){ ?>
			<span class="error"> <?php if(!isset($stored_client_id)){ ?> <b> Client ID </b> <?php } if(!isset($stored_client_secret)){ ?> <b> Client Secret </b> is't configured  <?php } ?> </span>
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
			<?php if(isset($stored_client_id) && !isset($stored_client_secret)){ ?>
			<div class="error-help">
				<h5 class="title">How to get Client Secret ? </h5>
				<ol>
					<li> Go to <b><a href="https://accounts.zoho.com/developerconsole" target="_blank"> this </a></b> link </li>
					<li> Find the row where <b>Client ID</b> = <code><?=$stored_client_id?></code>, Click <b> 3 Vertical Dots </b> -> <b>Edit</b></li>
					<li> There you can find Client Secret </li>
				</ol>
			</div>
			<? } ?>
		<?php } ?>
		<div class="inp-item">
			<label for="oauth_auth_uri"> Auth URL </label>
			<input id="oauth_auth_uri" name="oauth_auth_uri" type="text" placeholder=" Auth URI " value="<?=$auth_uri?>" required/>
		</div>
		<div class="inp-item">
			<label for="oauth_access_token_uri"> Access Token URL </label>
			<input id="oauth_access_token_uri" name="oauth_access_token_uri" type="text" placeholder=" Access Token URI " value="<?=$access_token_uri?>" required/>
		</div>
		<div class="inp-item">
			<label for="oauth_client_id"> Client ID </label>
			<input id="oauth_client_id" name="oauth_client_id" type="text" placeholder=" Clien ID" value="<?=$stored_client_id?>" required/>
			<input id="oauth_client_id" type="hidden" name="oauth_config_post" value="CONFIG_CLIENT_DATA_POST"/>
		</div>
		<div class="inp-item">
			<label for="oauth_client_secret"> Client Secret </label>
			<input id="oauth_client_secret" name="oauth_client_secret"type="text" placeholder=" Clien Secret" value="<?=$stored_client_secret?>" required/>
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
		<h2> STEP 2 : Current OAuth Token Values </h2>
		<div class="inp-item">
			<label for="oauth_access_token"> ACCESS TOKEN </label>
			<input id="oauth_access_token" class="readonly-value-holder" readonly type="text" placeholder=" ACCESS TOKEN" value="<?=$access_token?>" />
		</div>
		<div class="inp-item">
			<label for="oauth_refresh_token"> REFRESH TOKEN </label>
			<input id="oauth_refresh_token" class="readonly-value-holder" readonly type="text" placeholder=" REFRESH TOKEN" value="<?=$stored_refresh_token?>" />
		</div>
		<?php if($created_on!=null){ ?>
		<div class="inp-item">
			<label for="oauth_created_on"> ACCESS TOKEN CREATED ON </label>
			<input id="oauth_created_on" readonly type="text" placeholder=" ACCESS TOKEN CREATED ON" value="<?=date('F d, Y h:i:s', $created_on);?>" />
		</div>
		<div class="inp-item">
			<label for="oauth_expires_in"> ACCESS TOKEN EXPIRES ON <b>( in <?=round(($expires_in - time())/ 60)?> mins </b> )</label>
			<input id="oauth_expires_in" readonly type="text" placeholder=" ACCESS TOKEN EXPIRES ON" value="<?=date('F d, Y h:i:s', $expires_in);?>" />
		</div>
		
		<?php } ?>
			
			<?php if(isset($stored_client_id) && isset($stored_client_secret)){ ?>
			<div class="inp-item">
			<label for="oauth_scopes"> SCOPE <b>( Comma Seperated OAUTH SCOPES </b><a href="https://desk.zoho.com/support/APIDocument.do#Authentication#OauthTokens" target="_blank"> Available Scopes</a> )</label>
			<input id="oauth_scopes" required name="oauth_scope" type="text" placeholder="DESK.TICKETS.ALL,DESK.CONTACTS.READ,..." value="<?=$stored_oauth_scope?>" />
			<input type="hidden" name="oauth_config_post" value="CONFIG_OAUTH_SCOPE_POST">
			</div>
			<div class="inp-item">
			<input type="submit" id="oauth_generate_tokens" value="Click to Generate New Access Token & Refresh Token"/> 
			</div>
			<?php } ?>
		</div>
	</form>

	<a href="login.php?action=logout"> Logout </a>

	<script type="text/javascript">
		/*
		var elem =document.getElementById("oauth_generate_tokens");
		var scopesElement =document.getElementById("oauth_scopes");
		elem.addEventListener('click',function(e){
			e.preventDefault();
			scopes = scopesElement.value;
			if(scopes.trim()!=""){
				window.location.assign("https://accounts.zoho.com/oauth/v2/auth?response_type=code&client_id=<?=$stored_client_id;?>&access_type=offline&redirect_uri=<?=$redirect_uri?>&scope="+scopes);
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
			input.readonly-value-holder{
				background-color: lightcyan;
				color: darkcyan !important;
				padding: 7px 15px;
				font-size: 18px;
				border: 1px dotted;
				cursor: pointer;
			}
	
	</style>

        <?php }


	/* ******

	STEPS:
	=====

	1. Place oauth-config.php file in your server.

	2. Go to URL of this file as with view-mode=home parameter
		-> For Example : https://localhost/OAuth/php/oauth-config.php?view-mode=home
		

	****** */



?>
