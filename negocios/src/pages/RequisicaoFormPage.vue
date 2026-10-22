<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputProdutoBarras from '@components/MgInputProdutoBarras.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import SelectEstoqueLocal from 'components/selects/SelectEstoqueLocal.vue'
import { requisicaoStore, DESTINOS } from 'stores/requisicao'
import { negocioStore } from 'stores/negocio'

// Nova requisição / editar (TASK-55, doc-6). Editar só enquanto aguardando, pela loja que pediu.
const route = useRoute()
const router = useRouter()
const sRequisicao = requisicaoStore()
const sNegocio = negocioStore()
const { form, salvando, isNovo } = storeToRefs(sRequisicao)

const temCliente = computed(() => form.value.destino !== 'L')

// pessoa cadastrada preenche o nome e o endereço (dá para mudar depois)
function escolherPessoa(pessoa) {
  if (!pessoa) return
  form.value.nome = pessoa.fantasia || pessoa.pessoa || form.value.nome
  const endereco = [
    [pessoa.endereco, pessoa.numero].filter(Boolean).join(', '),
    pessoa.complemento,
    pessoa.bairro,
    [pessoa.cidade, pessoa.uf].filter(Boolean).join('/'),
  ]
    .filter(Boolean)
    .join(' - ')
  if (endereco) form.value.endereco = endereco
}

async function salvar() {
  const salva = await sRequisicao.salvar()
  if (salva) router.replace(`/requisicao/${salva.codrequisicao}`)
}

onMounted(async () => {
  if (route.params.codrequisicao) {
    if (await sRequisicao.carregar(route.params.codrequisicao)) {
      sRequisicao.editar(sRequisicao.requisicao)
    }
    return
  }
  sRequisicao.novo({ codestoquelocalrequisitante: sNegocio.padrao.codestoquelocal })
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-form @submit.prevent="salvar">
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline q-pb-none">
            {{ isNovo ? 'Nova requisição' : 'Editar requisição' }}
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <SelectEstoqueLocal
                  v-model="form.codestoquelocalrequisitante"
                  label="Loja que pede"
                  outlined
                  :readonly="!isNovo"
                  :rules="[(v) => !!v || 'Informe a loja que pede']"
                />
              </div>
              <div class="col-12 col-sm-6">
                <SelectEstoqueLocal
                  v-model="form.codestoquelocalrequisitada"
                  label="Loja que atende"
                  outlined
                  autofocus
                  :rules="[
                    (v) => !!v || 'Informe a loja que atende',
                    (v) => v !== form.codestoquelocalrequisitante || 'Tem que ser outra loja',
                  ]"
                />
              </div>
              <div class="col-12">
                <div class="text-caption text-grey-7">Para onde vai a mercadoria</div>
                <q-option-group v-model="form.destino" :options="DESTINOS" type="radio" />
              </div>
              <template v-if="temCliente">
                <div class="col-12">
                  <MgSelectPessoa
                    v-model="form.codpessoa"
                    label="Cliente cadastrado (opcional)"
                    clearable
                    @select="escolherPessoa"
                  />
                </div>
                <div class="col-12 col-sm-8">
                  <MgInput
                    v-model="form.nome"
                    label="Nome de quem retira ou recebe"
                    maxlength="100"
                    :rules="[(v) => !!v || 'Informe o nome']"
                  />
                </div>
                <div class="col-12 col-sm-4">
                  <MgInput v-model="form.telefone" label="Telefone" maxlength="50" type="tel" />
                </div>
                <div class="col-12">
                  <MgInput
                    v-model="form.endereco"
                    label="Endereço da entrega"
                    maxlength="300"
                    :rules="[(v) => form.destino !== 'E' || !!v || 'Informe o endereço da entrega']"
                  />
                </div>
              </template>
              <div class="col-12">
                <MgInput
                  v-model="form.observacoes"
                  label="Observações"
                  type="textarea"
                  autogrow
                  maxlength="500"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card flat bordered class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline q-pb-none">Produtos</q-card-section>
          <q-card-section>
            <MgInputProdutoBarras
              label="Acrescentar produto (bipe, ou digite 5* e o código)"
              pesquisa
              @select="sRequisicao.adicionarItem"
            />
          </q-card-section>
          <MgEmptyState v-if="!form.itens.length" icon="inventory_2" class="q-mb-md">
            Nenhum produto na requisição.
          </MgEmptyState>
          <q-list v-else separator>
            <q-item v-for="(item, indice) in form.itens" :key="item.codprodutobarra">
              <q-item-section avatar>
                <q-avatar rounded size="48px">
                  <img v-if="item.produto.imagem" :src="item.produto.imagem" />
                  <q-icon v-else name="inventory_2" color="grey-5" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ item.produto.descricao }}</q-item-label>
                <q-item-label caption>{{ item.produto.barras }}</q-item-label>
              </q-item-section>
              <q-item-section side style="width: 130px">
                <MgInputValor
                  v-model="item.quantidade"
                  :decimals="item.produto.decimais || 0"
                  :min="0"
                  :suffix="item.produto.sigla"
                  :rules="[(v) => v > 0 || 'Maior que zero']"
                />
              </q-item-section>
              <q-item-section side>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  tabindex="-1"
                  @click="sRequisicao.removerItem(indice)"
                >
                  <q-tooltip>Tirar da requisição</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn
            fab
            icon="save"
            color="primary"
            type="submit"
            :loading="salvando"
            :disable="!form.itens.length"
          >
            <q-tooltip anchor="center left" self="center right">Salvar</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-form>
    </div>
  </q-page>
</template>
