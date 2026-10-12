<?php

namespace AgroBateria;

/**
 * Saída da bateria: uma linha por cenário na tela, na hora em que ele termina,
 * e o mesmo conteúdo em markdown com --relatorio.
 *
 * Níveis: OK, FALHA (regra quebrada), INFO (medida, não reprova) e ERRO (o
 * próprio cenário não conseguiu rodar — conta como falha).
 */
final class Relatorio
{
    private array $itens = [];
    private array $blocos = [];
    private string $camada = '';

    public function camada(string $nome): void
    {
        $this->camada = $nome;
        $this->blocos[] = ['camada', $nome];
        echo "\n== {$nome}\n";
    }

    public function ok(string $id, string $titulo, string $detalhe = ''): void
    {
        $this->add('OK', $id, $titulo, $detalhe);
    }

    public function falha(string $id, string $titulo, string $detalhe = ''): void
    {
        $this->add('FALHA', $id, $titulo, $detalhe);
    }

    public function info(string $id, string $titulo, string $detalhe = ''): void
    {
        $this->add('INFO', $id, $titulo, $detalhe);
    }

    public function erro(string $id, string $titulo, string $detalhe = ''): void
    {
        $this->add('ERRO', $id, $titulo, $detalhe);
    }

    /** OK ou FALHA conforme a condição; devolve a condição. */
    public function checar(string $id, string $titulo, bool $cond, string $detalhe = ''): bool
    {
        $this->add($cond ? 'OK' : 'FALHA', $id, $titulo, $detalhe);
        return $cond;
    }

    private function add(string $nivel, string $id, string $titulo, string $detalhe): void
    {
        $this->itens[] = compact('nivel', 'id', 'titulo', 'detalhe') + ['camada' => $this->camada];
        $this->blocos[] = ['item', count($this->itens) - 1];
        echo sprintf("[%-5s] %-6s %s%s\n", $nivel, $id, $titulo, $detalhe !== '' ? ' — ' . $detalhe : '');
    }

    /** Tabela solta (métricas do estresse, invariantes com linhas). */
    public function tabela(string $titulo, array $cabecalho, array $linhas): void
    {
        $this->blocos[] = ['tabela', $titulo, $cabecalho, $linhas];
        echo "\n  {$titulo}\n";
        $larg = array_map('mb_strlen', $cabecalho);
        foreach ($linhas as $l) {
            foreach (array_values($l) as $i => $v) {
                $larg[$i] = max($larg[$i] ?? 0, mb_strlen((string) $v));
            }
        }
        $fmt = function (array $l) use ($larg) {
            $cel = [];
            foreach (array_values($l) as $i => $v) {
                $v = (string) $v;
                $cel[] = $v . str_repeat(' ', max(0, $larg[$i] - mb_strlen($v)));
            }
            return '  ' . implode('  ', $cel) . "\n";
        };
        echo $fmt($cabecalho);
        foreach ($linhas as $l) {
            echo $fmt($l);
        }
    }

    public function temFalha(): bool
    {
        foreach ($this->itens as $i) {
            if ($i['nivel'] === 'FALHA' || $i['nivel'] === 'ERRO') {
                return true;
            }
        }
        return false;
    }

    public function contagem(): array
    {
        $n = ['OK' => 0, 'FALHA' => 0, 'INFO' => 0, 'ERRO' => 0];
        foreach ($this->itens as $i) {
            $n[$i['nivel']]++;
        }
        return $n;
    }

    public function sumario(): string
    {
        $n = $this->contagem();
        return "OK {$n['OK']} · FALHA {$n['FALHA']} · INFO {$n['INFO']} · ERRO {$n['ERRO']}";
    }

    public function markdown(array $cabecalho): string
    {
        $md = "# Bateria do agro — " . date('d/m/Y H:i') . "\n\n";
        foreach ($cabecalho as $k => $v) {
            $md .= "- **{$k}:** {$v}\n";
        }
        $md .= "- **Resultado:** " . $this->sumario() . "\n";
        $aberta = false;
        foreach ($this->blocos as $b) {
            if ($b[0] === 'camada') {
                $md .= "\n## {$b[1]}\n\n| | Id | Cenário | Detalhe |\n|---|---|---|---|\n";
                $aberta = true;
            } elseif ($b[0] === 'item') {
                $i = $this->itens[$b[1]];
                if (!$aberta) {
                    $md .= "\n| | Id | Cenário | Detalhe |\n|---|---|---|---|\n";
                    $aberta = true;
                }
                $md .= '| ' . $i['nivel'] . ' | ' . $i['id'] . ' | ' . static::esc($i['titulo']) . ' | ' . static::esc($i['detalhe']) . " |\n";
            } else {
                [, $titulo, $cab, $linhas] = $b;
                $md .= "\n**{$titulo}**\n\n| " . implode(' | ', $cab) . " |\n|" . str_repeat('---|', count($cab)) . "\n";
                foreach ($linhas as $l) {
                    $md .= '| ' . implode(' | ', array_map(fn ($v) => static::esc((string) $v), array_values($l))) . " |\n";
                }
                $aberta = false;
            }
        }
        return $md;
    }

    private static function esc(string $s): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], $s);
    }

    /** 12345.678 -> "12.345,678" (casas conforme o valor). */
    public static function kg(null|int|float|string $v, int $casas = -1): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
        $f = (float) $v;
        if ($casas < 0) {
            $casas = abs($f - round($f)) < 0.0005 ? 0 : 3;
        }
        return number_format($f, $casas, ',', '.');
    }
}
