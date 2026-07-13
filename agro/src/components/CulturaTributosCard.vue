<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCulturaStore } from 'src/stores/cultura'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectTributo from '@components/MgSelectTributo.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

// Card de config fiscal da cultura (tblculturatributo) embutido na
// CulturaDetailPage. Lê/escreve tudo na store do domínio cultura; dialog
// simples de adicionar/editar/excluir. É essa config que alimenta o líquido
// das fixações (motor ContratoCalculoService).
const props = defineProps({
  codcultura: { type: Number, required: true },
})

const store = useCulturaStore()
const { culturatributos, formTributo, dialogTributo, salvandoTributo, unidadesReferencia } =
  storeToRefs(store)

const baseOptions = [
  { label: '% sobre o valor', value: 'VALOR' },
  { label: '% sobre a UPF (unidade)', value: 'UNIDADE' },
]

const isNovo = computed(() => !formTributo.value.codculturatributo)

function fmtPct(v) {
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 4 })
}
function baseDescr(t) {
  return t.base === 'UNIDADE'
    ? `% sobre a ${t.UnidadeReferencia?.codigo || 'UPF'} × peso da saca`
    : '% sobre o valor bruto'
}

// Ao trocar a base: UNIDADE precisa da UPF (auto-seleciona quando só há uma);
// VALOR não usa unidade, então limpa.
function onBaseChange(v) {
  formTributo.value.base = v
  if (v === 'VALOR') {
    formTributo.value.codunidadereferencia = null
  } else if (!formTributo.value.codunidadereferencia && unidadesReferencia.value.length === 1) {
    formTributo.value.codunidadereferencia = unidadesReferencia.value[0].value
  }
}

onMounted(() => {
  store.carregarCulturaTributos(props.codcultura)
  store.carregarUnidadesReferencia()
})
</script>

<template>
  <q-card bordered flat class="overflow-hidden">
    <q-item>
      <q-item-section avatar>
        <q-avatar color="indigo-1" text-color="indigo-8" icon="account_balance" />
      </q-item-section>
      <q-item-section>
        <q-item-label class="text-subtitle1">Tributos / Descontos</q-item-label>
        <q-item-label caption>FETHAB, IAGRO, SENAR e Funrural deduzidos nas fixações</q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn
          flat
          round
          size="sm"
          color="primary"
          icon="add"
          @click="store.novoTributo(codcultura)"
        >
          <q-tooltip>Novo tributo</q-tooltip>
        </q-btn>
      </q-item-section>
    </q-item>
    <q-separator />
    <q-list separator>
      <q-item v-for="t in culturatributos" :key="t.codculturatributo">
        <q-item-section avatar>
          <q-avatar color="indigo-1" text-color="indigo-8" icon="receipt_long" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-weight-medium">
            {{ t.Tributo?.codigo }}
            <q-badge
              v-if="t.grupofethab"
              color="orange-6"
              label="isenta cooperativa"
              class="q-ml-xs"
            />
            <q-badge v-if="t.funrural" color="teal-6" label="só na venda" class="q-ml-xs" />
          </q-item-label>
          <q-item-label caption>{{ baseDescr(t) }}</q-item-label>
        </q-item-section>
        <q-item-section side class="text-right">
          <q-item-label class="text-weight-bold text-indigo-9"
            >{{ fmtPct(t.percentual) }}%</q-item-label
          >
        </q-item-section>
        <q-item-section side>
          <div class="row items-center no-wrap">
            <MgInfoCriacao :registro="t" />
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="edit"
              @click="store.editarTributo(t)"
            />
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="delete"
              @click="store.excluirTributo(t)"
            />
          </div>
        </q-item-section>
      </q-item>

      <MgEmptyState v-if="!culturatributos.length" plain icon="account_balance">
        Nenhum tributo configurado — as fixações desta cultura não terão dedução.
      </MgEmptyState>
    </q-list>

    <!-- Dialog adicionar/editar -->
    <q-dialog v-model="dialogTributo">
      <q-card flat style="width: 480px; max-width: 95vw">
        <q-form @submit.prevent="store.salvarTributo()">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">{{ isNovo ? 'Novo Tributo' : 'Editar Tributo' }}</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelectTributo v-model="formTributo.codtributo" label="Tributo" />
              </div>
              <div class="col-12 col-sm-7">
                <q-select
                  :model-value="formTributo.base"
                  @update:model-value="onBaseChange"
                  :options="baseOptions"
                  label="Cálculo"
                  outlined
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-5">
                <MgInputValor
                  v-model="formTributo.percentual"
                  :decimals="4"
                  suffix="%"
                  label="Alíquota"
                  lazy-rules
                  :rules="[(v) => v != null && v >= 0]"
                />
              </div>
              <div v-if="formTributo.base === 'UNIDADE'" class="col-12">
                <q-select
                  v-model="formTributo.codunidadereferencia"
                  :options="unidadesReferencia"
                  label="Unidade de referência (UPF)"
                  outlined
                  emit-value
                  map-options
                  lazy-rules
                  :rules="[(v) => v != null || 'Selecione a unidade']"
                />
              </div>
              <div class="col-12 col-sm-5">
                <MgInputValor
                  v-model="formTributo.ordem"
                  :decimals="0"
                  :grouping="false"
                  align="left"
                  label="Ordem"
                />
              </div>
              <div class="col-12">
                <q-toggle
                  v-model="formTributo.grupofethab"
                  label="Pertence ao grupo FETHAB (contrato de cooperativa fica isento)"
                  color="orange-6"
                />
              </div>
              <div class="col-12">
                <q-toggle
                  v-model="formTributo.funrural"
                  label="Funrural — só aplica quando a filial paga na venda"
                  color="teal-6"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvandoTributo" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-card>
</template>
