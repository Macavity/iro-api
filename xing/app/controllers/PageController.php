<?php

class PageController extends BaseController {

    protected $layout = 'layouts.master';

    /**
     * Display a listing of the resource.
     *
     * @param $serial   string
     * @param $fmId     number
     *
     * @return Response
     */
	public function index($serial, $fmId)
	{
        $oAuthClient = new Paneon\OAuthClient\OAuthClient();
        $oAuthClient->debug = 0;
        $oAuthClient->debug_http = 1;
        $oAuthClient->server = 'XING';
        $oAuthClient->redirect_uri = route('form', array('serial' => $serial, 'fmId' => $fmId));

        $oAuthClient->client_id = Config::get('xing.consumer_key');
        $oAuthClient->client_secret = Config::get('xing.consumer_secret');

        if(strlen($oAuthClient->client_id) == 0
            || strlen($oAuthClient->client_secret) == 0)
            die('Please go to XING My Apps page https://dev.xing.com/applications , '.
                'create an application, and in the line 22'.
                ' set the client_id to Consumer key and client_secret with Consumer secret.');

        if(($success = $oAuthClient->Initialize()))
        {
            if(($success = $oAuthClient->Process()))
            {
                if(strlen($oAuthClient->access_token))
                {
                    $success = $oAuthClient->CallAPI(
                        'https://api.xing.com/v1/users/me',
                        'GET', array(), array('FailOnAccessError'=>true), $user);
                }
            }
            $success = $oAuthClient->Finalize($success);
        }
        if($oAuthClient->exit)
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

                $success = $oAuthClient->CallAPI(
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

                    $_SESSION['data'][$fmId]['fields'] = $cleanedData;

                    echo "<!-- ".print_r($cleanedData,true)." -->";

                    $this->layout->content = View::make('pages.data')
                        ->with(array(
                            'userName' => $user->display_name,
                            'fmID' => $fmId,
                            'result' => $userResult,
                            'data' => $cleanedData,
                        ));

                }
                else
                {
                    $this->layout->content = View::make('pages.index')
                        ->with(array(
                            'userName' => $user->display_name,
                            'fmId' => $fmId,
                        ));

                }
            }
            else
            {
                $this->layout->content = View::make('pages.index')
                    ->with('userName', $user->display_name)
                    ->with('fmId', $fmId)
                    ->with('serial', $serial);
            }



        }
        else
        {
            dd("404");
            Redirect::to('404');
        }

	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
        return View::make('pages.create');
	}

    public function auth()
    {

    }

}
