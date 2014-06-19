<?php


/**
 * Non-namespaced version of FileMaker 12 PHP API
 */
include_once(base_path().'/app/libraries/filemaker-12/FileMaker.php');


/*
 *---------------------------------------------------------------
 * Job Visibility Types
 *---------------------------------------------------------------
 */
define('PANEON_JOB_TYPE_HIDDEN', 0);
define('PANEON_JOB_TYPE_NORMAL', 1);
define('PANEON_JOB_TYPE_ARCHIVE', 2);


class BaseController extends Controller {

    /**
     * @var Object
     */
    protected $layout = 'layouts.master';

    /**
     * @var FileMaker
     */
    protected $fm = null;

    protected $fmId = 0;

    protected $fmLayout = 'Projektliste_Web';

    /**
     * @var FileMaker_Record
     */
    protected $fmRecord = null;

    protected $fmRecordId = 0;



    /**
     * @var Client
     */
    protected $client = null;

    /**
     * @var string
     */
    protected $serialNumber = "";

    /**
     * Show an error alert page
     *
     * @param string $message
     */
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

    protected function initClient($serial)
    {
        $this->serialNumber = $serial;

        /*
         * Find the Client
         */
        $this->client = Client::where('serial', '=', $this->serialNumber)->first();

        if(!$this->client)
        {
            throw(new Exception("Seriennummer ungültig."));
        }

    }

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

    protected function initializeFileMaker()
    {

        $this->fm = new FileMaker($this->client->db_name, 'http://'.$this->client->host, $this->client->fm_user, $this->client->fm_password);

        if(FileMaker::isError($this->fm))
        {
            throw(new Exception("Es konnte keine Verbindung mit der iRO Datenbank hergestellt werden."));
        }
    }

    /**
     * Get all jobs, optionally above a specified change date
     *
     * @param int $dateTimestamp
     *
     * @throws Exception
     * @return FileMaker_Record[]
     */
    protected function findFileMakerJobs($dateTimestamp = 0)
    {
        $date = strftime("%m/%d/%Y", $dateTimestamp);

        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('AenderungsDatum', '>'.$date);

        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        return $records;
    }

    /**
     * @throws Exception
     * @return FileMaker_Record[]
     */
    protected function findArchivedFileMakerJobs()
    {
        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('Web_Projekt','="Archiv"');

        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        return $records;
    }

    /**
     * @throws Exception
     * @return FileMaker_Record[]
     */
    protected function findHiddenFileMakerJobs()
    {
        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('Web_Projekt','="Nein"');

        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        return $records;
    }

    /**
     * @throws Exception
     * @return FileMaker_Record[]
     */
    protected function findPublicFileMakerJobs()
    {
        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('Web_Projekt','="Ja"');

        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        return $records;
    }

    /**
     * @return FileMaker_Record[]
     * @throws Exception
     */
    protected function findAny()
    {
        $findCommand =& $this->fm->newFindAnyCommand($this->fmLayout);
        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        return $records;
    }

    /**
     * @param  FileMaker_Error|FileMaker_Result|FileMaker_Record[] $error
     *
     * @throws Exception
     * @return bool
     */
    protected function fmErrorHandling($error)
    {
        if(FileMaker::isError($error))
        {
            /**
             * @var FileMaker_Error $error
             */

            $message = $error->getMessage();
            $code = $error->getCode();

            switch($code){
                case 102:
                    $message = "Feld fehlt in Layout (".$this->fmLayout.").";
                    break;
                case 401:
                    $message = "Es wurden keine Ergebnisse gefunden.";
                    break;
                case 8003:
                    $message = "Der Speichervorgang konnte auf den Datensatz nicht zugreifen.";
                    break;
            }

            throw(new Exception($message, $code));
        }
        return true;
    }


    protected function getOAuthClient($redirectUrl = "")
    {
        $oAuthClient = new Paneon\OAuthClient\OAuthClient();
        $oAuthClient->debug = 0;
        $oAuthClient->debug_http = 1;
        $oAuthClient->server = 'XING';
        if(empty($redirectUrl)){
            $oAuthClient->redirect_uri = route('form', array(
                'serial' => $this->serialNumber,
                'fmId' => $this->fmRecordId
            ));
        }
        else {
            $oAuthClient->redirect_uri = $redirectUrl;
        }

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
     * @param $id
     *
     * @return FileMaker_Record
     * @throws Exception
     */
    protected function findFileMakerRecord($id)
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
    }
}