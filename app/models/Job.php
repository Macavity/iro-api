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
    protected $hidden = array('serial', 'fm_user', 'fm_password');

    public function getFieldLabels()
    {
        return Client::$fieldLabels;
    }



}
