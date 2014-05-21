<?php

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
     * @var FileMaker
     */
    protected  $fm = null;

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

        if(empty($this->client))
        {
            $this->showError("Seriennummer ungültig.");
            return;
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
            throw(new Exception($error->getMessage(), $error->getCode()));
        }
        return true;
    }

}