<script setup>
import { onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useProdutoStore } from 'src/stores/produtoStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useProdutoStore()

const codFormatado = (v) => String(v).padStart(6, '0')
const formataMoeda = (v) =>
  (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

const abcColor = (abc) => ({ A: 'green-6', B: 'amber-7', C: 'blue-6', D: 'red-6' })[abc] || 'grey-6'

const breadcrumb = (p) =>
  [p.secaoproduto, p.familiaproduto, p.grupoproduto, p.subgrupoproduto, p.marca]
    .filter(Boolean)
    .join(' › ')

const precoEmbalagem = (p, emb) =>
  emb.preco ? Number(emb.preco) : Number(p.preco) * Number(emb.quantidade)

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/produto/${row.codproduto}/inativo`)
      : await api.post(`v1/produto/${row.codproduto}/inativo`)
    store.upsertLocal(data.data || data)
    notifySuccess((data.data || data).inativo ? 'Produto inativado' : 'Produto reativado')
  } catch (e) {
    notifyError(e, 'Erro ao alterar situação')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o produto "${row.produto}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/produto/${row.codproduto}`)
      store.removeLocal(row.codproduto)
      notifySuccess('Produto excluído')
    } catch (e) {
      notifyError(e, 'Erro ao excluir produto')
    }
  })
}

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

onMounted(() => store.fetchItems(true))
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="margin: auto; max-width: 1280px">
        <div
          v-if="store.items.length === 0 && !store.loading"
          class="text-center text-grey-6 q-pa-xl"
        >
          Nenhum produto encontrado
        </div>

        <div class="row q-col-gutter-md">
          <div v-for="p in store.items" :key="p.codproduto" class="col-xs-12 col-md-6">
            <q-card bordered flat class="full-height">
              <q-item clickable :to="{ name: 'produto-detalhe', params: { id: p.codproduto } }">
                <q-item-section avatar>
                  <q-avatar rounded size="64px" color="grey-3" text-color="grey-7">
                    <img v-if="p.url" :src="p.url" />
                    <q-icon v-else name="inventory_2" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label class="row items-center q-gutter-xs">
                    <q-badge :color="abcColor(p.abc)" :label="p.abc" />
                    <span class="text-caption text-grey-6">#{{ codFormatado(p.codproduto) }}</span>
                    <q-badge v-if="p.inativo" color="orange-7" label="Inativo" />
                  </q-item-label>
                  <q-item-label
                    class="text-weight-medium"
                    :class="p.inativo ? 'text-strike text-grey-5' : ''"
                  >
                    {{ p.produto }}
                  </q-item-label>
                  <q-item-label caption class="text-grey-6 ellipsis">
                    {{ breadcrumb(p) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side top>
                  <div class="text-subtitle1 text-weight-medium text-primary">
                    {{ formataMoeda(p.preco) }}
                  </div>
                  <div class="text-caption text-grey-6 text-right">/ {{ p.unidademedida }}</div>
                </q-item-section>
              </q-item>

              <q-separator inset />

              <q-card-section class="q-py-sm">
                <!-- Embalagens -->
                <div
                  v-if="p.ProdutoEmbalagemS && p.ProdutoEmbalagemS.length"
                  class="text-caption text-grey-7 q-mb-xs"
                >
                  <span
                    v-for="emb in p.ProdutoEmbalagemS"
                    :key="emb.codprodutoembalagem"
                    class="q-mr-md"
                  >
                    C/{{ Number(emb.quantidade) }}: {{ formataMoeda(precoEmbalagem(p, emb)) }}
                  </span>
                </div>
                <!-- Variações + barras -->
                <div
                  v-for="v in p.ProdutoVariacaoS"
                  :key="v.codprodutovariacao"
                  class="text-caption text-grey-7"
                >
                  <q-icon name="style" size="14px" class="q-mr-xs" />
                  {{ v.variacao || '{Sem variação}' }}
                  <span v-if="v.referencia" class="text-grey-5">· ref {{ v.referencia }}</span>
                  <span
                    v-for="b in v.ProdutoBarraS"
                    :key="b.codprodutobarra"
                    class="text-grey-5 q-ml-sm"
                  >
                    {{ b.barras }}
                  </span>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-actions align="right">
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  :to="{ name: 'produto-editar', params: { id: p.codproduto } }"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="content_copy"
                  :to="{ name: 'produto-novo', query: { duplicar: p.codproduto } }"
                >
                  <q-tooltip>Duplicar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  :icon="p.inativo ? 'play_arrow' : 'pause'"
                  @click="toggleInativo(p)"
                >
                  <q-tooltip>{{ p.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluir(p)">
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </q-card-actions>
            </q-card>
          </div>
        </div>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" :to="{ name: 'produto-novo' }">
        <q-tooltip anchor="center left" self="center right">Novo Produto</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
