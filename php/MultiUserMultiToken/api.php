<?php

    session_start();

    if (!isset($_SESSION['OAUTH_USER_ID'])) {
        header("location:login.php");
    }
    
    require 'database_con.php';
    
    if($_SERVER['REQUEST_METHOD']=='GET'){
        if(isset($_REQUEST['module']) && $_REQUEST['module']=='tokens'){
            Response(json_encode(getUserClientDetails($_SESSION['OAUTH_USER_ID'])));
        }
    }
    elseif ($_SERVER['REQUEST_METHOD']=='POST') {
        if(isset($_POST['oauth_config_post'])) {
            if($_REQUEST['oauth_config_post'] == 'CONFIG_CLIENT_DATA_POST'){
                storeClientDataInFile($_POST['oauth_auth_uri'], $_POST['oauth_access_token_uri'], $_POST['oauth_client_id'], $_POST['oauth_client_secret'], $_POST['oauth_redirect_uri']);
            }
            else if($_REQUEST['oauth_config_post'] == 'CONFIG_OAUTH_SCOPE_POST'){
                storeOAuthScopeDataInFile($_POST['oauth_scope']);
            }
        }
    }
    
    function Response($response){
        die($response);
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

?>

