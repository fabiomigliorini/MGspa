<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { api } from 'src/boot/axios'
import { rhStore } from 'src/stores/rh'
import { useAuthStore } from 'src/stores'
import { formataData, formataFromNow, formataNumero } from '@components/formatters'
import { abrirPdf } from '@components/abrirPdf'
import { tipoIndicadorLabel, extrairErro } from 'src/utils/rhFormatters'
import DialogEditarMeta from './DialogEditarMeta.vue'
import AcertoModal from './AcertoModal.vue'
import CardRubricas from 'src/components/rh/CardRubricas.vue'
import MgInputValor from '@components/MgInputValor.vue'

const $q = useQuasar()
const route = useRoute()
const sRh = rhStore()
const user = useAuthStore()

const loading = ref(false)
const podeEditar = computed(() => user.temPermissao('Recursos Humanos'))

// --- DADOS DO COLABORADOR ---

const colaborador = ref(null)

const nome = computed(() => colaborador.value?.colaborador?.pessoa?.fantasia || '—')
const cargo = computed(() => {
  const cargos = colaborador.value?.colaborador?.colaborador_cargo_s || []
  return cargos.length > 0 ? cargos[0].cargo?.cargo : null
})
const setorNome = computed(() => colaborador.value?.setor?.setor || null)
const rubricas = computed(() =>
  (colaborador.value?.colaborador_rubrica_s || [])
    .slice()
    .sort((a, b) => (a.descricao || '').localeCompare(b.descricao || '', 'pt-BR')),
)
const indicadores = computed(() => colaborador.value?.indicadores || [])

// --- HELPERS ---

const linkTitulo = computed(() => {
  if (!colaborador.value?.codtitulo) return ''
  return process.env.MGSIS_URL + 'index.php?r=titulo/view&id=' + colaborador.value.codtitulo
})

const diasUteisPeriodo = computed(() => {
  const p = (sRh.periodos || []).find(
    (per) => String(per.codperiodo) === String(route.params.codperiodo),
  )
  return p?.diasuteis || 0
})

// Voltar reabre a aba da unidade do colaborador no cockpit (derivada do resource).
const voltarTo = computed(() => {
  const cod = colaborador.value?.setor?.codunidadenegocio
  return {
    name: 'rhDashboard',
    params: { codperiodo: route.params.codperiodo },
    query: cod ? { tab: 'un-' + cod } : {},
  }
})

// --- DIALOG EDITAR COLABORADOR (setor + gestor) ---

const setorOptions = computed(() => sRh.setores || [])

const dialogColaborador = ref(false)
const salvandoColaborador = ref(false)
const formColaborador = ref({ codsetor: null, gestor: false })

const abrirEdicaoColaborador = () => {
  formColaborador.value = {
    codsetor: colaborador.value.codsetor,
    gestor: colaborador.value.gestor,
  }
  dialogColaborador.value = true
}

const salvarColaborador = async () => {
  if (salvandoColaborador.value) return
  salvandoColaborador.value = true
  const f = formColaborador.value
  try {
    if (f.codsetor !== colaborador.value.codsetor) {
      await sRh.atualizarSetorColaborador(
        route.params.codperiodo,
        colaborador.value.codperiodocolaborador,
        f.codsetor,
      )
    }
    if (f.gestor !== colaborador.value.gestor) {
      await sRh.toggleGestor(route.params.codperiodo, colaborador.value.codperiodocolaborador)
    }
    dialogColaborador.value = false
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Colaborador atualizado',
    })
    await recarregar()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao salvar colaborador'),
    })
  } finally {
    salvandoColaborador.value = false
  }
}

// --- DIALOG RUBRICA ---

const dialogRubrica = ref(false)
const isNovaRubrica = ref(false)
const salvando = ref(false)
const modelRubrica = ref({})

const modeloVazio = () => ({
  codcolaboradorrubrica: null,
  codrubrica: 0,
  descricao: '',
  tipovalor: 'F',
  percentual: null,
  valorfixo: null,
  valorunitario: null,
  quantidade: null,
  codindicador: null,
  tipocondicao: null,
  codindicadorcondicao: null,
  concedido: true,
  recorrente: true,
  observacao: '',
})

const rubricaOptions = computed(() => [
  { label: 'Outros (avulsa)', value: 0 },
  ...(sRh.rubricas || [])
    .filter((r) => !r.inativo)
    .slice()
    .sort((a, b) => (a.descricao || '').localeCompare(b.descricao || '', 'pt-BR'))
    .map((r) => ({ label: r.descricao, value: r.codrubrica })),
])

const totalQ = computed(
  () =>
    (Number(modelRubrica.value.valorunitario) || 0) * (Number(modelRubrica.value.quantidade) || 0),
)

const abrirNovaRubrica = () => {
  isNovaRubrica.value = true
  modelRubrica.value = modeloVazio()
  mostrarTodosBase.value = false
  mostrarTodosCondicao.value = false
  dialogRubrica.value = true
}

const editarRubrica = (r) => {
  isNovaRubrica.value = false
  mostrarTodosBase.value = false
  mostrarTodosCondicao.value = false
  modelRubrica.value = {
    codcolaboradorrubrica: r.codcolaboradorrubrica,
    codrubrica: r.codrubrica || 0,
    descricao: r.descricao || '',
    tipovalor: r.tipovalor,
    percentual: r.percentual,
    valorfixo: r.valorfixo,
    valorunitario: r.valorunitario,
    quantidade: r.quantidade,
    codindicador: r.codindicador,
    tipocondicao: r.tipocondicao,
    codindicadorcondicao: r.codindicadorcondicao,
    concedido: r.concedido,
    recorrente: r.recorrente,
    observacao: r.observacao || '',
  }
  dialogRubrica.value = true
}

const onSelecionarRubrica = (codrubrica) => {
  modelRubrica.value.codrubrica = codrubrica
  if (!codrubrica) {
    modelRubrica.value.descricao = ''
    return
  }
  const r = (sRh.rubricas || []).find((x) => x.codrubrica === codrubrica)
  if (!r) return
  // Snapshot da descrição do catálogo (coluna é NOT NULL — igual ao aplicarMassa).
  modelRubrica.value.descricao = r.descricao
  modelRubrica.value.tipovalor = r.tipovalor
  modelRubrica.value.tipocondicao = r.tipocondicao
  modelRubrica.value.recorrente = r.recorrente
  if (r.tipovalor === 'F') {
    modelRubrica.value.valorfixo = r.valorpadrao
  } else if (r.tipovalor === 'P') {
    modelRubrica.value.percentual = r.valorpadrao
  } else if (r.tipovalor === 'Q') {
    modelRubrica.value.valorunitario = r.valorunitariopadrao
    if (!modelRubrica.value.quantidade) modelRubrica.value.quantidade = diasUteisPeriodo.value
  }
}

// Ao trocar para Unitário × Quantidade, pré-preenche a quantidade com os dias úteis
watch(
  () => modelRubrica.value.tipovalor,
  (tipo) => {
    if (tipo === 'Q' && !modelRubrica.value.quantidade) {
      modelRubrica.value.quantidade = diasUteisPeriodo.value
    }
  },
)

const salvarRubrica = async () => {
  if (salvando.value) return
  salvando.value = true
  const m = modelRubrica.value
  const payload = {
    codrubrica: m.codrubrica || null,
    descricao: m.descricao,
    tipovalor: m.tipovalor,
    percentual: m.percentual,
    valorfixo: m.valorfixo,
    valorunitario: m.valorunitario,
    quantidade: m.quantidade,
    codindicador: m.codindicador,
    tipocondicao: m.tipocondicao,
    codindicadorcondicao: m.codindicadorcondicao,
    concedido: m.concedido,
    recorrente: m.recorrente,
    observacao: m.observacao,
  }
  try {
    if (isNovaRubrica.value) {
      await sRh.criarColaboradorRubrica(colaborador.value.codperiodocolaborador, payload)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Rubrica criada' })
    } else {
      await sRh.atualizarColaboradorRubrica(m.codcolaboradorrubrica, payload)
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Rubrica atualizada',
      })
    }
    dialogRubrica.value = false
    await recarregar()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao salvar rubrica'),
    })
  } finally {
    salvando.value = false
  }
}

const excluirRubrica = (r) => {
  $q.dialog({
    title: 'Excluir Rubrica',
    message: 'Tem certeza que deseja excluir esta rubrica?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.excluirColaboradorRubrica(r.codcolaboradorrubrica)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Rubrica excluída' })
      await recarregar()
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao excluir rubrica'),
      })
    }
  })
}

const toggleConcedido = async (r) => {
  try {
    await sRh.toggleConcedido(r.codcolaboradorrubrica)
    await recarregar()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao alterar concedido'),
    })
  }
}

const submitRubrica = () => {
  salvarRubrica()
}

// --- DIALOG EDITAR META ---

const dialogMeta = ref(false)
const indicadorMeta = ref(null)

const editarMeta = (ind) => {
  indicadorMeta.value = ind
  dialogMeta.value = true
}

// --- AÇÕES HEADER ---

const recalcularColaborador = async () => {
  try {
    await sRh.recalcular(route.params.codperiodo, colaborador.value.codperiodocolaborador)
    $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Recalculado' })
    await recarregar()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao recalcular'),
    })
  }
}

const encerrarColaborador = () => {
  $q.dialog({
    title: 'Encerrar Colaborador',
    message: 'Tem certeza? Um título será gerado.',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Encerrar', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.encerrar(route.params.codperiodo, colaborador.value.codperiodocolaborador)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Encerrado' })
      await recarregar()
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao encerrar'),
      })
    }
  })
}

const estornarColaborador = () => {
  $q.dialog({
    title: 'Estornar Encerramento',
    message: 'Tem certeza? O título será cancelado.',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Estornar', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.estornar(route.params.codperiodo, colaborador.value.codperiodocolaborador)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Estornado' })
      await recarregar()
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao estornar'),
      })
    }
  })
}

// --- SELECT OPTIONS (INDICADORES) ---

// Rótulo funciona com os dois shapes: colaborador.indicadores (com relations)
// e a lista completa do período (IndicadorResource, campos *_nome).
const montarLabelIndicador = (ind) => {
  let label = tipoIndicadorLabel(ind.tipo)
  const un = ind.unidade_negocio?.descricao ?? ind.unidade_negocio_nome
  const setor = ind.setor?.setor ?? ind.setor_nome
  const contexto = [un, setor, ind.colaborador_nome].filter(Boolean).join(' / ')
  if (contexto) label += ' — ' + contexto
  if (ind.valoracumulado) label += ' (' + formataNumero(ind.valoracumulado) + ')'
  return label
}

const montarOptions = (lista) =>
  lista.map((ind) => ({ label: montarLabelIndicador(ind), value: ind.codindicador }))

const indicadorOptions = computed(() => montarOptions(indicadores.value))
const indicadorOptionsTodos = computed(() => montarOptions(sRh.indicadores || []))

// "Mostrar todos": troca a lista estreita (setor/unidade do colaborador) pela
// lista completa do período. getIndicadores é buscado no clique.
const mostrarTodosBase = ref(false)
const mostrarTodosCondicao = ref(false)
const selBase = ref(null)
const selCondicao = ref(null)

const expandirIndicadores = async (qual) => {
  await sRh.getIndicadores(route.params.codperiodo)
  if (qual === 'base') {
    mostrarTodosBase.value = true
    nextTick(() => selBase.value?.showPopup())
  } else {
    mostrarTodosCondicao.value = true
    nextTick(() => selCondicao.value?.showPopup())
  }
}

// --- ACERTO (ENCONTRO DE CONTAS) ---

const acertoStatus = ref(null) // 'pendente' | 'efetivado' | null
const dialogAcerto = ref(false)

// Status do acerto vem da lista de acertos (só o encerrado tem acerto).
const fetchAcertoStatus = async () => {
  acertoStatus.value = null
  if (!colaborador.value || colaborador.value.status !== 'E') return
  try {
    const ret = await sRh.getAcertos(route.params.codperiodo)
    const item = (ret.data.data || []).find(
      (a) => String(a.codperiodocolaborador) === String(colaborador.value.codperiodocolaborador),
    )
    acertoStatus.value = item?.status_acerto || null
  } catch {
    acertoStatus.value = null
  }
}

const imprimirRecibo = () => {
  abrirPdf(
    api,
    'v1/rh/periodo/' +
      route.params.codperiodo +
      '/acertos/' +
      colaborador.value.codperiodocolaborador +
      '/recibos',
    {},
    { title: 'Recibo — ' + nome.value },
  )
}

const realizarAcerto = () => {
  dialogAcerto.value = true
}

const onAcertoEfetivado = async () => {
  await recarregar()
}

const estornarAcerto = () => {
  $q.dialog({
    title: 'Estornar Acerto',
    message: 'Tem certeza que deseja estornar o acerto deste colaborador?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Estornar', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.estornarAcerto(route.params.codperiodo, colaborador.value.codperiodocolaborador)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Acerto estornado' })
      await recarregar()
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao estornar acerto'),
      })
    }
  })
}

// --- LIFECYCLE ---

const recarregar = async () => {
  await sRh.getColaboradores(route.params.codperiodo)
  colaborador.value =
    sRh.colaboradores.find(
      (c) => String(c.codperiodocolaborador) === String(route.params.codperiodocolaborador),
    ) || null
  await fetchAcertoStatus()
}

const carregar = async () => {
  loading.value = true
  try {
    await Promise.all([
      sRh.colaboradores.length === 0
        ? sRh.getColaboradores(route.params.codperiodo)
        : Promise.resolve(),
      sRh.periodos.length === 0 ? sRh.getPeriodos() : Promise.resolve(),
      sRh.rubricas.length === 0 ? sRh.getRubricas() : Promise.resolve(),
      sRh.setores.length === 0 ? sRh.getSetores() : Promise.resolve(),
    ])
    colaborador.value =
      sRh.colaboradores.find(
        (c) => String(c.codperiodocolaborador) === String(route.params.codperiodocolaborador),
      ) || null
    await fetchAcertoStatus()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao carregar colaborador'),
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  carregar()
})

watch(
  () => route.params.codperiodocolaborador,
  () => {
    if (route.name === 'rhColaboradorDetalhe') carregar()
  },
)
</script>

<template>
  <!-- DIALOG RUBRICA -->
  <q-dialog v-model="dialogRubrica">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-form @submit.prevent="submitRubrica()">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="isNovaRubrica">NOVA RUBRICA</template>
          <template v-else>EDITAR RUBRICA</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- CATÁLOGO -->
            <div class="col-12">
              <q-select
                outlined
                :model-value="modelRubrica.codrubrica"
                label="Rubrica do Catálogo"
                :options="rubricaOptions"
                map-options
                emit-value
                autofocus
                @update:model-value="onSelecionarRubrica"
              />
            </div>

            <!-- DESCRIÇÃO (só avulsa) -->
            <div class="col-12" v-if="!modelRubrica.codrubrica">
              <q-input
                outlined
                v-model="modelRubrica.descricao"
                label="Descrição"
                :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
              />
            </div>

            <!-- TIPO DE CÁLCULO -->
            <div class="col-12">
              <small class="text-grey">Tipo de cálculo:</small>
              <q-radio
                v-model="modelRubrica.tipovalor"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="F"
                label="Fixo"
              />
              <q-radio
                v-model="modelRubrica.tipovalor"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="P"
                label="Percentual"
              />
              <q-radio
                v-model="modelRubrica.tipovalor"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="Q"
                label="Unitário × Quantidade"
              />
            </div>

            <!-- FIXO -->
            <div class="col-12" v-if="modelRubrica.tipovalor === 'F'">
              <MgInputValor
                v-model="modelRubrica.valorfixo"
                label="Valor Fixo"
                prefix="R$"
                :rules="[(val) => val != null || 'Obrigatório']"
              />
            </div>

            <!-- PERCENTUAL + INDICADOR BASE -->
            <template v-if="modelRubrica.tipovalor === 'P'">
              <div class="col-4">
                <MgInputValor
                  v-model="modelRubrica.percentual"
                  label="Percentual %"
                  :rules="[(val) => val > 0 || 'Obrigatório']"
                />
              </div>
              <div class="col-8">
                <q-select
                  ref="selBase"
                  outlined
                  v-model="modelRubrica.codindicador"
                  label="Indicador base"
                  :options="mostrarTodosBase ? indicadorOptionsTodos : indicadorOptions"
                  map-options
                  emit-value
                  clearable
                >
                  <template #after-options>
                    <template v-if="!mostrarTodosBase">
                      <q-separator />
                      <q-item clickable @click="expandirIndicadores('base')">
                        <q-item-section avatar>
                          <q-icon name="expand_more" color="grey-7" />
                        </q-item-section>
                        <q-item-section class="text-grey-8">
                          Mostrar todos os indicadores
                        </q-item-section>
                      </q-item>
                    </template>
                  </template>
                </q-select>
              </div>
            </template>

            <!-- UNITÁRIO × QUANTIDADE -->
            <template v-if="modelRubrica.tipovalor === 'Q'">
              <div class="col-4">
                <MgInputValor
                  v-model="modelRubrica.valorunitario"
                  label="Valor unitário"
                  prefix="R$"
                  :rules="[(val) => val != null || 'Obrigatório']"
                />
              </div>
              <div class="col-4">
                <q-input
                  outlined
                  v-model.number="modelRubrica.quantidade"
                  label="Quantidade"
                  type="number"
                  :rules="[(val) => val > 0 || 'Obrigatório']"
                />
              </div>
              <div class="col-4">
                <q-input outlined :model-value="formataNumero(totalQ)" label="Total" readonly>
                  <template #prepend>
                    <q-icon name="functions" />
                  </template>
                </q-input>
              </div>
            </template>

            <!-- CONDIÇÃO -->
            <div class="col-12">
              <small class="text-grey">Condição:</small>
              <q-radio
                v-model="modelRubrica.tipocondicao"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                :val="null"
                label="Nenhuma"
              />
              <q-radio
                v-model="modelRubrica.tipocondicao"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="M"
                label="Meta Atingida"
              />
              <q-radio
                v-model="modelRubrica.tipocondicao"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="R"
                label="Ranking (1º lugar)"
              />
            </div>

            <!-- INDICADOR DA CONDIÇÃO -->
            <div class="col-12" v-if="modelRubrica.tipocondicao">
              <q-select
                ref="selCondicao"
                outlined
                v-model="modelRubrica.codindicadorcondicao"
                label="Indicador da Condição"
                :options="mostrarTodosCondicao ? indicadorOptionsTodos : indicadorOptions"
                map-options
                emit-value
                clearable
              >
                <template #after-options>
                  <template v-if="!mostrarTodosCondicao">
                    <q-separator />
                    <q-item clickable @click="expandirIndicadores('condicao')">
                      <q-item-section avatar>
                        <q-icon name="expand_more" color="grey-7" />
                      </q-item-section>
                      <q-item-section class="text-grey-8">
                        Mostrar todos os indicadores
                      </q-item-section>
                    </q-item>
                  </template>
                </template>
              </q-select>
            </div>

            <!-- TOGGLES -->
            <div class="col-12">
              <q-toggle v-model="modelRubrica.concedido" label="Concedido" />
              <q-toggle v-model="modelRubrica.recorrente" label="Recorrente" />
            </div>

            <!-- OBSERVAÇÃO -->
            <div class="col-12">
              <q-input
                outlined
                v-model="modelRubrica.observacao"
                label="Observação"
                type="textarea"
                autogrow
                rows="2"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG EDITAR META -->
  <DialogEditarMeta v-model="dialogMeta" :indicador="indicadorMeta" @salvo="recarregar()" />

  <!-- MODAL ACERTO (Encontro de Contas) -->
  <AcertoModal
    v-if="colaborador"
    v-model="dialogAcerto"
    :colaborador="colaborador"
    :codperiodo="route.params.codperiodo"
    @efetivado="onAcertoEfetivado()"
  />

  <!-- DIALOG EDITAR COLABORADOR (setor + gestor) -->
  <q-dialog v-model="dialogColaborador">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvarColaborador()">
        <q-card-section class="text-grey-9 text-overline"> EDITAR COLABORADOR </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-select
                outlined
                v-model="formColaborador.codsetor"
                label="Setor"
                :options="setorOptions"
                option-label="label"
                option-value="codsetor"
                emit-value
                map-options
                autofocus
              />
            </div>
            <div class="col-12">
              <q-toggle v-model="formColaborador.gestor" label="Gestor" />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Salvar" type="submit" :loading="salvandoColaborador" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- CONTEÚDO PRINCIPAL -->
  <div style="max-width: 1280px; margin: auto">
    <q-inner-loading :showing="loading" />

    <template v-if="!loading && colaborador">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar color="grey-8" text-color="grey-4" size="80px" v-if="nome !== '—'">
            {{ nome.slice(0, 1) }}
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <div class="text-h4 text-grey-9">
            <router-link
              v-if="colaborador?.colaborador?.codpessoa"
              :to="{
                name: 'pessoaView',
                params: { id: colaborador.colaborador.codpessoa },
              }"
              class="text-primary"
            >
              {{ nome }}
            </router-link>
            <template v-else>{{ nome }}</template>
          </div>
          <div class="text-h5 text-grey-7">
            <span v-if="cargo">{{ cargo }}</span>
          </div>
          <!-- SETOR + GESTOR (read-only; edição via dialog) -->
          <div class="q-mt-xs text-caption text-grey row items-center q-gutter-xs">
            <span>{{ setorNome || 'Sem setor' }}</span>
            <q-badge v-if="colaborador.gestor" color="blue" label="Gestor" />
          </div>
          <div v-if="colaborador?.colaborador?.contratacao" class="text-caption text-grey">
            Contratação:
            {{ formataData(colaborador.colaborador.contratacao) }}
            ({{ formataFromNow(colaborador.colaborador.contratacao) }})
          </div>
        </q-item-section>
        <q-item-section side>
          <div class="row items-center q-gutter-sm">
            <!-- STATUS DO COLABORADOR (recalcular / encerrar / estornar) -->
            <template v-if="podeEditar">
              <q-btn
                v-if="colaborador.status === 'A'"
                flat
                round
                icon="refresh"
                color="grey-7"
                @click="recalcularColaborador()"
              >
                <q-tooltip>Recalcular</q-tooltip>
              </q-btn>
              <q-btn
                v-if="colaborador.status === 'A'"
                flat
                round
                icon="check_circle"
                color="green-7"
                @click="encerrarColaborador()"
              >
                <q-tooltip>Encerrar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="colaborador.status === 'E' && acertoStatus !== 'efetivado'"
                flat
                round
                icon="undo"
                color="grey-7"
                @click="estornarColaborador()"
              >
                <q-tooltip>Estornar Encerramento</q-tooltip>
              </q-btn>
            </template>
            <!-- RECIBO (só quando acerto efetivado) -->
            <q-btn
              v-if="podeEditar && acertoStatus === 'efetivado'"
              flat
              round
              icon="print"
              color="grey-7"
              @click="imprimirRecibo()"
            >
              <q-tooltip>Imprimir Recibo</q-tooltip>
            </q-btn>
            <!-- ACERTO (Encontro de Contas) -->
            <template v-if="podeEditar">
              <q-btn v-if="colaborador.status !== 'E'" flat round icon="handshake" color="grey-5">
                <q-tooltip>Encerre o colaborador primeiro</q-tooltip>
              </q-btn>
              <q-btn
                v-else-if="acertoStatus === 'efetivado'"
                flat
                round
                icon="undo"
                color="grey-7"
                @click="estornarAcerto()"
              >
                <q-tooltip>Estornar Acerto</q-tooltip>
              </q-btn>
              <q-btn v-else flat round icon="handshake" color="primary" @click="realizarAcerto()">
                <q-tooltip>Realizar Acerto (Encontro de Contas)</q-tooltip>
              </q-btn>
            </template>
            <q-btn
              v-if="podeEditar"
              flat
              round
              icon="edit"
              color="grey-7"
              @click="abrirEdicaoColaborador()"
            >
              <q-tooltip>Editar Colaborador (setor / gestor)</q-tooltip>
            </q-btn>
            <q-btn flat round icon="arrow_back" color="grey-7" :to="voltarTo">
              <q-tooltip>Voltar</q-tooltip>
            </q-btn>
          </div>
        </q-item-section>
      </q-item>

      <div class="q-pa-md">
        <!-- RUBRICAS -->
        <CardRubricas
          :rubricas="rubricas"
          :valortotal="colaborador.valortotal"
          :status="colaborador.status"
          :codtitulo="colaborador.codtitulo"
          :linkTitulo="linkTitulo"
          :podeEditar="podeEditar"
          :codperiodo="route.params.codperiodo"
          nomeRotaExtrato="rhIndicadorExtrato"
          @editar="editarRubrica"
          @excluir="excluirRubrica"
          @toggle-concedido="toggleConcedido"
          @nova-rubrica="abrirNovaRubrica"
          @editar-meta="editarMeta"
        />
      </div>
    </template>

    <!-- COLABORADOR NÃO ENCONTRADO -->
    <div v-else-if="!loading && !colaborador" class="q-pa-xl text-center text-grey">
      Colaborador não encontrado
    </div>
  </div>
</template>
