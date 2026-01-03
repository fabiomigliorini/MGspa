<?php

namespace Mg\NotaFiscal\Requests;

use DateTime;
use DateInterval;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\Pessoa\Pessoa;
use Mg\Estoque\EstoqueLocal;

class NotaFiscalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            'codestoquelocal' => 'nullable|integer|exists:tblestoquelocal,codestoquelocal',
            'codpessoa' => 'required|integer|exists:tblpessoa,codpessoa',
            'codnaturezaoperacao' => 'required|integer|exists:tblnaturezaoperacao,codnaturezaoperacao',

            'emitida' => 'required|boolean',
            'modelo' => 'required|integer|in:55,65',
            'serie' => 'required|integer',
            'numero' => 'nullable|integer',
            'nfechave' => 'nullable|string|max:44',

            'emissao' => 'required|date',
            'saida' => 'required|date',

            // Valores
            'valordesconto' => 'nullable|numeric|min:0',
            'valorfrete' => 'nullable|numeric|min:0',
            'valorseguro' => 'nullable|numeric|min:0',
            'valoroutras' => 'nullable|numeric|min:0',

            // Transporte
            'frete' => 'nullable|integer|in:0,1,2,3,4,9',
            'codpessoatransportador' => 'nullable|integer|exists:tblpessoa,codpessoa',
            'volumes' => 'nullable|integer',
            'pesobruto' => 'nullable|numeric|min:0',
            'pesoliquido' => 'nullable|numeric|min:0',
            'placa' => 'nullable|string|max:10',
            'codestadoplaca' => 'nullable|integer|exists:tblestado,codestado',

            // Observacoes
            'observacoes' => 'nullable|string|max:1500',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validaFrete($validator);
            $this->validaPlaca($validator);
            $this->validaCodEstadoPlaca($validator);
            $this->validaEstoqueLocal($validator);
            $this->validaModelo($validator);
            $this->validaNumero($validator);
            $this->validaSaida($validator);
            $this->validaChaveNFE($validator);
            $this->validaPessoaPelaChaveNFE($validator);
            $this->validaSeriePelaChaveNFE($validator);
            $this->validaNumeroPelaChaveNFE($validator);
            $this->validaEmissaoPelaChaveNFE($validator);
        });
    }

    protected function validaFrete(Validator $validator): void
    {
        $frete = $this->input('frete');
        $placa = $this->input('placa');
        $codpessoatransportador = $this->input('codpessoatransportador');

        if ($frete === '' || $frete === null) {
            $validator->errors()->add('frete', 'Informe a modalidade de Frete!');
            return;
        }

        if (empty($placa) && empty($codpessoatransportador)) {
            return;
        }

        if ($frete == NotaFiscalService::FRETE_SEM) {
            $validator->errors()->add('frete', "Quando informada placa ou transportador, não pode selecionar 'Sem Frete'!");
        }
    }

    protected function validaPlaca(Validator $validator): void
    {
        $placa = $this->input('placa');

        if (empty($placa)) {
            return;
        }

        // Padrao Placa Modelo Antigo
        if (preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa)) {
            return;
        }
        // Padrao Placa Mercosul Carro
        if (preg_match('/^[A-Z]{3}[0-9]{1}[A-Z]{1}[0-9]{2}$/', $placa)) {
            return;
        }
        // Padrao Placa Mercosul Moto
        if (preg_match('/^[A-Z]{3}[0-9]{2}[A-Z]{1}[0-9]{1}$/', $placa)) {
            return;
        }

        $validator->errors()->add('placa', 'Placa inválida!');
    }

    protected function validaCodEstadoPlaca(Validator $validator): void
    {
        $placa = $this->input('placa');
        $codestadoplaca = $this->input('codestadoplaca');

        if (empty($placa)) {
            return;
        }

        if (empty($codestadoplaca)) {
            $validator->errors()->add('codestadoplaca', 'Informe o estado da Placa!');
        }
    }

    protected function validaEstoqueLocal(Validator $validator): void
    {
        $codestoquelocal = $this->input('codestoquelocal');
        $codfilial = $this->input('codfilial');

        if (empty($codestoquelocal)) {
            return;
        }

        if (empty($codfilial)) {
            return;
        }

        $estoqueLocal = EstoqueLocal::find($codestoquelocal);

        if (!$estoqueLocal) {
            return;
        }

        if ($estoqueLocal->codfilial != $codfilial) {
            $validator->errors()->add('codestoquelocal', 'O Local de Estoque não bate com a Filial selecionada!');
        }
    }

    protected function validaModelo(Validator $validator): void
    {
        $nfechave = $this->input('nfechave');
        $emitida = $this->input('emitida');
        $modelo = $this->input('modelo');

        if (empty($nfechave)) {
            if (!$emitida) {
                return;
            }

            if (empty($modelo)) {
                $validator->errors()->add('modelo', 'Modelo não pode ser vazio!');
                return;
            }

            if ($modelo != NotaFiscalService::MODELO_NFCE && $modelo != NotaFiscalService::MODELO_NFE) {
                $validator->errors()->add('modelo', 'Modelo incorreto!');
            }
        } else {
            $modeloChave = substr($nfechave, 20, 2);
            if ($modelo != $modeloChave) {
                $validator->errors()->add('modelo', 'Modelo informado não bate com a Chave!');
            }
        }
    }

    protected function validaNumero(Validator $validator): void
    {
        $emitida = $this->input('emitida');
        $numero = $this->input('numero');
        $codpessoa = $this->input('codpessoa');
        $serie = $this->input('serie');
        $codfilial = $this->input('codfilial');
        $modelo = $this->input('modelo');

        if (!$emitida && empty($numero)) {
            $validator->errors()->add('numero', 'Preencha o número da Nota Fiscal!');
            return;
        }

        if (empty($numero)) {
            return;
        }
        if (empty($codpessoa)) {
            return;
        }
        if (empty($serie)) {
            return;
        }
        if (empty($codfilial)) {
            return;
        }
        if (empty($modelo)) {
            return;
        }

        $query = NotaFiscal::where('serie', $serie)
            ->where('numero', $numero);

        // Se está editando, exclui a própria nota da busca
        if ($this->route('codnotafiscal')) {
            $query->where('codnotafiscal', '!=', $this->route('codnotafiscal'));
        }

        if ($emitida) {
            $query->where('emitida', true)
                ->where('codfilial', $codfilial)
                ->where('modelo', $modelo);
        } else {
            $query->where('emitida', false)
                ->where('codpessoa', $codpessoa);
        }

        if ($query->exists()) {
            $validator->errors()->add('numero', 'Esta Nota Fiscal já está cadastrada no sistema!');
        }
    }

    protected function validaSaida(Validator $validator): void
    {
        $emissao = $this->input('emissao');
        $saida = $this->input('saida');

        if (empty($emissao) || empty($saida)) {
            return;
        }

        try {
            $saidaDate = Carbon::parse($saida);
            $emissaoDate = Carbon::parse($emissao);
            $maximo = Carbon::now()->addDays(90);
        } catch (\Exception $e) {
            return;
        }

        if ($saidaDate < $emissaoDate) {
            $validator->errors()->add('saida', "A data de Entrada/Saída precisa ser posterior à " . $emissaoDate->format('d/m/Y') . "!");
        }

        if ($saidaDate > $maximo) {
            $validator->errors()->add('saida', "A data de Entrada/Saída precisa ser anterior à " . $maximo->format('d/m/Y') . " (90 dias da emissão)!");
        }
    }

    protected function validaChaveNFE(Validator $validator): void
    {
        $nfechave = $this->input('nfechave');

        if (empty($nfechave)) {
            return;
        }

        $digito = $this->calculaDigitoChaveNFE($nfechave);

        if ($digito == -1) {
            $validator->errors()->add('nfechave', 'Chave da NFE Inválida!');
            return;
        }

        if (substr($nfechave, 43, 1) != $digito) {
            $validator->errors()->add('nfechave', 'Dígito da Chave da NFE Inválido!');
            return;
        }

        $query = NotaFiscal::where('nfechave', $nfechave);

        if ($this->route('codnotafiscal')) {
            $query->where('codnotafiscal', '!=', $this->route('codnotafiscal'));
        }

        if ($this->input('codfilial')) {
            $query->where('codfilial', $this->input('codfilial'));
        }

        if ($query->exists()) {
            $validator->errors()->add('nfechave', 'Esta Chave já está cadastrada no sistema!');
        }
    }

    protected function validaPessoaPelaChaveNFE(Validator $validator): void
    {
        $nfechave = $this->input('nfechave');
        $codpessoa = $this->input('codpessoa');
        $codfilial = $this->input('codfilial');
        $emitida = $this->input('emitida');

        if (empty($nfechave) || empty($codpessoa) || empty($codfilial)) {
            return;
        }

        $chaveNumeros = preg_replace('/\D/', '', $nfechave);
        $cnpj = substr($chaveNumeros, 6, 14);

        if (strlen($cnpj) != 14) {
            return;
        }

        // CNPJs das SEFAZs que podem aparecer em notas emitidas por MEI
        $cnpjsSefaz = [
            '05599253000147', // RO
            '87958674000181', // RS
            '03507415000578', // MT
            '58290502000184', // MT
            '12200192000169', // AL
            '16907746000113', // MG
        ];

        if (in_array($cnpj, $cnpjsSefaz)) {
            return;
        }

        if ($emitida) {
            $filial = \Mg\Filial\Filial::with('Pessoa')->find($codfilial);
            if ($filial && $filial->Pessoa) {
                $cnpjFilial = $filial->Pessoa->cnpj;
                if ($cnpj != $cnpjFilial) {
                    $validator->errors()->add('nfechave', 'A filial selecionada não bate com o CNPJ da chave da NFE!');
                }
            }
        } else {
            $pessoas = Pessoa::where('cnpj', $cnpj)->get();
            $achou = false;

            foreach ($pessoas as $pessoa) {
                if ($pessoa->codpessoa == $codpessoa) {
                    $achou = true;
                    break;
                }
            }

            if (!$achou) {
                $validator->errors()->add('nfechave', 'A pessoa selecionada não bate com o CNPJ da chave da NFE!');
            }
        }
    }

    protected function validaSeriePelaChaveNFE(Validator $validator): void
    {
        $nfechave = $this->input('nfechave');
        $serie = $this->input('serie');

        if (empty($nfechave) || empty($serie)) {
            return;
        }

        $chaveNumeros = preg_replace('/\D/', '', $nfechave);
        $serieChave = intval(substr($chaveNumeros, 22, 3));

        if ($serieChave != $serie) {
            $validator->errors()->add('serie', 'A série informada não bate com a chave da NFE!');
        }
    }

    protected function validaNumeroPelaChaveNFE(Validator $validator): void
    {
        $nfechave = $this->input('nfechave');
        $numero = $this->input('numero');

        if (empty($nfechave) || empty($numero)) {
            return;
        }

        $chaveNumeros = preg_replace('/\D/', '', $nfechave);
        $numeroChave = intval(substr($chaveNumeros, 25, 9));

        if ($numeroChave != $numero) {
            $validator->errors()->add('numero', 'O número informado não bate com a chave da NFE!');
        }
    }

    protected function validaEmissaoPelaChaveNFE(Validator $validator): void
    {
        $nfechave = $this->input('nfechave');
        $emissao = $this->input('emissao');

        if (empty($nfechave) || empty($emissao)) {
            return;
        }

        $chaveNumeros = preg_replace('/\D/', '', $nfechave);

        // Chave NFE: posições 2-3 = ano (YY), posições 4-5 = mês (MM)
        $anoChave = intval(substr($chaveNumeros, 2, 2));
        $mesChave = substr($chaveNumeros, 4, 2);

        // Converte ano de 2 dígitos para 4 dígitos (ex: 25 -> 2025)
        $anoChave = ($anoChave >= 0 && $anoChave <= 99) ? $anoChave + 2000 : $anoChave;

        try {
            $emissaoDate = Carbon::parse($emissao);
        } catch (\Exception $e) {
            return;
        }

        $mesEmissao = $emissaoDate->format('m');
        $anoEmissao = intval($emissaoDate->format('Y'));

        // Valida se mês e ano da emissão batem com a chave
        if ($mesChave != $mesEmissao || $anoChave != $anoEmissao) {
            $mesAnoChave = $mesChave . '/' . $anoChave;
            $mesAnoEmissao = $mesEmissao . '/' . $anoEmissao;
            $validator->errors()->add('emissao', "A data de emissão ({$mesAnoEmissao}) não corresponde ao mês/ano da chave NFE ({$mesAnoChave})!");
        }
    }

    /**
     * Calcula o dígito verificador da chave da NFE
     */
    protected function calculaDigitoChaveNFE(string $chave): int
    {
        $chaveNumeros = preg_replace('/\D/', '', $chave);
        $chaveNumeros = substr($chaveNumeros, 0, 43);

        if (strlen($chaveNumeros) != 43) {
            return -1;
        }

        $key = 0;
        $c = 2;

        for ($i = strlen($chaveNumeros); $i >= 1; $i--) {
            if ($c > 9) {
                $c = 2;
            }

            $key += (intval(substr($chaveNumeros, $i - 1, 1)) * $c);
            $c++;
        }

        $resto = $key % 11;

        if ($resto == 0 || $resto == 1) {
            return 0;
        }

        return 11 - $resto;
    }
}
