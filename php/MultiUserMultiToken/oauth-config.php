<?php
    session_start();

    if (!isset($_SESSION['OAUTH_USER_ID'])) {
        header("location:login.php");
    }

    require 'database_con.php';

    $oauth_data = getClientDetails($_REQUEST['state']);
    
    if($oauth_data['OAUTH_USER_ID']!=$_SESSION['OAUTH_USER_ID']){
        die(" Forbidden ");
    }

    $stored_client_id = $oauth_data['CLIENT_ID'];
    $stored_client_secret = $oauth_data['CLIENT_SECRET'];
    $stored_redirect_uri = $oauth_data['OAUTH_REDIRECT_URI'];
    $stored_refresh_token = $oauth_data['OAUTH_REFRESH_TOKEN'];
    $stored_oauth_scope = $oauth_data['OAUTH_SCOPE'];
    $stored_auth_uri = $oauth_data['AUTH_URL'];
    $stored_access_token_uri = $oauth_data['ACCESS_TOKEN_URL'];

    $current_page_url = explode('?', ((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"), 2)[0];

    $redirect_uri = $current_page_url; // https://localhost/php/oauth-config.php //URL of this file

    $refresh_token = null;

    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_REQUEST['accounts-server'])) { //executed when zoho server posts information to this page
        assignNewAccessToken('authorization_code', $_REQUEST['code']);
    }

    function assignNewAccessToken($accessType, $key) {

        global $stored_client_id;
        global $stored_client_secret;
        global $stored_redirect_uri;
        global $stored_refresh_token;
        global $stored_oauth_scope;
        global $stored_auth_uri;
        global $stored_access_token_uri;

        $auth_query_params = array(
            'client_id' => $stored_client_id,
            'client_secret' => $stored_client_secret,
            'redirect_uri' => $stored_redirect_uri,
            'scope' => $stored_oauth_scope
        );

        $auth_query_params['grant_type'] = $accessType;

        if ($accessType == 'authorization_code') {
            $auth_query_params['code'] = $key;
        } else {
            $auth_query_params['refresh_token'] = $key;
        }

        $auth_url = $stored_access_token_uri . "?" . urldecode(http_build_query($auth_query_params));

        $ch = curl_init($auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);

        $auth_response = json_decode(curl_exec($ch));

        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            //for example we are storing these values in session.You can store these values either in DB or a file;
            
            updateAccessToken($auth_response->access_token, time(), (time() + $auth_response->expires_in_sec - 1), $stored_client_id);
            
            if ($accessType == 'authorization_code') {
                storeRefreshTokenInFile($auth_response->refresh_token);
            }

            header("location:" . $stored_redirect_uri . "?view-mode=home");
        } else {
            echo " Error while getting OAuth Token ::::: ";
            print_r($auth_response);
            print_r($info);
        }

        showControlPanel();
    }

    function storeOAuthScopeDataInFile($scope) {
        global $stored_client_id;
        updateClientScope($scope, $stored_client_id);
    }

    function storeClientDataInFile($auth_uri, $access_token_uri, $client_id, $client_secret, $redirect_uri) {
        insertClientData($auth_uri, $access_token_uri, $client_id, $client_secret, $redirect_uri, $_SESSION['OAUTH_USER_ID']);
    }

    function storeRefreshTokenInFile($refresh_token) {
        global $stored_client_id;
        updateRefreshToken($refresh_token, $stored_client_id);
    }

    function getNewAccessCode() {

        global $stored_client_id;
        global $stored_client_secret;
        global $stored_redirect_uri;
        global $stored_refresh_token;
        global $stored_oauth_scope;
        global $stored_auth_uri;
        global $stored_access_token_uri;

        header("location:" . $stored_auth_uri . "?response_type=code&client_id=$stored_client_id&scope=" . strtolower($stored_oauth_scope) . "&access_type=offline&redirect_uri=$stored_redirect_uri&state=$stored_client_id");
    }
    
    function showControlPanel(){
        
    }

 
    
    ?>
    
    
