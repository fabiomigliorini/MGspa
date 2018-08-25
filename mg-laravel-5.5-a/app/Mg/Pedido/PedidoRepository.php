<?php

namespace Mg\Pedido;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

use Mg\MgRepository;

class PedidoRepository extends MgRepository
{

    public static function validate ($data)
    {

        $rules = [
            'codestoquelocal' => 'required',
            'indtipo' => [
                'required',
                Rule::in([Pedido::TIPO_VENDA, Pedido::TIPO_COMPRA, Pedido::TIPO_TRANSFERENCIA]),
            ]
        ];

        $validator = Validator::make($data, $rules);
        if (!$validator->passes()) {
            throw new ValidationException($validator);
        }

        if ($data['indtipo'] == Pedido::TIPO_TRANSFERENCIA) {
            if (empty($data['codestoquelocalorigem'])) {
                $validator->errors()->add('codestoquelocalorigem', 'Local de Estoque de Origem não informado!');
                throw new ValidationException($validator);
            }
            if (!empty($data['codgrupoeconomico'])) {
                $validator->errors()->add('codgrupoeconomico', 'Grupo Economico não deve ser informado para transferências!');
                throw new ValidationException($validator);
            }
        } else {
            if (!empty($data['codestoquelocalorigem'])) {
                $validator->errors()->add('codestoquelocalorigem', 'Local de Estoque de Origem não deve ser informado quando não for uma transferência!');
                throw new ValidationException($validator);
            }
            if (empty($data['codgrupoeconomico']) && $data['indtipo'] == Pedido::TIPO_COMPRA) {
                $validator->errors()->add('codgrupoeconomico', 'Grupo Economico deve ser informado para Compra!');
                throw new ValidationException($validator);
            }
        }

    }

    public static function insert ($data)
    {
        static::validate($data);
        $model = new Pedido();
        $model->fill($data);
        $model->indstatus = Pedido::STATUS_PENDENTE;
        $model->save();
        return $model;
    }

    public static function update (Pedido $model, $data)
    {
        $model->fill($data);
        $data = $model->getAttributes();
        static::validate($data);
        $model->save();
        return $model;
    }

    public static function delete (Pedido $model)
    {
        $sql = "
          SELECT COUNT(npbpi.codnegocioprodutobarrapedidoitem) AS count
          FROM tblpedido p
          INNER JOIN tblpedidoitem pi ON (pi.codpedido = p.codpedido)
          INNER JOIN tblnegocioprodutobarrapedidoitem npbpi ON (npbpi.codpedidoitem = pi.codpedidoitem)
          WHERE p.codpedido = :codpedido
        ";
        $res = DB::select($sql, ['codpedido' => $model->codpedido]);
        if ($res[0]->count > 0) {
            $validator = Validator::make([], []);
            $validator->errors()->add('codpedido', 'Pedido já vinculado à um Negócio!');
            throw new ValidationException($validator);
        }
        $model->delete();
        return $model;
    }

}
