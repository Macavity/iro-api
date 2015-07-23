<?php

/**
 * Class Job
 *
 * @property String $name;
 * @property String $host;
 * @property String $db_name;
 * @property String $serial;
 * @property String $fm_user;
 * @property String $fm_password;
 *
 */
class Job extends Eloquent {

    protected $guarded = array();

    public static $rules = array(
    );

    public static $fieldLabels = array(
    );


    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array();

    /**
     * @var FileMaker_Record
     */
    private $record;

    /**
     * @param FileMaker_Record $record
     * @throws Exception
     */
    public function __construct($record){

        /** @var $jobId int */
        $jobId = $record->getField('ID');

        if($jobId != intval($jobId) || empty($jobId) || $jobId <= 0){
            throw(new Exception("Malformed Data"));
        }

        $this->record = $record;

    }

    public function getData(){

        $record = $this->record;

        $jobId = $record->getField('ID');

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
        $title = Paneon\PaneonHelper\Paneon::removeHTML($title);


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
            'objectID'     => $jobId,
            'fm_id'     => $jobId,
            'visible'   => $visible,
            'timestamp' => time(),
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
        return $row;
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
