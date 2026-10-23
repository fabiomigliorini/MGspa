<script setup>
// Títulos pagos no período, para a contabilidade (TASK-256), aberto pelo cabeçalho do painel
// /portador. Filial e fiscal/gerencial são do título; o PDF agrupa por fiscal/gerencial, filial,
// portador e pagamento. Fica aberto para gerar a próxima filial.
import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import MgInputData from '@components/MgInputData.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import { hoje, somaMeses, inicioMes, fimMes } from '@components/utils/datas'
import { usePortadorStore } from 'src/stores/portadorStore'
import { abrirPdf } from 'src/utils/abrirPdf'

const store = usePortadorStore()
const { dialogContabilidade } = storeToRefs(store)

const GERENCIAL_OPTIONS = [
  { label: 'Fiscal', value: 0 },
  { label: 'Gerencial', value: 1 },
  { label: 'Todos', value: null },
]

const mesPassado = somaMeses(hoje(), -1)
const filtros = ref({
  transacao_de: inicioMes(mesPassado),
  transacao_ate: fimMes(mesPassado),
  codfilial: null,
  gerencial: 0,
  codportador: null,
})

const obrigatorio = [(v) => !!v || 'Obrigatório']

const gerar = () => {
  const params = Object.fromEntries(
    Object.entries(filtros.value).filter(([, v]) => v !== null && v !== ''),
  )
  abrirPdf('v1/pagamento/contabilidade', params, { title: 'Títulos pagos' })
}
</script>

<template>
  <q-dialog v-model="dialogContabilidade">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="gerar">
        <q-card-section class="text-grey-9 text-overline">TÍTULOS PAGOS</q-card-section>
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <MgInputData
                v-model="filtros.transacao_de"
                label="De"
                :rules="obrigatorio"
                autofocus
              />
            </div>
            <div class="col-6">
              <MgInputData v-model="filtros.transacao_ate" label="Até" :rules="obrigatorio" />
            </div>
            <div class="col-12">
              <MgSelectFilial v-model="filtros.codfilial" clearable label="Filial (todas)" />
            </div>
            <div class="col-12">
              <q-option-group
                v-model="filtros.gerencial"
                :options="GERENCIAL_OPTIONS"
                type="radio"
                inline
              />
            </div>
            <div class="col-12">
              <MgSelectPortador v-model="filtros.codportador" clearable label="Portador (todos)" />
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
          <q-btn flat label="Gerar PDF" color="primary" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
