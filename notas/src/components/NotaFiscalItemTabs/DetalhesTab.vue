<script setup>
import { computed } from 'vue'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { storeToRefs } from 'pinia'

const notaFiscalStore = useNotaFiscalStore()

// State
const nota = computed(() => notaFiscalStore.currentNota)
const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

// Usa o editingItem do store diretamente (ref reativa)
const { editingItem } = storeToRefs(notaFiscalStore)





</script>

<template>


  <!-- Produto e Quantidades -->
  <div class="text-subtitle1 text-weight-bold q-mb-md bg-primary text-white q-pa-sm">
    <q-icon name="inventory_2" size="sm" class="q-mr-xs" />
    DETALHES
  </div>

  <div class="row q-col-gutter-md">


    <!-- Ordem -->
    <div class="col-12 col-sm-2">
      <q-input v-model.number="editingItem.ordem" label="Ordem" outlined type="number" min="0" hint="Ordem de exibição"
        :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Descrição Alternativa -->
    <div class="col-12 col-sm-10">
      <q-input v-model="editingItem.descricaoalternativa" label="Descrição Alternativa" outlined maxlength="120"
        hint="Descrição customizada para a NFe (máx. 120 caracteres)" :disable="notaBloqueada" />
    </div>

    <!-- Pedido -->
    <div class="col-12 col-sm-6">
      <q-input v-model="editingItem.pedido" label="Número do Pedido" outlined maxlength="15"
        hint="Referência ao pedido de venda/compra" :disable="notaBloqueada" />
    </div>

    <!-- Item do Pedido -->
    <div class="col-12 col-sm-6">
      <q-input v-model="editingItem.pedidoitem" label="Item do Pedido" outlined maxlength="6"
        hint="Número do item no pedido" :disable="notaBloqueada" />
    </div>

    <!-- Observações -->
    <div class="col-12">
      <q-input v-model="editingItem.observacoes" label="Observações" outlined type="textarea" rows="3" maxlength="1500"
        counter hint="Informações adicionais sobre o item (máx. 1500 caracteres)" :disable="notaBloqueada" />
    </div>

  </div>

</template>
