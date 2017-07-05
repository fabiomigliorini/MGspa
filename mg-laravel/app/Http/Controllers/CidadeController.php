<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\CidadeRepository;

class CidadeController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\CidadeRepository';

}
