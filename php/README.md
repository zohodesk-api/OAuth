STEPS:
======

1. Place oauth-config.php file in your server.

2. Go https://accounts.zoho.com/developerconsole
		-> Click Add Client ID
			-> Authorized redirect URIs = URL of oauth-config.php file.

3. Edit oauth-config.php file in your server.
		-> Replace $redirect_uri, $client_id, $client_secret values with your own obtained values in oauth-config.php file.
		
		- oauth-config.php Line 38 : $redirect_uri = URL of oauth-config.php file. Your own redirect url of oauth-config.php file wherever you place oauth-config.php file.
	
		- oauth-config.php - Line 40 : $client_id = Your client_id from https://accounts.zoho.com/developerconsole -> Client ID
			
		- oauth-config.php - Line 42 : $client_secret = //Your client_secret from https://accounts.zoho.com/developerconsole -> 3 dots -> edit -> Client Secret

4. Once Goto https://accounts.zoho.com/oauth/v2/auth?response_type=code&client_id={client_id}&scope=Desk.tickets.ALL&access_type=offline&redirect_uri={redirect_uri}
