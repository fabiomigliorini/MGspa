<script setup>
// Conta corrente da maquineta de parceiro (doc-4, "Itens de parceiro"): o que devemos ao parceiro
// por esta maquineta, como o extrato do período do portador (PeriodoLancamentos): agrupado por
// dia, uma linha do tempo à esquerda e, à direita, o valor e o saldo corrente na mesma coluna (no
// celular, o saldo embaixo do valor), por semana (domingo a sábado, a atual ao abrir), mês ou um
// período personalizado. Crédito = o borderô que cada caixa lançou (leva ao caixa, com
// a foto ou "sem borderô"); débito = o título a pagar gerado aqui (leva ao título); ajuste com
// observação (comissão que o parceiro desconta, saldo inicial). Gerar título e Ajuste no
// cabeçalho; cancelar na linha (o borderô se cancela na tela do caixa; cancelar o débito do título
// estorna o título junto, que só se estorna por aqui).
import { computed, ref } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInputData from '@components/MgInputData.vue'
import BorderoFotosDialog from '@components/caixa/BorderoFotosDialog.vue'
import {
  formataNumero,
  formataData,
  formataDataCompleta,
  formataHora,
  formataMesAno,
} from '@components/formatters'
import { useCaixaItemStore, PERIODOS_CONTA } from 'src/stores/caixaItemStore'

const $q = useQuasar()
const store = useCaixaItemStore()
const { conta, contaModo, contaDe, contaAte } = storeToRefs(store)

// "Semana de 27/09 a 03/10/2026", "out/2026", "De 01/09 a 15/10/2026"
const tituloPeriodo = computed(() => {
  if (!contaDe.value) return ''
  if (contaModo.value === 'M') return formataMesAno(contaDe.value)
  const periodo = `${formataData(contaDe.value, 0)} a ${formataData(contaAte.value)}`
  return contaModo.value === 'S' ? `Semana de ${periodo}` : `De ${periodo}`
})

// o diálogo do período: semana, mês ou personalizado (este com o De/Até no mesmo diálogo); só
// troca ao aplicar
const dialogPeriodo = ref(false)
const escolha = ref({ modo: 'S', de: null, ate: null })
function abrirPeriodo() {
  escolha.value = { modo: contaModo.value, de: contaDe.value, ate: contaAte.value }
  dialogPeriodo.value = true
}
async function aplicarPeriodo() {
  const { modo, de, ate } = escolha.value
  await (modo === 'P' ? store.filtrarConta(de, ate) : store.periodoConta(modo))
  dialogPeriodo.value = false
}

// os cancelados (riscados) só aparecem no toggle
const mostrarCancelados = ref(false)
const cancelados = computed(() => (conta.value?.linhas ?? []).filter((l) => l.cancelado).length)

// as linhas (já em ordem de transação) agrupadas por dia
const dias = computed(() => {
  const ret = []
  for (const l of conta.value?.linhas ?? []) {
    if (l.cancelado && !mostrarCancelados.value) continue
    const data = formataData(l.transacao)
    if (ret.at(-1)?.data !== data) {
      const extenso = formataDataCompleta(l.transacao)
      ret.push({ data, titulo: extenso.charAt(0).toUpperCase() + extenso.slice(1), linhas: [] })
    }
    ret.at(-1).linhas.push(l)
  }
  return ret
})

// B borderô, T título, A ajuste
const ICONE = { B: 'point_of_sale', T: 'request_quote', A: 'tune' }

const temSaldoColuna = computed(() => $q.screen.gt.xs)

const link = (l) => {
  if (l.origem === 'B') {
    return {
      name: 'portador-detalhe',
      params: { codportador: l.codportador, codportadorperiodo: l.codportadorperiodo },
    }
  }
  if (l.origem === 'T') return { name: 'titulo-detalhe', params: { codtitulo: l.codtitulo } }
  return null
}
const corAvatar = (l) => (l.cancelado ? 'grey-2' : l.valor < 0 ? 'red-1' : 'green-1')
const corIcone = (l) => (l.cancelado ? 'grey-5' : l.valor < 0 ? 'red-8' : 'green-8')
const corValor = (l) =>
  l.cancelado ? 'text-strike text-grey-5' : l.valor < 0 ? 'text-red-8' : 'text-green-8'
const valor = (l) => (l.valor > 0 ? '+' : '') + formataNumero(l.valor)

const fotosDe = ref(null)
const dialogFotos = ref(false)
function abrirFotos(l) {
  fotosDe.value = l
  dialogFotos.value = true
}

function cancelar(l) {
  $q.dialog({
    title: l.origem === 'T' ? 'Cancelar o débito e estornar o título' : 'Cancelar o ajuste',
    message: l.origem === 'T' ? `O título ${l.numero} será estornado junto. Motivo:` : 'Motivo:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (v) => (v || '').trim().length >= 5,
    },
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar lançamento', color: 'negative', flat: true },
  }).onOk((j) => store.cancelarAcerto(l.codigo, j))
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section class="row items-center q-pb-sm">
      <div class="col text-subtitle1 text-weight-medium">Conta corrente</div>
      <q-toggle
        v-model="mostrarCancelados"
        :disable="!cancelados"
        :label="`Mostrar cancelados (${cancelados})`"
        color="primary"
      />
      <q-btn flat round size="sm" color="grey-7" icon="tune" @click="store.abrirAjuste()">
        <q-tooltip>Ajuste (comissão, saldo inicial)</q-tooltip>
      </q-btn>
      <q-btn flat round size="sm" color="primary" icon="request_quote" @click="store.abrirTitulo()">
        <q-tooltip>Gerar título a pagar</q-tooltip>
      </q-btn>
    </q-card-section>

    <!-- o período: setas andam uma semana (domingo a sábado) ou um mês; o ícone escolhe semana,
         mês ou personalizado (De/Até) -->
    <q-card-section class="row items-center justify-center no-wrap q-py-none">
      <q-btn
        v-if="contaModo !== 'P'"
        flat
        round
        color="grey-7"
        icon="chevron_left"
        @click="store.navegarConta(-1)"
      >
        <q-tooltip>{{ contaModo === 'M' ? 'Mês anterior' : 'Semana anterior' }}</q-tooltip>
      </q-btn>
      <div class="text-subtitle1 text-grey-9 q-px-sm">{{ tituloPeriodo }}</div>
      <q-btn flat round size="sm" color="grey-7" icon="date_range" @click="abrirPeriodo">
        <q-tooltip>Semana, mês ou personalizado</q-tooltip>
      </q-btn>
      <q-btn
        v-if="contaModo !== 'P'"
        flat
        round
        color="grey-7"
        icon="chevron_right"
        :disable="conta?.ultimo"
        @click="store.navegarConta(1)"
      >
        <q-tooltip>{{ contaModo === 'M' ? 'Próximo mês' : 'Próxima semana' }}</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-card-section v-if="conta" class="q-pt-none">
      <!-- cabeçalho das colunas: o mesmo esqueleto da linha, para alinhar -->
      <div v-if="temSaldoColuna" class="row no-wrap text-caption text-grey-7">
        <div style="width: 92px" />
        <div class="col row no-wrap q-px-sm">
          <div class="col" />
          <div class="text-right q-pl-md" style="width: 112px">Valor</div>
          <div class="text-right q-pl-md" style="width: 112px">Saldo</div>
        </div>
      </div>
      <div class="row no-wrap text-caption text-grey-7 q-py-xs">
        <div class="col">Saldo anterior</div>
        <div class="text-right q-px-sm">{{ formataNumero(conta.saldoanterior) }}</div>
      </div>

      <div v-for="dia in dias" :key="dia.data" class="q-mt-sm">
        <div class="text-caption text-weight-bold text-grey-8 q-py-xs">{{ dia.titulo }}</div>

        <div v-for="(l, i) in dia.linhas" :key="`${l.origem}${l.codigo}`" class="row no-wrap">
          <!-- hora -->
          <div
            class="row items-center justify-end text-caption text-grey-7 q-pr-sm"
            style="width: 48px; height: 56px"
          >
            {{ formataHora(l.transacao) }}
          </div>

          <!-- trilho: o ícone da origem, ligado ao anterior e ao próximo do dia -->
          <div class="column items-center no-wrap" style="width: 36px">
            <div style="width: 2px; height: 10px" :class="i ? 'bg-grey-3' : ''" />
            <q-avatar
              size="36px"
              font-size="20px"
              :color="corAvatar(l)"
              :text-color="corIcone(l)"
              :icon="ICONE[l.origem]"
            />
            <div
              class="col"
              style="width: 2px; min-height: 10px"
              :class="i < dia.linhas.length - 1 ? 'bg-grey-3' : ''"
            />
          </div>

          <!-- o lançamento -->
          <q-item
            :clickable="!!link(l)"
            :to="link(l)"
            class="col rounded-borders q-px-sm q-ml-sm q-mb-xs"
            style="min-height: 56px"
          >
            <q-item-section>
              <q-item-label :class="l.cancelado ? 'text-strike text-grey-6' : ''">
                {{ l.texto }}
              </q-item-label>
              <q-item-label caption class="row items-center q-gutter-x-xs">
                <span v-if="l.observacoes">{{ l.observacoes }}</span>
                <span v-if="l.usuariocriacao" class="text-grey-6">{{ l.usuariocriacao }}</span>
                <q-badge v-if="l.semBordero" color="orange-8" label="sem borderô" />
                <q-badge v-if="l.tituloestornado" color="grey-6" label="título estornado" />
                <q-badge
                  v-else-if="l.origem === 'T' && !l.cancelado && l.titulosaldo === 0"
                  color="green-7"
                  label="pago"
                />
                <q-badge v-if="l.cancelado" color="grey-5" label="cancelado" />
                <q-btn
                  v-if="l.fotos.length"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="photo_camera"
                  @click.stop.prevent="abrirFotos(l)"
                >
                  <q-tooltip>Ver a foto do borderô</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="l.podeCancelar"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="block"
                  @click.stop.prevent="cancelar(l)"
                >
                  <q-tooltip>Cancelar</q-tooltip>
                </q-btn>
              </q-item-label>
              <q-item-label v-if="l.cancelado && l.justificativa" caption class="text-grey-7">
                {{ l.justificativa }}
                <template v-if="l.usuariocancelamento"> · {{ l.usuariocancelamento }}</template>
              </q-item-label>
            </q-item-section>

            <q-item-section side top style="width: 112px">
              <q-item-label class="text-weight-bold" :class="corValor(l)">
                {{ valor(l) }}
              </q-item-label>
              <q-item-label v-if="!temSaldoColuna && l.saldo !== null" caption>
                saldo {{ formataNumero(l.saldo) }}
              </q-item-label>
            </q-item-section>

            <q-item-section v-if="temSaldoColuna" side top style="width: 112px">
              <q-item-label :class="l.saldo < 0 ? 'text-red-8' : 'text-grey-8'">
                {{ l.saldo !== null ? formataNumero(l.saldo) : '' }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </div>
      </div>

      <MgEmptyState v-if="!dias.length" plain icon="receipt_long">
        Nenhum lançamento no período.
      </MgEmptyState>

      <q-separator class="q-mt-sm" />
      <div class="row no-wrap items-center text-weight-bold q-pt-sm">
        <div class="col q-pr-sm">Saldo em {{ formataData(conta.ate) }}</div>
        <div class="text-right q-pr-sm">{{ formataNumero(conta.saldofinal) }}</div>
      </div>
    </q-card-section>

    <q-dialog v-model="dialogPeriodo">
      <q-card flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">PERÍODO</q-card-section>
        <q-form @submit.prevent="aplicarPeriodo">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-option-group
                  v-model="escolha.modo"
                  type="radio"
                  inline
                  :options="PERIODOS_CONTA"
                />
              </div>
              <template v-if="escolha.modo === 'P'">
                <div class="col-6">
                  <MgInputData
                    v-model="escolha.de"
                    label="De"
                    autofocus
                    :rules="[(v) => !!v || 'Obrigatório']"
                  />
                </div>
                <div class="col-6">
                  <MgInputData
                    v-model="escolha.ate"
                    label="Até"
                    :rules="[
                      (v) => !!v || 'Obrigatório',
                      () => escolha.ate >= escolha.de || 'Antes do De',
                    ]"
                  />
                </div>
              </template>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Aplicar" color="primary" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <BorderoFotosDialog
      v-model="dialogFotos"
      :codportadormovimento="fotosDe?.codigo ?? null"
      :fotos="fotosDe?.fotos ?? []"
    />
  </q-card>
</template>
