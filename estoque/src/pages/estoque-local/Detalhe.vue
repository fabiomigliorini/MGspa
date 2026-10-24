<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import MgInputData from '@components/MgInputData.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataCodigo, formataData, formataNumeroInteligente } from '@components/formatters'
import { hoje, inicioMes } from '@components/utils/datas'
import { useEstoqueLocalStore } from 'src/stores/estoqueLocalStore'
import EstoqueLocalDialog from 'components/estoque-local/EstoqueLocalDialog.vue'

// Detalhe do local de estoque (TASK-55): dados, impressora da via de separação e, na etapa
// 11, os indicadores de qualidade do estoque dele como requisitado.
const route = useRoute()
const sLocal = useEstoqueLocalStore()

// indicadores: período pela criação da requisição; cada número filtra a lista
const periodo = ref({ inicio: inicioMes(hoje()), fim: hoje() })
const filtro = ref(null)

const MOTIVOS = {
  E: 'Estoque do sistema errado',
  A: 'Avariado / vencido',
  N: 'Não localizado',
  M: 'Enviado a mais',
  S: 'Sem saldo',
}

const divergentes = computed(() => {
  const lista = sLocal.indicadores?.divergentes || []
  if (!filtro.value) return lista
  if (filtro.value === 'substituido') return lista.filter((i) => i.codprodutobarrasubstituto)
  return lista.filter((i) => i.motivo === filtro.value)
})

function carregarIndicadores() {
  if (!route.params.id || !periodo.value.inicio || !periodo.value.fim) return
  sLocal.carregarIndicadores(route.params.id, periodo.value.inicio, periodo.value.fim)
}

watch(
  () => route.params.id,
  (id) => {
    if (!id) return
    sLocal.carregarLocal(id)
    carregarIndicadores()
  },
  { immediate: true },
)
watch(periodo, carregarIndicadores, { deep: true })
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div v-if="sLocal.carregandoLocal && !sLocal.local" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <template v-else-if="sLocal.local">
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center no-wrap">
            <q-btn flat round icon="arrow_back" class="q-mr-sm" :to="{ name: 'estoque-local' }" />
            <q-avatar color="brown-6" text-color="white" font-size="14px" class="q-mr-md">
              {{ sLocal.local.sigla }}
            </q-avatar>
            <div class="col">
              <div class="text-h5 ellipsis">{{ sLocal.local.estoquelocal }}</div>
              <div class="text-caption text-grey-7">
                {{ formataCodigo(sLocal.local.codestoquelocal) }} · {{ sLocal.local.filial }}
              </div>
            </div>
            <q-badge v-if="sLocal.local.inativo" color="orange-7" class="q-mr-sm">Inativo</q-badge>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="edit"
              @click="sLocal.editar(sLocal.local)"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              :icon="sLocal.local.inativo ? 'play_arrow' : 'pause'"
              @click="sLocal.alternarInativo(sLocal.local)"
            >
              <q-tooltip>{{ sLocal.local.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
          </q-card-section>
          <q-separator />
          <q-list>
            <q-item>
              <q-item-section avatar><q-icon name="print" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Impressora da via de separação</q-item-label>
                <q-item-label>{{
                  sLocal.local.impressora || 'Nenhuma (não imprime sozinho)'
                }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="inventory" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Estoque</q-item-label>
                <q-item-label>
                  {{ sLocal.local.controlaestoque ? 'Controla estoque' : 'Não controla estoque' }}
                  <template v-if="sLocal.local.deposito"> · depósito</template>
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- INDICADORES DE QUALIDADE DO ESTOQUE (como requisitado) -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline q-pb-none">
            Qualidade do estoque nas requisições atendidas
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-6 col-sm-3">
                <MgInputData v-model="periodo.inicio" label="De" />
              </div>
              <div class="col-6 col-sm-3">
                <MgInputData v-model="periodo.fim" label="Até" />
              </div>
            </div>
          </q-card-section>
          <q-card-section v-if="sLocal.indicadores" class="q-pt-none">
            <div class="row q-gutter-sm items-center">
              <q-chip
                clickable
                :outline="filtro !== null"
                color="grey-8"
                text-color="white"
                @click="filtro = null"
              >
                {{ sLocal.indicadores.resumo.itens }} itens ·
                {{ sLocal.indicadores.resumo.atendidos }} atendidos 100%
                <template v-if="sLocal.indicadores.resumo.percentual !== null">
                  ({{ formataNumeroInteligente(sLocal.indicadores.resumo.percentual) }}%)
                </template>
              </q-chip>
              <q-chip
                v-for="(nome, cod) in MOTIVOS"
                :key="cod"
                clickable
                :outline="filtro !== cod"
                color="deep-orange-7"
                :text-color="filtro === cod ? 'white' : undefined"
                @click="filtro = cod"
              >
                {{ nome }}: {{ sLocal.indicadores.resumo.motivos[cod] }}
              </q-chip>
              <q-chip
                clickable
                :outline="filtro !== 'substituido'"
                color="blue-7"
                :text-color="filtro === 'substituido' ? 'white' : undefined"
                @click="filtro = 'substituido'"
              >
                Substituídos: {{ sLocal.indicadores.resumo.substituidos }}
              </q-chip>
            </div>
          </q-card-section>
          <MgEmptyState
            v-if="sLocal.indicadores && !divergentes.length"
            icon="fact_check"
            class="q-mb-md"
          >
            Nenhum item divergente no período.
          </MgEmptyState>
          <q-list v-else-if="sLocal.indicadores" separator>
            <q-item
              v-for="i in divergentes"
              :key="i.codrequisicaoitem"
              clickable
              :to="{ name: 'produto-detalhe', params: { id: i.codproduto } }"
            >
              <q-item-section>
                <q-item-label>{{ i.produto }} {{ i.variacao }}</q-item-label>
                <q-item-label caption>
                  {{ formataData(i.criacao) }} · requisição {{ formataCodigo(i.codrequisicao) }} de
                  {{ i.siglarequisitante }} · {{ i.barras }}
                </q-item-label>
                <q-item-label caption class="text-deep-orange-7" v-if="i.motivo">
                  {{ MOTIVOS[i.motivo] }}
                  <template v-if="i.produtosubstituto">
                    · substituído por {{ i.produtosubstituto }} {{ i.variacaosubstituto }}
                  </template>
                  <template v-if="i.observacao"> · {{ i.observacao }}</template>
                  <template v-if="i.usuariojustificou"> · {{ i.usuariojustificou }}</template>
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label>
                  {{ formataNumeroInteligente(i.atendido) }} de
                  {{ formataNumeroInteligente(i.quantidade) }} {{ i.sigla }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>

    <EstoqueLocalDialog />
  </q-page>
</template>
