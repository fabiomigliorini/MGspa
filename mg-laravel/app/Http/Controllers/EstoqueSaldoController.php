<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\EstoqueSaldoRepository;

class EstoqueSaldoController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\EstoqueSaldoRepository';

}
