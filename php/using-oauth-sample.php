<?php

    /******    SAMPLE PAGE FOR USING OAUTH CODE GENERATED FROM OAUTH-CONFIG.PHP file        ******/

    include_once 'oauth-config.php';

    $oauth_token = $_SESSION['OAUTH_AUTHTOKEN']; //your_oauth_token from session/db/file
    $org_id=3528969563; //<================ REPLACE_WITH_YOUR_ORG_ID

    $ticket_data=array(
        "departmentId"=>"2154890873800000006907", //<================ REPLACE_WITH_YOUR_DEPARTMENT_ID  
        "contactId"=>"2154890038730000081001", //<================ REPLACE_WITH_YOUR_CONTACT_ID  
        "subject"=>date("h:i:s A d.m.Y")." - Newly created ticket from PHP ",
        "description"=>json_encode($_REQUEST)
    );
    
    $headers=array(
            "Authorization:Zoho-oauthtoken $oauth_token",
            "orgId: $org_id",
            "contentType: application/json; charset=utf-8",
    );

    $url="https://desk.zoho.com/api/v1/tickets";

    $ticket_data=(gettype($ticket_data)==="array")? json_encode($ticket_data):$ticket_data;
    
    $ch= curl_init($url);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
    curl_setopt($ch,CURLOPT_POST,TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$ticket_data); //convert ticket data array to json
    
    $response= curl_exec($ch);
    $info= curl_getinfo($ch);
    
    if($info['http_code']==200){
        echo "<h2>Request Successful, Response:</h2> <br>";
        echo $response;
    }
    else{
        echo "Request not successful. Response code : ".$info['http_code']." <br>";
        echo "Response : $response";
    }
    
    curl_close($ch);

?>
