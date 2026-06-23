<script setup>
import { computed } from 'vue'
import { formataNumero, formataData } from '@components/formatters'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'

// Card "Dados do contrato" da tela de detalhe. Layout manual (não array) na
// MESMA ordem do MgContratoForm: identificação → embarque → partes →
// observações. Cada campo tem a largura que o conteúdo pede (Janela larga,
// Local estreito; partes ganham um terço cada). Lê o contrato do store da tela
// e emite `editar` (a página abre o form compartilhado).
defineEmits(['editar'])

const store = useContratoDetalheStore()
const comissaoTipoLabel = { PERCENTUAL: '%', SACA: 'R$/sc', TOTAL: 'R$ total' }

function nomePessoa(p) {
  return p?.fantasia || p?.pessoa || '—'
}
function rs(v) {
  return 'R$ ' + formataNumero(v, 2)
}

const c = computed(() => store.contrato)

const operacao = computed(() => (c.value.operacao === 'COMPRA' ? 'Compra' : 'Venda'))
const quantidade = computed(() =>
  c.value.volumeemaberto ? 'Volume em aberto' : `${formataNumero(c.value.quantidade, 0)} sc`,
)
const janela = computed(() => {
  const ini = c.value.embarqueinicio ? formataData(c.value.embarqueinicio) : null
  const fim = c.value.embarquefim ? formataData(c.value.embarquefim) : null
  if (!ini && !fim) return null
  return `${ini || '—'} a ${fim || '—'}`
})

// Corretora só aparece se houver comissão lançada (espelha o form).
const temCorretora = computed(() => c.value.codpessoacorretora && Number(c.value.comissaovalor))
const comissao = computed(() => {
  if (!temCorretora.value) return null
  const tipo = comissaoTipoLabel[c.value.comissaotipo] || ''
  return `${tipo} ${formataNumero(c.value.comissaovalor, 2)}`.trim()
})
</script>

<template>
  <q-card flat bordered class="q-mb-md">
    <q-item class="bg-primary text-white">
      <q-item-section>
        <q-item-label class="text-subtitle1">Dados do contrato</q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn flat round size="sm" color="white" icon="edit" @click="$emit('editar')">
          <q-tooltip>Editar contrato</q-tooltip>
        </q-btn>
      </q-item-section>
    </q-item>
    <q-separator />

    <!-- Identificação + embarque -->
    <q-card-section class="row q-col-gutter-sm q-py-sm">
      <div class="col-6 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Data do contrato</div>
        <div class="text-body2">{{ formataData(c.datacontrato) }}</div>
      </div>
      <div class="col-6 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Operação</div>
        <div class="text-body2">{{ operacao }}</div>
      </div>
      <div class="col-6 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Quantidade</div>
        <div class="text-body2">{{ quantidade }}</div>
      </div>
      <div v-if="janela" class="col-12 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Janela de embarque</div>
        <div class="text-body2">{{ janela }}</div>
      </div>
      <div class="col-6 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Filial</div>
        <div class="text-body2">{{ c.Filial?.filial || '—' }}</div>
      </div>
      <div v-if="c.localentrega" class="col-6 col-md-4">
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
          Nº {{ c.numerocontraparte }}
        </div>
      </div>
      <div v-if="temCorretora" class="col-12 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Corretora</div>
        <div class="text-body2">{{ nomePessoa(c.Corretora) }}</div>
        <div v-if="c.numerocorretora" class="text-caption text-grey-7">
          Nº {{ c.numerocorretora }}
        </div>
        <div class="text-caption text-grey-7">
          Comissão {{ comissao }}
          <span v-if="c.comissaototal">· Total {{ rs(c.comissaototal) }}</span>
        </div>
      </div>
      <div v-if="c.codpessoacooperativa" class="col-12 col-md-4">
        <div class="text-caption text-uppercase text-grey-6">Cooperativa</div>
        <div class="text-body2">{{ nomePessoa(c.Cooperativa) }}</div>
        <div v-if="c.numerocooperativa" class="text-caption text-grey-7">
          Nº {{ c.numerocooperativa }}
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
