<?php

class DataController extends BaseController {

    private $serialNumber;


    public function jobListAll($serial)
    {
        $this->initClient($serial);

        $this->initializeFileMaker();

        $findCommand =& $this->fm->newFindCommand('Projektliste_Web');
        $findCommand->addFindCriterion('Web_Projekt','="Ja"');

        $result = $findCommand->execute();

        $records = $result->getRecords();

        foreach($records as $record){

            /** @var $record FileMaker_Record */

            /** @var $jobId int */
            $jobId = $record->getField('ID');

            if($jobId != intval($record->getField('ID')) || empty($jobId) || $jobId <= 0){
                Paneon::debug("Überspringe wegen ungültiger JobId: JobId ".$record->getField('ID').", ProjektName: ".$record->getField('ProjektName'));
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

            $google_title = $record->getField('Google Titel');
            $google_desc = $record->getField('Google Beschreibung');


            if(empty($google_title)){
                $title = $city.' Jobs - '.$position_name.' | Pape.de';
            }
            else{
                $title = $google_title.' | Pape.de';
            }
            $title = Paneon::removeHTML($title);


            Paneon::debug("Jobtitel:", $title);

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
            Paneon::debug("Sichtbarkeit:", $visible);

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
            );
        }


    }

    public function jobList($serial, $start, $count)
    {
        $this->initClient($serial);

    }

    public function jobDetail($serial, $jobId)
    {
        $this->initClient($serial);

        $this->initializeFileMaker();

        $this->fmRecord = $this->findFileMakerRecord($fmId);



    }
}