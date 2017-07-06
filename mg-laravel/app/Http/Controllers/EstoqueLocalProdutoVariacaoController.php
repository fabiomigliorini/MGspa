<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\EstoqueLocalProdutoVariacaoRepository;

class EstoqueLocalProdutoVariacaoController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\EstoqueLocalProdutoVariacaoRepository';

}
