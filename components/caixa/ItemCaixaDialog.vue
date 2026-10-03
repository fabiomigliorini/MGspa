<script setup>
// Item do caixa na sessão aberta (M13 doc-3). Maquineta/terceiro (Bilhete Agora, Redeflex):
// vendido (o relatório do parceiro, só informação), entrada e saída de dinheiro. Contagem
// (chips, ingressos): entrada e saída de estoque (bloco que chegou, devolução). Entrada − saída
// vira um pagamento na gaveta, sempre o mesmo.
import { ref, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { caixaSessaoStore } from '@components/stores/caixaSessaoStore'

const store = caixaSessaoStore()
const form = ref({})

watch(
  () => store.dialogItem,
  (aberto) => {
    if (!aberto || !store.item) return
    const i = store.item
    form.value = {
      valorvendido: i.valorvendido,
      valorentrada: i.valorentrada || null,
      valorsaida: i.valorsaida || null,
      observacoes: i.observacoes || '',
    }
  },
)

async function salvar() {
  const ok = await store.salvarItem(store.item.codcaixaitem, {
    valorvendido: store.item.modo === 'M' ? form.value.valorvendido : null,
    valorentrada: form.value.valorentrada || 0,
    valorsaida: form.value.valorsaida || 0,
    observacoes: form.value.observacoes || null,
  })
  if (ok) store.dialogItem = false
}
</script>

<template>
  <q-dialog v-model="store.dialogItem">
    <q-card v-if="store.item" flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-h6">{{ store.item.item }}</q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div v-if="store.item.modo === 'M'" class="col-12">
              <MgInputValor
                v-model="form.valorvendido"
                label="Vendido (relatório do parceiro)"
                autofocus
              />
            </div>
            <div class="col-6">
              <MgInputValor
                v-model="form.valorentrada"
                :label="store.item.modo === 'M' ? 'Entrou na gaveta' : 'Estoque recebido'"
                :autofocus="store.item.modo === 'C'"
              />
            </div>
            <div class="col-6">
              <MgInputValor
                v-model="form.valorsaida"
                :label="store.item.modo === 'M' ? 'Saiu da gaveta' : 'Estoque devolvido'"
              />
            </div>
            <div class="col-12">
              <MgInput
                v-model="form.observacoes"
                label="Observação"
                type="textarea"
                autogrow
                maxlength="300"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
          <q-btn flat label="Salvar" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
