<?php

/**
 * Class Client
 *
 * @property Integer    $id
 * @property String     $name
 * @property String     $host
 * @property String     $db_name
 * @property String     $serial
 * @property String     $fm_user
 * @property String     $fm_password
 *
 */
class Client extends Eloquent {

	protected $guarded = array();

	public static $rules = array(
        'name' => 'required',
        'host' => 'required',
        'db_name' => 'required',
        'serial' => 'required',
        'fm_user' => 'required',
        'fm_password' => 'required',
    );

    public static $fieldLabels = array(
        'name' => 'Name',
        'host' => 'Host',
        'db_name' => 'Datenbankname',
        'serial' => 'Seriennummer',
        'fm_user' => 'FM Benutzer',
        'fm_password' => 'FM Passwort',
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
