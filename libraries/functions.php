<?php

function getContent($file, $params = array())
{
    ob_start();

    foreach($params as $key => $value){
        $$key = $value;
    }

    include('views/'.$file);

    $template = ob_get_contents();

    ob_end_clean();

    return $template;
}

function getLabel($key){


    switch($key){
        case 'PersonenID_Adressen::Ort': return "Adresse - Ort";
        case 'PersonenID_Adressen::Land': return "Adresse - Land";
        case 'PersonenID_Adressen::PLZ': return "Adresse - PLZ";
        case 'PersonenID_Adressen::Strasse':  return "Adresse - Strasse";
        case 'FON | PRIV': return "Privat - Telefon";
        case 'MAIL | PRIV': return "Privat - E-Mail";
        case 'MOBIL': return "Mobil";
        case 'FON | BIZ': return "Geschäft - Telefon";
        case 'MAIL | BIZ': return "Geschäft - E-Mail";

        default:
            return $key;
    }
}