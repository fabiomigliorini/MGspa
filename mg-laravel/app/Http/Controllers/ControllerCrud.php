<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ControllerCrud extends Controller
{

    protected $repositoryName = '';

    public function show(Request $request, $id)
    {
        $this->authorize();
        $model = app($this->repositoryName)::findOrFail($id);
        $details = app($this->repositoryName)::details($model);
        return response()->json($details, 200);
    }

    public function index(Request $request)
    {
        $this->authorize();
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = app($this->repositoryName)::query($filter, $sort, $fields);
        return response()->json($qry->paginate()->appends($request->all()), 206);
    }

    public function store(Request $request)
    {
        $this->authorize();
        $model = app($this->repositoryName)::new($request->all());
        app($this->repositoryName)::validate($model);
        $model = app($this->repositoryName)::create($model);
        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorize();
        $model = app($this->repositoryName)::findOrFail($id);
        $model = app($this->repositoryName)::fill($model, $request->all());
        app($this->repositoryName)::validate($model);
        $model = app($this->repositoryName)::update($model);
        return response()->json($model, 200);
    }

    public function destroy(Request $request, $id)
    {
        $this->authorize();
        $model = app($this->repositoryName)::findOrFail($id);
        app($this->repositoryName)::delete($model);
        return response()->json($model, 204);
    }

    public function activate(Request $request, $id) {
        $this->authorize();
        $model = app($this->repositoryName)::findOrFail($id);
        $model = app($this->repositoryName)::activate($model);
        return response()->json($model, 200);
    }

    public function inactivate(Request $request, $id) {
        $this->authorize();
        $model = app($this->repositoryName)::findOrFail($id);
        $model = app($this->repositoryName)::inactivate($model);
        return response()->json($model, 200);
    }

}
