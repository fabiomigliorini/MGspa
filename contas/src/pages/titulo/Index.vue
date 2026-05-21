<script setup>
import { onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { formataNumero, tempoRelativo, formataData, formataCodigo } from '@components/formatters'
import { useTituloStore } from 'src/stores/tituloStore'
import { abrirPdf } from 'src/utils/abrirPdf'

const store = useTituloStore()
const $q = useQuasar()
const route = useRoute()

function abrirRelatorio() {
  $q.dialog({
    title: 'Relatório de Títulos',
    message:
      'Deseja o relatório com as informações de Tipo do Título, Conta Contábil e Observações?',
    ok: { label: 'Sim, detalhado', color: 'primary', unelevated: true, flat: true },
    cancel: { label: 'Não, simplificado', color: 'primary', unelevated: true, flat: true },
    persistent: false,
  })
    .onOk(() => gerarPdf(true))
    .onCancel(() => gerarPdf(false))
}

function gerarPdf(detalhado) {
  const params = { detalhado: detalhado ? 1 : 0 }
  for (const [k, v] of Object.entries(store.filters)) {
    if (v !== null && v !== undefined && v !== '') params[k] = v
  }
  abrirPdf('v1/titulo/listagem/relatorio', params, { title: 'Títulos' })
}

function classeVencimento(t) {
  if (!t.saldo || Number(t.saldo) === 0) return 'text-grey'
  if (new Date(t.vencimento) < new Date()) return 'text-red'
  return 'text-green'
}

function classeValor(t) {
  return t.operacao === 'CR' ? 'text-orange' : 'text-green'
}

function statusIcon(t) {
  if (t.estornado) return { icon: 'undo', color: 'grey' }
  if (Number(t.saldo) === 0) return { icon: 'done_all', color: 'green' }
  if (new Date(t.vencimento) < new Date()) return { icon: 'warning', color: 'red' }
  return { icon: 'receipt_long', color: 'primary' }
}

async function carregarMais(index, done) {
  await store.fetchItems(false)
  done(!store.hasMore)
}

onMounted(() => {
  let queryApplied = false
  for (const key of Object.keys(store.filters)) {
    if (route.query[key] === undefined) continue
    const raw = route.query[key]
    store.filters[key] = /^cod/.test(key) ? Number(raw) : raw
    queryApplied = true
  }
  if (queryApplied || store.items.length === 0) {
    store.fetchItems(true)
  }
})
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <q-list v-if="store.items.length > 0" bordered separator class="bg-white rounded-borders">
          <q-item
            v-for="t in store.items"
            :key="t.codtitulo"
            clickable
            :to="{ name: 'titulo-detalhe', params: { codtitulo: t.codtitulo } }"
            :class="{ 'bg-red-1': !!t.estornado }"
          >
            <!-- Avatar status -->
            <q-item-section avatar class="gt-xs">
              <q-avatar
                :icon="statusIcon(t).icon"
                :color="statusIcon(t).color"
                text-color="white"
                size="40px"
              />
            </q-item-section>

            <!-- Pessoa, Número, Tipo/Conta -->
            <q-item-section>
              <q-item-label class="text-primary text-weight-medium ellipsis">
                {{ t.fantasia }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                <span class="text-weight-bold">{{ t.numero }}</span>
                · {{ formataCodigo(t.codtitulo) }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ t.tipotitulo }} · {{ t.contacontabil }}
              </q-item-label>
            </q-item-section>

            <!-- Filial / Portador / Nosso Número -->
            <q-item-section class="gt-xs" style="flex: 0 0 200px; min-width: 0">
              <q-item-label
                caption
                class="ellipsis"
                :class="t.gerencial ? 'text-orange' : 'text-green'"
              >
                {{ t.filial }}
              </q-item-label>
              <q-item-label caption class="ellipsis" v-if="t.portador">
                {{ t.portador }}
              </q-item-label>
              <q-item-label caption class="ellipsis" v-if="t.nossonumero">
                {{ t.nossonumero }}
              </q-item-label>
            </q-item-section>

            <!-- Datas (gt-xs) -->
            <q-item-section class="gt-xs" style="flex: 0 0 130px; min-width: 0">
              <q-item-label class="text-weight-bold ellipsis" :class="classeVencimento(t)">
                {{ formataData(t.vencimento) }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ tempoRelativo(t.vencimento) }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formataData(t.emissao) }}
              </q-item-label>
            </q-item-section>

            <!-- Valor / Saldo (sempre visível) -->
            <q-item-section style="flex: 0 0 95px; min-width: 0">
              <q-item-label class="lt-sm text-weight-bold text-right" :class="classeVencimento(t)">
                {{ formataData(t.vencimento) }}
              </q-item-label>
              <q-item-label class="text-weight-bold text-right" :class="classeValor(t)">
                <span v-if="Number(t.saldo) !== 0">
                  {{ formataNumero(Math.abs(t.saldo)) }} {{ t.operacaosaldo }}
                </span>
                <span v-else-if="t.estornado" class="text-grey">Estornado</span>
                <span v-else class="text-grey">Liquidado</span>
              </q-item-label>
              <q-item-label class="text-grey text-caption text-right">
                {{ formataNumero(Math.abs(t.valor)) }} {{ t.operacao }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else-if="!store.loading" class="text-center text-grey q-pa-xl">
          Nenhum título encontrado
        </div>

        <div v-if="store.items.length" class="text-caption text-grey q-mt-md text-center">
          {{ store.items.length }} de {{ store.total }} título(s)
        </div>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row q-gutter-sm items-end">
        <q-btn fab-mini color="grey-8" icon="print" @click="abrirRelatorio">
          <q-tooltip anchor="top middle" self="bottom middle">Relatório</q-tooltip>
        </q-btn>
        <q-btn fab icon="add" color="primary" :to="{ name: 'titulo-novo' }">
          <q-tooltip anchor="top middle" self="bottom middle">Novo Título</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
