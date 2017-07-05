<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\EstadoRepository;

class EstadoController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\EstadoRepository';

}
