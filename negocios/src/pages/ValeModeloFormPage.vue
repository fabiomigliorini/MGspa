<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { valeModeloStore } from 'stores/valeModelo'
import { formataReal, formataNumero, formataNumeroInteligente } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputProdutoBarras from '@components/MgInputProdutoBarras.vue'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const sVale = valeModeloStore()
const { form, carregandoForm, salvando, isNovo, valorProdutos, valorVale } = storeToRefs(sVale)

// Bipar ou digitar o codigo acrescenta o item ao kit; o multiplicador do
// campo ("5*") entra como quantidade.
const acrescentarProduto = (produto, quantidade) => sVale.itemAcrescentar(produto, quantidade)

const SEM_IMAGEM = 'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg'
const imagemItem = (item) => item.imagem || SEM_IMAGEM

// Ultimo bipado primeiro: o card novo nasce no topo da grade, que e a
// confirmacao visual de quem esta montando o kit. O indice real viaja junto
// porque editar, remover e o +/- mexem direto em form.itens.
const itensExibicao = computed(() =>
  form.value.itens.map((item, indice) => ({ item, indice })).reverse(),
)

const preenchimentoObrigatorioRule = [
  (val) => (val && parseFloat(val) >= 0.001) || '* Obrigatório!',
]

// Edição do item num diálogo, como na listagem de produtos do negócio.
// Aqui o item é só quantidade x preço, então o diálogo tem três campos.
const dialogItem = ref(false)
const indiceEdicao = ref(null)
const edicao = ref({ quantidade: null, valorunitario: null, valorprodutos: null })

const editarItem = (indice, item) => {
  indiceEdicao.value = indice
  edicao.value = {
    quantidade: item.quantidade,
    valorunitario: item.valorunitario,
    valorprodutos: Math.round((item.quantidade || 0) * (item.valorunitario || 0) * 100) / 100,
  }
  dialogItem.value = true
}

const recalcularValorProdutos = () => {
  edicao.value.valorprodutos =
    Math.round(edicao.value.quantidade * edicao.value.valorunitario * 100) / 100
}

// mexeu no total, o preço unitário é que cede
const recalcularValorUnitario = () => {
  if (!edicao.value.quantidade) return
  edicao.value.valorunitario =
    Math.round((edicao.value.valorprodutos / edicao.value.quantidade) * 100) / 100
}

// Sem confirmação: aqui só se mexe no formulário em memória. Quem grava é
// o Salvar do modelo, lá embaixo, e esse sim confirma.
const salvarItem = () => {
  const item = form.value.itens[indiceEdicao.value]
  item.quantidade = parseFloat(edicao.value.quantidade)
  item.valorunitario = parseFloat(edicao.value.valorunitario)
  dialogItem.value = false
}

const confirmarRemocaoItem = (indice, item) => {
  $q.dialog({
    title: 'Excluir',
    message: `Tem certeza que você deseja excluir "${item.produto}" do kit?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(() => sVale.itemRemover(indice))
}

// ---- 4. confirmacao de gravacao ----
const submit = () => {
  $q.dialog({
    title: isNovo.value ? 'Salvar modelo' : 'Salvar alterações',
    message: `Confirma salvar "${form.value.modelo}" com face de ${formataReal(valorVale.value)}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Salvar', color: 'primary', flat: true },
  }).onOk(async () => {
    if (await sVale.salvar()) router.push('/vale-modelo')
  })
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
                <MgInput
                  v-model="form.modelo"
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
                <MgInput v-model="form.observacoes" label="Observações" maxlength="200" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- VALOR DO VALE -->
        <q-card bordered flat class="q-mb-md">
          <q-card-section class="q-pb-none">
            <div class="text-grey-9 text-overline">Valor do vale</div>
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md items-center">
              <div class="col-12 col-sm-4">
                <MgInputValor
                  :model-value="valorProdutos"
                  label="Produtos do kit"
                  prefix="R$"
                  readonly
                />
              </div>
              <div class="col-12 col-sm-4">
                <MgInputValor v-model="form.valoravulso" label="Avulso" prefix="R$" :min="0" />
              </div>
              <div class="col-12 col-sm-4">
                <MgInputValor :model-value="valorVale" label="Total (face)" prefix="R$" readonly />
              </div>
              <div class="col-12">
                <div class="text-caption text-grey-7">
                  O total é a face do vale — o crédito que a emissão vai gerar. Um modelo sem
                  produto nenhum vale o avulso digitado.
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- ITENS DO KIT -->
        <q-card bordered flat class="q-mb-md">
          <q-card-section class="row items-center q-pb-none">
            <div class="text-grey-9 text-overline">Itens do kit</div>
            <q-space />
            <div class="text-subtitle1 text-weight-medium">{{ formataReal(valorProdutos) }}</div>
          </q-card-section>
          <q-card-section>
            <MgInputProdutoBarras
              label="Acrescentar produto ao kit (bipe, ou digite 5* e o código)"
              pesquisa
              @select="acrescentarProduto"
            />
          </q-card-section>
        </q-card>

        <!-- Os cards ficam fora do card do campo de bipe: a grade e a lista do
             kit, nao um detalhe do campo. -->
        <MgEmptyState v-if="!form.itens.length" icon="inventory_2" class="q-mb-md">
          Kit sem itens. Sem itens o modelo vale zero e não serve para vender.
        </MgEmptyState>

        <div v-else class="row q-col-gutter-md q-mb-md">
          <div
            v-for="{ item, indice } in itensExibicao"
            :key="item.codprodutobarra"
            class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-3"
          >
            <q-card flat bordered>
              <q-img ratio="1" :src="imagemItem(item)" />
              <q-separator />

              <q-card-section class="q-pb-none">
                <div class="absolute" style="top: 0; right: 5px; transform: translateY(-42px)">
                  <q-btn color="primary" round icon="edit" @click="editarItem(indice, item)" />
                  <q-btn
                    round
                    color="negative"
                    icon="delete"
                    class="q-ma-sm"
                    @click="confirmarRemocaoItem(indice, item)"
                  />
                </div>

                <div class="text-h5">
                  <small class="text-grey-7">R$</small>
                  {{ formataNumero((item.quantidade || 0) * (item.valorunitario || 0)) }}
                </div>

                <div class="text-overline text-grey-7">
                  <q-btn
                    size="xs"
                    label="-"
                    round
                    flat
                    @click="sVale.itemSomarQuantidade(indice, -1)"
                  />
                  {{ formataNumeroInteligente(item.quantidade) }}
                  <q-btn
                    size="xs"
                    label="+"
                    round
                    flat
                    @click="sVale.itemSomarQuantidade(indice, 1)"
                  />
                  de
                  {{ formataNumero(item.valorunitario) }}
                </div>
              </q-card-section>

              <q-card-section class="q-pt-none">
                <div class="text-caption text-grey-7">{{ item.barras }}</div>
                <div class="text-caption">{{ item.produto }}</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Dentro do q-form de propósito: com type="submit" o Quasar valida
             os :rules antes de chamar o submit. Fora do form, o clique
             pularia a validação. Cancelar não existe -- a seta do cabeçalho
             já volta para a listagem. -->
        <q-page-sticky v-if="!isNovo" position="bottom-right" :offset="[18, 82]">
          <q-btn
            fab
            flat
            color="primary"
            icon="receipt_long"
            :to="`/vale-modelo/emitidos?codvalemodelo=${form.codvalemodelo}`"
          >
            <q-tooltip anchor="center left" self="center right"
              >Ver vales emitidos deste modelo</q-tooltip
            >
          </q-btn>
        </q-page-sticky>
        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn fab icon="save" color="primary" type="submit" :loading="salvando">
            <q-tooltip anchor="center left" self="center right">Salvar</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-form>
    </div>

    <!-- Editar Item -->
    <q-dialog v-model="dialogItem">
      <div class="q-pa-md">
        <q-card flat>
          <q-form @submit="salvarItem">
            <q-card-section>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <MgInputValor
                    v-model="edicao.quantidade"
                    autofocus
                    lazy-rules
                    :min="0.001"
                    :decimals="3"
                    label="Quantidade"
                    :rules="preenchimentoObrigatorioRule"
                    @change="recalcularValorProdutos()"
                  />
                </div>
              </div>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <MgInputValor
                    v-model="edicao.valorunitario"
                    :min="0.01"
                    prefix="R$"
                    label="Preço"
                    :rules="preenchimentoObrigatorioRule"
                    @change="recalcularValorProdutos()"
                  />
                </div>
                <div class="col-6">
                  <MgInputValor
                    v-model="edicao.valorprodutos"
                    :min="0.01"
                    prefix="R$"
                    label="Total Produto"
                    :rules="preenchimentoObrigatorioRule"
                    @change="recalcularValorUnitario()"
                  />
                </div>
              </div>
            </q-card-section>

            <q-card-actions align="right">
              <q-btn
                flat
                label="Cancelar"
                color="grey-8"
                tabindex="-1"
                @click="dialogItem = false"
              />
              <q-btn type="submit" flat label="Salvar" color="primary" />
            </q-card-actions>
          </q-form>
        </q-card>
      </div>
    </q-dialog>
  </q-page>
</template>
