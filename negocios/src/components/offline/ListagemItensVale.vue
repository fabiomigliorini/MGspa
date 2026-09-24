<script setup>
// Uma seção de vale compras do negócio: o cabeçalho do vale e a grade dos
// itens do kit.
//
// A grade recebe o vale por prop — não lê o store — porque o negócio pode
// ter vários vales, cada um com a sua lista. Quem manda salvar continua
// sendo o store (actions vale*).
//
// Aqui só se TIRA item e se AJUSTA quantidade/preço: o universo do vale é o
// kit do modelo. Produto fora da lista o cliente leva como mercadoria, pela
// bipagem normal.
import { ref, computed } from 'vue'
import { Dialog } from 'quasar'
import { produtoStore } from 'stores/produto'
import { negocioStore } from 'stores/negocio'
import { formataData, formataNumero, formataNumeroInteligente } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  vale: { type: Object, required: true },
  letra: { type: String, default: 'A' },
})

const sProduto = produtoStore()
const sNegocio = negocioStore()

const dialogItem = ref(false)
const edicao = ref({
  uuid: null,
  produto: null,
  quantidade: null,
  valorunitario: null,
})

const itens = computed(() =>
  props.vale.itens
    .filter((item) => item.inativo == null)
    .sort((a, b) => String(a.ordenacao).localeCompare(String(b.ordenacao))),
)

const preenchimentoObrigatorioRule = [
  (val) => (val && parseFloat(val) >= 0.001) || '* Obrigatório!',
]

const valorProdutosEdicao = computed(
  () =>
    Math.round(
      (parseFloat(edicao.value.quantidade) || 0) *
        (parseFloat(edicao.value.valorunitario) || 0) *
        100,
    ) / 100,
)

const editar = (item) => {
  edicao.value = {
    uuid: item.uuid,
    produto: item.produto,
    quantidade: item.quantidade,
    valorunitario: item.valorunitario,
  }
  dialogItem.value = true
}

const salvarItem = async () => {
  await sNegocio.valeItemSalvar(
    props.vale.uuid,
    edicao.value.uuid,
    parseFloat(edicao.value.quantidade),
    parseFloat(edicao.value.valorunitario),
  )
  dialogItem.value = false
}

const somarQuantidade = (item, passo) => {
  const total = parseFloat(item.quantidade) + passo
  if (total <= 0) {
    return
  }
  sNegocio.valeItemSalvar(props.vale.uuid, item.uuid, total, item.valorunitario)
}

const inativarItem = (item) => {
  Dialog.create({
    title: 'Excluir',
    message: 'Tem certeza que você deseja tirar esse item do vale?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(() => {
    sNegocio.valeItemInativar(props.vale.uuid, item.uuid)
  })
}

const excluirVale = () => {
  Dialog.create({
    title: 'Excluir',
    message: 'Tem certeza que você deseja excluir o Vale ' + props.letra + ' do negócio?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(() => {
    sNegocio.valeExcluir(props.vale.uuid)
  })
}

const linkProduto = (codproduto) => {
  return process.env.MGLARA_URL + 'produto/' + codproduto
}
</script>

<template>
  <!-- Editar Item do Vale -->
  <q-dialog v-model="dialogItem">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvarItem()">
        <q-card-section>
          <div class="text-h6 q-mb-sm">EDITAR ITEM</div>
          <div class="text-caption text-grey-7 q-mb-md">{{ edicao.produto }}</div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <MgInputValor
                autofocus
                lazy-rules
                :min="0.001"
                :decimals="3"
                v-model="edicao.quantidade"
                label="Quantidade"
                :rules="preenchimentoObrigatorioRule"
              />
            </div>
            <div class="col-6">
              <MgInputValor
                lazy-rules
                :min="0.01"
                v-model="edicao.valorunitario"
                prefix="R$"
                label="Preço"
                :rules="preenchimentoObrigatorioRule"
              />
            </div>
            <div class="col-12 text-right">
              <span class="text-caption text-grey-7">Total do item </span>
              <span class="text-subtitle1">{{ formataNumero(valorProdutosEdicao) }}</span>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" @click="dialogItem = false" />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Cabeçalho do vale -->
  <q-card flat bordered class="q-mb-md">
    <q-card-section class="q-pb-sm">
      <div class="row items-center">
        <div class="col">
          <div class="text-overline text-grey-7">Vale {{ letra }}</div>
          <div class="text-subtitle1">
            {{ vale.codpessoafavorecido == 1 ? 'Ao portador' : (vale.favorecido ?? 'Ao portador') }}
          </div>
          <div class="text-caption text-grey-7">
            <template v-if="vale.modelo">{{ vale.modelo }}<br /></template>
            <template v-if="vale.aluno">{{ vale.aluno }} </template>
            <template v-if="vale.turma">· {{ vale.turma }} </template>
            <template v-if="vale.validade">· vale até {{ formataData(vale.validade) }}</template>
          </div>
        </div>
        <div class="col-auto text-right">
          <div class="text-caption text-grey-7">Valor do vale</div>
          <div class="text-h5 text-primary">{{ formataNumero(vale.valorvale) }}</div>
          <div class="text-caption text-grey-7" v-if="vale.valoravulso">
            {{ formataNumero(vale.valorprodutos) }} em produtos +
            {{ formataNumero(vale.valoravulso) }} avulso
          </div>
        </div>
        <div class="col-auto q-ml-sm" v-if="sNegocio.podeEditar">
          <q-btn
            flat
            round
            size="sm"
            color="grey-7"
            icon="edit"
            @click="sNegocio.abrirVale(vale.uuid)"
          >
            <q-tooltip>Editar Vale</q-tooltip>
          </q-btn>
          <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluirVale()">
            <q-tooltip>Excluir Vale</q-tooltip>
          </q-btn>
        </div>
      </div>
    </q-card-section>

    <q-separator />

    <!-- Itens do kit -->
    <q-card-section v-if="itens.length === 0" class="text-grey-7">
      Vale somente de valor, sem lista de produtos.
    </q-card-section>

    <q-card-section v-else>
      <div class="row q-col-gutter-md">
        <div
          class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2"
          v-for="item in itens"
          :key="item.uuid"
        >
          <q-card flat bordered>
            <q-img ratio="1" :src="sProduto.urlImagem(item.codimagem)" />
            <q-separator />

            <q-card-section class="q-pb-none">
              <div class="absolute" style="top: 0; right: 5px; transform: translateY(-42px)">
                <q-btn
                  v-if="sNegocio.podeEditar"
                  color="primary"
                  round
                  icon="edit"
                  @click="editar(item)"
                />
                <q-btn
                  v-if="sNegocio.podeEditar"
                  round
                  color="negative"
                  icon="delete"
                  class="q-ma-sm"
                  @click="inativarItem(item)"
                />
              </div>

              <Transition
                mode="out-in"
                :duration="{ enter: 300, leave: 300 }"
                leave-active-class="animated bounceOut"
                enter-active-class="animated bounceIn"
              >
                <div class="text-h5" :key="item.valorprodutos">
                  <small class="text-grey-7">R$</small>
                  {{ formataNumero(item.valorprodutos) }}
                </div>
              </Transition>

              <div class="text-overline text-grey-7">
                <q-btn
                  v-if="sNegocio.podeEditar"
                  size="xs"
                  label="-"
                  round
                  flat
                  @click="somarQuantidade(item, -1)"
                />
                {{ formataNumeroInteligente(item.quantidade) }}
                <q-btn
                  v-if="sNegocio.podeEditar"
                  size="xs"
                  label="+"
                  round
                  flat
                  @click="somarQuantidade(item, 1)"
                />
                de
                {{ formataNumero(item.valorunitario) }}
              </div>
            </q-card-section>

            <q-item clickable v-ripple :href="linkProduto(item.codproduto)" target="_blank">
              <q-item-section class="text-caption text-grey-7">
                <q-item-label overline>{{ item.barras }}</q-item-label>
                <q-item-label>{{ item.produto }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-card>
        </div>
      </div>
    </q-card-section>
  </q-card>
</template>
