<?php

/**
 * Class Client
 *
 * @property Integer    $id
 * @property String     $name
 * @property String     $host
 * @property String     $db_name
 * @property String     $serial
 * @property Integer    $version
 * @property String     $fm_user
 * @property String     $fm_password
 * @property String     $cache_type
 * @property Integer    $cache_time
 * @property Integer    $cache_time_detail
 * @property String     $last_refresh
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
        'version' => 'API Version',
        'fm_user' => 'FM Benutzer',
        'fm_password' => 'FM Passwort',
        'cache_time' => 'Cache Dauer (Liste)',
        'cache_time_detail' => 'Cache Dauer (Single)',
        'cache_type' => 'Art des Caches',
        'api_token' => 'API Token',
        'last_refresh' => 'Letzter Import',
    );


    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('serial', 'fm_user', 'fm_password', 'api_token');

    public function getFieldLabels()
    {
        return Client::$fieldLabels;
    }

    public function getCacheId($type){
        switch($type){
            case 'joblist-algolia':
                return $this->id."-joblist-algolia";
            case 'joblist':
                return $this->id."-joblist-normal";
            case 'joblist-archive':
                return $this->id."-joblist-archive";
        }
    }

    public function getJobsChId(){
        return 30543;
    }
}
