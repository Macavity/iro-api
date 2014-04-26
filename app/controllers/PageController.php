<?php

/**
 * Non-namespaced version of FileMaker 12 PHP API
 */
include_once(base_path().'/app/libraries/filemaker-12/FileMaker.php');


class PageController extends BaseController {

    /**
     * @var Object
     */
    protected $layout = 'layouts.master';

    protected $fmLayout = 'Personenliste_Web';

    private $fmRecordId = 0;

    private $fmId = 0;

    /**
     * Filled from the FileMaker database field, is used as a placeholder if not empty
     *
     * @var string
     */
    private $fmXingLink = "";

    private $serialNumber = "";

    private $searchQuery = "";

    /**
     * @var FileMaker
     */
    private $fm = null;

    /**
     * The User who logged into Xing
     *
     * @var Object
     * @property $display_name
     */
    private $xingUser = null;

    /**
     * @var Client
     */
    private $client = null;

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

        $this->serialNumber = $serial;
        $this->fmRecordId = $fmId;

        $oAuthClient = $this->getOAuthClient();

        /*
         * =============================================
         * Login to Xing Done
         * =============================================
         */
        try{
            $this->doXingLogin($oAuthClient);
        }
        catch(Exception $e)
        {
            $messageString = $e->getMessage();

            if($e->getCode() > 0){
                $messageString = "Fehler: ".$e->getCode().$messageString. "<!-- (Datei: ".$e->getFile().", Zeile: " .$e->getLine()." -->";
            }

            $this->showError($messageString);
            return;
        }

        // Set Display Name of logged in user
        $this->layout->userName = $this->xingUser->display_name;

        /*
         * Find the Client
         */
        $this->client = Client::where('serial', '=', $this->serialNumber)->first();

        if(empty($this->client))
        {
            $this->showError("Seriennummer ungültig.");
            return;
        }

        try {

            error_reporting(E_ALL ^ E_DEPRECATED ^ E_STRICT ^ E_NOTICE);

            $this->initializeFileMaker();

            $this->findFileMakerRecord($fmId);

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

                $data = $this->processQuery($this->searchQuery, $oAuthClient);

                if($isPostedForm)
                {
                    $data = $this->processQuery($this->searchQuery, $oAuthClient);

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

            if($e->getCode() > 0){
                $messageString = "Fehler: ".$e->getCode().$messageString."<!-- Datei: ".$e->getFile().", Zeile: " .$e->getLine()." -->";

            }

            $this->showError($messageString);
            return;
        }



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

    public function showError($message = "")
    {
        if(empty($message))
        {
            $this->layout->content = View::make('error')
                ->with('fmId', $this->fmId)
                ->with('serial', $this->serialNumber);
        }
        else
        {
            $this->layout->content = View::make('error')
                ->with('fmId', $this->fmId)
                ->with('serial', $this->serialNumber)
                ->with('message', $message);
        }
    }

    public function showSuccess()
    {
        $this->layout->content = View::make('pages.success');
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
            Log::error("Failed to connect to  XING API", array('context' => 'PageController.processQuery'));
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

    private function getOAuthClient()
    {
        $oAuthClient = new Paneon\OAuthClient\OAuthClient();
        $oAuthClient->debug = 0;
        $oAuthClient->debug_http = 1;
        $oAuthClient->server = 'XING';
        $oAuthClient->redirect_uri = route('form', array(
            'serial' => $this->serialNumber,
            'fmId' => $this->fmRecordId
        ));

        $oAuthClient->client_id = Config::get('xing.consumer_key');
        $oAuthClient->client_secret = Config::get('xing.consumer_secret');

        if(empty($oAuthClient->client_id) || empty($oAuthClient->client_secret))
        {
            die('Please go to XING My Apps page https://dev.xing.com/applications , '.
                'create an application, and in the line 22'.
                ' set the client_id to Consumer key and client_secret with Consumer secret.');
        }

        return $oAuthClient;
    }

    /**
     * @param Paneon\OAuthClient\OAuthClient $oAuthClient
     *
     * @throws Exception
     * @return null
     */
    private function doXingLogin($oAuthClient)
    {
        $user = null;

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
            else{
                dd($oAuthClient->error);
                throw(new Exception("Fehler 534: Es konnte keine Verbindung zu XING hergestellt werden."));
            }
            $success = $oAuthClient->Finalize($success);
        }

        if($oAuthClient->exit)
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
            dd($oAuthClient);
            throw(new Exception("Fehler 554 : Es konnte keine Verbindung zu XING hergestellt werden."));
        }
    }

    private function initializeFileMaker()
    {

        $this->fm = new FileMaker($this->client->db_name, 'http://'.$this->client->host, $this->client->fm_user, $this->client->fm_password);

        if(FileMaker::isError($this->fm))
        {
            throw(new Exception("Es konnte keine Verbindung mit der iRO Datenbank hergestellt werden."));
        }
    }

    private function showData($data)
    {
        $displayName = $data['display_name']['value'];

        unset($data['display_name']);

        $this->layout->content = View::make('pages.data')
            ->with(array(
                'userName' => $this->xingUser->display_name,
                'searchQuery' => $this->searchQuery,
                'resultName' => $displayName,
                'serial' => $this->serialNumber,
                'fmId' => $this->fmId,
                'data' => $data,
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
                    }
                    $record->setField($item['field'], $newValue);
                }
            }
        }

        $result = $record->commit();

        $this->fmErrorHandling($result);

        return true;
    }

    private function findFileMakerRecord($id)
    {
        $findCommand = $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('ID', '='.$id);
        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        $this->fmErrorHandling($records);

        if(count($records) > 0){
            $record = $records[0];
            $this->fmRecordId = $record->getRecordId();
            $this->fmId = $record->getField('ID');

            $xingLink = trim($record->getField('XING'));

            $this->fmXingLink = (empty($xingLink)) ? "" : $xingLink;

            return $record;
        }

        throw(new Exception("Kein Ergebnis gefunden."));

        return false;
    }

    /**
     * @param  FileMaker_Error|FileMaker_Result|FileMaker_Record[] $error
     *
     * @throws Exception
     * @return bool
     */
    private function fmErrorHandling($error)
    {
        if(FileMaker::isError($error))
        {
            /**
             * @var FileMaker_Error $error
             */
            throw(new Exception($error->getMessage(), $error->getCode()));
            return false;
        }
        return true;
    }

}
