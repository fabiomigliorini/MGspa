<script setup>
// Conta corrente da maquineta de parceiro (doc-4, "Itens de parceiro"): o que devemos ao parceiro
// por esta maquineta. Crédito = o borderô que cada caixa lançou (leva ao caixa, com a foto ou o
// aviso "sem borderô"); débito = o título a pagar gerado aqui (leva ao título); ajuste com
// observação (comissão que o parceiro desconta, saldo inicial). Saldo anterior, as linhas do
// período de/até com o saldo corrente e o saldo de hoje. Gerar título e Ajuste no cabeçalho;
// cancelar na linha (o borderô se cancela na tela do caixa; o débito do título, só com o título
// estornado).
import { computed, ref } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInputData from '@components/MgInputData.vue'
import BorderoFotosDialog from '@components/caixa/BorderoFotosDialog.vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const $q = useQuasar()
const store = useCaixaItemStore()
const { conta, contaDe, contaAte } = storeToRefs(store)

const mostrarCancelados = ref(false)
const cancelados = computed(() => (conta.value?.linhas ?? []).filter((l) => l.cancelado).length)
const linhas = computed(() =>
  (conta.value?.linhas ?? []).filter((l) => mostrarCancelados.value || !l.cancelado),
)

const ICONE = { B: 'point_of_sale', T: 'request_quote', A: 'tune' }
const texto = (l) => {
  if (l.origem === 'B') return (l.valor < 0 ? 'Devolução · ' : 'Borderô · ') + l.portador
  if (l.origem === 'T') return `Título ${l.numero ?? ''}`
  return 'Ajuste'
}
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
    title: l.origem === 'T' ? 'Cancelar o débito do título' : 'Cancelar o ajuste',
    message: 'Motivo:',
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
  <q-card flat bordered class="q-mb-md">
    <q-card-section class="row items-center q-pb-sm">
      <div class="col">
        <div class="text-subtitle1 text-weight-medium">Conta corrente</div>
        <div class="text-caption text-grey-7">O que devemos ao parceiro por esta maquineta</div>
      </div>
      <div class="col-auto text-right q-mr-sm">
        <div class="text-caption text-grey-7">Saldo a pagar</div>
        <div
          class="text-h6 text-weight-bold"
          :class="(conta?.saldo ?? 0) < 0 ? 'text-red-8' : 'text-grey-9'"
        >
          {{ formataNumero(conta?.saldo ?? 0) }}
        </div>
      </div>
      <q-btn flat round size="sm" color="grey-7" icon="tune" @click="store.abrirAjuste()">
        <q-tooltip>Ajuste (comissão, saldo inicial)</q-tooltip>
      </q-btn>
      <q-btn flat round size="sm" color="primary" icon="request_quote" @click="store.abrirTitulo()">
        <q-tooltip>Gerar título a pagar</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-card-section class="q-pt-none">
      <div class="row q-col-gutter-md items-center">
        <div class="col-6 col-sm-3">
          <MgInputData v-model="contaDe" label="De" @update:model-value="store.carregarConta()" />
        </div>
        <div class="col-6 col-sm-3">
          <MgInputData v-model="contaAte" label="Até" @update:model-value="store.carregarConta()" />
        </div>
        <div class="col-12 col-sm-6 text-right">
          <q-toggle
            v-if="cancelados"
            v-model="mostrarCancelados"
            :label="`Mostrar cancelados (${cancelados})`"
            color="primary"
          />
        </div>
      </div>
    </q-card-section>

    <q-list v-if="conta" separator>
      <q-item>
        <q-item-section>
          <q-item-label class="text-grey-7">Saldo anterior</q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-item-label class="text-grey-8">{{ formataNumero(conta.saldoanterior) }}</q-item-label>
        </q-item-section>
      </q-item>

      <q-item
        v-for="l in linhas"
        :key="`${l.origem}${l.codigo}`"
        :clickable="!!link(l)"
        :to="link(l)"
      >
        <q-item-section avatar>
          <q-avatar
            size="36px"
            font-size="20px"
            :color="l.cancelado ? 'grey-2' : l.valor < 0 ? 'red-1' : 'green-1'"
            :text-color="l.cancelado ? 'grey-5' : l.valor < 0 ? 'red-8' : 'green-8'"
            :icon="ICONE[l.origem]"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label :class="l.cancelado ? 'text-strike text-grey-6' : ''">
            {{ texto(l) }}
          </q-item-label>
          <q-item-label caption class="row items-center q-gutter-x-xs">
            <span>{{ formataTimestamp(l.transacao) }}</span>
            <span v-if="l.usuariocriacao">· {{ l.usuariocriacao }}</span>
            <span v-if="l.observacoes">· {{ l.observacoes }}</span>
            <q-badge v-if="l.semBordero" color="orange-8" label="sem borderô" />
            <q-badge v-if="l.tituloestornado" color="grey-6" label="título estornado" />
            <q-badge
              v-else-if="l.origem === 'T' && !l.cancelado && l.titulosaldo === 0"
              color="green-6"
              label="pago"
            />
            <q-badge v-if="l.cancelado" color="grey-5" label="cancelado" />
            <q-btn
              v-if="l.fotos?.length"
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
          <q-item-label class="text-weight-bold" :class="corValor(l)">{{ valor(l) }}</q-item-label>
          <q-item-label v-if="l.saldo !== null" caption>
            saldo {{ formataNumero(l.saldo) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="!linhas.length">
        <q-item-section>
          <MgEmptyState plain icon="receipt_long">Nenhum lançamento no período.</MgEmptyState>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label class="text-weight-bold">Saldo no fim do período</q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-item-label class="text-weight-bold">
            {{ formataNumero(conta.saldofinal) }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>

    <BorderoFotosDialog
      v-model="dialogFotos"
      :codportadormovimento="fotosDe?.codigo ?? null"
      :fotos="fotosDe?.fotos ?? []"
    />
  </q-card>
</template>
