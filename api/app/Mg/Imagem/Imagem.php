<?php

namespace Mg\Imagem;

use App\Models\Usuario;
use Mg\MgModel;

class Imagem extends MgModel
{
    protected $table = 'tblimagem';
    protected $primaryKey = 'codimagem';

    protected $fillable = [
        'observacoes',
        'arquivo',
    ];

    protected $casts = [
        'codimagem' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function getUrlAttribute(): string
    {
        $base = rtrim(config('services.legacy.imagens_url'), '/');
        return $base . '/' . $this->arquivo;
    }

    public function getDirectoryAttribute(): string
    {
        // Diretório de gravação local (volume compartilhado /opt/www/Arquivos/Imagens
        // ou /media/publico/Arquivos/Imagens — configurável via env IMAGEM_PATH).
        return rtrim(env('IMAGEM_PATH', public_path('imagens')), '/');
    }

    public function getPathAttribute(): string
    {
        return $this->directory . '/' . $this->arquivo;
    }
}
