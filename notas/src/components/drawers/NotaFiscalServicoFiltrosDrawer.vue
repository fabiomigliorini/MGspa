<script setup>
import { computed, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useNotaFiscalServicoStore } from '../../stores/notaFiscalServicoStore'
import { NFSE_STATUS_OPTIONS } from '../../constants/notaFiscalServico'
import MgSelect from '@components/MgSelect.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const store = useNotaFiscalServicoStore()

const activeFiltersCount = computed(() => store.activeFiltersCount)

const debouncedFetch = useDebounceFn(() => {
  store.fetchNotas(true)
}, 800)

watch(() => store.filters, debouncedFetch, { deep: true })

const limparFiltros = () => {
  store.clearFilters()
  store.fetchNotas(true)
}
</script>

<template>
  <div class="column full-height">
    <div class="q-pa-md bg-primary text-white">
      <div class="text-h6">
        <q-icon name="filter_list" class="q-mr-sm" />
        Filtros
      </div>
      <div class="row items-center justify-between">
        <div class="text-caption">
          {{ activeFiltersCount }}
          {{ activeFiltersCount === 1 ? 'filtro ativo' : 'filtros ativos' }}
        </div>
        <q-btn
          v-if="activeFiltersCount > 0"
          flat
          round
          icon="close"
          color="white"
          size="sm"
          @click="limparFiltros"
        >
          <q-tooltip>Limpar Filtros</q-tooltip>
        </q-btn>
      </div>
    </div>

    <q-separator />

    <div class="q-pa-md">
      <div class="text-caption text-grey-7 q-mb-md">Status</div>

      <div class="q-mb-md">
        <MgSelect
          v-model="store.filters.status"
          :options="NFSE_STATUS_OPTIONS"
          label="Status"
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        >
          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section avatar>
                <q-icon :name="scope.opt.icon" :color="scope.opt.color" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ scope.opt.label }}</q-item-label>
              </q-item-section>
            </q-item>
          </template>
          <template v-slot:selected-item="scope">
            <q-icon :name="scope.opt.icon" :color="scope.opt.color" size="xs" class="q-mr-xs" />
            {{ scope.opt.label }}
          </template>
        </MgSelect>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Busque pela Nota</div>

      <div class="q-mb-md">
        <MgInputValor
          v-model="store.filters.numero"
          :decimals="0"
          :grouping="false"
          align="left"
          label="Número da DPS"
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="tag" />
          </template>
        </MgInputValor>
      </div>

      <div class="q-mb-md">
        <MgInputValor
          v-model="store.filters.nfsenumero"
          :decimals="0"
          :grouping="false"
          align="left"
          label="Número da NFS-e"
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="numbers" />
          </template>
        </MgInputValor>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Relacionamentos</div>

      <div class="q-mb-md">
        <MgSelectFilial
          v-model="store.filters.codfilial"
          label="Filial"
          clearable
          :bottom-slots="false"
        />
      </div>

      <div class="q-mb-md">
        <MgSelectPessoa
          v-model="store.filters.codpessoa"
          label="Tomador"
          clearable
          :bottom-slots="false"
        />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Período de Emissão</div>

      <div class="q-mb-md">
        <MgInputData
          v-model="store.filters.emissao_inicio"
          type="date"
          label="Emissão - De"
          clearable
        />
      </div>

      <div class="q-mb-md">
        <MgInputData
          v-model="store.filters.emissao_fim"
          type="date"
          label="Emissão - Até"
          clearable
        />
      </div>
    </div>
  </div>
</template>
