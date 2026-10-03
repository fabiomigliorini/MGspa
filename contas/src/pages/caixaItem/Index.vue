<script setup>
// Itens do caixa (M13 doc-3): mercadoria de parceiro fora do fiscal que passa pela gaveta. Modo
// contagem (chips, ingressos: conta o estoque na abertura e no fechamento) ou maquineta/terceiro
// (Bilhete Agora, Redeflex: vendido, entrada e saída). Com parceiro, o líquido da sessão vira
// título de repasse (Duplicata a Pagar) no fechamento; sem parceiro, é só informação.
import { onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectContaContabil from '@components/MgSelectContaContabil.vue'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const $q = useQuasar()
const store = useCaixaItemStore()
const { items, loading, dialog, model, registro, isNovo, salvando } = storeToRefs(store)

const MODOS = [
  { label: 'Maquineta / terceiro', value: 'M' },
  { label: 'Contagem', value: 'C' },
]

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o item "${row.item}"?`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(() => store.excluir(row))
}

onMounted(() => store.fetchItems())
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-card v-if="items.length" bordered flat>
        <q-list separator>
          <q-item v-for="row in items" :key="row.codcaixaitem">
            <q-item-section>
              <q-item-label :class="row.inativo ? 'text-strike text-grey-6' : ''">
                {{ row.item }}
              </q-item-label>
              <q-item-label caption>
                {{ row.mododescricao }} · {{ row.filial ?? 'Todas as filiais' }}
              </q-item-label>
              <q-item-label caption>
                <template v-if="row.codpessoa">
                  Repasse para {{ row.fantasia }} · {{ row.contacontabil }}
                </template>
                <template v-else>Sem parceiro: não gera título</template>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row no-wrap">
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="store.abrirEditar(row)"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  :icon="row.inativo ? 'play_arrow' : 'pause'"
                  @click="store.alternarInativo(row)"
                >
                  <q-tooltip>{{ row.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluir(row)">
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
      <MgEmptyState v-else-if="!loading" icon="inventory_2">Nenhum item do caixa.</MgEmptyState>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="store.abrirNovo">
        <q-tooltip anchor="center left" self="center right">Novo item</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO ITEM DO CAIXA' : 'EDITAR ITEM DO CAIXA' }}
        </q-card-section>
        <q-form @submit.prevent="store.salvar">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-8">
                <MgInput
                  v-model="model.item"
                  label="Item"
                  maxlength="50"
                  autofocus
                  :rules="[(v) => !!v]"
                  lazy-rules
                />
              </div>
              <div class="col-12 col-sm-4">
                <MgInput v-model.number="model.ordem" label="Ordem" type="number" min="0" />
              </div>
              <div class="col-12">
                <q-btn-toggle
                  v-model="model.modo"
                  spread
                  no-caps
                  unelevated
                  toggle-color="primary"
                  color="grey-3"
                  text-color="grey-9"
                  :options="MODOS"
                />
              </div>
              <div class="col-12">
                <MgSelectFilial
                  v-model="model.codfilial"
                  label="Filial (vazio = todas)"
                  clearable
                />
              </div>
              <div class="col-12">
                <MgSelectPessoa v-model="model.codpessoa" label="Parceiro" clearable />
              </div>
              <div class="col-12">
                <MgSelectContaContabil
                  v-model="model.codcontacontabil"
                  label="Conta contábil do repasse"
                  clearable
                  :rules="[(v) => !model.codpessoa || !!v || 'Com parceiro, informe a conta']"
                  lazy-rules
                />
              </div>
            </div>
          </q-card-section>
          <q-card-section v-if="registro" class="q-pt-none">
            <MgInfoCriacao :registro="registro" />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" color="primary" type="submit" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
