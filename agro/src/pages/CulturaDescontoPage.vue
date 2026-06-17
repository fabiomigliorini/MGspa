<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const route = useRoute()
const codcultura = Number(route.params.codcultura)

const cad = useCadastro('tabela-desconto', 'codtabeladesconto', 'Faixa')
const cultura = ref(null)
const tipo = ref('UMIDADE')

const tipos = [
  { label: 'Umidade', value: 'UMIDADE' },
  { label: 'Impureza', value: 'IMPUREZA' },
  { label: 'Avariados', value: 'AVARIADOS' },
  { label: 'Esverdeados', value: 'ESVERDEADOS' },
  { label: 'Quebrados', value: 'QUEBRADOS' },
]

const colunas = [
  { name: 'faixainicio', label: 'De (%)', field: 'faixainicio', align: 'right' },
  { name: 'faixafim', label: 'Até (%)', field: 'faixafim', align: 'right' },
  {
    name: 'percentualdesconto',
    label: 'Desconto (%)',
    field: 'percentualdesconto',
    align: 'right',
  },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

// useCadastro recarrega sem filtro após salvar/excluir; o computed garante que
// a tabela sempre mostre só a cultura + tipo selecionados, ordenado pela faixa.
const faixas = computed(() =>
  cad.items
    .filter((f) => f.codcultura === codcultura && f.tipo === tipo.value)
    .sort((a, b) => Number(a.faixainicio) - Number(b.faixainicio)),
)

function fmt(v) {
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 3 })
}

async function carregarFaixas() {
  await cad.carregar({ codcultura, tipo: tipo.value, sort: 'faixainicio' })
}
function trocarTipo(t) {
  tipo.value = t
  carregarFaixas()
}

function nova() {
  cad.abrirNovo({ codcultura, tipo: tipo.value })
}
function salvar() {
  cad.salvar((f) => ({
    codcultura: f.codcultura,
    tipo: f.tipo,
    faixainicio: f.faixainicio,
    faixafim: f.faixafim,
    percentualdesconto: f.percentualdesconto,
  }))
}

onMounted(async () => {
  try {
    const { data } = await api.get(`v1/cultura/${codcultura}`)
    cultura.value = data.data ?? data
    await carregarFaixas()
  } catch (e) {
    notifyError(e)
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn
            flat
            round
            size="sm"
            color="grey-7"
            icon="arrow_back"
            :to="{ name: 'cultura-detalhe', params: { codcultura } }"
          />
          <q-avatar
            color="deep-orange-1"
            text-color="deep-orange-8"
            icon="percent"
            class="q-ml-sm"
          />
          <div class="col q-ml-md">
            <div class="text-h6">Tabela de Desconto</div>
            <div class="text-caption text-grey-7">{{ cultura?.cultura }}</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="nova">
            <q-tooltip>Nova faixa</q-tooltip>
          </q-btn>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <q-btn-toggle
            :model-value="tipo"
            :options="tipos"
            no-caps
            unelevated
            toggle-color="primary"
            color="grey-3"
            text-color="grey-9"
            @update:model-value="trocarTipo"
          />
        </q-card-section>
      </q-card>

      <q-banner rounded class="bg-blue-1 text-blue-9 q-mb-md">
        <template #avatar><q-icon name="info" color="blue-7" /></template>
        Valores de <b>padrão de mercado</b> — variam por comprador/cooperativa. Ajuste conforme o
        seu (o fator de umidade por ponto é o que mais muda).
      </q-banner>

      <q-card bordered flat>
        <q-table
          :rows="faixas"
          :columns="colunas"
          row-key="codtabeladesconto"
          :loading="cad.carregando"
          flat
          hide-pagination
          :rows-per-page-options="[0]"
          no-data-label="Nenhuma faixa para este tipo."
        >
          <template #body-cell-faixainicio="props">
            <q-td :props="props">{{ fmt(props.row.faixainicio) }}</q-td>
          </template>
          <template #body-cell-faixafim="props">
            <q-td :props="props">{{ fmt(props.row.faixafim) }}</q-td>
          </template>
          <template #body-cell-percentualdesconto="props">
            <q-td :props="props" class="text-weight-medium text-deep-orange-9">
              {{ fmt(props.row.percentualdesconto) }}%
            </q-td>
          </template>
          <template #body-cell-acoes="props">
            <q-td :props="props">
              <MgInfoCriacao :registro="props.row" />
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                icon="edit"
                @click="cad.editar(props.row)"
              />
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                icon="delete"
                @click="cad.excluir(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card>

      <q-dialog v-model="cad.dialog">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit="salvar">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">{{ cad.isNovo ? 'Nova Faixa' : 'Editar Faixa' }}</div>
              <div class="text-caption">{{ cad.form.tipo }}</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <MgInputValor
                    v-model="cad.form.faixainicio"
                    :decimals="1"
                    suffix="%"
                    label="De"
                  />
                </div>
                <div class="col-6">
                  <MgInputValor v-model="cad.form.faixafim" :decimals="1" suffix="%" label="Até" />
                </div>
                <div class="col-12">
                  <MgInputValor
                    v-model="cad.form.percentualdesconto"
                    :decimals="3"
                    suffix="%"
                    label="Desconto aplicado"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="cad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
