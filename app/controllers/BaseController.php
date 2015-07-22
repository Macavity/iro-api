<?php


/**
 * Non-namespaced version of FileMaker 12 PHP API
 */
//include_once(base_path().'/app/libraries/filemaker-12/FileMaker.php');

//use Paneon\FileMaker12;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

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

    protected $fmAction = '';

    /**
     * @var FileMaker_Record
     */
    protected $fmRecord = null;

    protected $fmRecordId = 0;

    /**
     * Google Analytics Measurement Protocol
     * @var TheIconic\Tracking\GoogleAnalytics\Analytics
     */
    protected $gamp = false;

    protected $gampTrackingId = "UA-24950655-2";

    /**
     * @var Client
     */
    protected $client = null;

    /**
     * @var string
     */
    protected $serialNumber = "";

    protected $fmXingLink;

    protected $currentTimestamp;
    protected $log = array();

    public function __construct(){
        $this->currentTimestamp = time();
    }

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
            $any = Client::all();

            print_r($any);

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
        $this->fmAction = "findFileMakerJobs";
        $date = strftime("%m/%d/%Y", $dateTimestamp);

        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('AenderungsDatum', '>'.$date);

        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        return $records;
    }

    protected function log($string){
        $this->log[] = $string;
    }

    protected function getLog(){
        return $this->log;
    }

    /**
     * @param string $sortDirection
     * @return FileMaker_Record[]
     * @throws Exception
     */
    protected function findArchivedFileMakerJobs($sortDirection = "asc")
    {
        $sortDirection = ($sortDirection == "asc") ? FILEMAKER_SORT_ASCEND : FILEMAKER_SORT_DESCEND;

        $this->fmAction = "findArchivedFileMakerJobs";
        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('Web_Projekt','="Archiv"');
        $findCommand->addSortRule('Start', 1, $sortDirection);

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
        $this->fmAction = "findHiddenFileMakerJobs";
        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('Web_Projekt','="Nein"');

        $result = $findCommand->execute();

        $this->fmErrorHandling($result);

        $records = $result->getRecords();

        return $records;
    }

    /**
     * @param string $sortDirection
     * @throws Exception
     * @return FileMaker_Record[]
     */
    protected function findPublicFileMakerJobs($sortDirection = "asc")
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');


        $sortDirection = ($sortDirection == "asc") ? FILEMAKER_SORT_ASCEND : FILEMAKER_SORT_DESCEND;
        $this->fmAction = "findPublicFileMakerJobs";
        $findCommand =& $this->fm->newFindCommand($this->fmLayout);
        $findCommand->addFindCriterion('Web_Projekt','="Ja"');
        $findCommand->addSortRule('Start', 1, $sortDirection);

        $findCommand->setRange(0,60);

        try {
            $result = $findCommand->execute();
            $this->fmErrorHandling($result);

            $records = $result->getRecords();

            return $records;
        }
        catch(Exception $e){
            die($e->getMessage());
        }

    }

    /**
     * @throws Exception
     * @return FileMaker_Record[]
     */
    protected function findExternalFileMakerJobs()
    {
        $this->fmAction = "findExternalFileMakerJobs";
        $findCommand =& $this->fm->newFindCommand($this->fmLayout);

        $findCommand->addFindCriterion('Web Export','="Ja"');

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
        $this->fmAction = "findAny";
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
            $backtrace = $error->getBacktrace();
            //Paneon::debug("Backtrace", $backtrace);

            switch($code){
                case 102:
                    $message = "Feld fehlt in Layout, bitte kontaktieren Sie den Heads2Hunt Support wegen dieses Fehlers.";
                    break;
                case 401:
                    $message = "Es wurden keine Ergebnisse gefunden.";
                    break;
                case 8003:
                    $message = "Der Speichervorgang konnte auf den Datensatz nicht zugreifen.";
                    break;
            }
            Log::error("FileMaker Error", array(
                'serial' => $this->serialNumber,
                'fmId' => $this->fmId,
                'fmAction' => $this->fmAction,
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'backtrace' => $error->getBacktrace(),
            ));

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
        $this->fmAction = "findFileMakerRecord($id)";
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
    }

    protected function trackPageHit($url){

        $gamp = new Analytics();

        $gamp->setProtocolVersion(1)
            ->setAsyncRequest(true)
            ->setTrackingId( $this->gampTrackingId )
            ->setClientId( $this->getTrackedClientId() )
            ->setUserId( $this->client->db_name )
            ->setIpOverride( $_SERVER["REMOTE_ADDR"] )
            ->setDocumentHostName($_SERVER['HTTP_HOST'])
            // Page Hit
            ->setDocumentPath( '/'.$this->client->id.'-'.$url );
        $response = $gamp->sendPageview();



        //Paneon\PaneonHelper\Paneon::debug("gamp",$response);

    }

    protected function trackEvent($category, $action){

        $gamp = new Analytics();

        $response = $gamp->setProtocolVersion(1)
            ->setAsyncRequest(true)
            ->setTrackingId( $this->gampTrackingId )
            ->setClientId( $this->getTrackedClientId() )
            ->setUserId( $this->client->db_name )
            ->setIpOverride( $_SERVER["REMOTE_ADDR"] )
            ->setDocumentHostName($_SERVER['HTTP_HOST'])
            // Event
            ->setEventCategory( $this->client->id.'-'.$category )
            ->setEventAction( $action )
            ->sendEvent();
        //Paneon\PaneonHelper\Paneon::debug("gamp",$response);
    }

    // Handle the parsing of the _ga cookie or setting it to a unique identifier
    protected function getTrackedClientId() {
        /*if (isset($_COOKIE['_ga'])) {
            list($version,$domainDepth, $cid1, $cid2) = preg_split('[\.]', $_COOKIE["_ga"],4);
            $contents = array('version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid1.'.'.$cid2);
            $cid = $contents['cid'];
        }
        else {*/
            $cid = $this->client->serial;
        //}
        return $cid;
    }

    public function removeHTML($text){

        // FM 12 liefert decodierte Entities
        $text = html_entity_decode($text);


        // Alle Tags entfernen
        $text = strip_tags($text);

        $text = str_replace("&lt;br&gt;", "", $text);
        $text = str_replace("&amp;lt;br&amp;gt;", "", $text);


        return $text;
    }
}
