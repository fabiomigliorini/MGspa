<script setup>
// A maquineta e seus períodos (TASK-188 M9.8), no padrão do portador e seus períodos (doc-4): o
// cabeçalho da maquineta e os períodos em abas Ano → Mês → Período, só com o que existe. Cada
// período mostra a situação (aberto, pendente, conferido), o borderô × sistema, a foto e os
// lançamentos em cartão (venda, título, adiantamento), com as correções na linha. A URL leva
// direto ao período. Gerente da filial, Financeiro e Administrador. O cadastro (editar, parear,
// juntar, inativar, excluir) fica no cabeçalho, como no portador.
import { computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { formataNumero, formataDataAbreviada } from '@components/formatters'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'
import {
  maquinetaIntegracaoLabel,
  maquinetaIntegracaoColor,
} from 'src/constants/maquinetaIntegracao'
import PeriodoCabecalho from 'components/maquineta/PeriodoCabecalho.vue'
import PeriodoLancamentos from 'components/maquineta/PeriodoLancamentos.vue'
import MaquinetaDialog from 'components/maquineta/MaquinetaDialog.vue'
import MaquinetaParearDialog from 'components/maquineta/MaquinetaParearDialog.vue'
import MaquinetaJuntarDialog from 'components/maquineta/MaquinetaJuntarDialog.vue'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'

const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const store = useMaquinetaPeriodoStore()
const { maquineta, periodos, periodo, carregando } = storeToRefs(store)
const sMaquineta = useMaquinetaStore()

const codmaquineta = computed(() => Number(route.params.codmaquineta))
const codperiodo = computed(() =>
  route.params.codmaquinetalote ? Number(route.params.codmaquinetalote) : null,
)

// ---- abas: o período fica no mês do início ----
const ano = (p) => new Date(p.abertura).getFullYear()
const mes = (p) => new Date(p.abertura).getMonth()
const para = (p) => ({
  name: 'maquineta-detalhe',
  params: { codmaquineta: codmaquineta.value, codmaquinetalote: p.codmaquinetalote },
})
// a aba de ano ou mês leva ao período da tela, se estiver nela; senão, ao último dela
const destino = (lista) =>
  para(
    lista.find((p) => p.codmaquinetalote === periodo.value?.codmaquinetalote) ??
      lista[lista.length - 1],
  )

// anos, meses e períodos do mais novo para o mais antigo
const anos = computed(() =>
  [...new Set(periodos.value.map(ano))].reverse().map((a) => ({
    ano: a,
    to: destino(periodos.value.filter((p) => ano(p) === a)),
  })),
)
const doAno = computed(() =>
  periodo.value ? periodos.value.filter((p) => ano(p) === ano(periodo.value)) : [],
)
const meses = computed(() =>
  [...new Set(doAno.value.map(mes))].reverse().map((m) => ({
    mes: m,
    to: destino(doAno.value.filter((p) => mes(p) === m)),
  })),
)
const doMes = computed(() =>
  periodo.value ? doAno.value.filter((p) => mes(p) === mes(periodo.value)).reverse() : [],
)

// a aba mostra só a data final (15/jul/2026); sem fim, "aberto"
const rotulo = (p) => (p.fim ? formataDataAbreviada(p.fim, 4) : 'aberto')
const BADGE = { aberto: ['green-7', 'Aberto'], pendente: ['amber-8', 'Pendente'] }

// ---- cadastro ----
function excluir() {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir a maquineta "${maquineta.value.apelido}"?`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    if (await sMaquineta.excluir(maquineta.value)) router.push({ name: 'maquineta' })
  })
}

// juntada: esta deixa de existir, a tela vai para a que ficou
const juntada = (destino) =>
  router.push({ name: 'maquineta-detalhe', params: { codmaquineta: destino.codmaquineta } })

// ---- carregar: sem período na URL, o servidor manda o mais novo (o watch abaixo leva a URL) ----
async function carregar() {
  await store.carregar(codmaquineta.value, codperiodo.value)
}

watch([codmaquineta, codperiodo], ([cod, per]) => {
  if (cod === maquineta.value?.codmaquineta && per && per === periodo.value?.codmaquinetalote)
    return
  carregar()
})
onMounted(carregar)

watch(
  () => periodo.value?.codmaquinetalote,
  (id) => {
    if (id && !codperiodo.value) router.replace(para(periodo.value))
  },
)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <template v-if="maquineta">
        <div class="row items-center q-col-gutter-x-sm q-mb-sm">
          <div class="col-auto">
            <q-btn flat round icon="arrow_back" color="grey-7" :to="{ name: 'maquineta' }" />
          </div>
          <div class="col" style="min-width: 0">
            <div
              class="text-h5 ellipsis"
              :class="maquineta.inativo ? 'text-strike text-grey-6' : 'text-grey-9'"
            >
              {{ maquineta.apelido }}
            </div>
            <div class="text-caption text-grey-7">
              <q-badge
                :color="maquinetaIntegracaoColor(maquineta.integracao)"
                :label="maquinetaIntegracaoLabel(maquineta.integracao)"
                class="q-mr-xs"
              />
              {{ maquineta.adquirente }} ·
              {{ maquineta.compartilhada ? 'Todas as filiais' : maquineta.filial }}
              <template v-if="maquineta.serial"> · {{ maquineta.serial }}</template>
              <template v-if="maquineta.inativo"> · inativa</template>
            </div>
          </div>
          <div class="col-12 col-sm-auto">
            <div class="row items-center no-wrap justify-end">
              <MgInfoCriacao :registro="maquineta" />
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                @click="sMaquineta.abrirEditar(maquineta)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="maquineta.integracao === 'S' && !maquineta.inativo"
                flat
                round
                size="sm"
                color="grey-7"
                icon="qr_code_2"
                @click="sMaquineta.abrirParear(maquineta)"
              >
                <q-tooltip>Parear de novo</q-tooltip>
              </q-btn>
              <q-btn
                v-if="!maquineta.integracao"
                flat
                round
                size="sm"
                color="grey-7"
                icon="merge"
                @click="sMaquineta.abrirJuntar(maquineta)"
              >
                <q-tooltip>Juntar com outra</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                :icon="maquineta.inativo ? 'play_arrow' : 'pause'"
                @click="sMaquineta.alternarInativo(maquineta)"
              >
                <q-tooltip>{{ maquineta.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
              </q-btn>
              <q-btn
                v-if="!maquineta.integracao"
                flat
                round
                size="sm"
                color="grey-7"
                icon="delete"
                @click="excluir"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </div>
          </div>
        </div>

        <q-card v-if="periodos.length && periodo" flat bordered class="q-mb-md">
          <!-- abas: ano → mês → período -->
          <q-tabs
            align="left"
            outside-arrows
            mobile-arrows
            no-caps
            active-color="primary"
            indicator-color="primary"
            class="text-grey-8"
          >
            <q-route-tab v-for="a in anos" :key="a.ano" :to="a.to" exact :label="String(a.ano)" />
          </q-tabs>
          <q-separator />
          <q-tabs
            align="left"
            outside-arrows
            mobile-arrows
            no-caps
            active-color="primary"
            indicator-color="primary"
            class="text-grey-8"
          >
            <q-route-tab v-for="m in meses" :key="m.mes" :to="m.to" exact :label="MESES[m.mes]" />
          </q-tabs>
          <q-separator />
          <q-tabs
            align="left"
            outside-arrows
            mobile-arrows
            no-caps
            active-color="primary"
            indicator-color="primary"
            class="text-grey-8"
          >
            <q-route-tab v-for="p in doMes" :key="p.codmaquinetalote" :to="para(p)" exact>
              <div class="column items-center q-py-xs">
                <div class="text-weight-medium">{{ rotulo(p) }}</div>
                <div class="text-caption">R$ {{ formataNumero(p.total) }}</div>
                <q-badge
                  v-if="BADGE[p.situacao]"
                  :color="BADGE[p.situacao][0]"
                  :label="BADGE[p.situacao][1]"
                />
                <div v-else class="text-caption text-grey-6">conferido</div>
                <q-badge v-if="p.semBordero" color="orange-8" label="sem borderô" />
              </div>
            </q-route-tab>
          </q-tabs>
        </q-card>

        <div v-if="periodo" class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-md-5">
            <PeriodoCabecalho />
          </div>
          <div class="col-12 col-md-7">
            <PeriodoLancamentos />
          </div>
        </div>
        <MgEmptyState v-else-if="!carregando" icon="credit_card">
          Nenhum cartão nesta maquineta desde o início das conferências.
        </MgEmptyState>
      </template>
    </div>

    <q-inner-loading :showing="carregando" color="primary" />

    <MaquinetaDialog />
    <MaquinetaParearDialog />
    <MaquinetaJuntarDialog @juntada="juntada" />
  </q-page>
</template>
