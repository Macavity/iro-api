<?php

class AlgoliaController extends DataController {

    protected $serialNumber;

    /**
     * @var AlgoliaSearch\Client
     */
    private $searchClient;

    /**
     * @var AlgoliaSearch\Index
     */
    private $searchIndex;

    private $searchIndexName;

    public function initSearchClient(){
        $this->searchClient = new AlgoliaSearch\Client("52TJDTSW2T", "8a93393a5414d81d7acb2c88b663621f");

        // Pape
        if($this->client->id == 2 || $this->client->id == 14){
            $this->searchIndexName = 'pape';
        }
        else {
            return Response::view('error', array(), 404);
        }

        $this->searchIndex = $this->searchClient->initIndex($this->searchIndexName);
    }

    public function import($serial, $type = "new")
    {
        if($type == "new"){
            return $this->checkCache($serial, "modified");
        }
        else {
            return $this->checkCache($serial, "open");
        }

    }

    public function checkCache($serial, $type = "modified"){
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        // Type: open - Get all open projects
        // Type: modified (default) - get all modified projects since the last import
        try {

            $this->initClient($serial);

            $this->initSearchClient();

            $this->initializeFileMaker();

            //$this->trackPageHit('/search/check-cache/jobs/'.$type);
            $this->trackPageHit('/'.$serial.'/search/check-cache/jobs/'.$type);

            $lastRefresh = $this->client->getAttribute("last_refresh");

            if(empty($lastRefresh) || $lastRefresh == false){
                $type = "open";
                $this->log("No earlier refresh found => Switch type to all public jobs");
            }

            if($type == "open"){
                // Rare Call: Get all Public Projects
                $records = $this->findPublicFileMakerJobs();
            }
            else{
                // Common Call: Get last changed and then retrieve only changed records
                $records = $this->findModifiedJobs($lastRefresh);
            }

            $changedProjects = array();

            if(FileMaker::isError($records) == false)
            {
                foreach($records as $record)
                {
                    try {
                        $job = new Job($record);

                        $jobId = $job->getId();
                        $jobData = $job->getData();

                        $inMirror = empty($mirrorByJobId[$jobId]) ? false : true;
                        $isIdentical = false;

                        /**
                         * @var JobMirror $jobMirror
                         */
                        $jobMirror = JobMirror::where('client', '=', $this->client->id)->where('job_id', '=', $jobId)->first();

                        if ($jobMirror) {
                            $isIdentical = $job->identicalToMirror($jobMirror);

                            if ($isIdentical) {
                                $this->log("Job " . $jobId . " ist mit MySQL Fallback identisch.");

                                // No update in Algolia necessary.
                            } else {
                                $this->log("Job " . $jobId . " unterscheidet sich vom MySQL Fallback.");

                                $jobMirror->data = json_encode($jobData);
                                $jobMirror->save();

                                $this->log("Job $jobId wurde in MySQL Fallback aktualisiert.");

                                // Update Algolia
                                $changedProjects[] = $jobData;
                            }

                        } else {
                            $jobMirror = new JobMirror();
                            $jobMirror->client = $this->client->id;
                            $jobMirror->job_id = $jobId;
                            $jobMirror->data = json_encode($jobData);

                            $jobMirror->save();
                            $this->log("Job $jobId wurde in MySQL Fallback eingefügt.");

                            if ($jobData['visible'] == PANEON_JOB_TYPE_NORMAL) {
                                // Insert into Algolia
                                $changedProjects[] = $jobData;
                                $this->log("Job $jobId wird in Algolia eingefügt.");
                            } else {
                                // Don't insert hidden/archived jobs into Algolia
                            }
                        }

                        // Delete from Algolia if hidden/archived
                        if (!$isIdentical && ($jobData['visible'] == PANEON_JOB_TYPE_ARCHIVE || $jobData['visible'] == PANEON_JOB_TYPE_HIDDEN) )
                        {
                            $this->searchIndex->deleteObject($jobId);
                            $this->log("Job $jobId wurde aus Suchindex entfernt.", true);
                        }

                    }
                    catch(Exception $e)
                    {
                        $this->log(__LINE__.' '.$e->getMessage());
                        break;
                    }

                }

                //print_r($changedProjects);

                if(count($changedProjects)){
                    $this->searchIndex->saveObjects($changedProjects);
                    $this->log(count($changedProjects)." wurden in Suchindex {$this->searchIndexName} importiert.", true);
                }

                $this->client->last_refresh = time();
                $this->client->save();
                $this->log("Setze last_refresh auf: ".strftime("%d.%m.%Y %H:%M:%S", $this->client->last_refresh));
            }
            else {
                $this->log("no records found");
            }
        }
        catch(Exception $e){
            return View::make('filemaker_error', array(
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'log' => $this->getLog(),
            ));
        }


        return View::make('search.successful', array(
            'log' => $this->getLog(),
        ));

    }
}
