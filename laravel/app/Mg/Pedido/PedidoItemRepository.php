<?php

namespace Mg\Pedido;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

use Mg\MgRepository;

class PedidoItemRepository extends MgRepository
{

    public static function validate ($data)
    {

        $rules = [
            'codpedido' => 'required',
            'codprodutovariacao' => 'required',
            'quantidade' => 'required|numeric',
        ];

        $validator = Validator::make($data, $rules);
        if (!$validator->passes()) {
            throw new ValidationException($validator);
        }

    }

    public static function insert ($data)
    {
        static::validate($data);
        $model = new PedidoItem();
        $model->fill($data);
        $model->indstatus = PedidoItem::STATUS_PENDENTE;
        $model->save();
        return $model;
    }

    public static function update (PedidoItem $model, $data)
    {
        $model->fill($data);
        $data = $model->getAttributes();
        static::validate($data);
        $model->save();
        return $model;
    }

    public static function delete (PedidoItem $model)
    {
        $sql = "
          SELECT COUNT(npbpi.codnegocioprodutobarrapedidoitem) AS count
          FROM tblpedidoitem pi
          INNER JOIN tblnegocioprodutobarrapedidoitem npbpi ON (npbpi.codpedidoitem = pi.codpedidoitem)
          WHERE pi.codpedidoitem = :codpedidoitem
        ";
        $res = DB::select($sql, ['codpedidoitem' => $model->codpedidoitem]);
        if ($res[0]->count > 0) {
            $validator = Validator::make([], []);
            $validator->errors()->add('codpedidoitem', 'Item já vinculado à um Negócio!');
            throw new ValidationException($validator);
        }
        $model->delete();
        return $model;
    }

}
