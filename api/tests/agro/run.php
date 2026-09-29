<?php

/**
 * Bateria de conferência do agro — como usar e o que cada camada faz: README.md.
 *
 *   docker exec -u www-data -w /opt/www/MGspa/api mgspa-api php tests/agro/run.php <camada> [opções]
 */

declare(strict_types=1);

foreach (['Ambiente', 'Api', 'Relatorio', 'Referencia', 'Massa', 'Patio', 'Invariantes', 'Cenario'] as $arquivo) {
    require __DIR__ . "/lib/{$arquivo}.php";
}
foreach (glob(__DIR__ . '/cenarios/*.php') as $arquivo) {
    require $arquivo;
}
require __DIR__ . '/estresse/Aparelho.php';
require __DIR__ . '/estresse/Estresse.php';

use AgroBateria\Ambiente;
use AgroBateria\Api;
use AgroBateria\Cenarios;
use AgroBateria\Estresse\Estresse;
use AgroBateria\Invariantes;
use AgroBateria\Massa;
use AgroBateria\Patio;
use AgroBateria\Relatorio;

const CAMADAS = [
    'cenarios' => [Cenarios\Fluxo::class, Cenarios\Contrato::class, Cenarios\Silos::class],
    'corrida' => [Cenarios\Corrida::class],
    'desconto' => [Cenarios\Desconto::class],
    'valores' => [Cenarios\Valores::class],
    'listagem' => [Cenarios\Listagem::class],
    'permissoes' => [Cenarios\Permissoes::class],
    'estresse' => [Estresse::class],
];

$camada = null;
$opc = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([\w-]+)(?:=(.*))?$/', $arg, $m)) {
        $opc[$m[1]] = $m[2] ?? true;
    } elseif ($camada === null) {
        $camada = $arg;
    }
}
if (isset($opc['rampa'])) {
    $opc['rampa'] = array_map('intval', explode(',', (string) $opc['rampa']));
}

$validas = array_merge(['conferir', 'limpar', 'recolher', 'todos'], array_keys(CAMADAS));
if (!in_array($camada, $validas, true)) {
    fwrite(STDERR, "Uso: php tests/agro/run.php <" . implode('|', $validas) . "> [--usuario=] [--manter] [--relatorio]\n"
        . "     estresse: [--aparelhos=3] [--caminhoes=150] [--rampa=3,6,12,24] [--minutos=3] [--semente=2809]\n"
        . "     desconto: [--vetores=10000]\n");
    exit(2);
}

Ambiente::iniciar();
$r = new Relatorio();
$cabecalho = ['Camada' => $camada, 'API' => Ambiente::urlApi()];
$inicio = microtime(true);

if ($camada === 'conferir') {
    echo "Conferência SÓ LEITURA do banco inteiro (o mesmo SQL de conferencia.sql)\n";
    Invariantes::rodar($r, 'tudo');
} elseif ($camada === 'limpar') {
    $n = Massa::limpar();
    echo 'Massa ZZTESTE apagada: ' . json_encode($n) . "\n";
    exit($n['restantes'] === 0 ? 0 : 1);
} elseif ($camada === 'recolher') {
    echo 'Massa ZZTESTE recolhida do pátio (continua na listagem): ' . json_encode(Massa::recolher()) . "\n";
    exit(0);
} else {
    if (Massa::existe()) {
        echo 'Massa ZZTESTE de uma rodada anterior encontrada; apagando antes de começar: ' . json_encode(Massa::limpar()) . "\n";
    }
    $usuario = Ambiente::usuario($opc['usuario'] ?? null);
    $token = Ambiente::token($usuario);
    $cabecalho['Usuário'] = "{$usuario->usuario} ({$usuario->codusuario})";
    echo "Bateria do agro — {$cabecalho['API']} — usuário {$cabecalho['Usuário']}\n";

    $manter = isset($opc['manter']) || in_array($camada, ['estresse', 'todos'], true);
    try {
        $m = Massa::montar();
        $p = new Patio(new Api(Ambiente::urlApi(), $token['token']), $m);
        $classes = $camada === 'todos' ? array_merge(...array_values(CAMADAS)) : CAMADAS[$camada];
        foreach ($classes as $classe) {
            (new $classe($p, $r, $opc))->rodar();
        }
        if ($camada !== 'estresse') {
            $r->camada('Conferência da massa (invariantes de saldo)');
            Invariantes::rodar($r, 'zz', 'Z/');
        }
    } finally {
        Ambiente::revogar($token['id']);
        if ($manter) {
            echo "\nMassa ZZTESTE mantida e recolhida do pátio: " . json_encode(Massa::recolher())
                . "\nConfira em /cargas com o período limpo e o motorista \"ZZTESTE\". Para apagar: run.php limpar\n";
        } else {
            $n = Massa::limpar();
            echo "\nMassa ZZTESTE apagada (restantes: {$n['restantes']})\n";
        }
    }
}

$cabecalho['Duração'] = round(microtime(true) - $inicio) . ' s';
echo "\n" . $r->sumario() . ' — ' . $cabecalho['Duração'] . "\n";
if (isset($opc['relatorio'])) {
    $arq = Ambiente::arquivo(date('Y-m-d-Hi') . "-{$camada}.md");
    file_put_contents($arq, $r->markdown($cabecalho));
    echo "Relatório: {$arq}\n";
}
exit($r->temFalha() ? 1 : 0);
