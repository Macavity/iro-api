<?php

require_once(base_path().'/app/libraries/Paneon/PaneonHelper/Paneon.php');
require_once(base_path().'/app/macros.php');

use Paneon\PaneonHelper;

class DataController extends BaseController {

    protected $serialNumber;

    public function jobListAll($serial, $sortDirection = "desc", $type = "normal")
    {
        $cacheId = "empty";
        $cacheActive = false;

        $cacheForceRefresh = (Input::get('forceRefresh') == 1);


        // Alter Wert
        if($sortDirection == "all"){
            $sortDirection = "desc";
        }

        if($sortDirection != "desc"){
            // Default
            $sortDirection = "asc";
        }

        try {

            $this->initClient($serial);

            $this->initializeFileMaker();

            //$findCommand =& $this->fm->newFindCommand('Projektliste_Web');

            if($type == "archiv"){
                $cacheId = $this->client->id."-joblist-archiv-".$sortDirection;
            }
            else {
                $cacheId = $this->client->id."-joblist-normal-".$sortDirection;
            }

            if(Cache::has($cacheId) && $cacheForceRefresh == false){
                $jobList = Cache::get($cacheId);
                $cacheActive = true;
                $this->log("Cache active");

                $this->trackPageHit('/jobs/'.$sortDirection.'/'.$type.'/Cached');
                $this->trackEvent('joblist', 'cached call');

            }
            else {
                if($cacheForceRefresh){
                    $this->trackEvent('joblist', 'forced refresh');
                    $this->trackPageHit('/jobs/'.$sortDirection.'/'.$type.'/forcedRefresh');
                }
                else {
                    $this->trackEvent('joblist', 'normal refresh');
                    $this->trackPageHit('/jobs/'.$sortDirection.'/'.$type.'/Fresh');
                }

                if($type == "archiv"){
                    $this->log("find Archived Jobs");
                    $records = $this->findArchivedFileMakerJobs($sortDirection);
                }
                else {
                    $this->log("find Public Jobs");
                    $records = $this->findPublicFileMakerJobs($sortDirection);

                }

                $jobList = array();
                $this->log("Foreach records");

                foreach($records as $record){

                    /** @var $record FileMaker_Record */

                    /** @var $jobId int */
                    $jobId = $record->getField('ID');

                    //$this->log("Job ID:".$jobId);

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
                    $title = $this->removeHTML($title);


                    //$this->log("Jobtitel:".$title);

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
                        'last_modified' => $record->getField('AenderungZeitstempel'),
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
                $this->log("foreach end");
                // Cache the joblist for 24 Hours
                $expiresAt = Carbon::now()->addHours(1);

                Cache::put($cacheId, $jobList, $expiresAt);
                $this->log("cached ($cacheId) for 1h");
            }

            return Response::json(array(
                'cacheActive' => $cacheActive,
                'cacheId' => $cacheId,
                'results' => $jobList,
                'log' => $this->getLog(),
            ));
        }
        catch(Exception $e){

            return Response::json(array(
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'serial' => $serial,
                'cacheId' => $cacheId,
                'cacheActive' => $cacheActive,
                'log' => $this->getLog(),
            ));
        }


    }

    public function externalJobList($serial, $format = "json")
    {
        $cacheId = "empty";
        $cacheActive = false;

        $cacheForceRefresh = (Input::get('forceRefresh') == 1);

        try {

            $this->initClient($serial);

            $this->initializeFileMaker();

            $cacheId = $this->client->id . "-external-" . $format;

            $this->trackPageHit('/jobs/external/'.$format);

            if (Cache::has($cacheId) && $cacheForceRefresh == false) {
                $jobList = Cache::get($cacheId);
                $cacheActive = true;
                $this->trackEvent('joblist_extern_'.$format, 'cached call');
            }
            else {
                if($cacheForceRefresh){
                    $this->trackEvent('joblist_extern_'.$format, 'forced refresh');
                }
                else {
                    $this->trackEvent('joblist_extern_'.$format, 'normal refresh');
                }
                $records = $this->findExternalFileMakerJobs();

                $jobList = array();

                foreach ($records as $record) {

                    /** @var $record FileMaker_Record */

                    /** @var $jobId int */
                    $jobId = $record->getField('ID');

                    $externalType = $record->getField('Web Export');

                    // Just in case someone made the field into a Yes/No Selection List
                    if ($externalType == "Nein" || $externalType == "No") {
                        continue;
                    }

                    if ($jobId != intval($record->getField('ID')) || empty($jobId) || $jobId <= 0) {
                        continue;
                    }

                    $startDate = $record->getField("Start");

                    if (!empty($startDate)) {
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

                    $rewriteLink = $position_name . '-' . $city;
                    //$rewriteLink = iconv("UTF-8", "ASCII//TRANSLIT", $rewriteLink);
                    $rewriteLink = str_replace(
                        array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'),
                        array('ae', 'oe', 'ue', 'ss', 'Ae', 'Oe', 'Ue'), $rewriteLink);
                    $rewriteLink = preg_replace('/[^A-z0-9]+/', "_", $rewriteLink);

                    $rewriteLink = preg_replace("/_+/", "_", $rewriteLink);

                    //remove all underscores at the beginning and end
                    $rewriteLink = trim($rewriteLink, "_");

                    $rewriteLink = $jobId . '-' . $rewriteLink;

                    $google_title = $record->getField('Google Titel');
                    $google_desc = $record->getField('Google Beschreibung');


                    if (empty($google_title)) {
                        $title = $city . ' Jobs - ' . $position_name;
                    } else {
                        $title = $google_title;
                    }
                    $title = $this->removeHTML($title);


                    //Paneon::debug("Jobtitel:", $title);

                    /*
                     * Sichtbarkeit des Datensatzes
                     */
                    $webProject = $record->getField('Web_Projekt');

                    switch ($webProject) {
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

                    /*
                     * Format for jobs.ch
                     */
                    if ($format == "jobsch") {

                        $intro = nl2br(trim($intro));
                        $position = nl2br(trim($position));
                        $jobDescription = $position;

                        if ($this->client->name == "iRO Stocker") {
                            /*
                             *  Intro
                             */
                            $intro = str_replace("<br>", "<br />", $intro);
                            $intro = str_replace("<br/>", "<br />", $intro);

                            $introLines = explode("<br />", $intro);

                            $intro = '<h2>' . $introLines[0] . '</h2>';
                            for ($i = 1; count($introLines) > $i; $i++) {

                                $intro .= "\n" . $introLines[$i];
                                if (count($introLines) > ($i + 1)) {
                                    $intro .= '<br />';
                                }
                            }

                            /*
                             *  Job Description
                             */
                            $jobDescription = "";

                            $positionLines = explode("<br />", $position);

                            if (count($positionLines) > 0) {
                                $jobDescription .= '<b>Ihre Aufgaben:</b><br />' . "\n" . '<ul>';
                                foreach ($positionLines as $line) {
                                    $jobDescription .= "\n" . '<li>' . $line . '</li>';
                                }
                                $jobDescription .= '</ul>';
                            }

                            $candidateLines = explode("<br>", $candidate);

                            if (count($candidateLines) > 0) {
                                $jobDescription .= '<b>Ihr Profil:</b><br /><ul>';
                                foreach ($candidateLines as $line) {
                                    $jobDescription .= '<li>' . $line . '</li>';
                                }
                                $jobDescription .= '</ul>';
                            }

                            $resumeLines = explode("<br>", $resume);

                            if (count($resumeLines) > 0) {
                                $jobDescription .= '<b>Ihre Perspektiven:</b><br><ul>';
                                foreach ($resumeLines as $line) {
                                    $jobDescription .= '<li>' . $line . '</li>';
                                }
                                $jobDescription .= '</ul>';
                            }

                        }

                        $row = array(
                            'ORGANISATIONID' => $this->client->getJobsChId(),
                            'INSERATID' => trim($jobId),
                            'VORSPANN' => $this->sanitizeForXML($intro),
                            'BERUF' => $this->sanitizeForXML(nl2br(trim($title))),
                            'TEXT' => $this->sanitizeForXML($jobDescription),

                            'ORT' => $this->sanitizeForXML(nl2br(trim($city))),
                            'KONTAKT' => $this->sanitizeForXML(nl2br(trim($web_berater))),
                            'EMAIL' => $this->sanitizeForXML(nl2br(trim($web_berater_email))),
                            'DIREKT_URL' => 'http://www.stocker-hrc.ch/de/ihre-karriere/stellenangebote/detail-stellenangebot/?tx_cfvacancy_job[jobId]=' . trim($jobId),
                        );

                    } else {

                        $row = array(
                            'fm_id' => $jobId,
                            'visible' => $visible,
                            'timestamp' => $this->currentTimestamp,
                            'start_date' => $startDate,
                            'position' => $position_name,
                            'industry' => $branche,
                            'location' => $city,
                            'contact' => $web_berater,
                            'contact_mail' => $web_berater_email,
                            'lang' => ($language == 'Englisch' || strstr($language, "en")) ? 'en' : 'de',
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

                    }

                    $jobList[] = $row;
                }

                // Cache the joblist for 24 Hours
                $expiresAt = Carbon::now()->addHours(24);

                Cache::put($cacheId, $jobList, $expiresAt);
            }

            /*
             * Export for: jobs.ch
             */
            if ($format == "jobsch") {

                $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
                $xmlString .= "\n" . '<JOBS>';

                if (count($jobList) > 0) {
                    $xmlString .= "\n " . '<INSERATE>';

                    foreach ($jobList as $record) {
                        $xmlString .= "\n  " . '<INSERAT>';


                        foreach ($record as $key => $value) {
                            if (!empty($value)) {
                                $xmlString .= "\n   " . '<' . $key . '>' . $value . '</' . $key . '>';
                            }
                        }

                        $xmlString .= "\n  " . '</INSERAT>';
                    }

                    $xmlString .= "\n " . '</INSERATE>';
                }

                $xmlString .= "\n" . '</JOBS>';

                $response = Response::make($xmlString, 200)
                    ->header('Content-Type', 'text/xml');
            } else {
                $response = Response::json(array(
                    'cacheActive' => $cacheActive,
                    'cacheId' => $cacheId,
                    'results' => $jobList,
                ));
            }

            return $response;
        }
        catch(Exception $e){
            return Response::json(array(
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'serial' => $serial,
                'cacheId' => $cacheId,
                'cacheActive' => $cacheActive,
                'log' => $this->getLog(),
            ));
        }

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

            $this->trackPageHit('/data/job-detail/'.$jobId);

            $this->trackEvent('job-detail', $jobId);

            $findCommand =& $this->fm->newFindCommand('Projektliste_Web');
           //$findCommand->addFindCriterion('Web_Projekt','="Ja"');
            $findCommand->addFindCriterion('ID','="'.$jobId.'"');

            $result = $findCommand->execute();

            if(!$this->fmErrorHandling($result)){
                throw(new Exception("Kein Datensatz gefunden."));
            }

            $record = $result->getFirstRecord();

            /** @var $record FileMaker_Record */

            /** @var $jobId int */
            $jobId = $record->getField('ID');

            $markdownCheckbox = $record->getField("MarkdownCheckbox");

            $formatter = (empty($markdownCheckbox) || $markdownCheckbox != "Ja") ? "simple" : "markdown";

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
            $title = $this->removeHTML($title);


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

            if($visible == PANEON_JOB_TYPE_HIDDEN){
                // Only return data to visible or archived projects
                throw(new Exception("Kein Datensatz gefunden."));
            }

            $jobRow = array(
                'fm_id'     => $jobId,
                'visible'   => $visible,
                'timestamp' => $this->currentTimestamp,
                'formatter' => $formatter,
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

    private function formatIntro($text){
        if($this->client->name == "iRO Stocker"){}
        return $text;
    }

    private function formatJobDescription($text){
        if($this->client->name == "iRO Stocker"){

        }
        return $text;
    }

    private function sanitizeForXML($text){
        return str_replace(
            array('&',     "'",      '<',    '>',    '"',      'Ä',      'Ö',      'Ü',      'ä',      'ö',      'ü',      'ß'),
            array('&amp;', '&apos;', '&lt;', '&gt;', '&quot;', '&#196;', '&#214;', '&#220;', '&#228;', '&#246;', '&#252;', '&#223;'),
            $text
        );
    }
}
