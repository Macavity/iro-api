<?php

namespace Paneon\PaneonHelper;

class Paneon {

    private $log = array();

    public static function debug($label, $data = "", $logLevel = 3){
        global $config, $debugLog;

        $string = "";

        if(is_array($label) || is_object($label)){
            $string .= " ".print_r($label,true)." ";
        }
        else{
            $string .= " $label ";
        }

        if($data === ""){
        }
        elseif(is_array($data) || is_object($data)){
            $string .= " ".print_r($data,true)." ";
        }
        else{
            $string .= " $data ";
        }

        if(empty($config['log_output']) || $config['log_output'] == FALSE){
            $output = "\n<!-- ".$string." -->";
        }
        else{
            $output = "\n<br>".$string;
        }

        $debugLog[] = $string;

        if(!defined("PAPE_LOG_LEVEL") || PAPE_LOG_LEVEL >= $logLevel){
            echo $output;
        }

    }

    /**
     * @param $dateString
     * @return \DateTime
     */
    public static function fm12TimeToTimestamp($dateString){
        // 07 16:12:18.12.2015
        // MM HH:mm:ss/DD/YYYY
        $dateTime = \DateTime::createFromFormat("m H:i:s.d.Y", $dateString);

        return $dateTime;
    }

    public static function removeHTML($text){

        // FM 12 liefert decodierte Entities
        $text = html_entity_decode($text);


        // Alle Tags entfernen
        $text = strip_tags($text);

        $text = str_replace("&lt;br&gt;", "", $text);
        $text = str_replace("&amp;lt;br&amp;gt;", "", $text);


        return $text;
    }
}
