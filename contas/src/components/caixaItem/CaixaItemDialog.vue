<script setup>
// Criar e editar o item do caixa: o nome e o modo. Cédula (chips) conta como cédula no caixa;
// maquineta de parceiro não se conta e leva o mínimo para gerar o título a pagar ao parceiro
// (pessoa, filial e conta contábil). O modo não muda depois que o item foi lançado em caixa.
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectContaContabil from '@components/MgSelectContaContabil.vue'
import { useCaixaItemStore, MODOS } from 'src/stores/caixaItemStore'

const store = useCaixaItemStore()
const { dialog, model, isNovo, salvando } = storeToRefs(store)
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-card-section class="text-grey-9 text-overline">
        {{ isNovo ? 'NOVO ITEM DO CAIXA' : 'EDITAR ITEM DO CAIXA' }}
      </q-card-section>
      <q-form @submit.prevent="store.salvar">
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInput
                v-model="model.item"
                label="Item"
                maxlength="50"
                autofocus
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <q-option-group v-model="model.modo" type="radio" inline :options="MODOS" />
            </div>
            <template v-if="model.modo === 'M'">
              <div class="col-12">
                <MgSelectPessoa
                  v-model="model.codpessoa"
                  label="Parceiro"
                  :rules="[(v) => !!v || 'Informe o parceiro']"
                  lazy-rules
                />
              </div>
              <div class="col-12 col-sm-6">
                <MgSelectFilial
                  v-model="model.codfilial"
                  outlined
                  label="Filial do título"
                  :rules="[(v) => !!v || 'Obrigatório']"
                  lazy-rules
                />
              </div>
              <div class="col-12 col-sm-6">
                <MgSelectContaContabil
                  v-model="model.codcontacontabil"
                  outlined
                  label="Conta contábil do título"
                  :rules="[(v) => !!v || 'Obrigatório']"
                  lazy-rules
                />
              </div>
            </template>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" color="primary" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
