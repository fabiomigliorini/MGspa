<script setup>
// Cadastro da maquineta: o mesmo form para criar (FAB da lista) e editar (cabeçalho da
// maquineta). SafraPay nova pareia pelo QR Code que o pinpad lê.
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'
import { MAQUINETA_INTEGRACAO_OPTIONS } from 'src/constants/maquinetaIntegracao'

const store = useMaquinetaStore()
const { dialog, model, salvando, isNovo, qr } = storeToRefs(store)

const submit = () => {
  if (isNovo.value && model.value.integracao === 'S') {
    if (qr.value.qrcode) {
      store.confirmarLeitura()
    } else {
      store.gerarQrCode()
    }
    return
  }
  store.salvar()
}
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        {{ isNovo ? 'NOVA MAQUINETA' : 'EDITAR MAQUINETA' }}
      </q-card-section>
      <q-form @submit.prevent="submit">
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-select
                v-model="model.integracao"
                :options="MAQUINETA_INTEGRACAO_OPTIONS"
                emit-value
                map-options
                outlined
                label="Integração"
                :readonly="!isNovo || !!qr.qrcode"
                :tabindex="!isNovo || !!qr.qrcode ? -1 : undefined"
              />
            </div>

            <div class="col-12 col-sm-8">
              <MgInput
                v-model="model.apelido"
                label="Apelido"
                maxlength="50"
                autofocus
                :readonly="!!qr.qrcode"
                lazy-rules
                :rules="[(v) => !!v && v.length >= 3]"
              />
            </div>

            <div class="col-12 col-sm-4">
              <MgSelectFilial
                v-model="model.codfilial"
                outlined
                label="Filial"
                :readonly="!!qr.qrcode"
                lazy-rules
                :rules="[(v) => !!v]"
              />
            </div>

            <div v-if="model.integracao !== 'S' || !isNovo" class="col-12 col-sm-6">
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

            <div v-if="isNovo && model.integracao === 'S'" class="col-12">
              <div v-if="qr.qrcode" class="column items-center">
                <img :src="qr.qrcode" style="width: 220px; height: 220px" alt="QR Code" />
                <div class="text-caption text-grey-7 q-mt-sm text-center">
                  Leia o QR Code no pinpad e clique em Confirmar leitura.
                </div>
              </div>
              <div v-else class="text-caption text-grey-7">
                Gera o QR Code que o pinpad lê para parear com a SafraPay.
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator inset />
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            v-if="isNovo && model.integracao === 'S'"
            flat
            :label="qr.qrcode ? 'Confirmar leitura' : 'Gerar QR Code'"
            type="submit"
            :loading="salvando"
          />
          <q-btn v-else flat label="Salvar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
