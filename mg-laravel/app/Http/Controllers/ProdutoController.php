<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\ProdutoRepository;

class ProdutoController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\ProdutoRepository';

}
