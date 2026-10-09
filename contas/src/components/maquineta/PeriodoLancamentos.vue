<script setup>
// Detalhe do período da maquineta (TASK-188 M9.8) na ordem do relatório da maquininha: modalidade
// → bandeira → hora, uma linha por venda (hora, NSU, autorização, parcelas e valor, como no papel;
// no computador, em cinza, o caixa e a venda). A linha leva à venda ou ao pagamento. A venda
// cancelada no próprio período fica fora, como no papel, e volta riscada no "Mostrar cancelados";
// o cancelamento de venda de outro período e o estorno ficam num bloco no fim, negativos. No
// período não conferido, cada venda tem Corrigir (crédito/débito, maquineta, bandeira, autorização,
// parcelas, valor), Registro indevido (sai do período como se nunca tivesse entrado) e Alterar a
// data (TASK-204: a data manda no período, o cartão vai para o período da data); o cancelamento
// tem a data própria, que se altera sem mexer na venda.
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataNumero, formataData, formataHora } from '@components/formatters'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import CorrecaoPagamentoDialog from 'components/conferencia/CorrecaoPagamentoDialog.vue'
import AlterarDataDialog from '@components/portador/AlterarDataDialog.vue'
import { borderoDoPeriodo } from 'components/maquineta/linhas'

const $q = useQuasar()
const store = useMaquinetaPeriodoStore()
const sConferencia = useConferenciaStore()
const { periodo } = storeToRefs(store)

const editavel = computed(() => periodo.value?.situacao !== 'conferido')
const computador = computed(() => $q.screen.gt.xs)

const bordero = computed(() => borderoDoPeriodo(periodo.value))
const mostrarCancelados = ref(false)
const blocos = computed(() =>
  bordero.value.blocos
    .map((b) => ({ ...b, linhas: b.linhas.filter((x) => mostrarCancelados.value || !x.cancelada) }))
    .filter((b) => b.linhas.length),
)
const vazio = computed(() => !bordero.value.blocos.length && !bordero.value.cancelamentos.length)

// NSU e autorização, cada um quando houver (a autorização sem rótulo: é o número do papel); no
// celular, só o primeiro que tiver
const codigos = (l) => [l.nsu ? `NSU ${l.nsu}` : null, l.autorizacao].filter(Boolean)
const codigo = (l) => (computador.value ? codigos(l).join(' · ') : (l.nsu ?? l.autorizacao ?? ''))
const venda = (l) => [l.pdv || 'Escritório', l.documento].filter(Boolean).join(' · ')
const link = (l) =>
  l.codnegocio
    ? { href: `${process.env.NEGOCIOS_URL}/negocio/${l.codnegocio}`, target: '_blank' }
    : { to: { name: 'pagamento-detalhe', params: { id: l.codpagamento } } }
// corrigir e indevido só no cartão que caiu aqui, ainda valendo
const podeCorrigir = (x) => editavel.value && !x.cancelada && x.l.estado !== 'C'

// ---- correções ----
const dialogCorrecao = ref(false)
const corrigindo = ref(null)
function corrigir(x) {
  corrigindo.value = x.l
  dialogCorrecao.value = true
}

// alterar a data: da venda que vale (ou do estorno), ou do cancelamento (a venda cancelada aqui
// e a de outro período cancelada aqui)
const dataDe = ref(null)
const dialogData = ref(false)
const ehCancelamento = (x) => x.cancelada || !!x.outroPeriodo
const podeAlterarData = (x) =>
  editavel.value && (ehCancelamento(x) || x.l.estado !== 'C' || x.l.operacao === 'DB')
function abrirData(x) {
  dataDe.value = {
    codpagamento: x.l.codpagamento,
    cancelamento: ehCancelamento(x),
    data: ehCancelamento(x) ? (x.l.cancelamento ?? x.l.transacao) : x.l.transacao,
  }
  dialogData.value = true
}
async function alterarData(dados) {
  const d = dataDe.value
  if (await store.alterarData(d.codpagamento, dados, d.cancelamento)) dialogData.value = false
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
    <q-card-section class="row items-center">
      <div class="col text-subtitle1 text-weight-medium">Detalhe</div>
      <q-toggle
        v-if="bordero.cancelados"
        v-model="mostrarCancelados"
        :label="`Mostrar cancelados (${bordero.cancelados})`"
        color="primary"
      />
    </q-card-section>

    <q-list v-if="!vazio">
      <template v-for="b in blocos" :key="b.chave">
        <!-- a bandeira, com quantidade e valor, como no papel -->
        <q-separator />
        <div
          class="row no-wrap items-center text-weight-medium text-grey-9 q-px-md q-py-sm bg-grey-2"
        >
          <div class="col ellipsis">{{ b.titulo }}</div>
          <div class="text-right" style="width: 40px">{{ b.quantidade }}</div>
          <div class="text-right" style="width: 96px">{{ formataNumero(b.valor) }}</div>
          <div v-if="editavel" style="width: 96px" />
        </div>
        <q-separator />

        <q-item
          v-for="x in b.linhas"
          :key="x.chave"
          clickable
          v-bind="link(x.l)"
          :class="x.cancelada ? 'text-strike text-grey-5' : ''"
        >
          <q-item-section>
            <div class="row no-wrap items-center">
              <div style="width: 56px">{{ formataHora(x.momento) }}</div>
              <div class="col ellipsis" :class="x.cancelada ? '' : 'text-grey-8'">
                {{ codigo(x.l) }}
                <span v-if="computador && venda(x.l)" class="text-caption text-grey-6 q-ml-sm">
                  {{ venda(x.l) }}
                </span>
                <q-badge v-if="x.l.correcoes" class="q-ml-xs" color="orange-8" label="corrigido" />
                <q-badge
                  v-if="!x.cancelada && x.l.estado === 'C'"
                  class="q-ml-xs"
                  color="grey-5"
                  label="cancelado depois"
                />
              </div>
              <div class="text-right" style="width: 40px">
                {{ x.l.parcelas > 1 ? `${x.l.parcelas}x` : '' }}
              </div>
              <div class="text-right text-weight-medium" style="width: 96px">
                {{ formataNumero(x.valor) }}
              </div>
              <div v-if="editavel" class="row no-wrap justify-end" style="width: 96px">
                <q-btn
                  v-if="podeAlterarData(x)"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="event"
                  @click.stop.prevent="abrirData(x)"
                >
                  <q-tooltip>
                    {{ ehCancelamento(x) ? 'Alterar a data do cancelamento' : 'Alterar a data' }}
                  </q-tooltip>
                </q-btn>
                <template v-if="podeCorrigir(x)">
                  <q-btn
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="edit"
                    @click.stop.prevent="corrigir(x)"
                  >
                    <q-tooltip>Corrigir</q-tooltip>
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
              </div>
            </div>
          </q-item-section>
        </q-item>
      </template>

      <!-- cancelamento de venda de outro período e estorno: descontam do total -->
      <template v-if="bordero.cancelamentos.length">
        <q-separator />
        <div
          class="row no-wrap items-center text-weight-medium text-red-8 q-px-md q-py-sm bg-red-1"
        >
          <div class="col ellipsis">Cancelamento de outro período</div>
          <div class="text-right" style="width: 96px">
            {{ formataNumero(bordero.cancelamentos.reduce((s, x) => s + x.valor, 0)) }}
          </div>
          <div v-if="editavel" style="width: 96px" />
        </div>
        <q-separator />
        <q-item v-for="x in bordero.cancelamentos" :key="x.chave" clickable v-bind="link(x.l)">
          <q-item-section>
            <div class="row no-wrap items-center">
              <div style="width: 56px">{{ formataHora(x.momento) }}</div>
              <div class="col ellipsis text-grey-8">
                {{ codigo(x.l) }}
                <span class="text-caption text-grey-6 q-ml-sm">
                  {{ x.outroPeriodo ? `venda de ${formataData(x.l.transacao)}` : 'estorno' }}
                  <template v-if="computador && venda(x.l)"> · {{ venda(x.l) }}</template>
                </span>
              </div>
              <div class="text-right" style="width: 40px">
                {{ x.l.parcelas > 1 ? `${x.l.parcelas}x` : '' }}
              </div>
              <div class="text-right text-weight-medium text-red-8" style="width: 96px">
                {{ formataNumero(x.valor) }}
              </div>
              <div v-if="editavel" class="row no-wrap justify-end" style="width: 96px">
                <q-btn
                  v-if="podeAlterarData(x)"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="event"
                  @click.stop.prevent="abrirData(x)"
                >
                  <q-tooltip>
                    {{ ehCancelamento(x) ? 'Alterar a data do cancelamento' : 'Alterar a data' }}
                  </q-tooltip>
                </q-btn>
              </div>
            </div>
          </q-item-section>
        </q-item>
      </template>
    </q-list>

    <MgEmptyState v-else plain icon="credit_card">Nenhum cartão neste período.</MgEmptyState>

    <AlterarDataDialog
      v-model="dialogData"
      :data="dataDe?.data"
      :rotulo="dataDe?.cancelamento ? 'a data do cancelamento' : 'a data'"
      :salvando="store.salvando"
      @salvar="alterarData"
    />
    <CorrecaoPagamentoDialog
      v-model="dialogCorrecao"
      :lancamento="corrigindo"
      @corrigido="store.recarregar()"
    />
  </q-card>
</template>
