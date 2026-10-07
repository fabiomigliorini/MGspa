<script setup>
// Detalhe de um pagamento (M6.1 doc-3): documento (venda, títulos movimentados, transferência,
// avulso), meio, maquineta, portadores, estado, pagamento original e contrários; estornar
// (baixa de título feita à mão). O pagamento vem do pagamentoListaStore; ações próprias do app
// (recibo, editar) entram no slot `acoes`.
import { computed } from 'vue'
import { useQuasar } from 'quasar'
import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { formataNumero, formataData, formataTimestamp, formataCodigo } from '@components/formatters'
import { visualPagamento } from '@components/cobranca/pagamento.js'
import LogoPagamento from '@components/cobranca/LogoPagamento.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

defineProps({
  // o app diz se o usuário pode estornar (grupos); o servidor confere de novo
  podeEstornar: {
    type: Boolean,
    default: false,
  },
  // rota do título (página do contas); sem ela, link para o contas
  toTitulo: {
    type: Function,
    default: null,
  },
})

const emit = defineEmits(['estornado'])

const $q = useQuasar()
const store = pagamentoListaStore()
const pag = computed(() => store.pagamento)

const OPERACAO = {
  CR: 'Recebimento',
  DB: 'Pagamento',
  TR: 'Transferência',
  CP: 'Encontro de contas',
}
const COR_ESTADO = { P: 'amber-8', E: 'green-7', C: 'red-7' }
const COR_TOTAL = { CR: 'text-green-8', DB: 'text-red-8', TR: 'text-blue-8', CP: 'text-grey-7' }

const urlPessoa = (cod) => (cod ? `${process.env.PESSOAS_URL}/pessoa/${cod}` : null)
const urlVenda = (cod) => `${process.env.NEGOCIOS_URL}/negocio/${cod}`
const urlTitulo = (cod) => `${process.env.CONTAS_URL}/titulo/${cod}`

const erro = (e, padrao) =>
  $q.notify({
    type: 'negative',
    message: e?.response?.data?.message ?? e?.message ?? padrao,
    color: 'red-5',
    icon: 'error',
  })

const estornar = () => {
  $q.dialog({
    title: 'Estornar',
    message: 'Estornar desfaz a baixa de todos os títulos deste pagamento. Informe o motivo:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (v) => (v || '').trim().length >= 5,
    },
    ok: { label: 'Estornar', color: 'negative', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async (justificativa) => {
    try {
      await store.estornar(pag.value.codpagamento, justificativa)
      $q.notify({ type: 'positive', message: 'Estornado', color: 'green-5', icon: 'done' })
      emit('estornado', store.pagamento)
    } catch (e) {
      $q.notify({
        type: 'negative',
        message: e?.response?.data?.message ?? e?.message ?? 'Erro ao estornar',
        color: 'red-5',
        icon: 'error',
      })
    }
  })
}
</script>

<template>
  <div v-if="pag">
    <!-- cabeçalho -->
    <div class="row items-center q-mb-md no-wrap">
      <logo-pagamento v-bind="visualPagamento(pag)" class="q-mr-md" />
      <div class="col">
        <div class="text-h5 text-grey-9">
          {{ OPERACAO[pag.operacao] }} {{ formataCodigo(pag.codpagamento) }}
          <q-badge :color="COR_ESTADO[pag.estado]" :label="pag.estadodescricao" class="q-ml-sm" />
        </div>
        <div v-if="pag.codliquidacaotituloantigo" class="text-grey-7">
          Liquidação {{ formataCodigo(pag.codliquidacaotituloantigo) }} (histórico)
        </div>
        <div v-if="pag.estado === 'C'" class="text-negative">
          Cancelado em {{ formataData(pag.cancelamento) }} · {{ pag.justificativa }}
        </div>
        <div v-if="pag.codperiodocolaboradoracerto" class="text-orange-8">
          Acerto de RH: estorne pelo acerto
        </div>
      </div>
    </div>

    <!-- resumo -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-xs-6 col-sm-4">
        <q-card bordered flat class="q-py-sm text-center">
          <div class="text-caption text-grey-7">Pessoa</div>
          <div class="text-h6 ellipsis">
            <a
              v-if="pag.codpessoa"
              :href="urlPessoa(pag.codpessoa)"
              target="_blank"
              class="text-primary"
              style="text-decoration: none"
            >
              {{ pag.fantasia }}
            </a>
            <span v-else class="text-grey-6">—</span>
          </div>
        </q-card>
      </div>
      <div class="col-xs-6 col-sm-2">
        <q-card bordered flat class="q-py-sm text-center">
          <div class="text-caption text-grey-7">Data</div>
          <div class="text-h6 text-grey-9">{{ formataData(pag.transacao) }}</div>
        </q-card>
      </div>
      <div class="col-xs-6 col-sm-3">
        <q-card bordered flat class="q-py-sm text-center">
          <div class="text-caption text-grey-7">
            {{ pag.meiodescricao + (pag.parcelas > 1 ? ' ' + pag.parcelas + 'x' : '') }}
          </div>
          <div class="text-h6 text-grey-9 ellipsis">{{ pag.portador || 'Sem portador' }}</div>
        </q-card>
      </div>
      <div class="col-xs-6 col-sm-3">
        <q-card bordered flat class="q-py-sm text-center">
          <div class="text-caption text-grey-7">Total</div>
          <div class="text-h6 ellipsis" :class="COR_TOTAL[pag.operacao]">
            R$ {{ formataNumero(pag.total) }}
          </div>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md">
      <!-- detalhes + ações -->
      <div class="col-xs-12 col-sm-7">
        <q-card bordered flat>
          <q-card-section class="text-grey-9 text-overline row items-center">
            DETALHES
            <q-space />
            <slot name="acoes" :pagamento="pag" />
            <q-btn
              v-if="podeEstornar && pag.estornavel"
              flat
              round
              size="sm"
              icon="undo"
              color="grey-7"
              @click="estornar"
            >
              <q-tooltip>Estornar</q-tooltip>
            </q-btn>
            <MgInfoCriacao :registro="pag" />
          </q-card-section>
          <q-list separator>
            <q-item>
              <q-item-section>
                <q-item-label caption>{{ pag.origemdescricao }}</q-item-label>
                <q-item-label>
                  <a
                    v-if="pag.codnegocio"
                    :href="urlVenda(pag.codnegocio)"
                    target="_blank"
                    class="text-primary"
                    style="text-decoration: none"
                  >
                    {{ pag.documento }}
                  </a>
                  <template v-else>{{ pag.documento }}</template>
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="pag.pdv || pag.filial">
                <q-item-label caption>{{ pag.filial }}</q-item-label>
                <q-item-label caption>{{ pag.pdv }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="pag.portadororigem || pag.portadordestino">
              <q-item-section>
                <q-item-label caption>De → Para</q-item-label>
                <q-item-label>
                  {{ pag.portadororigem || 'fora' }} → {{ pag.portadordestino || 'fora' }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="pag.maquineta || pag.autorizacao">
              <q-item-section>
                <q-item-label caption>Cartão</q-item-label>
                <q-item-label>
                  {{
                    [
                      pag.maquineta,
                      pag.bandeiradescricao,
                      pag.autorizacao ? 'aut. ' + pag.autorizacao : null,
                      pag.nsu ? 'NSU ' + pag.nsu : null,
                    ]
                      .filter(Boolean)
                      .join(' · ')
                  }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="pag.cmc7">
              <q-item-section>
                <q-item-label caption>Cheque</q-item-label>
                <q-item-label>
                  {{ pag.chequeemitente }} · bom para {{ formataData(pag.chequevencimento) }}
                </q-item-label>
                <q-item-label caption>{{ pag.cmc7 }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="pag.pagamentoorigem">
              <q-item-section>
                <q-item-label caption>Devolução do pagamento</q-item-label>
                <q-item-label>
                  {{ formataCodigo(pag.pagamentoorigem.codpagamento) }} ·
                  {{ pag.pagamentoorigem.meiodescricao }} · R$
                  {{ formataNumero(pag.pagamentoorigem.total) }} de
                  {{ formataData(pag.pagamentoorigem.transacao) }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-for="c in pag.contrarios" :key="c.codpagamento">
              <q-item-section>
                <q-item-label caption>Devolvido</q-item-label>
                <q-item-label :class="c.estado === 'C' ? 'text-strike text-grey-6' : ''">
                  {{ formataCodigo(c.codpagamento) }} · R$ {{ formataNumero(c.total) }} em
                  {{ formataData(c.transacao) }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <q-card-section v-if="pag.juros || pag.multa || pag.desconto || pag.valortroco">
            <div class="row q-col-gutter-md text-center">
              <div class="col">
                <div class="text-caption text-grey-7">Principal</div>
                <div>{{ formataNumero(pag.principal) }}</div>
              </div>
              <div class="col" v-if="pag.juros">
                <div class="text-caption text-grey-7">Juros</div>
                <div class="text-orange">{{ formataNumero(pag.juros) }}</div>
              </div>
              <div class="col" v-if="pag.multa">
                <div class="text-caption text-grey-7">Multa</div>
                <div class="text-orange">{{ formataNumero(pag.multa) }}</div>
              </div>
              <div class="col" v-if="pag.desconto">
                <div class="text-caption text-grey-7">Desconto</div>
                <div class="text-blue">{{ formataNumero(pag.desconto) }}</div>
              </div>
              <div class="col" v-if="pag.valortroco">
                <div class="text-caption text-grey-7">Troco</div>
                <div>{{ formataNumero(pag.valortroco) }}</div>
              </div>
              <div class="col">
                <div class="text-caption text-grey-7">Total</div>
                <div class="text-weight-bold">{{ formataNumero(pag.total) }}</div>
              </div>
            </div>
          </q-card-section>
          <q-card-section>
            <div class="text-body2 bg-grey-2 rounded-borders q-pa-md" style="white-space: pre-line">
              <span v-if="pag.observacoes">{{ pag.observacoes }}</span>
              <span v-else class="text-italic text-grey-7">Sem Observações</span>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-xs-12 col-sm-5" v-if="pag.movimentos?.length || pag.razao?.length">
        <!-- títulos movimentados -->
        <q-card bordered flat v-if="pag.movimentos?.length" class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline">
            TÍTULOS ({{ pag.movimentos.length }})
          </q-card-section>
          <q-list separator>
            <q-item
              v-for="m in pag.movimentos"
              :key="m.codmovimentotitulo"
              :to="toTitulo ? toTitulo(m.codtitulo) : undefined"
              :href="toTitulo ? undefined : urlTitulo(m.codtitulo)"
              :target="toTitulo ? undefined : '_blank'"
            >
              <q-item-section>
                <q-item-label class="text-weight-medium text-primary">
                  {{ m.titulo?.numero }}
                </q-item-label>
                <q-item-label caption v-if="m.titulo?.codpessoa != pag.codpessoa">
                  {{ m.titulo?.fantasia }}
                </q-item-label>
                <q-item-label caption :class="m.titulo?.gerencial ? 'text-orange' : 'text-green'">
                  {{ m.titulo?.filial }}
                </q-item-label>
                <q-item-label caption v-if="m.titulo?.boleto">
                  Boleto {{ m.titulo?.nossonumero }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label
                  class="text-weight-bold"
                  :class="m.operacao === 'CR' ? 'text-orange' : 'text-green'"
                >
                  {{ formataNumero(m.total) }} {{ m.operacao }}
                </q-item-label>
                <!-- juros, multa e desconto vêm na mesma linha da baixa -->
                <template v-if="m.juros || m.multa || m.desconto">
                  <q-item-label caption>Principal {{ formataNumero(m.principal) }}</q-item-label>
                  <q-item-label caption v-if="m.juros" class="text-orange">
                    Juros {{ formataNumero(m.juros) }}
                  </q-item-label>
                  <q-item-label caption v-if="m.multa" class="text-orange">
                    Multa {{ formataNumero(m.multa) }}
                  </q-item-label>
                  <q-item-label caption v-if="m.desconto" class="text-blue">
                    Desconto {{ formataNumero(m.desconto) }}
                  </q-item-label>
                </template>
                <q-item-label caption>{{ m.tipomovimentotitulo }}</q-item-label>
                <q-item-label caption>vence {{ formataData(m.titulo?.vencimento) }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- razão (M10): o que caiu em cada portador; riscado = desfeito -->
        <q-card bordered flat v-if="pag.razao?.length" class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline">RAZÃO</q-card-section>
          <q-list separator>
            <q-item v-for="r in pag.razao" :key="r.codportadormovimento">
              <q-item-section>
                <q-item-label :class="r.inativo ? 'text-strike text-grey-6' : ''">
                  {{ r.portador }}
                </q-item-label>
                <q-item-label caption>{{ r.periodo }}</q-item-label>
                <q-item-label caption v-if="r.inativo" class="text-grey-6">
                  desfeito em {{ formataTimestamp(r.inativo) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label
                  class="text-weight-bold"
                  :class="
                    r.inativo
                      ? 'text-strike text-grey-6'
                      : r.valor > 0
                        ? 'text-green-8'
                        : 'text-red-8'
                  "
                >
                  {{ r.valor > 0 ? '+' : '' }}{{ formataNumero(r.valor) }}
                </q-item-label>
                <q-item-label caption>{{ formataTimestamp(r.transacao) }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>
    </div>
  </div>
</template>
