<?php

namespace Mg\NFePHP;

use Illuminate\Support\Facades\DB;

use Mg\NotaFiscal\NotaFiscal;

class NFePHPRoboService
{
    public static function pendentes($minutos, $per_page = 50, $current_page = 1, $desc = false)
    {
        $desc = ($desc) ? 'DESC' : 'ASC';
        $offset = ($per_page * ($current_page - 1));
        $sql = "
            select
            	nf.codnotafiscal,
            	nf.modelo,
            	nf.serie,
            	nf.numero,
            	f.filial,
            	p.fantasia,
            	no.naturezaoperacao,
            	nf.emissao,
            	nf.valortotal,
            	nf.nfechave,
            	nf.nfeautorizacao,
            	nf.nfecancelamento,
            	nf.nfeinutilizacao
            from tblnotafiscal nf
            inner join tblfilial f on (f.codfilial = nf.codfilial)
            inner join tblpessoa p on (p.codpessoa = nf.codpessoa)
            inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = nf.codnaturezaoperacao)
            where nf.emitida = true
            and nf.nfeautorizacao is null
            and nf.nfecancelamento is null
            and nf.nfeinutilizacao is null
            and nf.numero != 0
            and (
                nf.emissao >= (now() - '{$minutos} minutes'::interval)
                -- NFC-e emitida offline tem 24h de prazo legal para ser transmitida.
                -- Com a janela padrao de 60 min ela simplesmente nunca era retentada.
                -- 26h da margem sobre o prazo. Nao subir a janela geral: o robo passaria
                -- a reprocessar toda nota rejeitada de vez a cada 10 min, em chamada
                -- SEFAZ inutil.
                or (nf.tpemis = 9 and nf.emissao >= (now() - '26 hours'::interval))
            )
            order by emissao {$desc}, codnotafiscal {$desc}
            limit {$per_page} offset {$offset}
        ";
        return DB::select($sql);
    }

    public static function resolverPendentes($minutos, $per_page = 10, $current_page = 1, $desc = false)
    {
        // carrega notas pendentes
        $pendentes = NFePHPRoboService::pendentes($minutos, $per_page, $current_page, $desc);
        $ret = [];

        // percorre as pendentes e chama metodo para resolver cada uma das notas
        foreach ($pendentes as $pendente) {
            $nf = NotaFiscal::findOrFail($pendente->codnotafiscal);
            $ret[$nf->codnotafiscal] = (object) $pendente;
            $ret[$nf->codnotafiscal]->resultado = static::resolver($nf);
        }

        return $ret;
    }

    public static function resolvido(NotaFiscal $nf)
    {
        if (!empty($nf->nfeautorizacao)) {
            return true;
        }
        if (!empty($nf->nfeinutilizacao)) {
            return true;
        }
        if (!empty($nf->nfecancelamento)) {
            return true;
        }
        return false;
    }

    public static function resolver(NotaFiscal $nf)
    {
        if (static::resolvido($nf)) {
            return (object) ['resolvido' => true];
        }
        // Tenta Enviar
        try {
            $resEnvioSincrono = NFePHPService::enviarSincrono($nf);
            $nf = $nf->fresh();

            // Se excecao no envio, provavelmente por problema na geracao do XML
        } catch (\Exception $e) {

            // tenta criar o XML e enviar novamente
            try {
                $resCriar = NFePHPService::criar($nf);
                $nf = $nf->fresh();
                $resEnvioSincrono = NFePHPService::enviarSincrono($nf);
                $nf = $nf->fresh();

                // se ainda assim der excecao, retorna erro
            } catch (\Exception $e2) {
                return (object) [
                    'resolvido' => false,
                    'erro' => $e2->getMessage()
                ];
            }
        }

        // se resolveu com procedimento acima, retorna sucesso
        // (o e-mail sai sozinho: quem despacha o NFePHPMailJob e o vincularProtocolo-
        // Autorizacao do NFePHPService, na transicao da nota para autorizada)
        if (static::resolvido($nf)) {
            return (object) [
                'resolvido' => true,
                'resEnvioSincrono' => $resEnvioSincrono,
            ];
        }

        // tenta consultar
        try {
            $resConsulta = NFePHPService::consultar($nf);
            $nf = $nf->fresh();

            // se excecao ao consultar, retorna mensagem
        } catch (\Exception $e) {
            return (object) [
                'resolvido' => false,
                'resEnvioSincrono' => $resEnvioSincrono,
                'resConsulta' => $e->getMessage()
            ];
        }

        // se resolveu com procedimento acima, retorna sucesso
        if (static::resolvido($nf)) {
            return (object) [
                'resolvido' => true,
                'resEnvioSincrono' => $resEnvioSincrono,
                'resConsulta' => $resConsulta,
            ];
        }

        // se ainda nao tinha criado o arquivo XML novo, tenta enviar criando
        if (empty($resCriar)) {

            // tenta recriar o arquivo xml e enviar novamente
            try {
                $resCriar = NFePHPService::criar($nf);
                $nf = $nf->fresh();
                $resEnvioSincrono = NFePHPService::enviarSincrono($nf);
                $nf = $nf->fresh();

                // caso execao, retorna mensagem
            } catch (\Exception $e) {
                return (object) [
                    'resolvido' => false,
                    'resEnvioSincrono' => $resEnvioSincrono ?? $e->getMessage(),
                    'resConsulta' => $resConsulta ?? null,
                    'resCriar' => $resCriar ?? $e->getMessage(),
                ];
            }

            // se resolveu, retorna sucesso
            if (static::resolvido($nf)) {
                return (object) [
                    'resolvido' => true,
                    'resEnvioSincrono' => $resEnvioSincrono,
                    'resCriar' => $resCriar,
                ];
            }
        }

        // se nao conseguiu resolver, retorna resultados das tentativas
        return (object) [
            'resolvido' => false,
            'resEnvioSincrono' => $resEnvioSincrono ?? null,
            'resConsulta' => $resConsulta ?? null,
            'resCriar' => $resCriar ?? null
        ];
    }
}
