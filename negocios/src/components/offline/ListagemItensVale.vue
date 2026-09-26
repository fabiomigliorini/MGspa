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
import { Dialog, Notify } from 'quasar'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
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

// A grade do kit abre num dialog: no card do vale (do tamanho dos cards de
// nota/titulo) ela nao cabe, e aberta na pagina viraria uma segunda parede
// de cards igual a da mercadoria. Quem precisa dela e quem vai tirar item ou
// mexer na quantidade, e ai abre.
const mostrarItens = ref(false)

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

// Comprovante so' deste vale. O codigo de barras e' o do titulo, que so'
// nasce no fechamento: antes disso nao ha o que imprimir.
const podeImprimir = computed(
  () => !!props.vale.codtitulo && sNegocio.negocio.codnegociostatus == 2,
)

const urlVale = () => '/v1/pdv/negocio/' + sNegocio.negocio.codnegocio + '/vale'

const imprimirVale = async () => {
  if (!sNegocio.padrao.impressora) {
    Notify.create({
      type: 'negative',
      message: 'Nenhuma impressora térmica selecionada!',
      timeout: 3000,
      actions: [{ icon: 'close', color: 'white' }],
    })
    return
  }
  await api.post(urlVale() + '/' + sNegocio.padrao.impressora, null, {
    params: { uuid: props.vale.uuid },
  })
  Notify.create({
    type: 'positive',
    message: 'Impressão Solicitada!',
    timeout: 1000,
    actions: [{ icon: 'close', color: 'white' }],
  })
}

const abrirVale = () =>
  abrirPdf(
    api,
    urlVale(),
    { uuid: props.vale.uuid },
    { title: 'Vale ' + props.letra, size: 'cupom', onImprimir: imprimirVale },
  )

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

  <!-- Card do vale: mesmo formato dos cards de nota/título -->
  <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2">
    <q-card flat bordered>
      <q-item>
        <q-item-section avatar>
          <q-avatar icon="card_giftcard" color="primary" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis">Vale {{ letra }}</q-item-label>
        </q-item-section>
        <q-item-section side top v-if="sNegocio.podeEditar || podeImprimir">
          <div class="row no-wrap">
            <q-btn
              v-if="podeImprimir"
              flat
              round
              size="sm"
              color="grey-7"
              icon="print"
              @click="abrirVale()"
            >
              <q-tooltip>Imprimir Vale</q-tooltip>
            </q-btn>
            <q-btn
              v-if="sNegocio.podeEditar"
              flat
              round
              size="sm"
              color="grey-7"
              icon="edit"
              @click="sNegocio.abrirVale(vale.uuid)"
            >
              <q-tooltip>Editar Vale</q-tooltip>
            </q-btn>
            <q-btn
              v-if="sNegocio.podeEditar"
              flat
              round
              size="sm"
              color="grey-7"
              icon="delete"
              @click="excluirVale()"
            >
              <q-tooltip>Excluir Vale</q-tooltip>
            </q-btn>
          </div>
        </q-item-section>
      </q-item>
      <q-separator inset />

      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis">
            {{ vale.codpessoafavorecido == 1 ? 'Ao portador' : (vale.favorecido ?? 'Ao portador') }}
          </q-item-label>
          <q-item-label caption lines="1" class="ellipsis" v-if="vale.modelo">
            {{ vale.modelo }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="vale.aluno || vale.turma">
        <q-item-section>
          <q-item-label class="ellipsis">{{ vale.aluno }}</q-item-label>
          <q-item-label caption class="ellipsis" v-if="vale.turma">
            Turma {{ vale.turma }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label>
            R$
            <span class="text-weight-bold">
              {{ formataNumero(vale.valorvale) }}
            </span>
          </q-item-label>
          <template v-if="vale.valorprodutos && vale.valoravulso">
            <q-item-label caption lines="1">
              {{ formataNumero(vale.valorprodutos) }} Em produtos
            </q-item-label>
            <q-item-label caption lines="1">
              {{ formataNumero(vale.valoravulso) }} Avulso
            </q-item-label>
          </template>
          <!-- <q-item-label caption lines="1">Valor do vale</q-item-label> -->
          <template v-if="vale.valordesconto">
            <q-item-label caption> {{ formataNumero(vale.valordesconto) }} Desconto </q-item-label>
            <q-item-label caption> {{ formataNumero(vale.valortotal) }} Pagar </q-item-label>
          </template>
        </q-item-section>
      </q-item>

      <q-item v-if="vale.validade">
        <q-item-section>
          <q-item-label class="ellipsis">{{ formataData(vale.validade) }}</q-item-label>
          <q-item-label caption lines="1">Validade</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Itens do kit: a grade abre num dialog, nao cabe no card -->
      <q-item v-if="itens.length === 0">
        <q-item-section>
          <q-item-label>Sem produtos</q-item-label>
          <q-item-label caption lines="1">Vale somente de valor</q-item-label>
        </q-item-section>
      </q-item>
      <q-item v-else clickable v-ripple @click="mostrarItens = true">
        <q-item-section>
          <q-item-label>
            {{ itens.length }} {{ itens.length == 1 ? 'item do kit' : 'itens do kit' }}
          </q-item-label>
          <q-item-label caption lines="1">
            R$ {{ formataNumero(vale.valorprodutos) }} em produtos
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-icon name="chevron_right" color="grey-7" />
        </q-item-section>
      </q-item>
    </q-card>
  </div>

  <!-- Itens do kit -->
  <q-dialog v-model="mostrarItens">
    <q-card flat style="width: 1086px; max-width: 95vw">
      <q-card-section class="row items-center no-wrap">
        <div class="col">
          <div class="text-h6 ellipsis">Vale {{ letra }} · {{ vale.modelo ?? 'Itens do kit' }}</div>
          <div class="text-caption text-grey-7 ellipsis">
            {{ vale.codpessoafavorecido == 1 ? 'Ao portador' : (vale.favorecido ?? 'Ao portador') }}
          </div>
        </div>
        <q-btn flat round icon="close" color="grey-7" v-close-popup />
      </q-card-section>

      <q-card-section class="q-pt-none">
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
  </q-dialog>
</template>
