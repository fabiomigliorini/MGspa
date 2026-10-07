<script setup>
// Lançamentos do período da maquineta (TASK-188 M9.8) como extrato, no padrão dos lançamentos do
// período do portador: agrupados por dia, a linha do tempo à esquerda (hora e o ícone da origem:
// venda, título, avulso; o cancelamento com o ícone de desfazer) e o valor à direita, com o
// acumulado do período. A linha leva à venda ou ao pagamento. No período não conferido, cada
// linha tem Corrigir (crédito/débito, maquineta, período, bandeira, autorização, parcelas, valor)
// e Registro indevido (sai do período como se nunca tivesse entrado).
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import {
  formataNumero,
  formataData,
  formataDataCompleta,
  formataHora,
} from '@components/formatters'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import CorrecaoPagamentoDialog from 'components/conferencia/CorrecaoPagamentoDialog.vue'
import { linhasDoPeriodo } from 'components/maquineta/linhas'

const $q = useQuasar()
const store = useMaquinetaPeriodoStore()
const sConferencia = useConferenciaStore()
const { periodo } = storeToRefs(store)

const editavel = computed(() => periodo.value?.situacao !== 'conferido')

// as linhas com o acumulado do período, agrupadas por dia
const dias = computed(() => {
  const ret = []
  let acumulado = 0
  for (const x of linhasDoPeriodo(periodo.value)) {
    acumulado = Math.round((acumulado + x.valor) * 100) / 100
    const data = formataData(x.momento)
    if (ret.at(-1)?.data !== data) {
      const extenso = formataDataCompleta(x.momento)
      ret.push({ data, titulo: extenso.charAt(0).toUpperCase() + extenso.slice(1), linhas: [] })
    }
    ret.at(-1).linhas.push({ ...x, acumulado })
  }
  return ret
})

const ICONE = { V: 'shopping_cart', T: 'request_quote', A: 'tune' }
const temAcumulado = computed(() => $q.screen.gt.xs)

const texto = (x) =>
  (x.cancelamento ? 'Cancelamento · ' : '') +
  [x.l.meiodescricao, x.l.documento || x.l.origemdescricao].filter(Boolean).join(' · ')
const detalhe = (x) =>
  [
    x.l.pdv || 'Escritório',
    x.l.fantasia,
    x.l.bandeiradescricao,
    x.l.parcelas > 1 ? `${x.l.parcelas}x` : null,
    x.l.autorizacao ? `aut. ${x.l.autorizacao}` : null,
  ]
    .filter(Boolean)
    .join(' · ')
const link = (x) =>
  x.l.codnegocio
    ? { href: `${process.env.NEGOCIOS_URL}/negocio/${x.l.codnegocio}`, target: '_blank' }
    : { to: { name: 'pagamento-detalhe', params: { id: x.l.codpagamento } } }
const corAvatar = (x) => (x.valor < 0 ? 'red-1' : 'green-1')
const corIcone = (x) => (x.valor < 0 ? 'red-8' : 'green-8')
const corValor = (x) => (x.valor < 0 ? 'text-red-8' : 'text-green-8')
const valor = (x) => (x.valor > 0 ? '+' : '') + formataNumero(x.valor)
// corrigir e indevido só no cartão que caiu aqui, ainda valendo
const podeCorrigir = (x) => editavel.value && !x.cancelamento && x.l.estado !== 'C'

// ---- correções ----
const dialogCorrecao = ref(false)
const corrigindo = ref(null)
function corrigir(x) {
  corrigindo.value = x.l
  dialogCorrecao.value = true
}

function indevido(x) {
  $q.dialog({
    title: 'Registro indevido',
    message: `Cancelar o lançamento de ${formataNumero(x.l.total)}? Ele sai do período como se nunca tivesse entrado (não é cancelamento na maquineta).`,
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      label: 'Justificativa',
      isValid: (v) => (v || '').trim().length >= 5,
    },
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar lançamento', color: 'negative', flat: true },
  }).onOk(async (justificativa) => {
    if (await sConferencia.indevido(x.l.codpagamento, justificativa)) store.recarregar()
  })
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section class="row items-center q-pb-sm">
      <div class="col text-subtitle1 text-weight-medium">Lançamentos</div>
    </q-card-section>

    <q-card-section v-if="dias.length" class="q-pt-none">
      <!-- cabeçalho das colunas: o mesmo esqueleto da linha, para alinhar -->
      <div v-if="temAcumulado" class="row no-wrap text-caption text-grey-7">
        <div style="width: 92px" />
        <div class="col row no-wrap q-px-sm">
          <div class="col" />
          <div class="text-right q-pl-md" style="width: 112px">Valor</div>
          <div class="text-right q-pl-md" style="width: 112px">Acumulado</div>
        </div>
      </div>

      <div v-for="dia in dias" :key="dia.data" class="q-mt-sm">
        <div class="text-caption text-weight-bold text-grey-8 q-py-xs">{{ dia.titulo }}</div>

        <div v-for="(x, i) in dia.linhas" :key="x.chave" class="row no-wrap">
          <!-- hora -->
          <div
            class="row items-center justify-end text-caption text-grey-7 q-pr-sm"
            style="width: 48px; height: 56px"
          >
            {{ formataHora(x.momento) }}
          </div>

          <!-- trilho: o ícone da origem, ligado ao anterior e ao próximo do dia -->
          <div class="column items-center no-wrap" style="width: 36px">
            <div style="width: 2px; height: 10px" :class="i ? 'bg-grey-3' : ''" />
            <q-avatar
              size="36px"
              font-size="20px"
              :color="corAvatar(x)"
              :text-color="corIcone(x)"
              :icon="x.cancelamento ? 'undo' : ICONE[x.l.origem]"
            />
            <div
              class="col"
              style="width: 2px; min-height: 10px"
              :class="i < dia.linhas.length - 1 ? 'bg-grey-3' : ''"
            />
          </div>

          <!-- o lançamento -->
          <q-item
            clickable
            v-bind="link(x)"
            class="col rounded-borders q-px-sm q-ml-sm q-mb-xs"
            style="min-height: 56px"
          >
            <q-item-section>
              <q-item-label>{{ texto(x) }}</q-item-label>
              <q-item-label caption class="row items-center q-gutter-x-xs">
                <span>{{ detalhe(x) }}</span>
                <q-badge
                  v-if="!x.cancelamento && x.l.estado === 'C'"
                  color="grey-5"
                  label="cancelado depois"
                />
                <q-badge v-if="x.l.correcoes" color="orange-8" label="corrigido" />
                <template v-if="podeCorrigir(x)">
                  <q-btn
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="edit"
                    @click.stop.prevent="corrigir(x)"
                  >
                    <q-tooltip>Corrigir (ou mover para outro período)</q-tooltip>
                  </q-btn>
                  <q-btn
                    v-if="x.l.origem !== 'T'"
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="block"
                    @click.stop.prevent="indevido(x)"
                  >
                    <q-tooltip>Registro indevido</q-tooltip>
                  </q-btn>
                </template>
              </q-item-label>
            </q-item-section>

            <q-item-section side top style="width: 112px">
              <q-item-label class="text-weight-bold" :class="corValor(x)">
                {{ valor(x) }}
              </q-item-label>
              <q-item-label v-if="!temAcumulado" caption>
                acumulado {{ formataNumero(x.acumulado) }}
              </q-item-label>
            </q-item-section>

            <q-item-section v-if="temAcumulado" side top style="width: 112px">
              <q-item-label class="text-grey-8">{{ formataNumero(x.acumulado) }}</q-item-label>
            </q-item-section>
          </q-item>
        </div>
      </div>
    </q-card-section>

    <MgEmptyState v-else plain icon="credit_card">Nenhum cartão neste período.</MgEmptyState>

    <CorrecaoPagamentoDialog
      v-model="dialogCorrecao"
      :lancamento="corrigindo"
      @corrigido="store.recarregar()"
    />
  </q-card>
</template>
