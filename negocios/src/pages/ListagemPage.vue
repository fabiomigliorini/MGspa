<script setup>
import { formataNumero, formataTimestamp, formataCodigo } from '@components/formatters'
import { abrirPdf } from '@components/abrirPdf'
import MgEmptyState from '@components/MgEmptyState.vue'
import { ref, watch } from 'vue'
import { debounce } from 'quasar'
import { api } from 'boot/axios'
import { iconeNegocio, corIconeNegocio } from '../utils/iconeNegocio.js'
import { listagemStore } from 'src/stores/listagem'
import { sincronizacaoStore } from 'stores/sincronizacao'

const sListagem = listagemStore()
const sSinc = sincronizacaoStore()
const scrollRef = ref(null)

const onLoad = async (index, done) => {
  await sListagem.getNegociosPaginacao()
  if (sListagem.paginacao.current_page >= sListagem.paginacao.last_page) {
    done(true)
  } else {
    done(false)
  }
}

const inicializa = debounce(async () => {
  await sListagem.getNegocios()
  try {
    scrollRef.value.reset()
    scrollRef.value.resume()
  } catch (error) {
    console.log(error)
  }
})

watch(
  () => sListagem.filtro,
  () => {
    inicializa()
  },
  { deep: true },
)

// aberto amarelo, cancelado vermelho (como a listagem de pagamentos)
const CLASSE_LINHA = { 1: 'bg-amber-1', 3: 'bg-red-1' }
const COR_STATUS = { 1: 'amber-8', 2: 'green-7', 3: 'red-7' }

// relatório do MGsis com os mesmos filtros da tela (TASK-189)
const relatorio = () =>
  abrirPdf(
    api,
    'v1/pdv/negocio/relatorio',
    { ...sListagem.filtro, pdv: sSinc.pdv.uuid },
    { title: 'Relatório de Negócios' },
  )
</script>
<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-infinite-scroll @load="onLoad" ref="scrollRef" :offset="250">
        <q-list
          v-if="sListagem.negocios.length"
          bordered
          separator
          class="bg-white rounded-borders overflow-hidden"
        >
          <q-item
            v-for="item in sListagem.negocios"
            :key="item.codnegocio"
            :to="'/negocio/' + item.codnegocio"
            :class="CLASSE_LINHA[item.codnegociostatus]"
          >
            <q-item-section avatar>
              <q-avatar
                :icon="iconeNegocio(item)"
                :color="corIconeNegocio(item)"
                text-color="white"
              />
            </q-item-section>

            <!-- pessoa, natureza e vendedor -->
            <q-item-section style="min-width: 0">
              <q-item-label class="text-weight-medium ellipsis">
                {{ item.fantasia || '—' }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formataCodigo(item.codnegocio) }} · {{ item.naturezaoperacao }}
              </q-item-label>
              <q-item-label caption class="ellipsis" v-if="item.fantasiavendedor">
                {{ item.fantasiavendedor }}
              </q-item-label>
            </q-item-section>

            <!-- local, usuário, PDV -->
            <q-item-section class="gt-xs" style="flex: 0 0 200px; min-width: 0">
              <q-item-label class="ellipsis">{{ item.estoquelocal }}</q-item-label>
              <q-item-label caption class="ellipsis">{{ item.usuario }}</q-item-label>
              <q-item-label caption class="ellipsis" v-if="item.pdv">
                {{ [item.pdv, item.setor].filter(Boolean).join(' · ') }}
              </q-item-label>
            </q-item-section>

            <!-- total, data e status -->
            <q-item-section side style="min-width: 130px">
              <q-item-label class="text-weight-bold text-grey-9">
                R$ {{ formataNumero(item.valortotal) }}
              </q-item-label>
              <q-item-label caption>{{ formataTimestamp(item.lancamento, 4, true) }}</q-item-label>
              <q-item-label caption>
                <q-badge :color="COR_STATUS[item.codnegociostatus]" :label="item.negociostatus" />
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <MgEmptyState v-else-if="!sListagem.carregando" icon="shopping_cart">
          Nenhum negócio no período e filtros escolhidos.
        </MgEmptyState>

        <div v-if="sListagem.negocios.length" class="text-caption text-grey q-mt-md text-center">
          {{ sListagem.negocios.length }} de {{ sListagem.paginacao.total }}
        </div>

        <template v-slot:loading>
          <div class="row justify-center q-my-md">
            <q-spinner-dots color="primary" size="32px" />
          </div>
        </template>
      </q-infinite-scroll>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab-mini
        color="grey-8"
        icon="print"
        :disable="!sListagem.negocios.length"
        @click="relatorio"
      >
        <q-tooltip anchor="center left" self="center right">Relatório em PDF</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
