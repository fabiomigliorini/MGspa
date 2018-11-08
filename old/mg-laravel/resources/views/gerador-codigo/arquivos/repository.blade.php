<?php echo "<?php\n"; ?>

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\{{ $model }};

class {{ $model }}Repository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\{{ $model }}';

    public static function validationRules ($model = null)
    {
        $rules = [
@foreach ($validacoes as $campo => $regras)
            '{{ $campo }}' => [
@foreach ($regras as $regra => $validacao)
                '{{ $validacao['rule'] }}',
@endforeach
            ],
@endforeach
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
@foreach ($validacoes as $campo => $regras)
@foreach ($regras as $regra => $validacao)
@if (isset($validacao['mensagem']))
            '{{ $campo }}.{{ $regra }}' => '{!! $validacao['mensagem'] !!}',
@endif
@endforeach
@endforeach
        ];

        return $messages;
    }

    public static function details($model)
    {
        return parent::details ($model);
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        return parent::query ($filter, $sort, $fields);
    }

}
