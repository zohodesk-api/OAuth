<?php

   class MyDB extends SQLite3 {
      function __construct() {
         $this->open('oauth_data.db');
      }
   }

   $db = new MyDB();

   function insertClientData($client_id, $client_secret, $redirect_uri, $user_id){
         return executeQuery("INSERT INTO OAUTH_CLIENT_DATA_TABLE (CLIENT_ID,CLIENT_SECRET,OAUTH_REDIRECT_URI, OAUTH_USER_ID)
                VALUES ('$client_id', '$client_secret', '$redirect_uri', '$user_id')");
   }

   function insertNewUser($user_email, $password){
         if(executeQuery("INSERT INTO OAUTH_USERS_TABLE (USER_EMAIL,PASSWORD)
                VALUES ('$user_email', '".sha1($password)."')")){
            return getUserIdFromEmail($user_email);
         }
         return FALSE;
   }

   function getUserIdFromEmail($user_email){
      return getQuery("SELECT USER_ID from OAUTH_USERS_TABLE WHERE USER_EMAIL='".$user_email."';")[0]['USER_ID'];
   }

   function checkLoginCredentials($user_email, $password){
      return getQuery("SELECT USER_ID from OAUTH_USERS_TABLE WHERE ( USER_EMAIL='".$user_email."' AND PASSWORD = '".sha1($password)."' );")[0]['USER_ID'];
   }

   function executeQuery($sql){
      global $db;
      return !($db->exec($sql)) ? FALSE:TRUE;
   }

   function getQuery($sql){
      global $db;
      $data = $db->query($sql);
      $i=0;
      while($row = $data->fetchArray(SQLITE3_ASSOC) ) {
         $data_array[$i++] = $row;
      }
      return $data_array;
   }

   function updateClientScope($scope,$client_id){
      return executeQuery("UPDATE OAUTH_CLIENT_DATA_TABLE SET OAUTH_SCOPE='$scope' WHERE CLIENT_ID='$client_id'");
   }

   function updateRefreshToken($refresh_token,$client_id){
      return executeQuery("UPDATE OAUTH_CLIENT_DATA_TABLE SET OAUTH_REFRESH_TOKEN='$refresh_token' WHERE CLIENT_ID='$client_id'");
   }

   function getClientDetails($client_id){
      return getQuery("SELECT * from OAUTH_CLIENT_DATA_TABLE where OAUTH_ID='$client_id' limit 1")[0];
   }

   function getUserClientDetails($user_id){
      return getQuery("SELECT * from OAUTH_CLIENT_DATA_TABLE where OAUTH_USER_ID='$user_id' limit 1")[0];
   }

   function getAllClientData(){
      printFormattedData(getQuery("SELECT * FROM OAUTH_CLIENT_DATA_TABLE"),true);
   }

   function getAllUsersData(){
      printFormattedData(getQuery("SELECT * FROM OAUTH_USERS_TABLE"),true);
   }

   function printFormattedData($data){
      echo "<pre>".print_r($data,true)."</pre>";
   }

 
?>