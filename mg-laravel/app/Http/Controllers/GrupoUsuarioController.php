<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\GrupoUsuarioRepository;

class GrupoUsuarioController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\GrupoUsuarioRepository';
}
