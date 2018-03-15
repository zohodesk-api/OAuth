<?php
	
	require 'database_con.php';

    $db = new MyDB();
	
	if(initializeUsersDatabase()){
		echo "<h1> Users Table Created </h1>";
	}

	if(initializeOauthClientDatabase()){
		echo "<h1> OAuth Client Table Created </h1>";
	}

	function initializeOauthClientDatabase(){
      return executeQuery("CREATE TABLE IF NOT EXISTS OAUTH_CLIENT_DATA_TABLE
                           (  OAUTH_ID             INTEGER               PRIMARY KEY AUTOINCREMENT,
                              CLIENT_ID            VARCHAR(150)  UNIQUE  NOT NULL,
                              CLIENT_SECRET        VARCHAR(150)          NOT NULL,
                              OAUTH_REDIRECT_URI   TEXT                  NULL,
                              OAUTH_REFRESH_TOKEN  VARCHAR(150)          NULL,
                              OAUTH_USER_ID        INTEGER               NOT NULL,
                              OAUTH_SCOPE          TEXT                  NULL)");
   }

   function initializeUsersDatabase(){
      return executeQuery("CREATE TABLE IF NOT EXISTS OAUTH_USERS_TABLE
                           (  USER_ID           INTEGER               PRIMARY KEY AUTOINCREMENT,
                              USER_EMAIL        VARCHAR(150)  UNIQUE  NOT NULL,
                              PASSWORD          TEXT                  NULL,
                              CREATED_TIME      DATETIME              DEFAULT CURRENT_TIMESTAMP)");
   }

?>