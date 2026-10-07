<script setup>
// Cadastro do portador (doc-4): o mesmo form para criar (FAB do painel) e editar (cabeçalho do
// portador). Financeiro e Admin.
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectBanco from '@components/MgSelectBanco.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import { usePortadorStore } from 'src/stores/portadorStore'
import { PORTADOR_TIPO_OPTIONS } from 'src/constants/portadorTipo'

const store = usePortadorStore()
const { form, dialog, salvando, isNovo } = storeToRefs(store)
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-form @submit.prevent="store.salvar()">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO PORTADOR' : 'EDITAR PORTADOR' }}
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-8">
              <MgInput
                v-model="form.portador"
                label="Portador"
                maxlength="50"
                autofocus
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-4">
              <q-select
                v-model="form.tipo"
                :options="PORTADOR_TIPO_OPTIONS"
                emit-value
                map-options
                outlined
                label="Tipo"
                lazy-rules
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <!-- espécie não tem banco, conta, Pix nem boleto -->
            <div v-if="form.tipo !== 'E'" class="col-12 col-sm-6">
              <MgSelectBanco v-model="form.codbanco" outlined clearable label="Banco" />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectFilial v-model="form.codfilial" outlined clearable label="Filial" />
            </div>
            <template v-if="form.tipo !== 'E'">
              <div class="col-4">
                <MgInputValor
                  v-model="form.agencia"
                  :decimals="0"
                  :grouping="false"
                  label="Agência"
                />
              </div>
              <div class="col-2">
                <MgInputValor
                  v-model="form.agenciadigito"
                  :decimals="0"
                  :grouping="false"
                  label="Dígito"
                />
              </div>
              <div class="col-4">
                <MgInputValor v-model="form.conta" :decimals="0" :grouping="false" label="Conta" />
              </div>
              <div class="col-2">
                <MgInputValor
                  v-model="form.contadigito"
                  :decimals="0"
                  :grouping="false"
                  label="Dígito"
                />
              </div>
              <div class="col-12">
                <MgInput v-model="form.pixdict" label="Chave Pix" maxlength="77" />
              </div>
            </template>
            <div v-if="form.tipo === 'E'" class="col-12 col-sm-6">
              <MgInputValor
                v-model="form.tolerancia"
                label="Tolerância da contagem"
                :min="0"
                hint="Diferença que ainda fecha o período"
              />
            </div>
            <div v-if="form.tipo !== 'E'" class="col-12">
              <q-checkbox v-model="form.emiteboleto" label="Emite Boleto" />
            </div>
            <template v-if="form.tipo !== 'E' && form.emiteboleto">
              <div class="col-6 col-sm-4">
                <MgInputValor
                  v-model="form.convenio"
                  :decimals="0"
                  :grouping="false"
                  label="Convênio"
                />
              </div>
              <div class="col-6 col-sm-4">
                <MgInputValor
                  v-model="form.carteira"
                  :decimals="0"
                  :grouping="false"
                  label="Carteira"
                />
              </div>
              <div class="col-6 col-sm-4">
                <MgInputValor
                  v-model="form.carteiravariacao"
                  :decimals="0"
                  :grouping="false"
                  label="Variação"
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
