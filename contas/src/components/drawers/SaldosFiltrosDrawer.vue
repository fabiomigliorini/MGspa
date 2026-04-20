<script setup>
import { ref, computed } from 'vue'
import moment from 'moment'
import { useSaldoStore } from 'src/stores/saldoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useSaldoStore()
const dateProxy = ref(null)

const minYearMonth = computed(() =>
  store.intervalo ? moment(store.intervalo.primeira_data).format('YYYY/MM') : undefined,
)
const maxYearMonth = computed(() =>
  store.intervalo ? moment(store.intervalo.ultima_data).format('YYYY/MM') : undefined,
)

const onDateChange = (val) => {
  store.dataSelecionada = val
  store.listaSaldos()
  dateProxy.value?.hide()
}
</script>

<template>
  <FilterDrawerShell title="Filtros" :active-count="0">
    <FilterGroup title="Data" first>
      <q-input
        :model-value="store.dataSelecionada"
        readonly
        outlined
        :bottom-slots="false"
        label="Dia"
      >
        <template #prepend>
          <q-icon name="event" class="cursor-pointer">
            <q-popup-proxy
              ref="dateProxy"
              cover
              transition-show="scale"
              transition-hide="scale"
            >
              <q-date
                :model-value="store.dataSelecionada"
                @update:model-value="onDateChange"
                mask="DD-MM-YYYY"
                :navigation-min-year-month="minYearMonth"
                :navigation-max-year-month="maxYearMonth"
              />
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>
    </FilterGroup>
  </FilterDrawerShell>
</template>
