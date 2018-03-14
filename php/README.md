STEPS:
======

1. Place oauth-config.php file in your server.

2. Go to URL of this file as with view-mode=home parameter

	For Example : https://localhost/OAuth/php/oauth-config.php?view-mode=home
		
USAGE:
======
	Include oauth-config.php file wherever you want to use oauth token.
		( e.g ) include_once 'oauth-config.php';

	Get Access Token from Session
		( e.g ) $oauth_token = $_SESSION['OAUTH_AUTHTOKEN'];

	** Access Tokens will be automatically updated when they expire.
	** If you face any issue, delete 'oauth-token-data.json' file and follow STEP 2.