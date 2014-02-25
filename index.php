<?php
/*
 * login_with_xing.php
 *
 * @(#) $Id: login_with_xing.php,v 1.2 2013/07/31 11:48:04 mlemos Exp $
 *
 */

/*
 *  Get the http.php file from http://www.phpclasses.org/httpclient
 */

error_reporting(E_ALL ^ E_DEPRECATED);
ini_set("display_errors", "stdout");

require('vendor/http/http.php');
require('vendor/oauth-api/oauth_client.php');
require('libraries/functions.php');
require('libraries/filemaker-12/FileMaker.php');

if(session_id() == '')
{
    session_start();
}

$fmID = (empty($_REQUEST['fmid']) || !is_numeric($_REQUEST['fmid'])) ? 0 : $_REQUEST['fmid'];
$iroClient = (empty($_REQUEST['clientid'])) ? 0 : $_REQUEST['clientid'];
/*
006D4-PPAD0-R70AA => 30
006D4-P3AD0-R70A5 => 30

"A"; 5
"D"; 8
"P"; 3
"Q"; 1
"R"; 9
   006D4-PPAD0-R70AA
=> 006843358097055

006843358097055


*/
$clients = array(
    // Demo Datenbank
    '006D4-PPAD0-R70AA' => array(
        'db_name' => 'iRO_35',
        'host' => 'http://host1.kon5.net/',
    ),
    // Kühne
    '006D4-P3AD0-R70A5' => array(
        'db_name' => 'iRO40KU1',
        'host' => 'http://host1.kon5.net/',
    ),
    // PR Hofer
    '006D4-P35D0-R70A5' => array(
        'db_name' => 'iRO40PH1',
        'host' => 'http://host1.kon5.net/',
    ),
);

$_SESSION['client_id'] = $iroClient;



if($fmID == 0 || !isset($clients[$_SESSION['client_id']]))
{
    $content = getContent('xing.error.php', array());
    include('views/layout.php');
    die();
}

$clientConfig = $clients[$_SESSION['client_id']];

$fm = new FileMaker($clientConfig['db_name'], $clientConfig['host'],'maintenance','');


/**
 * Import Data to the Database (Final Step)
 */
if(!empty($_POST['action']) && $_POST['action'] == "import")
{

    $sessionData = $_SESSION['data'][$fmID]['fields'];
}


$client = new oauth_client_class;
$client->debug = 0;
$client->debug_http = 1;
$client->server = 'XING';
$client->redirect_uri = 'http://api-dev.paneon.de/index.php?clientid='.$_SESSION['client_id'].'fmid='.$fmID;

$client->client_id = 'fbb32757b4e2dab792a8';
$client->client_secret = 'c9a177434719ec2eab49765b9e6fe571c76f3661';

if(strlen($client->client_id) == 0
    || strlen($client->client_secret) == 0)
    die('Please go to XING My Apps page https://dev.xing.com/applications , '.
        'create an application, and in the line 22'.
        ' set the client_id to Consumer key and client_secret with Consumer secret.');

if(($success = $client->Initialize()))
{
    if(($success = $client->Process()))
    {
        if(strlen($client->access_token))
        {
            $success = $client->CallAPI(
                'https://api.xing.com/v1/users/me',
                'GET', array(), array('FailOnAccessError'=>true), $user);
        }
    }
    $success = $client->Finalize($success);
}
if($client->exit)
    exit;

if($success)
{
    $user = $user->users[0];

    if(!empty($_GET['xinglink']))
    {
        $query = $_GET['xinglink'];

        if(substr_count($query,"profile/") > 0)
        {
            $query = substr($query, strpos($query, "profile/")+8);
        }

        /*
         *  Alexander_Pape3?someparameter=somevalue
         *  => Alexander_Pape3
         */
        if (substr_count($query, "?") > 0)
        {
            $query = substr($query, 0, strpos($query, "?"));
        }

        $success = $client->CallAPI(
            'https://api.xing.com/v1/users/'.$query,
            'GET', array(), array('FailOnAccessError' => true), $result);

        if($success)
        {

            $userResult = $result->users[0];

            //echo "<!-- ".print_r($userResult,true)." -->";

            $data = array(
                'Vorname' => $userResult->first_name,
                'Nachname' => $userResult->last_name,
                'XING' => $userResult->permalink,
                'Notiz' => '',
                'WeitereSprachen' => array(),
            );

            // Anrede
            switch($userResult->gender)
            {
                case 'm':
                    $data['Anrede'] = "Herr"; break;
                case 'f':
                    $data['Anrede'] = "Frau"; break;
            }

            // Geburtsdatum
            if(!empty($userResult->birth_data->day)
                && !empty($userResult->birth_data->month)
                && !empty($userResult->birth_data->year))
            {
                $data['Geburtsdatum'] = $userResult->birth_data->month.$userResult->birth_data->day.$userResult->birth_data->year;
            }

            // Mail | BIZ
            if(!empty($userResult->active_email))
            {
                $data['MAIL | BIZ'] = $userResult->active_email;
            }

            // Wants
            if(!empty($userResult->wants))
            {
                $data['Notiz'] .= "\nSucht: ".$userResult->wants;
            }

            // Haves
            if(!empty($userResult->haves))
            {
                $data['Notiz'] .= "\nBietet an: ".$userResult->haves;
            }

            // Languages
            if(!empty($userResult->languages))
            {
                $native = array();
                $langs = array(
                    'de' => 'deutsch',
                    'en' => 'englisch',
                    'es' => 'spanisch',
                    'fi' => 'finnisch',
                    'fr' => 'französisch',
                    'hu' => 'ungarisch',
                    'it' => 'italienisch',
                    'ja' => 'japanisch',
                    'ko' => 'koreanisch',
                    'nl' => 'niederländisch',
                    'pl' => 'polnisch',
                    'pt' => 'portugiesisch',
                    'ru' => 'russisch',
                    'sv' => 'schwedisch',
                    'tr' => 'türkisch',
                    'zh' => 'chinesisch',
                    'ro' => 'rumänisch',
                    'no' => 'norwegisch',
                    'cs' => 'tschechisch',
                    'el' => 'griechisch',
                    'da' => 'dänisch',
                    'ar' => 'arabisch',
                    'he' => 'hebräisch',
                );

                foreach($langs as $key => $label)
                {
                    if(!empty($userResult->$key))
                    {
                        switch($userResult->$key)
                        {
                            case 'native':  $data['Muttersprache'] = $label; break;
                            case 'basic':
                            case 'good':
                            case 'fluent':
                                $data['WeitereSprachen'][] = $label; break;
                        }
                    }
                }

            }

            // Interests
            if(!empty($userResult->interests))
            {
                $data['Notiz'] .= "\nInteressen: ".$userResult->interests;
            }

            // Image
            if(!empty($userResult->photo_urls)){
                if(!empty($userResult->photo_urls->large))
                {
                    $data['photo'] = $userResult->photo_urls->large;
                }
                elseif(!empty($userResult->photo_urls->thumb))
                {
                    $data['photo'] = $userResult->photo_urls->thumb;
                }
            }

            // Address
            if(!empty($userResult->private_address))
            {
                $data['PersonenID_Adressen::Ort'] = $userResult->private_address->city;
                $data['PersonenID_Adressen::Land'] = $userResult->private_address->country;
                $data['PersonenID_Adressen::PLZ'] = $userResult->private_address->zip_code;
                $data['PersonenID_Adressen::Strasse'] = $userResult->private_address->street;
                $data['FON | PRIV'] = $userResult->private_address->phone;
                $data['MAIL | PRIV'] = $userResult->private_address->email;
                $data['MOBIL'] = $userResult->private_address->city;
            }


            if(!empty($userResult->business_address))
            {
                $data['FON | BIZ'] = $userResult->business_address->phone;
                $data['MAIL | BIZ'] = $userResult->business_address->email;
            }

            if(!empty($userResult->professional_experience) && !empty($userResult->professional_experience->primary_company))
            {
                $data['Firmenname'] = $userResult->professional_experience->primary_company->name;
                $data['Position'] = $userResult->professional_experience->primary_company->title;
            }

            if(!empty($userResult->educational_background) && !empty($userResult->educational_background->qualifications))
            {
                $data['Firmenname'] = $userResult->professional_experience->primary_company->name;
                $data['Position'] = $userResult->professional_experience->primary_company->title;
            }

            $cleanedData = array();

            foreach($data as $key => $value){
                if(empty($value)){
                    continue;
                }
                $cleanedData[$key] = array(
                    'field' => $key,
                    'label' => getLabel($key),
                    'value' => $value,
                );
            }

            $_SESSION['data'][$fmID]['fields'] = $cleanedData;

            echo "<!-- ".print_r($cleanedData,true)." -->";

            $content = getContent('xing.data.php', array(
                'userName' => $user->display_name,
                'fmID' => $fmID,
                'result' => $userResult,
                'data' => $cleanedData,
            ));

            include('views/layout.php');
        }
        else
        {
            //echo "<br>Error".$client->error;
            $content = getContent('xing.form.php', array(
                'userName' => $user->display_name,
                'fmID' => $fmID,
            ));

            include('views/layout.php');
        }
    }
    else
    {
        // echo "<br>Error".$client->error;
        $content = getContent('xing.form.php', array(
            'userName' => $user->display_name,
            'fmID' => $fmID,
        ));

        include('views/layout.php');
    }



}
else
{
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
        <title>OAuth client error</title>
    </head>
    <body>
    <h1>Authentifizierungsfehler</h1>
    <pre>Error: <?php echo HtmlSpecialChars($client->error); ?></pre>
    </body>
    </html>
    <?php
}

