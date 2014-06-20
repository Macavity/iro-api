<?php

class XingController extends BaseController {

    protected $layout = "layouts.xing";

    /**
     * The User who logged into Xing
     *
     * @var Object
     * @property $display_name
     */
    private $xingUser = null;

    private $errorMessage = "";

    private $searchQuery = "";

    /**
     * @var Paneon\OAuthClient\OAuthClient
     */
    private $oAuthClient = null;


    const XING_IMPORT_DONE = 1;

    const XING_FIELD_EMPTY = 100;

    const XING_NOT_LOGGED_IN = 101;

    public function showIndex(){

        $loginStatus = $this->checkXingSession();

        if($loginStatus){
            $this->layout->content = View::make('xing.done');
        }
        else{
            Redirect::action('XingController@showXingLogin');
            return;
        }

    }

    public function showXingLogin(){

        /*
         * =============================================
         * Login to Xing Done
         * =============================================
         */
        try{
            $this->doXingLogin();

            $success = $this->oAuthClient->CallAPI(
                'https://api.xing.com/v1/users/me',
                'GET', array(), array('FailOnAccessError' => true), $result);

            if($success == false)
            {
                Log::error("Failed to connect to XING API", array('context' => 'PageController.processQuery'));
                throw(new Exception("Failed to connect to Xing Api"));
            }
        }
        catch(Exception $e)
        {
            $messageString = $e->getMessage();

            if($e->getCode() > 0){
                $messageString = "Fehler: ".$e->getCode().$messageString. "<!-- (Datei: ".$e->getFile().", Zeile: " .$e->getLine()." -->";
            }

            return Response::make($this::XING_NOT_LOGGED_IN);
        }

        $this->layout->content = View::make('xing.done');

    }

    public function doSearch($serial, $fmId){

        try {
            $this->doXingLogin();
        }
        catch(Exception $e){
            return Response::make($this::XING_NOT_LOGGED_IN);
        }

        error_reporting(E_ALL ^ E_DEPRECATED ^ E_STRICT ^ E_NOTICE);

        $this->initClient($serial);

        $this->initializeFileMaker();

        $this->fmRecord = $this->findFileMakerRecord($fmId);

        $this->searchQuery = $this->fmRecord->getField('XING');

        $isPostedForm = Input::get('formpost') == 'yes' ? true : false;

        /**
         * Form not filled?
         */
        if(empty($this->searchQuery))
        {
            return Response::make($this::XING_FIELD_EMPTY);
        }
        else
        {
            $data = $this->processQuery($this->searchQuery);

            return Response::make($this::XING_IMPORT_DONE);
        }
    }

    public function statusRaw(){


        $status = $this->checkXingSession();

        echo ($status ? "1" : "0");
    }

    public function checkXingSession(){

        try {
            $this->doXingLogin();

            $success = $this->oAuthClient->CallAPI(
                'https://api.xing.com/v1/users/me',
                'GET', array(), array('FailOnAccessError' => true), $result);

            if($success == false)
            {
                Log::error("Failed to connect to XING API", array('context' => 'PageController.processQuery'));
                throw(new Exception("Failed to connect to Xing Api"));
            }

            return true;
        }
        catch(Exception $e){
            return false;
        }
        /*
        $this->oAuthClient = $this->getOAuthClient(action('XingController@showXingLogin'));

        $sessionState = null;
        $user = null;

        try {

            if(($success = $this->oAuthClient->Initialize()))
            {
                if(($success = $this->oAuthClient->Process()))
                {
                    if(strlen($this->oAuthClient->access_token))
                    {
                        $success = $this->oAuthClient->CallAPI(
                            'https://api.xing.com/v1/users/me',
                            'GET', array(), array('FailOnAccessError'=>true), $user);
                    }
                }
                else{
                    throw(new Exception("Fehler ".__LINE__.": Es konnte keine Verbindung zu XING hergestellt werden."));
                }
                $success = $this->oAuthClient->Finalize($success);
            }

            if($this->oAuthClient->exit)
            {


                throw(new Exception("Fehler ".__LINE__.": oAuth Exit"));
            }
            else {
                if($success)
                {
                    $sessionState = true;
                }
                else
                {
                    throw(new Exception("Fehler ".__LINE__.": No Success"));
                }
            }

        }
        catch(Exception $e){
            $sessionState = false;
            $this->errorMessage = $e->getMessage();
            return $sessionState;
        }

        return $sessionState;*/
    }

    public function jsonXingLoggedIn(){
        $check = $this->checkXingSession();

        return Response::json(array(
            'r' => $check,
            't' => empty($this->errorMessage) ? "" : $this->errorMessage,
            //'o' => print_r($this->oAuthClient, true),
        ));
    }

    /**
     *
     * @throws Exception
     * @return null
     */
    private function doXingLogin()
    {
        $user = null;

        if($this->oAuthClient == null){
            $this->oAuthClient = $this->getOAuthClient(action('XingController@showXingLogin'));
        }

        if(($success = $this->oAuthClient->Initialize()))
        {
            if(($success = $this->oAuthClient->Process()))
            {
                if(strlen($this->oAuthClient->access_token))
                {
                    $success = $this->oAuthClient->CallAPI(
                        'https://api.xing.com/v1/users/me',
                        'GET', array(), array('FailOnAccessError'=>true), $user);
                }
            }
            else{
                dd($this->oAuthClient->error);
                throw(new Exception("Fehler 534: Es konnte keine Verbindung zu XING hergestellt werden."));
            }
            $success = $this->oAuthClient->Finalize($success);
        }

        if($this->oAuthClient->exit)
        {
            exit;
        }

        if($success)
        {
            /**
             * @var Object $user
             */
            $this->xingUser = $user->users[0];
            return $user;
        }
        else
        {
            throw(new Exception("Fehler 554: Es konnte keine Verbindung zu XING hergestellt werden."));
        }
    }

    /**
     * @param                                $query
     *
     * @throws Exception
     * @return array
     */
    private function processQuery($query)
    {
        if(substr_count($query,"profile/") > 0)
        {
            $query = substr($query, strpos($query, "profile/")+8);
        }
        elseif(substr_count($query,"profiles/") > 0)
        {
            $query = substr($query, strpos($query, "profiles/")+9);
        }
        //echo $query;


        /*
         *  Alexander_Pape3?someparameter=somevalue
         *  => Alexander_Pape3
         */
        if (substr_count($query, "?") > 0)
        {
            $query = substr($query, 0, strpos($query, "?"));
        }

        /**
         * Returned Xing Result
         * @var Object
         * @property $users
         */
        $result = null;

        $success = $this->oAuthClient->CallAPI(
            'https://api.xing.com/v1/users/'.$query,
            'GET', array(), array('FailOnAccessError' => true), $result);

        if($success == false)
        {
            Log::error("Failed to connect to XING API", array('context' => 'PageController.processQuery'));
            throw(new Exception("Failed to connect to Xing Api"));
        }

        /**
         * @var Object $userResult
         */
        $userResult = (!empty($result->users)) ? $result->users[0] : false;

        if($userResult === false)
        {
            Log::error("Failed to connect to  XING API", array('context' => 'PageController.processQuery', 'more' => 'No user found'));
            throw(new Exception("Failed to connect to Xing API"));
        }

        //dd($userResult);

        $data = array(
            'display_name' => $userResult->display_name,
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
        // TODO Save photo to container field (show photo in data)
        /*if(!empty($userResult->photo_urls)){
            if(!empty($userResult->photo_urls->large))
            {
                $data['Foto | Container'] = $userResult->photo_urls->large;
            }
            elseif(!empty($userResult->photo_urls->thumb))
            {
                $data['Foto | Container'] = $userResult->photo_urls->thumb;
            }
        }*/

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
                'label' => $this->getLabel($key),
                'value' => trim($value),
            );
        }

        $_SESSION['data'][$this->fmRecordId]['fields'] = $cleanedData;

        //echo "<!-- ".print_r($cleanedData,true)." -->";

        return $cleanedData;
    }
}