<?php

namespace Mg\PedidoItem;

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
          SELECT COUNT(npbpi.codnegocioprodutobarraPedidoItemitem) AS count
          FROM tblPedidoItem p
          INNER JOIN tblPedidoItemitem pi ON (pi.codPedidoItem = p.codPedidoItem)
          INNER JOIN tblnegocioprodutobarraPedidoItemitem npbpi ON (npbpi.codPedidoItemitem = pi.codPedidoItemitem)
          WHERE p.codPedidoItem = :codPedidoItem
        ";
        $res = DB::select($sql, ['codPedidoItem' => $model->codPedidoItem]);
        if ($res[0]->count > 0) {
            $validator = Validator::make([], []);
            $validator->errors()->add('codPedidoItem', 'PedidoItem já vinculado à um Negócio!');
            throw new ValidationException($validator);
        }
        $model->delete();
        return $model;
    }

}
