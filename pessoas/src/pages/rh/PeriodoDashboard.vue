<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { rhStore } from 'src/stores/rh'
import { useAuthStore } from 'src/stores'
import { feriadoStore } from 'src/stores/feriado'
import { api } from 'boot/axios'
import { formataData, formataNumero } from '@components/formatters'
import { abrirPdf } from '@components/abrirPdf'
import { extrairErro } from 'src/utils/rhFormatters'
import { useReprocessamentoPeriodo } from 'src/composables/useReprocessamentoPeriodo'
import CockpitResumo from 'src/components/rh/CockpitResumo.vue'
import CockpitUnidade from 'src/components/rh/CockpitUnidade.vue'
import DialogUnidade from 'src/components/rh/DialogUnidade.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const sRh = rhStore()
const user = useAuthStore()
const sFeriado = feriadoStore()

const loading = ref(false)
const tab = ref(route.query.tab || 'resumo')

const podeEditar = computed(() => user.temPermissao('Recursos Humanos'))

// Cockpit e KPIs leem do mesmo payload (v1/rh/periodo/{cod}/resumo == dashboard)
const dash = computed(() => sRh.resumo || {})
const periodo = computed(() => dash.value.periodo || {})
const totalColaboradores = computed(() => dash.value.totalcolaboradores || 0)
const colaboradoresEncerrados = computed(() => dash.value.colaboradoresencerrados || 0)
const colaboradoresAbertos = computed(
  () => totalColaboradores.value - colaboradoresEncerrados.value,
)
const totalSalario = computed(() => dash.value.totalsalario || 0)
const totalAdicional = computed(() => dash.value.totaladicional || 0)
const totalEncargos = computed(() => dash.value.totalencargos || 0)
const totalVariaveis = computed(() => dash.value.totalvariaveis || 0)
const custoTotal = computed(() => dash.value.total || 0)

// --- ABAS POR UNIDADE ---

const unidadeTabs = computed(() => {
  const map = new Map()
  ;(sRh.resumo?.unidades || []).forEach((u) => {
    if (u.codunidadenegocio && !map.has(u.codunidadenegocio)) {
      map.set(u.codunidadenegocio, u)
    }
  })
  return Array.from(map.values()).sort((a, b) =>
    (a.descricao || '').localeCompare(b.descricao || ''),
  )
})

// --- DIAS ÚTEIS ---

const diasUteisBanco = computed(() => periodo.value.diasuteis || 0)
const diasUteisCalculados = computed(() => periodo.value.diasuteiscalculados || 0)
const diasUteisDivergem = computed(() => diasUteisBanco.value !== diasUteisCalculados.value)

const editandoDiasUteis = ref(false)
const modelDiasUteis = ref(0)

const editarDiasUteis = () => {
  modelDiasUteis.value = diasUteisBanco.value
  editandoDiasUteis.value = true
}

const salvarDiasUteis = async () => {
  try {
    await sRh.atualizarPeriodo(route.params.codperiodo, { diasuteis: modelDiasUteis.value })
    editandoDiasUteis.value = false
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Dias úteis atualizado',
    })
    await carregar(route.params.codperiodo)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao atualizar dias úteis'),
    })
  }
}

const usarCalculado = () => {
  modelDiasUteis.value = diasUteisCalculados.value
}

const feriadosDoPeriodo = computed(() => {
  if (!periodo.value.periodoinicial || !periodo.value.periodofinal) return []
  const ini = periodo.value.periodoinicial.substring(0, 10)
  const fim = periodo.value.periodofinal.substring(0, 10)
  return (sFeriado.listagem || []).filter((f) => {
    if (f.inativo) return false
    const d = f.data?.substring(0, 10)
    return d && d >= ini && d <= fim
  })
})

// --- DIALOG EDITAR PERÍODO ---

const dialogPeriodo = ref(false)
const modelPeriodo = ref({})

const editarPeriodo = () => {
  modelPeriodo.value = {
    periodoinicial: periodo.value.periodoinicial?.substring(0, 10) || '',
    periodofinal: periodo.value.periodofinal?.substring(0, 10) || '',
    observacoes: periodo.value.observacoes || '',
    percentualmaxdesconto: periodo.value.percentualmaxdesconto ?? null,
  }
  dialogPeriodo.value = true
}

const salvarPeriodo = async () => {
  dialogPeriodo.value = false
  try {
    await sRh.atualizarPeriodo(route.params.codperiodo, modelPeriodo.value)
    $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Período atualizado' })
    await sRh.getPeriodos()
    await carregar(route.params.codperiodo)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao atualizar período'),
    })
  }
}

// --- AÇÕES DO PERÍODO ---

const fecharPeriodo = () => {
  $q.dialog({
    title: 'Fechar Período',
    message: 'Tem certeza que deseja fechar este período?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Fechar', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      await sRh.fecharPeriodo(route.params.codperiodo)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Período fechado' })
      await sRh.getPeriodos()
      await carregar(route.params.codperiodo)
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao fechar período'),
      })
    }
  })
}

const reabrirPeriodo = () => {
  $q.dialog({
    title: 'Reabrir Período',
    message: 'Tem certeza que deseja reabrir este período?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      await sRh.reabrirPeriodo(route.params.codperiodo)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Período reaberto' })
      await sRh.getPeriodos()
      await carregar(route.params.codperiodo)
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao reabrir período'),
      })
    }
  })
}

const duplicarPeriodo = () => {
  $q.dialog({
    title: 'Duplicar Período',
    message:
      'Será criado um novo período com a mesma configuração (colaboradores e rubricas recorrentes).',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Duplicar', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      const ret = await sRh.duplicarPeriodo(route.params.codperiodo)
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Período duplicado',
      })
      await sRh.getPeriodos()
      router.push({ name: 'rhDashboard', params: { codperiodo: ret.data.data.codperiodo } })
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao duplicar período'),
      })
    }
  })
}

const importarEstrutura = () => {
  $q.dialog({
    title: 'Importar Estrutura do Período Anterior',
    message:
      'Isto vai copiar colaboradores e rubricas recorrentes do período anterior. A operação é segura e pode ser repetida — registros já existentes são preservados.',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Importar', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      await sRh.importarEstruturaPeriodo(route.params.codperiodo)
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Estrutura importada com sucesso',
      })
      await sRh.getPeriodos()
      await carregar(route.params.codperiodo)
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao importar estrutura'),
      })
    }
  })
}

const excluirPeriodo = () => {
  $q.dialog({
    title: 'Excluir Período',
    message:
      'Tem certeza que deseja excluir este período? Todos os colaboradores, rubricas e indicadores serão removidos.',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.excluirPeriodo(route.params.codperiodo)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Período excluído' })
      await sRh.getPeriodos()
      if (sRh.periodos.length > 0) {
        router.push({ name: 'rhDashboard', params: { codperiodo: sRh.periodos[0].codperiodo } })
      } else {
        router.push({ name: 'rhIndex' })
      }
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao excluir período'),
      })
    }
  })
}

// --- REPROCESSAMENTO (composable) ---

const {
  reprocessando,
  progresso,
  reprocessarPeriodo,
  cancelarReprocessamento,
  pararPolling,
  checarEmAndamento,
} = useReprocessamentoPeriodo(
  () => route.params.codperiodo,
  () => carregar(route.params.codperiodo),
)

// --- APLICAR RUBRICA EM MASSA (presenteísmo) ---

const dialogMassa = ref(false)
const modelMassa = ref({ codrubrica: null })

const rubricaOptions = computed(() =>
  (sRh.rubricas || [])
    .filter((r) => !r.inativo)
    .slice()
    .sort((a, b) => (a.descricao || '').localeCompare(b.descricao || '', 'pt-BR'))
    .map((r) => ({ label: r.descricao, value: r.codrubrica })),
)

const abrirMassa = async () => {
  modelMassa.value = { codrubrica: null }
  if (sRh.rubricas.length === 0) {
    try {
      await sRh.getRubricas()
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao carregar catálogo de rubricas'),
      })
    }
  }
  dialogMassa.value = true
}

const aplicarMassa = async () => {
  if (!modelMassa.value.codrubrica) return
  dialogMassa.value = false
  try {
    await sRh.aplicarRubricaMassa(route.params.codperiodo, modelMassa.value.codrubrica)
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Rubrica aplicada a todos os colaboradores',
    })
    await carregar(route.params.codperiodo)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao aplicar rubrica em massa'),
    })
  }
}

// --- ADICIONAR UNIDADE ---

const dialogUnidade = ref(false)

// --- PDF / RELATÓRIOS (helper compartilhado @components/abrirPdf) ---

const relatorioFolha = () => {
  abrirPdf(
    api,
    'v1/rh/periodo/' + route.params.codperiodo + '/acertos/relatorio-folha',
    {},
    { title: 'Relatório Folha' },
  )
}

// Todos os recibos de todos os colaboradores do período.
const imprimirRecibosPeriodo = () => {
  abrirPdf(
    api,
    'v1/rh/periodo/' + route.params.codperiodo + '/acertos/recibos',
    {},
    { title: 'Recibos do período' },
  )
}

// Empresas (por CNPJ) vindas do resumo — filial do colaborador → empresa.
const empresasCartao = computed(() =>
  (dash.value.empresascartao || []).map((e) => ({ cod: e.codempresa, nome: e.empresa })),
)

const baixarPlanilhaCartao = async (empresa) => {
  try {
    const ret = await api.get(
      'v1/rh/periodo/' + route.params.codperiodo + '/acertos/planilha-cartao',
      { params: { codempresa: empresa.cod }, responseType: 'blob' },
    )
    const url = URL.createObjectURL(new Blob([ret.data]))
    const a = document.createElement('a')
    a.href = url
    a.download = 'cartao-' + empresa.nome.toLowerCase() + '-' + route.params.codperiodo + '.xlsx'
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: 'Erro ao gerar planilha ' + empresa.nome,
    })
  }
}

// --- LIFECYCLE ---

const carregar = async (codperiodo) => {
  if (!codperiodo) return
  loading.value = true
  try {
    await Promise.all([
      sRh.getResumo(codperiodo),
      sFeriado.listagem.length === 0 ? sFeriado.getListagem() : Promise.resolve(),
    ])
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao carregar período'),
    })
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await carregar(route.params.codperiodo)
  await checarEmAndamento()
})

watch(
  () => route.params.codperiodo,
  async (novoId) => {
    if (!novoId || route.name !== 'rhDashboard') return
    pararPolling()
    tab.value = 'resumo'
    await carregar(novoId)
    await checarEmAndamento()
  },
)

watch(tab, (novoTab) => {
  if (route.query.tab !== novoTab) {
    router.replace({ query: { ...route.query, tab: novoTab } })
  }
})
</script>

<template>
  <!-- DIALOG EDITAR PERÍODO -->
  <q-dialog v-model="dialogPeriodo">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvarPeriodo()">
        <q-card-section class="text-grey-9 text-overline">EDITAR PERÍODO</q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <MgInputData
                v-model="modelPeriodo.periodoinicial"
                label="Período Inicial"
                type="date"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-6">
              <MgInputData
                v-model="modelPeriodo.periodofinal"
                label="Período Final"
                type="date"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-8">
              <q-input
                outlined
                v-model="modelPeriodo.observacoes"
                label="Observações"
                type="textarea"
                rows="2"
                autogrow
              />
            </div>
            <div class="col-4">
              <MgInputValor
                v-model="modelPeriodo.percentualmaxdesconto"
                label="% Máx. Desconto Folha"
                :min="0"
                :max="100"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG APLICAR RUBRICA EM MASSA -->
  <q-dialog v-model="dialogMassa">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="aplicarMassa()">
        <q-card-section class="text-grey-9 text-overline">APLICAR RUBRICA A TODOS</q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-select
            outlined
            v-model="modelMassa.codrubrica"
            label="Rubrica do Catálogo"
            :options="rubricaOptions"
            map-options
            emit-value
            autofocus
            :rules="[(val) => !!val || 'Obrigatório']"
          />
          <div class="text-caption text-grey q-mt-sm">
            A rubrica será aplicada a todos os colaboradores do período (ex: presenteísmo R$200).
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Aplicar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG UNIDADE (criar/editar) -->
  <DialogUnidade v-model="dialogUnidade" @salvo="carregar(route.params.codperiodo)" />

  <div style="max-width: 1280px; margin: auto">
    <q-inner-loading :showing="loading" />

    <template v-if="!loading && periodo.codperiodo">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar color="amber" text-color="white" size="80px" icon="event_note" />
        </q-item-section>
        <q-item-section>
          <div class="text-h4 text-grey-9">
            {{ formataData(periodo.periodoinicial) }} a
            {{ formataData(periodo.periodofinal) }}
            <q-badge
              :color="periodo.status === 'A' ? 'green' : 'grey'"
              :label="periodo.status === 'A' ? 'Aberto' : 'Fechado'"
              class="q-ml-sm"
            />
          </div>
          <div class="text-caption text-grey" v-if="periodo.observacoes">
            {{ periodo.observacoes }}
          </div>
        </q-item-section>
        <q-item-section side top v-if="podeEditar">
          <div>
            <q-btn
              v-if="periodo.status === 'A'"
              flat
              round
              icon="edit"
              size="sm"
              color="grey-7"
              @click="editarPeriodo()"
            >
              <q-tooltip>Editar Período</q-tooltip>
            </q-btn>
            <q-btn
              v-if="periodo.status === 'A'"
              flat
              round
              icon="lock"
              size="sm"
              color="orange-7"
              :disable="reprocessando"
              @click="fecharPeriodo()"
            >
              <q-tooltip>Fechar Período</q-tooltip>
            </q-btn>
            <q-btn
              v-if="periodo.status === 'F'"
              flat
              round
              icon="lock_open"
              size="sm"
              color="grey-7"
              @click="reabrirPeriodo()"
            >
              <q-tooltip>Reabrir Período</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              icon="content_copy"
              size="sm"
              color="grey-7"
              @click="duplicarPeriodo()"
            >
              <q-tooltip>Duplicar Período</q-tooltip>
            </q-btn>
            <q-btn
              v-if="periodo.status === 'A'"
              flat
              round
              icon="cloud_download"
              size="sm"
              color="grey-7"
              @click="importarEstrutura()"
            >
              <q-tooltip>Importar Estrutura do Período Anterior</q-tooltip>
            </q-btn>
            <q-btn
              v-if="periodo.status === 'A'"
              flat
              round
              icon="sync"
              size="sm"
              color="grey-7"
              :disable="reprocessando"
              @click="reprocessarPeriodo()"
            >
              <q-tooltip>Reprocessar Indicadores</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              icon="delete"
              size="sm"
              color="red-7"
              :disable="reprocessando"
              @click="excluirPeriodo()"
            >
              <q-tooltip>Excluir Período</q-tooltip>
            </q-btn>
          </div>
        </q-item-section>
      </q-item>

      <div class="q-pa-md">
        <!-- CARDS RESUMO -->
        <div class="row q-col-gutter-md q-mb-md items-stretch">
          <div class="col-xs-4 col-sm">
            <q-card
              bordered
              flat
              class="full-height"
              :class="diasUteisDivergem && !editandoDiasUteis ? 'bg-red-1' : ''"
            >
              <q-card-section class="text-center" style="cursor: help" v-if="!editandoDiasUteis">
                <div class="text-caption" :class="diasUteisDivergem ? 'text-red' : 'text-grey'">
                  Dias Úteis
                  <q-icon name="info_outline" size="14px" />
                </div>
                <div class="text-h5" :class="diasUteisDivergem ? 'text-red' : 'text-grey-9'">
                  {{ diasUteisBanco }}
                  <q-btn
                    v-if="podeEditar && periodo.status === 'A'"
                    flat
                    round
                    icon="edit"
                    size="xs"
                    :color="diasUteisDivergem ? 'red' : 'grey-7'"
                    @click="editarDiasUteis()"
                  />
                </div>
                <q-tooltip>
                  <div>Calculado: {{ diasUteisCalculados }} dias</div>
                  <div class="text-caption">Seg a Sáb, excluindo feriados</div>
                </q-tooltip>
              </q-card-section>
              <q-card-section v-else class="text-center q-py-sm">
                <div class="text-caption text-grey q-mb-xs">Dias Úteis</div>
                <div class="row items-center justify-center no-wrap q-gutter-xs">
                  <q-input
                    v-model.number="modelDiasUteis"
                    type="number"
                    outlined
                    style="max-width: 70px"
                    input-class="text-center"
                    @keyup.enter="salvarDiasUteis()"
                  />
                  <q-btn
                    flat
                    round
                    icon="done"
                    size="sm"
                    color="green"
                    @click="salvarDiasUteis()"
                  />
                  <q-btn
                    flat
                    round
                    icon="close"
                    size="sm"
                    color="grey"
                    @click="editandoDiasUteis = false"
                  />
                </div>
                <q-btn
                  v-if="diasUteisBanco !== diasUteisCalculados"
                  flat
                  size="xs"
                  :label="'Usar calculado (' + diasUteisCalculados + ')'"
                  color="primary"
                  class="q-mt-xs"
                  @click="usarCalculado()"
                />
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-4 col-sm">
            <q-card bordered flat class="full-height">
              <q-card-section
                class="text-center"
                :style="feriadosDoPeriodo.length > 0 ? 'cursor: help' : ''"
              >
                <div class="text-caption text-grey">
                  Feriados
                  <q-icon v-if="feriadosDoPeriodo.length > 0" name="info_outline" size="14px" />
                </div>
                <div class="text-h5 text-grey-9">{{ feriadosDoPeriodo.length }}</div>
                <q-tooltip v-if="feriadosDoPeriodo.length > 0">
                  <div v-for="f in feriadosDoPeriodo" :key="f.codferiado">
                    {{ formataData(f.data, 0) }} — {{ f.feriado }}
                  </div>
                </q-tooltip>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-4 col-sm">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center">
                <div class="text-caption text-grey">Colaboradores</div>
                <div class="text-h5 text-grey-9">{{ totalColaboradores }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-4 col-sm">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center">
                <div class="text-caption text-grey">Abertos</div>
                <div class="text-h5 text-grey-9">{{ colaboradoresAbertos }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-4 col-sm">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center">
                <div class="text-caption text-grey">Encerrados</div>
                <div class="text-h5 text-grey-9">{{ colaboradoresEncerrados }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-4 col-sm">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center" style="cursor: help">
                <div class="text-caption text-grey">
                  Custo Total
                  <q-icon name="info_outline" size="14px" />
                </div>
                <div class="text-h5 text-grey-9">{{ formataNumero(custoTotal) }}</div>
                <q-tooltip>
                  <div>Salários: {{ formataNumero(totalSalario) }}</div>
                  <div>Adicional: {{ formataNumero(totalAdicional) }}</div>
                  <div>Encargos: {{ formataNumero(totalEncargos) }}</div>
                  <div>Variáveis: {{ formataNumero(totalVariaveis) }}</div>
                </q-tooltip>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- TOOLBAR DE AÇÕES DO PERÍODO -->
        <div class="row items-center q-gutter-sm q-mb-md" v-if="podeEditar">
          <q-btn
            flat
            icon="group_add"
            label="Aplicar Rubrica a Todos"
            color="primary"
            @click="abrirMassa()"
          >
            <q-tooltip>Aplicar uma rubrica do catálogo a todos (ex: presenteísmo R$200)</q-tooltip>
          </q-btn>
          <q-space />
          <q-btn flat icon="print" label="Recibos" color="grey-7" @click="imprimirRecibosPeriodo()">
            <q-tooltip>Todos os recibos de todos os colaboradores do período</q-tooltip>
          </q-btn>
          <q-btn
            flat
            icon="description"
            label="Relatório Folha"
            color="grey-7"
            @click="relatorioFolha()"
          />
          <q-btn
            v-for="e in empresasCartao"
            :key="e.cod"
            flat
            icon="credit_card"
            :label="'Cartão ' + e.nome"
            color="grey-7"
            @click="baixarPlanilhaCartao(e)"
          />
        </div>

        <!-- BARRA DE REPROCESSAMENTO -->
        <q-card bordered flat class="q-mb-md" v-if="reprocessando && progresso">
          <q-card-section class="q-py-sm">
            <div class="row items-center q-gutter-sm">
              <q-spinner color="primary" size="20px" />
              <span class="text-body2 text-grey-8">{{ progresso.mensagem }}</span>
              <q-space />
              <q-btn
                flat
                round
                icon="cancel"
                size="sm"
                color="red-7"
                @click="cancelarReprocessamento()"
              >
                <q-tooltip>Cancelar</q-tooltip>
              </q-btn>
            </div>
            <q-linear-progress
              :value="(progresso.progresso || 0) / 100"
              size="8px"
              stripe
              animated
              rounded
              color="primary"
              class="q-mt-sm"
            />
          </q-card-section>
        </q-card>

        <!-- TABS: RESUMÃO + UMA POR UNIDADE (+ adicionar unidade) -->
        <div class="row items-center no-wrap">
          <q-tabs
            v-model="tab"
            align="left"
            active-color="primary"
            indicator-color="primary"
            class="col text-grey-7"
            no-caps
          >
            <q-tab name="resumo" label="Resumão" />
            <q-tab
              v-for="u in unidadeTabs"
              :key="u.codunidadenegocio"
              :name="'un-' + u.codunidadenegocio"
            >
              <div class="row items-center no-wrap" :class="u.inativo ? 'text-grey-5' : ''">
                <span>{{ u.descricao }}</span>
                <q-icon v-if="u.inativo" name="pause" size="xs" class="q-ml-xs" />
              </div>
            </q-tab>
          </q-tabs>
          <q-btn
            v-if="podeEditar"
            flat
            round
            icon="add"
            size="sm"
            color="primary"
            @click="dialogUnidade = true"
          >
            <q-tooltip>Adicionar unidade</q-tooltip>
          </q-btn>
        </div>
        <q-separator />

        <q-tab-panels v-model="tab" animated class="bg-grey-2">
          <q-tab-panel name="resumo" class="q-pa-none q-mt-md">
            <CockpitResumo />
          </q-tab-panel>

          <q-tab-panel
            v-for="u in unidadeTabs"
            :key="u.codunidadenegocio"
            :name="'un-' + u.codunidadenegocio"
            class="q-pa-none q-mt-md"
          >
            <CockpitUnidade
              :codperiodo="route.params.codperiodo"
              :codunidade="u.codunidadenegocio"
              :descricao="u.descricao"
              :unidade="u"
              :podeEditar="podeEditar"
              :periodoStatus="periodo.status"
              @atualizado="carregar(route.params.codperiodo)"
            />
          </q-tab-panel>
        </q-tab-panels>
      </div>
    </template>
  </div>
</template>
