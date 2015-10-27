<?php

require_once(base_path().'/app/libraries/Paneon/PaneonHelper/Paneon.php');

class PageController extends BaseController {

    protected $fmLayout = 'Personenliste_Web';

    /**
     * Filled from the FileMaker database field, is used as a placeholder if not empty
     *
     * @var string
     */
    protected $fmXingLink = "";

    private $searchQuery = "";

    /**
     * @var Paneon\OAuthClient\OAuthClient
     */
    private $oAuthClient = null;

    /**
     * The User who logged into Xing
     *
     * @var Object
     * @property $display_name
     */
    private $xingUser = null;

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

        $this->fmRecordId = $fmId;

        try {
            $this->initClient($serial);
        }
        catch(Exception $e){
            $this->showError("Die Seriennummer ist ungültig oder Ihre Datenbank wurde nicht freigeschaltet. Bitte kontaktieren Sie Ihren Ansprechpartner bei Heads2Hunt.");
            return;
        }

        $this->oAuthClient = $this->getOAuthClient();

        /*
         * =============================================
         * Login to Xing Done
         * =============================================
         */
        try{
            $this->doXingLogin();
        }
        catch(Exception $e)
        {
            $messageString = $e->getMessage();

            if($e->getCode() > 0){
                $messageString = "Xing Fehler: ".$e->getCode()." ".$messageString;
            }
            Log::debug($messageString, array(
                'serial' => $this->serialNumber,
                'fmId' => $this->fmId,
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'backtrace' => $e->getTraceAsString(),
                'previous' => $e->getPrevious()
            ));

            $this->showError($messageString);
            return;
        }

        // Set Display Name of logged in user
        $this->layout->userName = $this->xingUser->display_name;

        try {
            $this->initializeFileMaker();

            $this->fmRecord = $this->findFileMakerRecordById($fmId);

            $query = Input::get('xinglink','');

            $isPostedForm = Input::get('formpost') == 'yes' ? true : false;

            /**
             * Form not filled?
             */
            if(empty($query))
            {
                $this->showForm();
                return;
            }
            else
            {
                $this->searchQuery = $query;

                $data = $this->processQuery($this->searchQuery, $this->oAuthClient);

                if($isPostedForm)
                {
                    $data = $this->processQuery($this->searchQuery, $this->oAuthClient);

                    if($this->importIntoFileMaker($data))
                    {
                        $this->showSuccess();
                        return;
                    }
                    else
                    {
                        $this->showData($data);
                        return;
                    }
                }
                else
                {
                    $this->showData($data);
                }
            }
        }
        catch(Exception $e)
        {
            $messageString = $e->getMessage();

            if(substr_count($messageString, "Unknown")
                || substr_count($messageString, "error")
                || substr_count($messageString, "Error")){
                $messageString = "Es gab einen Fehler beim Speichern der Daten. Bitte achten Sie darauf dass keine anderen Benutzer oder Sie selbst in einem der Datenbankfelder aktiv sind. Der Datensatz ist sonder gesperrt und ein Import von externen Daten ist nicht möglich.";
            }
            elseif($e->getCode() > 0){
                $messageString = "Fehler: ".$e->getCode()." ".$messageString;
            }

            Log::debug($messageString, array(
                'serial' => $this->serialNumber,
                'fmId' => $this->fmId,
                'searchQuery' => $this->searchQuery,
                'fmAction' => $this->fmAction,
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'backtrace' => $e->getTraceAsString(),
                'previous' => $e->getPrevious()
            ));

            $this->showError($messageString);
            return;
        }



	}

    public function systemCheck($serial){

        /**
         * Init Client
         */
        try {
            $this->initClient($serial);
        }
        catch(Exception $e){
            return Response::json(array(
                'initClient' => 'Failure'
            ));
        }

        /**
         *
         */
        try{
            $this->initializeFileMaker();
        }
        catch(Exception $e){
            return Response::json(array(
                'initFileMaker' => 'Failure'
            ));
        }

        try {
            $records = $this->findAny();
            $this->fmErrorHandling($records);
        }
        catch(Exception $e){
            return Response::json(array(
                'findAny' => 'Failure'
            ));
        }

        $response = Response::json(array(
            'systemCheck' => 'Success'
        ));

        return $response;
    }

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function showForm()
	{
        $this->layout->content = View::make('pages.index')
            ->with('userName', $this->xingUser->display_name)
            ->with('fmId', $this->fmId)
            ->with('fmXingLink', $this->fmXingLink)
            ->with('serial', $this->serialNumber);
	}

    public function showSuccess()
    {
        $this->layout->content = View::make('pages.success')
            ->with('fmId', $this->fmId)
            ->with('serial', $this->serialNumber);
    }

    /**
     * Show Login Form
     */
    public function showLogin()
    {
        $this->layout->content = View::make('pages.login');
    }

    /**
     * Process the Login
     */
    public function doLogin()
    {
        $rules = array(
            'email'     => 'required|email',
            'password'  => 'required'
        );

        $validator = Validator::make(Input::all(), $rules);

        if($validator->fails())
        {
            return Redirect::to('login')
                ->withErrors($validator)
                ->withInput(Input::only('email'));
        }
        else
        {
            $userdata = array(
                'email'     => Input::get('email'),
                'password'  => Input::get('password'),
            );

            if(Auth::attempt($userdata))
            {
                return Redirect::to('admin');
            }
            else
            {
                return Redirect::to('login')
                    ->withInput(Input::only('email'));
            }
        }

    }

    public function doLogout()
    {
        Auth::logout();
        return Redirect::to('login');
    }

    private function getLabel($key)
    {
        switch($key){
            case 'PersonenID_Adressen::Ort': return "Adresse: Ort";
            case 'PersonenID_Adressen::Land': return "Adresse: Land";
            case 'PersonenID_Adressen::PLZ': return "Adresse: PLZ";
            case 'PersonenID_Adressen::Strasse':  return "Adresse: Strasse";
            case 'FON | PRIV': return "Privat: Telefon";
            case 'MAIL | PRIV': return "Privat: E-Mail";
            case 'MOBIL': return "Mobil";
            case 'FON | BIZ': return "Geschäft: Telefon";
            case 'MAIL | BIZ': return "Geschäft: E-Mail";
            case 'Foto | Container': return "Foto";

            default:
                return $key;
        }
    }

    /**
     * @param                                $query
     * @param Paneon\OAuthClient\OAuthClient $oAuthClient
     *
     * @throws Exception
     * @return array
     */
    private function processQuery($query, $oAuthClient)
    {
        if(substr_count($query,"profile/") > 0)
        {
            $query = substr($query, strpos($query, "profile/")+8);
        }
        elseif(substr_count($query,"profiles/") > 0)
        {
            $query = substr($query, strpos($query, "profiles/")+9);
        }

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

        $success = $oAuthClient->CallAPI(
            'https://api.xing.com/v1/users/'.$query,
            'GET', array(), array('FailOnAccessError' => true), $result);

        if($success == false)
        {
            $e = new Exception();
            Log::error("Failed to connect to  XING API", array(
                'context' => 'PageController.processQuery',
                'serial' => $this->serialNumber,
                'fmId' => $this->fmId,
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'backtrace' => $e->getTraceAsString(),
                'previous' => $e->getPrevious()
            ));
            throw(new Exception("Es wurde kein Profil mit diesem Link gefunden. Bitte geben Sie keine Suchbegriffe in das Suchfeld ein sondern den Link zu einem existierenden Profil."));
        }

        /**
         * @var Object $userResult
         */
        $userResult = (!empty($result->users)) ? $result->users[0] : false;

        if($userResult === false)
        {
            $e = new Exception();
            Log::error("Failed to connect to  XING API", array(
                'context' => 'PageController.processQuery',
                'more' => 'No user found',
                'serial' => $this->serialNumber,
                'fmId' => $this->fmId,
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'backtrace' => $e->getTraceAsString(),
                'previous' => $e->getPrevious()
            ));
            throw(new Exception("Es wurde kein Profil mit diesem Link gefunden. Bitte geben Sie keine Suchbegriffe in das Suchfeld ein sondern den Link zu einem existierenden Profil."));
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

    private function showData($data)
    {
        $displayName = $data['display_name']['value'];

        unset($data['display_name']);

        $differentLastName = false;

        // Check FM Data: Last Name
        $fmLastName = $this->fmRecord->getField('Nachname');
        if(!empty($data['Nachname']) && !empty($fmLastName) && $fmLastName != $data['Nachname']['value']){
            $differentLastName = true;
        }

        $this->layout->content = View::make('pages.data')
            ->with(array(
                'userName' => $this->xingUser->display_name,
                'searchQuery' => $this->searchQuery,
                'resultName' => $displayName,
                'serial' => $this->serialNumber,
                'fmId' => $this->fmId,
                'data' => $data,
                'differentLastName' => $differentLastName,
            ));
    }

    private function importIntoFileMaker($data)
    {
        // Remove not existing fields
        unset($data['display_name'],$data['photo']);

        $record = $this->fm->getRecordById($this->fmLayout, $this->fmRecordId);

        $this->fmErrorHandling($record);

        foreach($data as $key => $item)
        {
            $field = str_replace(' ', '_', $key);

            if(Input::get($field) == 'yes')
            {
                if(!empty($item['field']))
                {
                    $newValue = $item['value'];

                    if($item['field'] == 'Notiz')
                    {
                        $newValue = $record->getField($item['field']) .
                            "\n===============\nXING Import: \n\n" . $newValue."\n===============\n";
                        $record->setField('Notiz', $newValue);
                        $record->setField('ZNotiz', $newValue);
                    }
                    else {
                        $record->setField($item['field'], $newValue);
                    }
                }
            }
        }

        $result = $record->commit();

        $this->fmErrorHandling($result);

        return true;
    }

    /**
     *
     * @throws Exception
     * @return null
     */
    private function doXingLogin()
    {
        $user = null;
    //
        if($this->oAuthClient == null){
            $this->oAuthClient = $this->getOAuthClient();
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
            throw(new Exception("Fehler 554 : Es konnte keine Verbindung zu XING hergestellt werden."));
        }
    }

}
