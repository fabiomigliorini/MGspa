<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataData, formataCodigo, formataDataIso } from '@components/formatters'
import { chequeStatusLabel, chequeStatusColor } from 'src/constants/chequeStatus'
import { abrirPdf } from '@components/abrirPdf'
import MgInputData from '@components/MgInputData.vue'
import MgSelectChequeMotivoDevolucao from '@components/MgSelectChequeMotivoDevolucao.vue'

const route = useRoute()
const $q = useQuasar()

const codchequerepasse = computed(() => Number(route.params.codchequerepasse))
const repasse = ref(null)
const carregando = ref(false)
const processando = ref(false)

const cheques = computed(() =>
  (repasse.value?.cheque_repasse_cheque_s || []).map((pivot) => ({
    ...pivot.cheque,
    codchequerepassecheque: pivot.codchequerepassecheque,
    compensacao: pivot.compensacao,
  })),
)

const totalCheques = computed(() =>
  cheques.value.reduce((soma, c) => soma + Number(c.valor || 0), 0),
)

const columns = [
  { name: 'codcheque', label: '#', field: 'codcheque', align: 'left' },
  { name: 'banco', label: 'Banco / Ag.', field: 'banco', align: 'left' },
  { name: 'conta', label: 'Conta / Nº', field: 'conta', align: 'left' },
  { name: 'pessoa', label: 'Cliente / Emitentes', field: 'pessoa', align: 'left' },
  { name: 'valor', label: 'Valor', field: 'valor', align: 'right' },
  { name: 'vencimento', label: 'Vencimento', field: 'vencimento', align: 'center' },
  { name: 'indstatus', label: 'Status', field: 'indstatus', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

// === Devolução de cheque ===
const devDialog = ref(false)
const devSalvando = ref(false)
const devModel = ref({
  codchequerepassecheque: null,
  codcheque: null,
  codchequemotivodevolucao: null,
  data: formataDataIso(new Date()),
  observacoes: '',
})

function abrirDevolucao(row) {
  devModel.value = {
    codchequerepassecheque: row.codchequerepassecheque,
    codcheque: row.codcheque,
    codchequemotivodevolucao: null,
    data: formataDataIso(new Date()),
    observacoes: '',
  }
  devDialog.value = true
}

async function confirmarDevolucao() {
  devSalvando.value = true
  try {
    await api.post(
      `v1/cheque-repasse/${codchequerepasse.value}/cheque/${devModel.value.codchequerepassecheque}/devolver`,
      {
        codchequemotivodevolucao: devModel.value.codchequemotivodevolucao,
        data: devModel.value.data,
        observacoes: devModel.value.observacoes,
      },
    )
    notifySuccess('Cheque devolvido')
    devDialog.value = false
    await carregar()
  } catch (e) {
    notifyError(e, 'Erro ao devolver cheque')
  } finally {
    devSalvando.value = false
  }
}

async function carregar() {
  carregando.value = true
  try {
    const { data } = await api.get(`v1/cheque-repasse/${codchequerepasse.value}`)
    repasse.value = data
  } catch (e) {
    notifyError(e, 'Erro ao carregar repasse')
  } finally {
    carregando.value = false
  }
}

const toggleInativo = () => {
  if (repasse.value.inativo) {
    reativar()
    return
  }
  $q.dialog({
    title: 'Inativar repasse',
    message:
      'Inativar este repasse devolve os cheques ainda "Repassados" para "À Repassar". Confirma?',
    ok: { label: 'Confirmar', color: 'primary', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(inativar)
}

async function inativar() {
  processando.value = true
  try {
    const { data } = await api.post(`v1/cheque-repasse/${codchequerepasse.value}/inativo`)
    repasse.value = data
    notifySuccess('Repasse inativado')
  } catch (e) {
    notifyError(e, 'Erro ao inativar repasse')
  } finally {
    processando.value = false
  }
}

async function reativar() {
  processando.value = true
  try {
    const { data } = await api.delete(`v1/cheque-repasse/${codchequerepasse.value}/inativo`)
    repasse.value = data
    notifySuccess('Repasse reativado')
  } catch (e) {
    notifyError(e, 'Erro ao reativar repasse')
  } finally {
    processando.value = false
  }
}

function imprimirBordero() {
  abrirPdf(
    api,
    `v1/cheque-repasse/${codchequerepasse.value}/pdf`,
    {},
    { title: 'Borderô de Repasse' },
  )
}

onMounted(carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1280px; margin: auto">
      <div class="row items-center q-mb-md">
        <q-btn
          flat
          dense
          round
          icon="arrow_back"
          :to="{ name: 'cheque-repasse' }"
          aria-label="Voltar"
        />
        <div class="text-h6 q-ml-sm">
          Repasse {{ repasse ? formataCodigo(repasse.codchequerepasse) : '' }}
        </div>
        <q-space />
        <q-btn
          v-if="repasse"
          flat
          icon="print"
          label="Borderô"
          color="primary"
          @click="imprimirBordero"
        />
        <q-btn
          v-if="repasse"
          :color="repasse.inativo ? 'green-7' : 'orange-8'"
          :icon="repasse.inativo ? 'play_arrow' : 'pause'"
          :label="repasse.inativo ? 'Reativar' : 'Inativar'"
          flat
          :loading="processando"
          @click="toggleInativo"
        />
      </div>

      <q-inner-loading :showing="carregando">
        <q-spinner-dots color="primary" size="40px" />
      </q-inner-loading>

      <template v-if="repasse">
        <q-card bordered flat class="q-mb-md">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-7">Portador</div>
                <div class="text-body1">{{ repasse.portador?.portador }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-7">Data</div>
                <div class="text-body1">{{ formataData(repasse.data) }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-7">Situação</div>
                <q-badge :color="repasse.inativo ? 'orange-7' : 'green-6'">
                  {{ repasse.inativo ? 'Inativo' : 'Ativo' }}
                </q-badge>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-7">Total</div>
                <div class="text-body1 text-weight-bold">R$ {{ formataNumero(totalCheques) }}</div>
              </div>
              <div v-if="repasse.observacoes" class="col-12">
                <div class="text-caption text-grey-7">Observações</div>
                <div class="text-body2">{{ repasse.observacoes }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card bordered flat>
          <q-card-section class="text-overline text-grey-8">
            CHEQUES ({{ cheques.length }})
          </q-card-section>
          <q-separator />
          <q-table
            :rows="cheques"
            :columns="columns"
            row-key="codcheque"
            flat
            hide-pagination
            :rows-per-page-options="[0]"
            :pagination="{ rowsPerPage: 0 }"
            no-data-label="Nenhum cheque neste repasse"
          >
            <template #body-cell-codcheque="props">
              <q-td :props="props" class="text-grey-7">
                <router-link
                  :to="{ name: 'cheque-detalhe', params: { codcheque: props.row.codcheque } }"
                  class="text-primary"
                >
                  {{ formataCodigo(props.row.codcheque) }}
                </router-link>
              </q-td>
            </template>
            <template #body-cell-banco="props">
              <q-td :props="props">
                <div>{{ props.row.banco?.banco ?? props.row.codbanco }}</div>
                <div class="text-grey-7 text-caption">Ag. {{ props.row.agencia }}</div>
              </q-td>
            </template>
            <template #body-cell-conta="props">
              <q-td :props="props">
                <div>{{ props.row.contacorrente }}</div>
                <div class="text-grey-7 text-caption">Nº {{ props.row.numero }}</div>
              </q-td>
            </template>
            <template #body-cell-pessoa="props">
              <q-td :props="props">
                <div class="text-weight-medium">{{ props.row.pessoa?.pessoa }}</div>
                <div
                  v-for="emit in props.row.cheque_emitente_s"
                  :key="emit.codchequeemitente"
                  class="text-grey-7 text-caption"
                >
                  {{ emit.emitente }}
                </div>
              </q-td>
            </template>
            <template #body-cell-valor="props">
              <q-td :props="props" class="text-weight-bold">
                {{ formataNumero(props.row.valor) }}
              </q-td>
            </template>
            <template #body-cell-vencimento="props">
              <q-td :props="props" class="text-weight-medium">
                {{ formataData(props.row.vencimento) }}
              </q-td>
            </template>
            <template #body-cell-indstatus="props">
              <q-td :props="props">
                <q-badge :color="chequeStatusColor(props.row.indstatus)">
                  {{ chequeStatusLabel(props.row.indstatus) }}
                </q-badge>
              </q-td>
            </template>
            <template #body-cell-acoes="props">
              <q-td :props="props">
                <q-btn
                  v-if="props.row.indstatus === 2 && !repasse.inativo"
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="assignment_return"
                  @click="abrirDevolucao(props.row)"
                >
                  <q-tooltip>Devolver cheque</q-tooltip>
                </q-btn>
              </q-td>
            </template>
          </q-table>
        </q-card>
      </template>
    </div>

    <!-- Dialog de devolução -->
    <q-dialog v-model="devDialog">
      <q-card bordered flat style="width: 440px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          DEVOLVER CHEQUE {{ formataCodigo(devModel.codcheque) }}
        </q-card-section>
        <q-form @submit.prevent="confirmarDevolucao">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelectChequeMotivoDevolucao
                  v-model="devModel.codchequemotivodevolucao"
                  outlined
                  label="Motivo da devolução"
                  autofocus
                  :rules="[(v) => !!v || 'Selecione o motivo']"
                />
              </div>
              <div class="col-12">
                <MgInputData
                  v-model="devModel.data"
                  label="Data da devolução"
                  :rules="[(v) => !!v || 'Informe a data']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="devModel.observacoes"
                  outlined
                  type="textarea"
                  label="Observações"
                  maxlength="200"
                  autogrow
                />
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Devolver" type="submit" :loading="devSalvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
