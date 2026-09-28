<script setup>
import { onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { valeEmitidosStore } from 'stores/valeEmitidos'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import { formataCodigo, formataData, formataHora, formataNumero } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'

const route = useRoute()
const sVales = valeEmitidosStore()
const { filtros, vales, carregando } = storeToRefs(sVales)
const scrollRef = ref(null)

// Vindo do atalho de um modelo: a lista é só daquele modelo, sem sobra de
// filtro de uma visita anterior.
if (route.query.codvalemodelo) {
  sVales.limparFiltros()
  filtros.value.codpessoafavorecido = Number(route.query.codpessoafavorecido) || null
  filtros.value.codvalemodelo = Number(route.query.codvalemodelo)
}

const carregarMais = async (pagina, done) => done(!(await sVales.carregar(pagina)))

// Filtro mudou: volta o scroll para a pagina 1. Uma espera so para o filtro
// inteiro, porque o campo de valor emite a cada tecla.
let timer = null
watch(
  filtros,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      vales.value = []
      scrollRef.value.reset()
      scrollRef.value.resume()
      scrollRef.value.trigger()
    }, 500)
  },
  { deep: true },
)
onUnmounted(() => clearTimeout(timer))

const linkNegocio = (vale) => `/negocio/${vale.codnegocio}`
const linkTitulo = (vale) => `${process.env.CONTAS_URL}/titulo/${vale.codtitulo}`

const imprimir = () =>
  abrirPdf(api, 'v1/vale-emitido/relatorio', sVales.params, { title: 'Vales Compras Emitidos' })
</script>

<template>
  <q-page class="bg-grey-2">
    <q-infinite-scroll ref="scrollRef" @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <div class="row justify-end q-mb-sm">
          <q-btn flat color="primary" icon="card_giftcard" label="Modelos" to="/vale-modelo" />
          <q-btn
            flat
            color="primary"
            icon="print"
            label="Imprimir lista"
            :disable="!vales.length"
            @click="imprimir"
          />
        </div>

        <MgEmptyState v-if="!carregando && !vales.length" icon="card_giftcard">
          Nenhum vale emitido com esses filtros.
        </MgEmptyState>

        <q-card v-else bordered flat>
          <q-list separator>
            <!-- Cabecalho com as mesmas larguras das colunas, como o da tabela de modelos -->
            <q-item class="text-caption text-weight-medium text-grey-10" style="min-height: 56px">
              <q-item-section class="gt-xs" style="flex: 0 0 90px; max-width: 90px">
                Data
              </q-item-section>
              <q-item-section class="text-right" style="flex: 0 0 85px; max-width: 85px">
                Valor
              </q-item-section>
              <q-item-section class="gt-xs text-right" style="flex: 0 0 120px; max-width: 120px">
                Saldo
              </q-item-section>
              <q-item-section>Aluno</q-item-section>
              <q-item-section class="gt-sm">Favorecido</q-item-section>
              <q-item-section side class="text-grey-10" style="flex: 0 0 90px; max-width: 90px">
                Situação
              </q-item-section>
            </q-item>

            <!-- A linha leva ao negocio; o saldo, ao titulo no app contas. -->
            <q-item
              v-for="vale in vales"
              :key="vale.codnegociovale"
              :to="linkNegocio(vale)"
              class="q-py-sm text-grey-9"
            >
              <q-item-section class="gt-xs" style="flex: 0 0 70px; max-width: 90px">
                <q-item-label caption>{{ formataData(vale.lancamento) }}</q-item-label>
                <q-item-label caption>{{ formataHora(vale.lancamento, true) }}</q-item-label>
              </q-item-section>

              <q-item-section class="text-right" style="flex: 0 0 85px; max-width: 85px">
                <q-item-label class="text-grey-9 text-bold">
                  {{ formataNumero(vale.valorvale) }}
                </q-item-label>
                <q-item-label caption class="text-primary">
                  {{ formataCodigo(vale.codnegocio) }}
                </q-item-label>
              </q-item-section>

              <q-item-section class="gt-xs text-right" style="flex: 0 0 120px; max-width: 120px">
                <a
                  v-if="vale.codtitulo"
                  :href="linkTitulo(vale)"
                  target="_blank"
                  class="full-width"
                  style="text-decoration: none"
                >
                  <q-item-label class="text-grey-9">{{ formataNumero(vale.saldo) }}</q-item-label>
                  <q-item-label caption class="text-primary ellipsis">{{
                    vale.numero
                  }}</q-item-label>
                </a>
              </q-item-section>

              <q-item-section>
                <q-item-label class="text-bold ellipsis">{{ vale.aluno || '—' }}</q-item-label>
                <q-item-label caption class="ellipsis" v-if="vale.turma">
                  {{ vale.turma }}
                </q-item-label>
              </q-item-section>

              <q-item-section class="gt-sm">
                <q-item-label class="text-bold ellipsis">
                  {{ vale.codpessoafavorecido == 1 ? 'Ao portador' : vale.favorecido }}
                </q-item-label>
                <q-item-label caption class="ellipsis" v-if="vale.modelo">
                  {{ vale.modelo }}
                </q-item-label>
              </q-item-section>

              <q-item-section side style="flex: 0 0 90px; max-width: 90px">
                <q-badge v-if="vale.cancelado" color="orange-7">Cancelado</q-badge>
                <q-badge v-else color="green-6">Ativo</q-badge>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>
  </q-page>
</template>
