<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\PaisRepository;

class PaisController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\PaisRepository';

}
