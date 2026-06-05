<script setup>
import { ref, watch, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import MgInputValor from '@components/MgInputValor.vue'

const cad = useCadastro('tabela-desconto', 'codtabeladesconto', 'Faixa')
const culturas = ref([])
const codcultura = ref(null)
const tipo = ref('UMIDADE')

const tipos = [
  { label: 'Umidade', value: 'UMIDADE' },
  { label: 'Impureza', value: 'IMPUREZA' },
  { label: 'Avariados', value: 'AVARIADOS' },
]

const colunas = [
  { name: 'faixainicio', label: 'De (%)', field: 'faixainicio', align: 'right' },
  { name: 'faixafim', label: 'Até (%)', field: 'faixafim', align: 'right' },
  { name: 'percentualdesconto', label: 'Desconto (%)', field: 'percentualdesconto', align: 'right' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

function fmt(v) {
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 3 })
}

async function recarregar() {
  if (!codcultura.value) return
  await cad.carregar({ codcultura: codcultura.value, tipo: tipo.value, sort: 'faixainicio' })
}

function nova() {
  cad.abrirNovo({ codcultura: codcultura.value, tipo: tipo.value })
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

watch([codcultura, tipo], recarregar)

onMounted(async () => {
  const { data } = await api.get('v1/cultura')
  culturas.value = data.data ?? data
  if (culturas.value.length) codcultura.value = culturas.value[0].codcultura
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="deep-orange-1" text-color="deep-orange-8" icon="percent" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Tabela de Desconto</div>
            <div class="text-caption text-grey-7">Faixas de umidade, impureza e avariados</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="nova">
            <q-tooltip>Nova faixa</q-tooltip>
          </q-btn>
        </q-card-section>

        <q-separator inset />

        <q-card-section class="row items-center q-col-gutter-md">
          <q-select
            v-model="codcultura"
            :options="culturas"
            option-value="codcultura"
            option-label="cultura"
            emit-value
            map-options
            outlined
            label="Cultura"
            class="col-12 col-sm-4"
          />
          <q-btn-toggle
            v-model="tipo"
            :options="tipos"
            no-caps
            unelevated
            toggle-color="primary"
            color="grey-3"
            text-color="grey-9"
            class="col-12 col-sm-auto"
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
          :rows="cad.items"
          :columns="colunas"
          row-key="codtabeladesconto"
          :loading="cad.carregando"
          flat
          hide-pagination
          :rows-per-page-options="[0]"
          no-data-label="Nenhuma faixa para esta cultura/tipo."
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
              <q-btn flat round size="sm" color="grey-7" icon="edit" @click="cad.editar(props.row)" />
              <q-btn flat round size="sm" color="grey-7" icon="delete" @click="cad.excluir(props.row)" />
            </q-td>
          </template>
        </q-table>
      </q-card>

      <q-dialog v-model="cad.dialog">
        <q-card bordered flat style="width: 380px; max-width: 90vw">
          <q-form @submit="salvar">
            <q-card-section>
              <div class="text-h6">{{ cad.isNovo ? 'Nova Faixa' : 'Editar Faixa' }}</div>
              <div class="text-caption text-grey-7">{{ cad.form.tipo }}</div>
            </q-card-section>
            <q-card-section class="q-gutter-md">
              <div class="row q-col-gutter-md">
                <MgInputValor v-model="cad.form.faixainicio" :decimals="1" suffix="%" label="De" class="col-6" />
                <MgInputValor v-model="cad.form.faixafim" :decimals="1" suffix="%" label="Até" class="col-6" />
              </div>
              <MgInputValor
                v-model="cad.form.percentualdesconto"
                :decimals="3"
                suffix="%"
                label="Desconto aplicado"
              />
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" unelevated label="Salvar" color="primary" :loading="cad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
