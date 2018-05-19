<?php echo "<?php\n"; ?>

namespace App\Models;

/**
 * Campos
@foreach ($cols as $col)
 * @property {{ str_pad($col->tipo, 30, ' ') }} {{ str_pad(trim("\${$col->column_name}"), 35, ' ') }} {!! $col->comentario !!}
@endforeach
 *
 * Chaves Estrangeiras
@foreach ($pais as $rel)
 * @property {{ str_pad($rel->model, 30, ' ') }} ${{ $rel->propriedade }}
@endforeach
 *
 * Tabelas Filhas
@foreach ($filhas as $rel)
 * @property {{str_pad($rel->model . '[]', 30, ' ')}} ${{ $rel->model }}S
@endforeach
 */

class {{ $model }} extends MGModel
{
    protected $table = '{{$tabela}}';
    protected $primaryKey = '{{str_replace('tbl', 'cod', $tabela)}}';
    protected $fillable = [
@foreach ($cols as $col)
@if (!in_array($col->column_name, [str_replace('tbl', 'cod', $tabela), 'criacao', 'codusuariocriacao', 'alteracao', 'codusuarioalteracao', 'inativo']))
        '{{$col->column_name}}',
@endif
@endforeach
    ];
    protected $dates = [
@foreach ($cols as $col)
@if (in_array($col->udt_name, ['date', 'timestamp']) )
        '{{$col->column_name}}',
@endif
@endforeach
    ];

    // Chaves Estrangeiras
@foreach ($pais as $rel)
    public function {{ $rel->propriedade }}()
    {
        return $this->belongsTo({{$rel->model}}::class, '{{$rel->column_name}}', '{{$rel->foreign_column_name}}');
    }

@endforeach
    // Tabelas Filhas
@foreach ($filhas as $rel)
    public function {{ $rel->model }}S()
    {
        return $this->hasMany({{$rel->model}}::class, '{{$rel->column_name}}', '{{$rel->foreign_column_name}}');
    }

@endforeach

}
