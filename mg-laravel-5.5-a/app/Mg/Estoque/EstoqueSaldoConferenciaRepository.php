<?php
namespace Mg\Estoque;
use Mg\MgRepository;
use DB;

class EstoqueSaldoConferenciaRepository extends MgRepository
{
    public static function criaConferencia() {

        DB::beginTransaction();

        try {
            $esc = new EstoqueSaldoConferencia();
            if (!$model->save()) {
              throw new Exception('Erro ao Salvar Movimento!');
            }

            DB::commit();

        } catch (Exception $ex) {
            DB::rollBack();
            throw new \Exception('Erro ao Salvar!');
        }
    }
}
