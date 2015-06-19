<?php

/**
 * Response in XML form
 * Sample $vars:
 *  [
 *      'job' => [
 *          '@a' => 'Sample attribute',
 *          'b' => 'Sample value'
 *      ]
 *  ];
 */
Response::macro('xml', function($rootElement = 'response', $vars = array(), $status = 200, $header = array(), $xml = null)
{
    if (is_null($xml)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$rootElement.'/>');
    }

    foreach ($vars as $key => $value) {
        if (is_array($value)) {
            Response::xml($value, $status, $header, $xml->addChild($key));
        }
        else {
            if( preg_match('/^@.+/', $key) ) {
                $attributeName = preg_replace('/^@/', '', $key);
                $xml->addAttribute($attributeName, $value);
            } else {
                $xml->addChild($key, $value);
            }
        }
    }

    if (empty($header)) {
        $header['Content-Type'] = 'application/xml';
    }

    return Response::make($xml->asXML(), $status, $header);
});
