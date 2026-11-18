<script setup>
import { computed, onMounted, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelect from '@components/MgSelect.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectCidade from '@components/MgSelectCidade.vue'
import MgSelectTributacaoNacional from '@components/MgSelectTributacaoNacional.vue'
import MgSelectNbs from '@components/MgSelectNbs.vue'
import { formataNumero, formataPercentual } from '@components/formatters'
import { hoje, lerDataBr } from '@components/utils/datas'
import { ISS_RETIDO_OPTIONS, calculaValoresNfse } from '../constants/notaFiscalServico'

// Formulario unico de criar e editar a nota fiscal de servico (TASK-279).
// Recebe a store do dominio em :cad e escreve em cad.form.
const props = defineProps({
  cad: { type: Object, required: true },
  disable: { type: Boolean, default: false },
})

const cad = computed(() => props.cad)

const obrigatorio = (v) => (v !== null && v !== undefined && v !== '') || 'Obrigatório'

// O Sistema Nacional recusa competencia depois da emissao (E0015). O MgInputData entrega
// 'DD/MM/AAAA' as rules.
const competenciaAteHoje = (v) => {
  const data = lerDataBr(v)
  return !data || data <= hoje() || 'A competência não pode ser depois de hoje'
}

// Com ISS retido quem retem e' o tomador: sem ele o Sistema Nacional recusa (E0204)
const tomadorSeRetido = (v) =>
  !cad.value.form?.issretido || !!v || 'Com ISS retido, informe o tomador'

// Mes da competencia sem percentual no cadastro da filial (TASK-280): o campo vazio diz o
// porque no proprio erro, que esconderia o aviso do hint
const SEM_CADASTRO = 'Mês sem percentual no cadastro da filial'
const semCadastroDoMes = () => cad.value.simplesConsultado && !cad.value.simplesMes

const tributosInformado = (v) =>
  Number(v) > 0 || (semCadastroDoMes() ? SEM_CADASTRO : 'Informe o percentual')

// A aliquota so vale com ISS retido (Simples, ISS no DAS: sem retencao ela nao vai na nota)
const aliquotaSeRetido = (v) => {
  if (!cad.value.form?.issretido) return true
  if (!(Number(v) > 0)) return semCadastroDoMes() ? SEM_CADASTRO : 'Informe a alíquota'
  // No Simples o ISS e' uma fatia do DAS: nunca passa do % total de tributos
  const tributos = Number(cad.value.form?.tributospercentual)
  return !(tributos > 0 && Number(v) > tributos) || 'Não pode passar dos Tributos'
}

const valores = computed(() => calculaValoresNfse(cad.value.form || {}))

// Retencao do ISS: o cadastro do cliente diz se ele retem (TASK-282). Escolher o tomador
// pre-seleciona; quem emite pode trocar, e o campo avisa quando a nota diverge do cadastro.
// Abrir uma nota salva nao dispara o @select, entao o que foi gravado nao muda.
function escolheuTomador(pessoa) {
  cad.value.tomadorRetemIss = !!pessoa.retemiss
  cad.value.form.issretido = !!pessoa.retemiss
}

const avisoRetencao = computed(() => {
  const cadastro = cad.value.tomadorRetemIss
  if (cadastro === null || cad.value.form?.issretido === cadastro) return undefined
  return cadastro
    ? 'No cadastro do cliente: retém o ISS'
    : 'No cadastro do cliente: não retém o ISS'
})

// Local da prestacao: padrao e a cidade da filial. Ao trocar a filial, acompanha so se
// ainda estava na cidade da filial anterior (ou vazio) — se a pessoa escolheu outra, fica.
watch(
  () => cad.value.form?.codfilial,
  (nova, antiga) => {
    if (!nova || nova === antiga) return
    const filialNova = props.cad.filialPorCodigo(nova)
    const filialAntiga = antiga ? props.cad.filialPorCodigo(antiga) : null
    const atual = cad.value.form.codcidadeprestacao
    if (!atual || atual === filialAntiga?.codcidade) {
      cad.value.form.codcidadeprestacao = filialNova?.codcidade ?? null
    }
  },
)

// Percentuais do Simples do mes (TASK-280): a nota nova e a troca de filial ou de mes puxam
// do cadastro da filial (mes sem cadastro esvazia os campos); abrir uma nota salva so
// consulta, para avisar, sem mudar o que foi gravado. Quem emite pode trocar o valor.
async function puxarSimples(preencher) {
  const mes = await cad.value.buscarSimplesMes()
  if (!preencher || mes === undefined) return
  cad.value.form.tributospercentual = mes?.tributospercentual ?? null
  cad.value.form.issaliquota = mes?.issaliquota ?? null
}

// So o mes conta: trocar o dia dentro do mes nao busca de novo
watch(
  () => [cad.value.form?.codfilial, cad.value.form?.competencia?.substring(0, 7)].join('|'),
  () => puxarSimples(true),
)

onMounted(() => puxarSimples(cad.value.isNovo))

function avisoSimples(campo, padrao) {
  if (!cad.value.simplesConsultado) return padrao
  const mes = cad.value.simplesMes
  if (!mes) return SEM_CADASTRO
  if (Number(cad.value.form?.[campo]) !== Number(mes[campo])) {
    return 'No cadastro da filial: ' + formataPercentual(mes[campo])
  }
  return padrao
}

const avisoTributos = computed(() => avisoSimples('tributospercentual', 'Aprox. do Simples no mês'))

const avisoAliquota = computed(() =>
  cad.value.form?.issretido
    ? avisoSimples('issaliquota', 'Do mês no Simples')
    : 'Só com ISS retido',
)
</script>

<template>
  <div v-if="cad.form">
    <!-- Nota: filial e competencia -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="bg-primary text-white text-body2">
        <q-icon name="receipt" size="1.5em" class="q-mr-sm" />
        Nota
      </q-card-section>
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-6">
            <MgSelect
              v-model="cad.form.codfilial"
              :options="cad.filiais"
              label="Filial *"
              emit-value
              map-options
              :rules="[obrigatorio]"
              :disable="disable"
              :autofocus="!cad.form.codfilial"
            />
          </div>
          <div class="col-6">
            <MgInputData
              v-model="cad.form.competencia"
              type="date"
              label="Competência *"
              :rules="[obrigatorio, competenciaAteHoje]"
              :disable="disable"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Tomador e retencao do ISS (quem retem e' o tomador) -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="bg-primary text-white text-body2">
        <q-icon name="business" size="1.5em" class="q-mr-sm" />
        Tomador
      </q-card-section>
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12">
            <MgSelectPessoa
              v-model="cad.form.codpessoa"
              :label="cad.form.issretido ? 'Tomador *' : 'Tomador (opcional)'"
              :rules="[tomadorSeRetido]"
              clearable
              :disable="disable"
              :autofocus="!!cad.form.codfilial"
              @select="escolheuTomador"
              @clear="cad.tomadorRetemIss = null"
            >
              <template v-slot:hint>Em branco: tomador não identificado</template>
            </MgSelectPessoa>
          </div>
          <div class="col-12">
            <MgSelect
              v-model="cad.form.issretido"
              :options="ISS_RETIDO_OPTIONS"
              label="Retenção do ISS"
              emit-value
              map-options
              :hint="avisoRetencao"
              :disable="disable"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Servico -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="bg-primary text-white text-body2">
        <q-icon name="design_services" size="1.5em" class="q-mr-sm" />
        Serviço
      </q-card-section>
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12">
            <MgSelectTributacaoNacional
              v-model="cad.form.codtributacaonacional"
              label="Código de Tributação Nacional *"
              :rules="[obrigatorio]"
              :disable="disable"
            />
          </div>
          <div class="col-12">
            <MgSelectNbs
              v-model="cad.form.codnbs"
              :codtributacaonacional="cad.form.codtributacaonacional"
              label="NBS *"
              :rules="[obrigatorio]"
              :disable="disable || !cad.form.codtributacaonacional"
            />
          </div>
          <div class="col-12">
            <MgInput
              v-model="cad.form.descricao"
              type="textarea"
              autogrow
              label="Descrição do Serviço *"
              maxlength="2000"
              counter
              hint="No DANFSe cabem ~3 linhas"
              :rules="[obrigatorio]"
              :disable="disable"
            />
          </div>
          <div class="col-12">
            <MgSelectCidade
              v-model="cad.form.codcidadeprestacao"
              label="Local da Prestação *"
              :rules="[obrigatorio]"
              :disable="disable"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Valores -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="bg-primary text-white text-body2">
        <q-icon name="attach_money" size="1.5em" class="q-mr-sm" />
        Valores
      </q-card-section>
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-xs-6 col-md-6">
            <MgInputValor
              v-model="cad.form.valorservico"
              label="Valor do Serviço *"
              prefix="R$"
              :min="0"
              :rules="[(v) => Number(v) > 0 || 'Informe o valor']"
              :disable="disable"
            />
          </div>
          <div class="col-xs-3 col-md-3">
            <MgInputValor
              v-model="cad.form.tributospercentual"
              label="Tributos *"
              suffix="%"
              :min="0"
              :max="99.99"
              :hint="avisoTributos"
              :rules="[tributosInformado]"
              :disable="disable"
            />
          </div>
          <!-- No Simples (ISS no DAS) a aliquota so vai na nota com ISS retido -->
          <div class="col-xs-3 col-md-3">
            <MgInputValor
              v-model="cad.form.issaliquota"
              label="Alíquota ISS"
              suffix="%"
              :min="0"
              :max="5"
              :hint="avisoAliquota"
              :rules="[aliquotaSeRetido]"
              :disable="disable || !cad.form.issretido"
            />
          </div>
          <div class="col-xs-6 col-md-6">
            <MgInputValor
              v-model="cad.form.valordescontoincondicionado"
              label="Desconto Incondicionado"
              prefix="R$"
              :min="0"
              clearable
              hint="Abate a base do ISS"
              :disable="disable"
            />
          </div>
          <div class="col-xs-6 col-md-6">
            <MgInputValor
              v-model="cad.form.valordescontocondicionado"
              label="Desconto Condicionado"
              prefix="R$"
              :min="0"
              clearable
              hint="Não abate a base"
              :disable="disable"
            />
          </div>
        </div>
      </q-card-section>

      <!-- Resumo (o backend recalcula ao salvar) -->
      <q-separator />
      <q-card-section class="bg-grey-1">
        <div class="row q-col-gutter-sm text-center">
          <div class="col-4">
            <div class="text-caption text-grey-7">Base de Cálculo</div>
            <div class="text-body2">R$ {{ formataNumero(valores.basecalculo) }}</div>
          </div>
          <div class="col-4">
            <div class="text-caption text-grey-7">ISS Retido</div>
            <div class="text-body2">
              {{ valores.issvalor != null ? 'R$ ' + formataNumero(valores.issvalor) : '-' }}
            </div>
          </div>
          <div class="col-4">
            <div class="text-caption text-grey-7">Valor Líquido</div>
            <div class="text-body1 text-weight-bold text-primary">
              R$ {{ formataNumero(valores.valorliquido) }}
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Observacoes -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="bg-primary text-white text-body2">
        <q-icon name="notes" size="1.5em" class="q-mr-sm" />
        Observações
      </q-card-section>
      <q-card-section>
        <MgInput
          v-model="cad.form.observacoes"
          type="textarea"
          autogrow
          label="Informações complementares"
          maxlength="2000"
          counter
          :disable="disable"
        />
      </q-card-section>
    </q-card>
  </div>
</template>
