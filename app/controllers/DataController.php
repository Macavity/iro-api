<?php

require_once(base_path().'/app/libraries/Paneon/PaneonHelper/Paneon.php');
require_once(base_path().'/app/macros.php');

class DataController extends BaseController {

    protected $serialNumber;


    public function jobListAll($serial, $type = "normal")
    {
        $cacheId = "empty";
        $cacheActive = false;

        $cacheForceRefresh = (Input::get('forceRefresh') == 1);

        try {

            $this->initClient($serial);

            $this->initializeFileMaker();

            $findCommand =& $this->fm->newFindCommand('Projektliste_Web');

            if($type == "archiv"){
                $cacheId = $this->client->id."-joblist-archiv";
            }
            else {
                $cacheId = $this->client->id."-joblist-normal";
            }

            if(Cache::has($cacheId) && $cacheForceRefresh == false){
                $jobList = Cache::get($cacheId);
                $cacheActive = true;
            }
            else {
                if($type == "archiv"){
                    $records = $this->findArchivedFileMakerJobs();
                }
                else {
                    $records = $this->findPublicFileMakerJobs();
                }

                $jobList = array();

                foreach($records as $record){

                    /** @var $record FileMaker_Record */

                    /** @var $jobId int */
                    $jobId = $record->getField('ID');

                    if($jobId != intval($record->getField('ID')) || empty($jobId) || $jobId <= 0){
                        continue;
                    }

                    $startDate = $record->getField("Start");

                    if(!empty($startDate))
                    {
                        $startDate = $this->formatDate($startDate);
                    }

                    $intro = $record->getField('Web_Firmenintro');
                    $detailslink = $record->getField('Web_Detailslink');
                    $position_name = $record->getField('ProjektName');
                    $position = $record->getField('JTBStellenbeschreibung');

                    $city = $record->getField('Web_Ort');
                    $candidate = $record->getField('JTBKandidatenbeschreibung');
                    $resume = $record->getField('JTBSchlusstext');
                    $attraktivitaet = $record->getField('JTBAttraktivitaet');

                    $web_berater = $record->getField('Web_Berater');
                    $web_berater_email = $record->getField('Web_Berater_Email');
                    $branche = $record->getField('JBranche');
                    $language = $record->getField('JSprache');

                    $searchText = $record->getField("Web_Volltext");

                    $rewriteLink = $position_name.'-'.$city;
                    //$rewriteLink = iconv("UTF-8", "ASCII//TRANSLIT", $rewriteLink);
                    $rewriteLink = str_replace(
                        array('ä', 'ö', 'ü', 'ß','Ä','Ö','Ü'),
                        array('ae', 'oe', 'ue', 'ss','Ae', 'Oe', 'Ue'), $rewriteLink);
                    $rewriteLink = preg_replace('/[^A-z0-9]+/', "_", $rewriteLink);

                    $rewriteLink = preg_replace("/_+/", "_", $rewriteLink);

                    //remove all underscores at the beginning and end
                    $rewriteLink = trim($rewriteLink, "_");

                    $rewriteLink = $jobId.'-'.$rewriteLink;

                    $google_title = $record->getField('Google Titel');
                    $google_desc = $record->getField('Google Beschreibung');


                    if(empty($google_title)){
                        $title = $city.' Jobs - '.$position_name;
                    }
                    else{
                        $title = $google_title;
                    }
                    $title = Paneon::removeHTML($title);


                    //Paneon::debug("Jobtitel:", $title);

                    /*
                     * Sichtbarkeit des Datensatzes
                     */
                    $webProject = $record->getField('Web_Projekt');

                    switch($webProject){
                        case 'Ja':
                            $visible = PANEON_JOB_TYPE_NORMAL;
                            break;
                        case 'Archiv':
                            $visible = PANEON_JOB_TYPE_ARCHIVE;
                            break;
                        default:
                            $visible = PANEON_JOB_TYPE_HIDDEN;
                    }
                    //Paneon::debug("Sichtbarkeit:", $visible);

                    $row = array(
                        'fm_id'     => $jobId,
                        'visible'   => $visible,
                        'timestamp' => $this->currentTimestamp,
                        'start_date' => $startDate,
                        'position'  => $position_name,
                        'industry'  => $branche,
                        'location'  => $city,
                        'contact'   => $web_berater,
                        'mail'      => $web_berater_email,
                        'lang'      => ($language == 'Englisch' || strstr($language,"en") ) ? 'en' : 'de',
                        "full_text" => $searchText,
                        'rewrite_link' => $rewriteLink,
                        /*
                        // Title & Desc
                        'google_title' => $title,
                        'google_desc' => $google_desc,

                        // Detail Daten
                        "Web_Firmenintro" => $intro,
                        "Web_Detailslink" => $detailslink,
                        "JTBStellenbeschreibung" => $position,
                        "JTBKandidatenbeschreibung" => $candidate,
                        "JTBSchlusstext" => $resume,
                        "JTBAttraktivitaet" => $attraktivitaet,
                        */
                    );

                    $jobList[] = $row;
                }

                // Cache the joblist for 24 Hours
                $expiresAt = Carbon::now()->addHours(24);

                Cache::put($cacheId, $jobList, $expiresAt);
            }


            return Response::json(array(
                'cacheActive' => $cacheActive,
                'results' => $jobList,
            ));
        }
        catch(Exception $e){
            return Response::json(array(
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'cacheId' => $cacheId,
                'cacheActive' => $cacheActive,
            ));
        }


    }

    public function externalJobList($serial, $format = "json")
    {
        $cacheId = "empty";
        $cacheActive = false;

        $cacheForceRefresh = (Input::get('forceRefresh') == 1);

        //try {

            $this->initClient($serial);

            $this->initializeFileMaker();

            $cacheId = $this->client->id."-external-".$format;

            if(Cache::has($cacheId) && $cacheForceRefresh == false){
                $jobList = Cache::get($cacheId);
                $cacheActive = true;
            }
            else {
                $records = $this->findExternalFileMakerJobs();

                $jobList = array();

                foreach($records as $record){

                    /** @var $record FileMaker_Record */

                    /** @var $jobId int */
                    $jobId = $record->getField('ID');

                    $externalType = $record->getField('Web Export');

                    // Just in case someone made the field into a Yes/No Selection List
                    if($externalType == "Nein" || $externalType == "No"){
                        continue;
                    }

                    if($jobId != intval($record->getField('ID')) || empty($jobId) || $jobId <= 0){
                        continue;
                    }

                    $startDate = $record->getField("Start");

                    if(!empty($startDate))
                    {
                        $startDate = $this->formatDate($startDate);
                    }

                    $intro = $record->getField('Web_Firmenintro');
                    $detailslink = $record->getField('Web_Detailslink');
                    $position_name = $record->getField('ProjektName');
                    $position = $record->getField('JTBStellenbeschreibung');

                    $city = $record->getField('Web_Ort');
                    $candidate = $record->getField('JTBKandidatenbeschreibung');
                    $resume = $record->getField('JTBSchlusstext');
                    $attraktivitaet = $record->getField('JTBAttraktivitaet');

                    $web_berater = $record->getField('Web_Berater');
                    $web_berater_email = $record->getField('Web_Berater_Email');
                    $branche = $record->getField('JBranche');
                    $language = $record->getField('JSprache');

                    $searchText = $record->getField("Web_Volltext");

                    $rewriteLink = $position_name.'-'.$city;
                    //$rewriteLink = iconv("UTF-8", "ASCII//TRANSLIT", $rewriteLink);
                    $rewriteLink = str_replace(
                        array('ä', 'ö', 'ü', 'ß','Ä','Ö','Ü'),
                        array('ae', 'oe', 'ue', 'ss','Ae', 'Oe', 'Ue'), $rewriteLink);
                    $rewriteLink = preg_replace('/[^A-z0-9]+/', "_", $rewriteLink);

                    $rewriteLink = preg_replace("/_+/", "_", $rewriteLink);

                    //remove all underscores at the beginning and end
                    $rewriteLink = trim($rewriteLink, "_");

                    $rewriteLink = $jobId.'-'.$rewriteLink;

                    $google_title = $record->getField('Google Titel');
                    $google_desc = $record->getField('Google Beschreibung');


                    if(empty($google_title)){
                        $title = $city.' Jobs - '.$position_name;
                    }
                    else{
                        $title = $google_title;
                    }
                    $title = Paneon::removeHTML($title);


                    //Paneon::debug("Jobtitel:", $title);

                    /*
                     * Sichtbarkeit des Datensatzes
                     */
                    $webProject = $record->getField('Web_Projekt');

                    switch($webProject){
                        case 'Ja':
                            $visible = PANEON_JOB_TYPE_NORMAL;
                            break;
                        case 'Archiv':
                            $visible = PANEON_JOB_TYPE_ARCHIVE;
                            break;
                        default:
                            $visible = PANEON_JOB_TYPE_HIDDEN;
                    }
                    //Paneon::debug("Sichtbarkeit:", $visible);

                    $row = array(
                        'fm_id'     => $jobId,
                        'visible'   => $visible,
                        'timestamp' => $this->currentTimestamp,
                        'start_date' => $startDate,
                        'position'  => $position_name,
                        'industry'  => $branche,
                        'location'  => $city,
                        'contact'   => $web_berater,
                        'contact_mail'      => $web_berater_email,
                        'lang'      => ($language == 'Englisch' || strstr($language,"en") ) ? 'en' : 'de',
                        "full_text" => $searchText,
                        'rewrite_link' => $rewriteLink,

                        // Title & Desc
                        'seo_title' => $title,
                        'seo_desc' => $google_desc,

                        // Detail Daten
                        "job_intro" => $intro,
                        "Web_Detailslink" => $detailslink,
                        "job_description" => $position,
                        "job_candidate" => $candidate,
                        "job_resume" => $resume,
                        "job_desirability" => $attraktivitaet,
                    );

                    $jobList[] = $row;
                }

                // Cache the joblist for 24 Hours
                $expiresAt = Carbon::now()->addHours(24);

                Cache::put($cacheId, $jobList, $expiresAt);
            }

            /*
             * Export for: jobs.ch
             */
            if($format == "jobsch"){

                $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
                $xmlString .= "\n".'<JOBS>';

                if(count($jobList) > 0){
                    $xmlString .= "\n ".'<INSERATE>';

                    foreach($jobList as $record){
                        $xmlString .= "\n  ".'<INSERAT>';

                        $row = array(
                            'ORGANISATIONID' => $this->client->getJobsChId(),
                            'INSERATID' => $record['fm_id'],
                            'BERUF' => $record['seo_title'],
                            'TEXT' => $record['job_description'],

                            'ORT' => $record['location'],
                            'KONTAKT' => $record['contact'],
                            'EMAIL' => $record['contact_mail'],
                            'URL' => 'http://www.stocker-hrc.ch/de/ihre-karriere/stellenangebote/detail-stellenangebot/?tx_cfvacancy_job[jobId]='.$record['fm_id'],
                        );

                        foreach($row as $key => $value){
                            if(!empty($value)){

                                $sanitizedValue = str_replace(
                                    array('&',     "'",      '<',    '>',    '"',      'Ä',      'Ö',      'Ü',      'ä',      'ö',      'ü',      'ß'),
                                    array('&amp;', '&apos;', '&lt;', '&gt;', '&quot;', '&#196;', '&#214;', '&#220;', '&#228;', '&#246;', '&#252;', '&#223;'),
                                    $value
                                );
                                $sanitizedValue = trim($sanitizedValue);
                                $sanitizedValue = nl2br($sanitizedValue);

                                $xmlString .= "\n   ".'<'.$key.'>'.$sanitizedValue.'</'.$key.'>';
                            }
                        }

                        $xmlString .= "\n  ".'</INSERAT>';
                    }

                    $xmlString .= "\n ".'</INSERATE>';
                }

                $xmlString .= "\n".'</JOBS>';

                $response = Response::make($xmlString, 200)
                    ->header('Content-Type', 'text/xml');
            }
            else {
                $response = Response::json(array(
                    'cacheActive' => $cacheActive,
                    'cacheId' => $cacheId,
                    'results' => $jobList,
                ));
            }

            return $response;


    }

    public function jobList($serial, $start, $count)
    {
        $this->initClient($serial);

    }

    public function jobDetail($serial, $jobId)
    {
        try {
            $this->initClient($serial);

            $this->initializeFileMaker();

            $findCommand =& $this->fm->newFindCommand('Projektliste_Web');
            $findCommand->addFindCriterion('Web_Projekt','="Ja"');
            $findCommand->addFindCriterion('ID','="'.$jobId.'"');

            $result = $findCommand->execute();

            $record = $result->getFirstRecord();

            if(!$this->fmErrorHandling($record)){
                throw(new Exception("Kein Datensatz gefunden."));
            }

            /** @var $record FileMaker_Record */

            /** @var $jobId int */
            $jobId = $record->getField('ID');

            if($jobId != intval($record->getField('ID')) || empty($jobId) || $jobId <= 0){
                //Paneon::debug("Überspringe wegen ungültiger JobId: JobId ".$record->getField('ID').", ProjektName: ".$record->getField('ProjektName'));
                throw(new Exception("Kein Datensatz gefunden."));
            }

            $startDate = $record->getField("Start");

            if(!empty($startDate))
            {
                $startDate = $this->formatDate($startDate);
            }

            $intro = $record->getField('Web_Firmenintro');
            $detailslink = $record->getField('Web_Detailslink');
            $position_name = $record->getField('ProjektName');
            $position = $record->getField('JTBStellenbeschreibung');

            $city = $record->getField('Web_Ort');
            $candidate = $record->getField('JTBKandidatenbeschreibung');
            $resume = $record->getField('JTBSchlusstext');
            $attraktivitaet = $record->getField('JTBAttraktivitaet');

            $web_berater = $record->getField('Web_Berater');
            $web_berater_email = $record->getField('Web_Berater_Email');
            $branche = $record->getField('JBranche');
            $language = $record->getField('JSprache');

            $searchText = $record->getField("Web_Volltext");

            $rewriteLink = $position_name.'-'.$city;
            //$rewriteLink = iconv("UTF-8", "ASCII//TRANSLIT", $rewriteLink);
            $rewriteLink = str_replace(
                array('ä', 'ö', 'ü', 'ß','Ä','Ö','Ü'),
                array('ae', 'oe', 'ue', 'ss','Ae', 'Oe', 'Ue'), $rewriteLink);
            $rewriteLink = preg_replace('/[^A-z0-9]+/', "_", $rewriteLink);

            $rewriteLink = preg_replace("/_+/", "_", $rewriteLink);

            //remove all underscores at the beginning and end
            $rewriteLink = trim($rewriteLink, "_");

            $rewriteLink = $jobId.'-'.$rewriteLink;

            $google_title = $record->getField('Google Titel');
            $google_desc = $record->getField('Google Beschreibung');


            if(empty($google_title)){
                $title = $city.' Jobs - '.$position_name;
            }
            else{
                $title = $google_title;
            }
            $title = Paneon::removeHTML($title);


            //Paneon::debug("Jobtitel:", $title);

            /*
             * Sichtbarkeit des Datensatzes
             */
            $webProject = $record->getField('Web_Projekt');

            switch($webProject){
                case 'Ja':
                    $visible = PANEON_JOB_TYPE_NORMAL;
                    break;
                case 'Archiv':
                    $visible = PANEON_JOB_TYPE_ARCHIVE;
                    break;
                default:
                    $visible = PANEON_JOB_TYPE_HIDDEN;
            }
            $jobRow = array(
                'fm_id'     => $jobId,
                'visible'   => $visible,
                'timestamp' => $this->currentTimestamp,
                'start_date' => $startDate,
                'position'  => $position_name,
                'industry'  => $branche,
                'location'  => $city,
                'contact'   => $web_berater,
                'contact_mail'      => $web_berater_email,
                'lang'      => ($language == 'Englisch' || strstr($language,"en") ) ? 'en' : 'de',
                "full_text" => $searchText,
                'rewrite_link' => $rewriteLink,

                // Title & Desc
                'seo_title' => $title,
                'seo_desc' => $google_desc,

                // Detail Daten
                "job_intro" => $intro,
                "Web_Detailslink" => $detailslink,
                "job_description" => $position,
                "job_candidate" => $candidate,
                "job_resume" => $resume,
                "job_desirability" => $attraktivitaet,
            );

            return Response::json(array(
                'result' => $jobRow,
            ));

        }
        catch(Exception $e){
            return Response::json(array(
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ));
        }




    }

    private function formatDate($date)
    {
        if(substr_count($date, "/") > 0){
            $date = explode("/", $date);
            /*
             *                   0    1   2
             * Eingangsformat: Monat/Tag/Jahr
             *
             *                   2    0   1
             * Ausgangsformat: Jahr-Monat-Tag
             */
            $date = $date[2]."-".$date[0]."-".$date[1];
        }
        elseif(substr_count($date, ".") > 0){
            $date = explode(".", $date);
            /*
             *                    0   1    2
             * Eingangsformat: Monat/Tag/Jahr
             *
             *                   2    0    1
             * Ausgangsformat: Jahr-Monat-Tag
             */
            $date = $date[2]."-".$date[0]."-".$date[1];
        }
        return $date;
    }
}
