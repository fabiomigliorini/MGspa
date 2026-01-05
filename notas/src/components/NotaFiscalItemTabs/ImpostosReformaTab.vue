<script setup>
import { computed, ref } from 'vue'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { getEnteIcon, getEnteColor } from 'src/composables/useTributoIcons'
import { CBS_CST_OPTIONS, CBS_CCLASSTRIB_OPTIONS } from 'src/constants/notaFiscal'
import SelectTributo from '../selects/SelectTributo.vue'
import { Notify } from 'quasar'
import { storeToRefs } from 'pinia'
import { round } from 'src/utils/formatters'

const notaFiscalStore = useNotaFiscalStore()

// State
const nota = computed(() => notaFiscalStore.currentNota)
const tributos = computed(() => notaFiscalStore.editingItem?.tributos || [])
const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

const codtributonovo = ref(null)
const tributoSelecionado = ref(null)

// Usa o editingItem do store diretamente(ref reativa)
const { editingItem } = storeToRefs(notaFiscalStore)

const baseCheia = computed(() => {
  return editingItem.value?.valortotalfinal || 0
})

const del = async (item) => {
  const index = tributos.value.indexOf(item);
  console.log([item, index, tributos.value])
  if (index !== -1) {
    tributos.value.splice(index, 1);
  }
}

const add = async () => {

  if (!codtributonovo.value) {
    Notify.create({
      type: 'negative',
      message: 'Selecione um tributo para adicionar!',
    })
    return

  }

  const ja = notaFiscalStore.editingItem?.tributos.some((i) => i.codtributo == codtributonovo.value)
  if (ja) {
    Notify.create({
      type: 'negative',
      message: 'Esse tributo já foi adicionado nesse item!',
    })
    return
  }

  console.log(tributoSelecionado.value);
  notaFiscalStore.editingItem?.tributos.push({
    codtributo: codtributonovo.value,
    tributo: JSON.parse(JSON.stringify(tributoSelecionado.value)),
    base: baseCheia.value
  })

  Notify.create({
    type: 'positive',
    message: 'Tributo adicionado!',
  })

}

const handleTributoSelect = (tributo) => {
  tributoSelecionado.value = tributo;
}

const updatedBaseReducaoPercentual = async (ti) => {
  if (!baseCheia.value) { return }
  if (ti.basereducao > 100) { return }
  ti.basereducao = round(baseCheia.value * (ti.basereducaopercentual / 100), 2);
  updatedBaseReducao(ti, false)
}

const updatedBaseReducao = async (ti, recalcularBaseReducaoPercentual) => {
  if (!baseCheia.value) { return }
  ti.base = baseCheia.value - ti.basereducao
  updatedBase(ti, false)
  if (recalcularBaseReducaoPercentual) {
    if (!ti.basereducao) {
      ti.basereducaopercentual = null
      return
    }
    ti.basereducaopercentual = round((ti.basereducao / baseCheia.value) * 100, 2)
  }
}

const updatedBase = async (ti, recalcularBaseReducao) => {
  if (!baseCheia.value) { return }
  if (!ti.base || !ti.aliquota) {
    ti.valor = null
    ti.valorcredito = null
    return
  }
  ti.valor = round(ti.base * (ti.aliquota / 100), 2);
  if (ti.geracredito) {
    ti.valorcredito = ti.valor
  }
  if (recalcularBaseReducao) {
    if (ti.base >= baseCheia.value) {
      ti.basereducao = null
      ti.basereducaopercentual = null
      return
    }
    ti.basereducao = baseCheia.value - ti.base
    ti.basereducaopercentual = round((ti.basereducao / baseCheia.value) * 100, 2)
  }
}

const updatedAliquota = async (ti) => {
  if (!baseCheia.value) { return }
  if (!ti.base || !ti.aliquota) {
    ti.valor = null
    ti.valorcredito = null
    return
  }
  ti.valor = round(ti.base * (ti.aliquota / 100), 2);
  if (ti.geracredito) {
    ti.valorcredito = ti.valor
  }
}

const updatedValor = async (ti) => {
  if (!baseCheia.value) { return }
  if (!ti.base) {
    ti.base = baseCheia.value
  }
  ti.aliquota = round((ti.valor / ti.base) * 100, 2);
  if (ti.geracredito) {
    ti.valorcredito = ti.valor
  }
}

</script>

<template>

  <q-banner class="bg-info text-white q-mb-md" rounded>
    <template v-slot:avatar>
      <q-icon name="info" />
    </template>
    Tributos da Reforma Tributária (CBS, IBS, etc). Gerencie os tributos aplicáveis a este item.
  </q-banner>

  <!-- Resumo de Valores (se houver tributos) -->
  <div class="text-subtitle1 text-weight-bold q-my-md bg-primary text-white q-pa-sm">
    <q-icon name="calculate" size="sm" class="q-mr-xs" />
    RESUMO
  </div>

  <div class="row q-col-gutter-md">

    <div class="col-6">
      <q-card flat bordered>
        <q-card-section class="text-center">
          <div class="text-caption text-grey-7">Total Tributos</div>
          <div class="text-h6 text-negative">
            R$ {{tributos.reduce((acc, t) => acc + (t.valor || 0), 0).toFixed(2)}}
          </div>
        </q-card-section>
      </q-card>
    </div>

    <div class="col-6">
      <q-card flat bordered>
        <q-card-section class="text-center">
          <div class="text-caption text-grey-7">Total Créditos</div>
          <div class="text-h6 text-positive">
            R$ {{tributos.filter(t => t.geracredito).reduce((acc, t) => acc + (t.valorcredito || 0),
              0).toFixed(2)}}
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>


  <template v-for="(tributoItem, i) in tributos" :key="i">

    <q-card flat bordered class="q-my-md">
      <q-card-section>

        <!-- CABECALHO -->
        <div class="text-subtitle1 text-weight-bold ">
          <q-icon v-if="tributoItem.tributo?.ente" :name="getEnteIcon(tributoItem.tributo?.ente)" size="sm"
            :color="getEnteColor(tributoItem.tributo?.ente)" />
          {{ tributoItem.tributo?.codigo }}
          {{ tributoItem.tributo?.ente }}
          <div class="text-caption text-grey-7">{{ tributoItem.tributo?.descricao || '' }}</div>

        </div>
      </q-card-section>
      <q-separator />
      <q-card-section>
        <div class="row q-col-gutter-md">

          <!-- CST e Classificação -->
          <div class="col-12 col-sm-6" v-if="tributoItem.tributo?.codigo == 'CBS'">
            <q-select v-model="tributoItem.cst" :options="CBS_CST_OPTIONS" label="CST" outlined emit-value map-options
              clearable :disable="notaBloqueada" />
          </div>

          <!-- CCLASSTRIB -->
          <div class="col-12 col-sm-6" v-if="tributoItem.tributo?.codigo == 'CBS'">
            <q-select v-model="tributoItem.cclasstrib" :options="CBS_CCLASSTRIB_OPTIONS"
              label="Classificação Tributária" outlined emit-value map-options clearable :disable="notaBloqueada" />
          </div>

          <!-- Redução de Base -->
          <div class="col-4">
            <q-input v-model.number="tributoItem.basereducaopercentual" label="% Redução" outlined type="number"
              step="0.01" min="0" max="100" suffix="%" :disable="notaBloqueada" input-class="text-right"
              @update:model-value="updatedBaseReducaoPercentual(tributoItem)" />
          </div>

          <div class="col-4">
            <q-input v-model.number="tributoItem.basereducao" label="Redução Base" outlined type="number" step="0.01"
              min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right"
              @update:model-value="updatedBaseReducao(tributoItem, true)" />
          </div>

          <div class="col-4">
            <q-input v-model="tributoItem.beneficiocodigo" label="Benefício" outlined maxlength="10"
              :disable="notaBloqueada" />
          </div>

          <!-- Base, Alíquota e Valor -->
          <div class="col-4">
            <q-input v-model.number="tributoItem.base" label="Base de Cálculo *" outlined type="number" step="0.01"
              min="0" prefix="R$" :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']"
              lazy-rules :disable="notaBloqueada" input-class="text-right"
              @update:model-value="updatedBase(tributoItem, true)" />
          </div>

          <div class="col-4">
            <q-input v-model.number="tributoItem.aliquota" label="Alíquota *" outlined type="number" step="0.01" min="0"
              max="100" suffix="%" :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']"
              lazy-rules :disable="notaBloqueada" input-class="text-right"
              @update:model-value="updatedAliquota(tributoItem)" />
          </div>

          <div class="col-4">
            <q-input v-model.number="tributoItem.valor" :label="'Valor ' + tributoItem.tributo?.codigo + '*'" outlined
              type="number" step="0.01" min="0" prefix="R$"
              :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']" lazy-rules
              :disable="notaBloqueada" input-class="text-right" @update:model-value="updatedValor(tributoItem)" />
          </div>

          <!-- Crédito -->
          <div class="col-6">
            <div style="height: 56px; display: flex; align-items: center">
              <q-toggle v-model="tributoItem.geracredito" label="Gera Crédito Tributário" color="primary"
                :disable="notaBloqueada" />
            </div>
          </div>

          <div v-if="tributoItem.geracredito" class="col-6">
            <q-input v-model.number="tributoItem.valorcredito" label="Valor do Crédito" outlined type="number"
              step="0.01" min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" />
          </div>

          <!-- Benefício e Fundamento Legal -->
          <div class="col-12">
            <q-input v-model="tributoItem.fundamentolegal" label="Fundamento Legal" outlined type="textarea" rows="2"
              maxlength="500" counter hint="Base legal para aplicação do tributo ou benefício (máx. 500 caracteres)"
              :disable="notaBloqueada" />
          </div>
        </div>
      </q-card-section>
      <q-separator />

      <q-card-actions align="right">
        <q-btn flat dense round icon="delete" color="negative" size="sm" :disable="notaBloqueada"
          @click="del(tributoItem)">
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
      </q-card-actions>
    </q-card>


  </template>

  <q-card flat bordered class="q-my-md">
    <q-card-section>
      <div class="text-subtitle1 text-weight-bold ">
        <q-icon name="add" size="sm" />
        Adicionar Tributo
        <div class="text-caption text-grey-7">
          Para adicionar um novo tributo ao item, selecione no campo abaixo!
        </div>
      </div>
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <SelectTributo v-model="codtributonovo" label="Tributo" @select="handleTributoSelect">
          </SelectTributo>
        </div>
        <div class="col-6">
          <q-btn color="primary" @click="add()" icon="add">
            Adicionar
          </q-btn>
        </div>
      </div>
    </q-card-section>
  </q-card>

</template>
