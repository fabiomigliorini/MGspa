<script setup>
// Cadastro da maquineta: o mesmo form para criar (FAB da lista) e editar (cabeçalho da
// maquineta). A SafraPay salva como as outras e se pareia dentro dela (botão Parear).
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'
import { MAQUINETA_INTEGRACAO_OPTIONS } from 'src/constants/maquinetaIntegracao'

const store = useMaquinetaStore()
const { dialog, model, salvando, isNovo } = storeToRefs(store)
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        {{ isNovo ? 'NOVA MAQUINETA' : 'EDITAR MAQUINETA' }}
      </q-card-section>
      <q-form @submit.prevent="store.salvar()">
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgSelect
                v-model="model.integracao"
                :options="MAQUINETA_INTEGRACAO_OPTIONS"
                emit-value
                map-options
                label="Integração"
                :readonly="!isNovo"
              />
            </div>

            <div class="col-12 col-sm-8">
              <MgInput
                v-model="model.apelido"
                label="Apelido"
                maxlength="50"
                autofocus
                lazy-rules
                :rules="[(v) => !!v && v.length >= 3]"
              />
            </div>

            <div class="col-12 col-sm-4">
              <MgSelectFilial
                v-model="model.codfilial"
                outlined
                label="Filial"
                lazy-rules
                :rules="[(v) => !!v]"
              />
            </div>

            <div class="col-12 col-sm-6">
              <MgInput
                v-model="model.serial"
                label="Serial"
                maxlength="50"
                lazy-rules
                :rules="[(v) => model.integracao !== 'P' || !!v]"
              />
            </div>

            <div v-if="model.integracao === 'M'" class="col-12 col-sm-6">
              <MgSelectPessoa
                v-model="model.codpessoa"
                label="Adquirente"
                lazy-rules
                :rules="[(v) => !!v]"
              />
            </div>

            <div v-if="model.integracao !== 'S'" class="col-12">
              <q-checkbox
                v-model="model.compartilhada"
                label="Compartilhada: aparece no PDV de todas as filiais (ex.: acesso de site)"
              />
            </div>

            <div v-if="isNovo && model.integracao === 'S'" class="col-12 text-caption text-grey-7">
              Depois de salvar, abra a maquineta e clique em Parear para ler o QR Code no pinpad.
            </div>
          </div>
        </q-card-section>

        <q-separator inset />
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
