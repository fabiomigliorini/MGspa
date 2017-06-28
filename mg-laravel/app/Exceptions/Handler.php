<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     *▶ @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response

     */
    public function render($request, Exception $e)
    {
        if ($e instanceof HttpException) {
            if ($e->getStatusCode() == 403) {
                return response()->json(['mensagem' => 'Operação não autorizada para seu usuário'], 403);
            }
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'mensagem' => 'Erro de validação!',
                'erros' => $e->validator->errors()
            ], 422);
        }

        if ($e instanceof QueryException) {
            $codigo = $e->getCode();
            $mensagem = 'Falha na Execução no Banco de Dados!';
            $erro = $e->getMessage();
            $sql = $e->getSql();
            if ($codigo == 23503) {
                if (strtolower(substr($sql, 0, 6)) == 'delete') {
                    $mensagem = 'Impossível excluir registro! Ele já está sendo utilizado!';
                } else {
                    $mensagem = 'Violação de chave estrangeira ao executar operação no Banco de Dados!';
                }
            }
            return response()->json([
                'mensagem' => $mensagem,
                'erros' => [$codigo => $erro],
                'sql' => $sql,
                'bindings' => $e->getBindings(),
            ], 409);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json(['mensagem' => 'Registro não encontrado'], 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json(['mensagem' => 'Página não encontrada'], 404);
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['mensagem' => 'Usuário não autenticado'], 401);
        }
        return redirect()->guest(route('auth/login'));
    }
}
