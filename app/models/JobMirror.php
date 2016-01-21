<?php

/**
 * Class Job_Mirror
 *
 * @property Integer $id;
 * @property Integer $client;
 * @property Integer $job_id;
 * @property String $data;
 */
class JobMirror extends Eloquent {

    protected $guarded = array('id');
    protected $fillable = array('client', 'job_id', 'data');

    public function client(){
        $this->hasOne('client');
    }
}
