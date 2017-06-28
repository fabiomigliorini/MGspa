<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\MarcaRepository;

class MarcaController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\MarcaRepository';

}
