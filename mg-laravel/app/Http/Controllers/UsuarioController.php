<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\UsuarioRepository;

class UsuarioController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\UsuarioRepository';

}
