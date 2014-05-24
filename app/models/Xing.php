<?php

/**
 * Class Xing
 *
 * @property String $client_id;
 * @property String $fm_id;
 * @property String $xing_link;
 * @property String $data;
 *
 */
class Xing extends Eloquent {

	protected $guarded = array();

    protected $table = "xing";

	public static $rules = array(
        'client_id' => 'required',
        'fm_id' => 'required',
        'xing_link' => 'required',
        'data' => 'required',
    );

    public static $fieldLabels = array(
        'client_id' => 'Kunden ID',
        'fm_id' => 'FileMaker Datensatz ID',
        'xing_link' => 'XING Link',
        'data' => 'Ergebnisdaten',
    );


    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('client_id');

    public function getFieldLabels()
    {
        return Xing::$fieldLabels;
    }

}
