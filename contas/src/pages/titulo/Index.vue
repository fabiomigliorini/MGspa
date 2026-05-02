<script setup>
import { onMounted } from 'vue'
import { date } from 'quasar'
import { formataNumero, tempoRelativo } from 'src/utils/formatters.js'
import { useTituloStore } from 'src/stores/tituloStore'

const store = useTituloStore()

const formatData = (v) => (v ? date.formatDate(v, 'DD/MM/YYYY') : '')
const formatCodigo = (v) => '#' + String(v).padStart(8, '0')

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
  if (store.items.length === 0) {
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
            <q-item-section style="min-width: 0">
              <q-item-label class="text-primary text-weight-medium ellipsis">
                {{ t.fantasia }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                <span class="text-weight-bold">{{ t.numero }}</span>
                · {{ formatCodigo(t.codtitulo) }}
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
                {{ formatData(t.vencimento) }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ tempoRelativo(t.vencimento) }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formatData(t.emissao) }}
              </q-item-label>
            </q-item-section>

            <!-- Valor / Saldo (sempre visível) -->
            <q-item-section style="flex: 0 0 95px; min-width: 0">
              <q-item-label class="lt-sm text-weight-bold text-right" :class="classeVencimento(t)">
                {{ formatData(t.vencimento) }}
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
      <q-btn fab icon="add" color="primary" :to="{ name: 'titulo-novo' }">
        <q-tooltip anchor="center left" self="center right">Novo Título</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
