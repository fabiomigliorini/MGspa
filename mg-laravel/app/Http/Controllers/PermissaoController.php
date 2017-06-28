<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\PermissaoRepository;

class PermissaoController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\PermissaoRepository';

}
