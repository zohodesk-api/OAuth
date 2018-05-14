STEPS:
======

1. Place oauth-config.php file in your server.

2. Go to URL of this file as with 'view-mode=home' parameter

	For Example : https://localhost/OAuth/php/oauth-config.php?view-mode=home
		
USAGE:
======
	Include oauth-config.php file wherever you want to use oauth token.
		( e.g ) include_once 'oauth-config.php';

	Get Access Token from Session
		( e.g ) $oauth_token = $_SESSION['OAUTH_AUTHTOKEN'];

	** Access Tokens will be automatically updated when they expire.
	** If you face any issue, delete 'oauth-token-data.json' file and follow STEP 2.

HOW IT WORKS:
=============
	Client Details such as CLIENT ID, CLIENT SECRET, REFRESH TOKEN are stored in 'oauth-token-data.json' file. 
	(So please restrict access of this file for outside world.)
	
	Note: **oath-token-data.json file is automatically created** and updated every time
	
	Whenever 'oauth-config.php' included in a file, if Access Token is expired then new token is created in the background, using the REFRESH TOKEN from the 'oauth-token-data.json' file.

	So It is suggested to move the handling of ** storing the data in file ** to database.
