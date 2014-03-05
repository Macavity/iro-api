<?php

class ClientsController extends BaseController {

    protected $layout = 'layouts.admin';

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{

        $clients = Client::all();

        $this->layout->content = View::make('clients.index')->with('clients', $clients);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
        $this->layout->content = View::make('clients.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{

        $rules = Client::$rules;

        $validator = Validator::make(Input::all(), $rules);

        if($validator->fails())
        {
            return Redirect::to('admin/clients/create')
                ->withErrors($validator)
                ->withInput(Input::all());
        }
        else
        {
            $client = Client::create(Input::all());
            Session::flash('message', 'Kunde '.$client->name.' erfolgreich erstellt.');
            return Redirect::to(action('ClientsController@index'));

        }
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
        $client = Client::find($id);
        $this->layout->content = View::make('clients.show')->with('client', $client);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
        $client = Client::find($id);

        $this->layout->content = View::make('clients.edit')->with('client', $client);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
        $client = Client::find($id);

		$rules = Client::$rules;

        $validator = Validator::make(Input::all(), $rules);

        if($validator->fails())
        {
            return Redirect::to(action('ClientsController@edit', $client->id))
                ->withErrors($validator)
                ->withInput(Input::all());

        }
        else {
            $client->fill(Input::all());
            $client->save();

            Session::flash('message', 'Kunde '.$client->name.' wurde gespeichert.');

            return Redirect::to(action('ClientsController@index'));
        }
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

}
