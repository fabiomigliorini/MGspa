<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\FilialRepository;

class FilialController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\FilialRepository';

}
