<?php

        session_start();

        if (!isset($_SESSION['OAUTH_USER_ID'])) {
            header("location:login.php");
        }

        require 'database_con.php';

        $current_page_url = explode('?',((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"),2)[0];

?>
<html>
    <head>
        <link rel="stylesheet" href="controlPanel.css"/>
    </head>
    <body>
        <h1 class="main_title"> Hi <?= explode('@', $_SESSION["OAUTH_USER_EMAIL"])[0] ?> </h1>
        <?php
        
        $oauth_data = getUserClientDetails($_SESSION['OAUTH_USER_ID']);
        
        //print_r($oauth_data);
        
        foreach ($oauth_data as $value){
            showControlPanel($value);
        }
        
        function showControlPanel($oauth_data) {
            
            global  $current_page_url;


        $stored_client_id = $oauth_data['CLIENT_ID'];
        $stored_client_secret = $oauth_data['CLIENT_SECRET'];
        $stored_redirect_uri = $oauth_data['OAUTH_REDIRECT_URI'];
        $stored_refresh_token = $oauth_data['OAUTH_REFRESH_TOKEN'];
        $stored_oauth_scope = $oauth_data['OAUTH_SCOPE'];
        $stored_auth_uri = $oauth_data['AUTH_URL'];
        $stored_access_token = $oauth_data['OAUTH_ACCESS_TOKEN'];
        $stored_access_token_uri = $oauth_data['ACCESS_TOKEN_URL'];
        $stored_created_on = $oauth_data['ACCESS_TOKEN_CREATED'];
        $stored_expires_on = $oauth_data['ACCESS_TOKEN_EXPIRES'];

        $redirect_uri = (!isset($stored_redirect_uri)) ? $current_page_url : $stored_redirect_uri;

        $auth_uri = (!isset($stored_auth_uri)) ? "https://accounts.zoho.com/oauth/v2/auth" : $stored_auth_uri;

        $access_token_uri = (!isset($stored_access_token_uri)) ? "https://accounts.zoho.com/oauth/v2/token" : $stored_access_token_uri;
        
        ?>
    

        
        <form method="POST">
            <h2> STEP 1 :  Configured Values </h2>
        <?php if (!isset($stored_client_id) || !isset($stored_client_secret)) { ?>
                <span class="error"> <?php if (!isset($stored_client_id)) { ?> <b> Client ID </b> <?php } if (!isset($stored_client_secret)) { ?> <b> Client Secret </b> is't configured  <?php } ?> </span>
                <a href="https://accounts.zoho.com/developerconsole" target="_blank"> Get Client ID and Client Secret </a>
                <div class="error-help">
                    <h5 class="title">How to get new Client ID and Client Secret ? </h5>
                    <ol>
                        <li> Go to <b><a href="https://accounts.zoho.com/developerconsole" target="_blank"> this </a></b> link </li>
                        <li> Click <button class="btn-add" disabled>Add Client ID</button> </li>
                        <li> Fill <b>Client Name</b>, <b>Client Domain</b> </li>
                        <li> Put <b>Authorized redirect URIs</b> = <code><?= $redirect_uri ?></code></li>
                    </ol>
                </div>
                <?php if (isset($stored_client_id) && !isset($stored_client_secret)) { ?>
                    <div class="error-help">
                        <h5 class="title">How to get Client Secret ? </h5>
                        <ol>
                            <li> Go to <b><a href="https://accounts.zoho.com/developerconsole" target="_blank"> this </a></b> link </li>
                            <li> Find the row where <b>Client ID</b> = <code><?= $stored_client_id ?></code>, Click <b> 3 Vertical Dots </b> -> <b>Edit</b></li>
                            <li> There you can find Client Secret </li>
                        </ol>
                    </div>
                    
        <?php } } ?>
                <div class="inp-item">
                    <label for="oauth_auth_uri"> Auth URL </label>
                    <input id="oauth_auth_uri" name="oauth_auth_uri" type="text" placeholder=" Auth URI " value="<?= $auth_uri ?>" required/>
                </div>
                <div class="inp-item">
                    <label for="oauth_access_token_uri"> Access Token URL </label>
                    <input id="oauth_access_token_uri" name="oauth_access_token_uri" type="text" placeholder=" Access Token URI " value="<?= $access_token_uri ?>" required/>
                </div>
                <div class="inp-item">
                    <label for="oauth_client_id"> Client ID </label>
                    <input id="oauth_client_id" name="oauth_client_id" type="text" placeholder=" Clien ID" value="<?= $stored_client_id ?>" required/>
                    <input id="oauth_client_id" type="hidden" name="oauth_config_post" value="CONFIG_CLIENT_DATA_POST"/>
                </div>
                <div class="inp-item">
                    <label for="oauth_client_secret"> Client Secret </label>
                    <input id="oauth_client_secret" name="oauth_client_secret"type="text" placeholder=" Clien Secret" value="<?= $stored_client_secret ?>" required/>
                </div>
                <div class="inp-item">
                    <label for="oauth_redirect_uri"> Redirect URI </label>
                    <input id="oauth_redirect_uri" name="oauth_redirect_uri" type="text" placeholder=" Redirect URI" value="<?= $redirect_uri ?>" required/>
                </div>
                <div class="inp-item">
                    <input type="submit" value="Update">
                </div>
            </form>

            <form method="POST">
                <h2> STEP 2 : Current OAuth Token Values </h2>
                <div class="inp-item">
                    <label for="oauth_access_token"> ACCESS TOKEN </label>
                    <input id="oauth_access_token" class="readonly-value-holder" readonly type="text" placeholder=" ACCESS TOKEN" value="<?= $stored_access_token ?>" />
                </div>
                <div class="inp-item">
                    <label for="oauth_refresh_token"> REFRESH TOKEN </label>
                    <input id="oauth_refresh_token" class="readonly-value-holder" readonly type="text" placeholder=" REFRESH TOKEN" value="<?= $stored_refresh_token ?>" />
                </div>
            <?php if ($stored_created_on != null) { ?>
                    <div class="inp-item">
                        <label for="oauth_created_on"> ACCESS TOKEN CREATED ON </label>
                        <input id="oauth_created_on" readonly type="text" placeholder=" ACCESS TOKEN CREATED ON" value="<?= date('F d, Y h:i:s', $stored_created_on); ?>" />
                    </div>
                    <div class="inp-item">
                        <label for="oauth_expires_in"> ACCESS TOKEN EXPIRES ON <b>( in <?= round(($stored_expires_on - time()) / 60) ?> mins </b> )</label>
                        <input id="oauth_expires_in" readonly type="text" placeholder=" ACCESS TOKEN EXPIRES ON" value="<?= date('F d, Y h:i:s', $stored_expires_on); ?>" />
                    </div>

            <?php } ?>

                <?php if (isset($stored_client_id) && isset($stored_client_secret)) { ?>
                    <div class="inp-item">
                        <label for="oauth_scopes"> SCOPE <b>( Comma Seperated OAUTH SCOPES </b><a href="https://desk.zoho.com/support/APIDocument.do#Authentication#OauthTokens" target="_blank"> Available Scopes</a> )</label>
                        <input id="oauth_scopes" required name="oauth_scope" type="text" placeholder="DESK.TICKETS.ALL,DESK.CONTACTS.READ,..." value="<?= $stored_oauth_scope ?>" />
                        <input type="hidden" name="oauth_config_post" value="CONFIG_OAUTH_SCOPE_POST">
                    </div>
                    <div class="inp-item">
                        <input type="submit" id="oauth_generate_tokens" value="Click to Generate New Access Token & Refresh Token"/> 
                    </div>
                <?php } ?>
            </div>
            </form>

            
            
            <?php
        }
    
    ?>
        <a href="login.php?action=logout"> Logout </a>
    </body>
</html>
