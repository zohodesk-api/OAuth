<?php
	
	session_start();

	if(isset($_REQUEST['action']) && $_REQUEST['action']=='logout'){
		session_destroy();
	}

	$errorMsg = null;
	$controlPanel = "oauth-config.php?view-mode=home";

	if(isset($_SESSION['OAUTH_USER_ID'])){
		header("location:$controlPanel");
	}

	if($_SERVER['REQUEST_METHOD']=='POST'){
		if(isset($_POST['email']) && isset($_POST['password'])){

			require 'database_con.php';
			
			if(checkLoginCredentials($_POST['email'],$_POST['password'])){
				$_SESSION['OAUTH_USER_ID'] = getUserIdFromEmail($_POST['email']);
				$_SESSION["OAUTH_USER_EMAIL"] = $_POST['email'];
				header("location:$controlPanel");
			}
			else{
				if(getUserIdFromEmail($_POST['email'])){
					$errorMsg = " Error: Incorrect Password ";
				}
				else{
					insertNewUser($_POST['email'], $_POST['password']);
					$_SESSION["OAUTH_USER_ID"] = getUserIdFromEmail($_POST['email']);
					$_SESSION["OAUTH_USER_EMAIL"] = $_POST['email'];
					header("location:$controlPanel");
				}
			}
		}
		else{
			$errorMsg = " Error: Fill All Details ";
		}
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Login</title>
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
			.error-msg-form{
				background-color: rgba(250,0,0,0.6);
				color: white;
				padding: 5px 0px;
				border-radius: 2px;
				display: inline-block;
				margin:0px 10px 10px 0px;
			}
	
	</style>
</head>
<body>
	<form method="POST">
		<h2> Sign In / Sign Up </h2>
		<div class="error-msg-form"><?=$errorMsg?></div>
		<div class="inp-item">
			<label for="oauth_email"> Email </label>
			<input id="oauth_email" name="email" type="text" placeholder="Your Email Address" value="<?=$_POST['email']?>" />
		</div>
		<div class="inp-item">
			<label for="oauth_password"> Password </label>
			<input id="oauth_password" name="password" type="text" placeholder="Your Password" value="<?=$_POST['password']?>" />
		</div>
		<div class="inp-item">
			<input type="submit" id="oauth_generate_tokens" value="Proceed"/>
		</div>
	</form>
</body>
</html>