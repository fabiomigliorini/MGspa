<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { valeModeloStore } from 'stores/valeModelo'
import { formataReal } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectProdutoBarra from '@components/MgSelectProdutoBarra.vue'

const route = useRoute()
const router = useRouter()
const sVale = valeModeloStore()
const { form, carregandoForm, salvando, isNovo, face } = storeToRefs(sVale)

// O select nao guarda selecao: escolher um produto acrescenta o item e limpa.
const produtoNovo = ref(null)
const acrescentarProduto = (opcao) => {
  sVale.itemAcrescentar(opcao)
  produtoNovo.value = null
}

const submit = async () => {
  if (await sVale.salvar()) {
    router.push('/vale-modelo')
  }
}

onMounted(async () => {
  const cod = route.params.codvalemodelo
  if (cod) {
    if (!(await sVale.carregarForm(cod))) router.push('/vale-modelo')
  } else {
    sVale.novo()
  }
})
</script>

<template>
  <q-page class="bg-grey-2">
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div v-if="carregandoForm" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <q-form v-else @submit.prevent="submit">
        <!-- IDENTIFICACAO -->
        <q-card bordered flat class="q-mb-md">
          <q-card-section class="row items-center q-pb-none">
            <div class="text-grey-9 text-overline">
              {{ isNovo ? 'Novo modelo de vale' : 'Modelo de vale' }}
            </div>
            <q-space />
            <MgInfoCriacao v-if="!isNovo" :registro="form" />
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="form.modelo"
                  outlined
                  label="Descrição"
                  maxlength="100"
                  autofocus
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div class="col-12 col-md-6">
                <MgSelectPessoa
                  v-model="form.codpessoafavorecido"
                  label="Favorecido (escola)"
                  clearable
                />
                <div class="text-caption text-grey-7">Em branco = vale ao portador</div>
              </div>
              <div class="col-12">
                <q-input v-model="form.observacoes" outlined label="Observações" maxlength="200" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- ITENS DO KIT -->
        <q-card bordered flat class="q-mb-md">
          <q-card-section class="row items-center q-pb-none">
            <div class="text-grey-9 text-overline">Itens do kit</div>
            <q-space />
            <div class="text-subtitle1 text-weight-medium">{{ formataReal(face) }}</div>
          </q-card-section>
          <q-card-section>
            <div class="q-mb-md">
              <MgSelectProdutoBarra
                v-model="produtoNovo"
                label="Acrescentar produto ao kit"
                @select="acrescentarProduto"
              />
            </div>

            <MgEmptyState v-if="!form.itens.length" plain icon="inventory_2">
              Kit sem itens. Sem itens o modelo vale zero e não serve para vender.
            </MgEmptyState>

            <div
              v-for="(item, indice) in form.itens"
              :key="item.codprodutobarra"
              class="row q-col-gutter-sm items-start q-mb-sm"
            >
              <div class="col-12 col-md-6 q-pt-md">
                <div class="text-body2">{{ item.produto }}</div>
                <div class="text-caption text-grey-7">{{ item.barras }}</div>
              </div>
              <div class="col-4 col-md-2">
                <MgInputValor
                  v-model="item.quantidade"
                  label="Qtde"
                  :decimals="3"
                  :min="0.001"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div class="col-4 col-md-2">
                <MgInputValor v-model="item.valorunitario" label="Unitário" prefix="R$" />
              </div>
              <div class="col-3 col-md-1 text-right q-pt-md">
                {{ formataReal((item.quantidade || 0) * (item.valorunitario || 0)) }}
              </div>
              <div class="col-1 q-pt-sm">
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="sVale.itemRemover(indice)"
                >
                  <q-tooltip>Remover item</q-tooltip>
                </q-btn>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <div class="row justify-end q-gutter-sm">
          <q-btn flat label="Cancelar" color="grey-8" to="/vale-modelo" tabindex="-1" />
          <q-btn label="Salvar" color="primary" type="submit" :loading="salvando" />
        </div>
      </q-form>
    </div>
  </q-page>
</template>
