<script setup>
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { formataNumero } from "@components/formatters"
import { useBoletoStore } from 'src/stores/boletoStore'
import { TIPOS_ABERTOS } from 'src/constants/tituloBoleto'

const route = useRoute()
const store = useBoletoStore()

function resumoDe(tipo) {
  return store.abertosResumo.find((c) => c.tipo === tipo)
}

onMounted(() => store.carregarResumoAbertos())
</script>

<template>
  <div class="bg-white">
    <q-list separator>
      <q-item
        v-for="item in TIPOS_ABERTOS"
        :key="item.tipo"
        clickable
        :to="{ name: 'boleto-abertos', query: { tipo: item.tipo } }"
        :active="route.query.tipo === item.tipo"
        active-class="bg-blue-1 text-primary"
      >
        <q-item-section>
          <q-item-label class="text-weight-bold text-right">
            {{ resumoDe(item.tipo) ? formataNumero(resumoDe(item.tipo).total) : '—' }}
          </q-item-label>
          <q-item-label caption class="text-right">
            {{ item.label }}
            <span v-if="resumoDe(item.tipo)" class="text-grey-6">
              ({{ resumoDe(item.tipo).quantidade }})
            </span>
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </div>
</template>
