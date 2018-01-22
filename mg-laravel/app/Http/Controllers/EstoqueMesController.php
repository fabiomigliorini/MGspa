<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\EstoqueMesRepository;

class EstoqueMesController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\EstoqueMesRepository';

}
