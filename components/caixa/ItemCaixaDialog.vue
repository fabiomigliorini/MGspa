<script setup>
// Item do caixa na sessão aberta (M13 doc-3; seletor do item desde o doc-4). Maquineta/terceiro
// (Bilhete Agora, Redeflex): vendido (o relatório do parceiro, só informação), entrada e saída de
// dinheiro. Contagem (chips, ingressos): entrada e saída de estoque (bloco que chegou, devolução).
// Entrada − saída vira um pagamento na gaveta, sempre o mesmo.
import { ref, computed, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { periodoStore } from '@components/stores/periodoStore'

const store = periodoStore()
const codcaixaitem = ref(null)
const form = ref({})

const itens = computed(() => store.periodo?.itens ?? [])
const item = computed(() => itens.value.find((i) => i.codcaixaitem === codcaixaitem.value))

const preencher = () => {
  const i = item.value
  form.value = {
    valorvendido: i?.valorvendido ?? null,
    valorentrada: i?.valorentrada || null,
    valorsaida: i?.valorsaida || null,
    observacoes: i?.observacoes || '',
  }
}

watch(
  () => store.dialogItem,
  (aberto) => {
    if (!aberto) return
    codcaixaitem.value = store.item
    preencher()
  },
)
watch(codcaixaitem, preencher)

async function salvar() {
  const ok = await store.salvarItem(codcaixaitem.value, {
    valorvendido: item.value.modo === 'M' ? form.value.valorvendido : null,
    valorentrada: form.value.valorentrada || 0,
    valorsaida: form.value.valorsaida || 0,
    observacoes: form.value.observacoes || null,
  })
  if (ok) store.dialogItem = false
}
</script>

<template>
  <q-dialog v-model="store.dialogItem">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-h6">Item do caixa</q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-select
                v-model="codcaixaitem"
                :options="itens"
                option-value="codcaixaitem"
                option-label="item"
                emit-value
                map-options
                outlined
                label="Item"
                :autofocus="!codcaixaitem"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <template v-if="item">
              <div v-if="item.modo === 'M'" class="col-12">
                <MgInputValor
                  v-model="form.valorvendido"
                  label="Vendido (relatório do parceiro)"
                  autofocus
                />
              </div>
              <div class="col-6">
                <MgInputValor
                  v-model="form.valorentrada"
                  :label="item.modo === 'M' ? 'Entrou na gaveta' : 'Estoque recebido'"
                  :autofocus="item.modo === 'C'"
                />
              </div>
              <div class="col-6">
                <MgInputValor
                  v-model="form.valorsaida"
                  :label="item.modo === 'M' ? 'Saiu da gaveta' : 'Estoque devolvido'"
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
            </template>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
