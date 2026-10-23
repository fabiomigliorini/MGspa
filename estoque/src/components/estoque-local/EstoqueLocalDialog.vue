<script setup>
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import { useEstoqueLocalStore } from 'src/stores/estoqueLocalStore'

// Formulário único do local de estoque: o Index abre para criar, o Detalhe para editar.
const router = useRouter()
const sLocal = useEstoqueLocalStore()
const { form, dialog, salvando, isNovo, impressoras } = storeToRefs(sLocal)

async function salvar() {
  const novo = isNovo.value
  const salvo = await sLocal.salvar()
  if (salvo && novo)
    router.push({ name: 'estoque-local-detalhe', params: { id: salvo.codestoquelocal } })
}
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-grey-9 text-overline text-uppercase">
          {{ isNovo ? 'Novo local de estoque' : 'Editar local de estoque' }}
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-8">
              <MgInput
                v-model="form.estoquelocal"
                label="Local"
                maxlength="50"
                autofocus
                :rules="[(v) => !!v || 'Informe o nome do local']"
              />
            </div>
            <div class="col-12 col-sm-4">
              <MgInput
                v-model="form.sigla"
                label="Sigla"
                maxlength="3"
                :rules="[(v) => !!v || 'Informe a sigla']"
              />
            </div>
            <div class="col-12">
              <MgSelectFilial
                v-model="form.codfilial"
                :rules="[(v) => !!v || 'Informe a filial']"
              />
            </div>
            <div class="col-12">
              <MgSelect
                v-model="form.codimpressora"
                :options="impressoras"
                label="Impressora da via de separação"
                hint="Onde sai sozinha a via de separação das requisições que este local recebe"
                emit-value
                map-options
                clearable
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-toggle v-model="form.controlaestoque" label="Controla estoque" />
            </div>
            <div class="col-12 col-sm-6">
              <q-toggle v-model="form.deposito" label="Depósito" />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
          <q-btn flat label="Salvar" color="primary" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
