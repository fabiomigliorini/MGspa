<script setup>
import { computed } from 'vue'
import { formataNumero, formataData, formataPercentual, formataReal } from '@components/formatters'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'

// Card "Dados do contrato" da tela de detalhe. Layout manual (não array) na
// MESMA ordem do ContratoForm: identificação → embarque → partes →
// observações. Cada campo tem a largura que o conteúdo pede (Janela larga,
// Local estreito; partes ganham um terço cada). Lê o contrato do store da tela
// e emite `editar` (a página abre o form compartilhado).
defineEmits(['editar'])

const store = useContratoDetalheStore()

function nomePessoa(p) {
  return p?.fantasia || p?.pessoa || '—'
}
function n(v) {
  return Number(v) || 0
}
const rs = formataReal
// Moeda genérica (R$/US$/€) — a comissão % de cada fixação sai na moeda dela.
function fmtMoeda(moeda, valor) {
  return `${store.simboloMoeda(moeda)} ${formataNumero(valor, 2)}`
}

const c = computed(() => store.contrato)

// Valor total do contrato = Σ(quantidade × preço) das fixações ativas (getter do
// store). Só faz sentido depois de ao menos uma fixação; antes não há preço.
const valorTotal = computed(() => (store.fixacoes.length ? store.valorFixadoBruto : null))

const operacao = computed(() => {
  if (!c.value.operacao) return '—'
  return c.value.operacao === 'COMPRA' ? 'Compra' : 'Venda'
})
const quantidade = computed(() =>
  c.value.volumeemaberto ? 'Volume em aberto' : `${formataNumero(c.value.quantidade, 0)} sc`,
)
const janela = computed(() => {
  const ini = c.value.embarqueinicio ? formataData(c.value.embarqueinicio) : null
  const fim = c.value.embarquefim ? formataData(c.value.embarquefim) : null
  if (!ini && !fim) return null
  return `${ini || '—'} a ${fim || '—'}`
})

// Corretora aparece sempre que houver corretora vinculada (espelha o form, que
// revela os campos por codpessoacorretora — não pela comissão).
const temCorretora = computed(() => !!c.value.codpessoacorretora)
const comissao = computed(() => {
  if (!temCorretora.value || c.value.comissaovalor == null) return null
  const v = c.value.comissaovalor
  switch (c.value.comissaotipo) {
    case 'PERCENTUAL':
      return formataPercentual(v)
    case 'SACA':
      return `${formataReal(v)}/sc`
    case 'TOTAL':
      return formataReal(v)
    default:
      return formataNumero(v, 2)
  }
})

// Comissão PERCENTUAL: o valor não é único no contrato (o preço vive na fixação).
// Uma linha por fixação, na moeda da liquidação — R$ quando BRL ou câmbio 100%
// travado (base = totalbrl); US$ enquanto a fixação estrangeira não travou
// (base = totalmoeda, ainda em dólar).
const ehPercentual = computed(() => c.value.comissaotipo === 'PERCENTUAL')
const comissaoLinhas = computed(() => {
  const pct = n(c.value.comissaovalor) / 100
  if (!ehPercentual.value || pct <= 0) return []
  return store.fixacoes.map((f) => {
    const travado = !store.ehUsd(f) || n(f.saldomoeda) <= 0.005
    return {
      cod: f.codcontratofixacao,
      quantidade: n(f.quantidade),
      moeda: travado ? 'BRL' : f.moeda,
      valor: (travado ? n(f.totalbrl) : n(f.totalmoeda)) * pct,
    }
  })
})
// Total da comissão por moeda ("R$ x + US$ y"). Só quando há mais de uma linha
// (com uma única fixação o total repetiria a linha).
const comissaoTotais = computed(() => {
  if (comissaoLinhas.value.length < 2) return []
  const map = {}
  for (const l of comissaoLinhas.value) {
    map[l.moeda] = (map[l.moeda] || 0) + l.valor
  }
  return Object.entries(map).map(([moeda, valor]) => fmtMoeda(moeda, valor))
})
</script>

<template>
  <q-card flat bordered class="q-mb-md">
    <q-item>
      <q-item-section>
        <q-item-label class="text-subtitle1">Dados do contrato</q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn flat round size="sm" color="grey-7" icon="edit" @click="$emit('editar')">
          <q-tooltip>Editar contrato</q-tooltip>
        </q-btn>
      </q-item-section>
    </q-item>
    <q-separator />

    <!-- Identificação + embarque -->
    <q-card-section class="row q-col-gutter-sm q-py-sm">
      <div class="col-6 col-md-3">
        <div class="text-caption text-uppercase text-grey-6">Data do contrato</div>
        <div class="text-body2">{{ formataData(c.datacontrato) }}</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-caption text-uppercase text-grey-6">Operação</div>
        <div class="text-body2">{{ operacao }}</div>
      </div>

      <div class="col-6 col-md-3">
        <div class="text-caption text-uppercase text-grey-6">Quantidade</div>
        <div class="text-body2">{{ quantidade }}</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-caption text-uppercase text-grey-6">Total do Contrato</div>
        <div class="text-body2">{{ valorTotal != null ? rs(valorTotal) : '—' }}</div>
      </div>
      <div v-if="janela" class="col-12 col-md-3">
        <div class="text-caption text-uppercase text-grey-6">Janela de embarque</div>
        <div class="text-body2">{{ janela }}</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-caption text-uppercase text-grey-6">Filial</div>
        <div class="text-body2">{{ c.Filial?.filial || '—' }}</div>
      </div>
      <div v-if="c.localentrega" class="col-6 col-md-3">
        <div class="text-caption text-uppercase text-grey-6">Local / FOB-CIF</div>
        <div class="text-body2">{{ c.localentrega }}</div>
      </div>
    </q-card-section>

    <q-separator inset />

    <!-- Partes -->
    <q-card-section class="row q-col-gutter-sm q-py-sm">
      <div class="col-12 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Contraparte</div>
        <div class="text-body2">{{ nomePessoa(c.Pessoa) }}</div>
        <div v-if="c.numerocontraparte" class="text-caption text-grey-7">
          {{ c.numerocontraparte }}
        </div>
      </div>
      <div v-if="temCorretora" class="col-12 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Corretora</div>
        <div class="text-body2">{{ nomePessoa(c.Corretora) }}</div>
        <div v-if="c.numerocorretora" class="text-caption text-grey-7">
          {{ c.numerocorretora }}
        </div>
        <div v-if="comissao" class="text-caption text-grey-7">
          <div>
            Comissão {{ comissao }}
            <span v-if="!ehPercentual && c.comissaototal">· Total {{ rs(c.comissaototal) }}</span>
          </div>
          <template v-if="comissaoLinhas.length">
            <div v-for="l in comissaoLinhas" :key="l.cod" class="q-pl-sm">
              {{ formataNumero(l.quantidade, 0) }} sc · {{ fmtMoeda(l.moeda, l.valor) }}
            </div>
            <div v-if="comissaoTotais.length" class="q-pl-sm text-grey-8 text-weight-medium">
              Total {{ comissaoTotais.join(' + ') }}
            </div>
          </template>
        </div>
      </div>
      <div v-if="c.codpessoacooperativa" class="col-12 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Cooperativa</div>
        <div class="text-body2">{{ nomePessoa(c.Cooperativa) }}</div>
        <div v-if="c.numerocooperativa" class="text-caption text-grey-7">
          {{ c.numerocooperativa }}
        </div>
      </div>
    </q-card-section>

    <!-- Observações -->
    <template v-if="c.observacao">
      <q-separator inset />
      <q-card-section>
        <div class="text-caption text-uppercase text-grey-6">Observações</div>
        <div class="text-body2" style="white-space: pre-line">{{ c.observacao }}</div>
      </q-card-section>
    </template>
  </q-card>
</template>
