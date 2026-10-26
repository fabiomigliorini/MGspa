<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumeroInteligente } from '@components/formatters'
import GeradorRascunho from 'components/requisicao/GeradorRascunho.vue'
import { requisicaoGeradorStore, COR_SITUACAO, arredondar } from 'stores/requisicaoGerador'
import { fracaoRecusada } from '../utils/quantidade.js'

// Gerador de requisição (TASK-258): um produto. Cada variação mostra a loja que pede (saldo,
// mín, máx, quanto falta para o máximo) e, embaixo, cada outra loja com saldo, mín, máx, o
// que já foi pedido dela em reposição aberta e o campo de quanto pedir. Digita-se em
// unidades; ao sair do campo, sobe para o múltiplo da embalagem de transferência e vai para o
// rascunho.
const route = useRoute()
const sGerador = requisicaoGeradorStore()

const codestoquelocal = computed(() => Number(route.params.codestoquelocal))
const dados = computed(() => sGerador.produto)
const produto = computed(() => dados.value?.produto)
const unidade = computed(() => ({ sigla: produto.value?.sigla, decimais: produto.value?.decimais }))
const temMultiplo = computed(() => Number(produto.value?.multiplo) > 1)

const urlEstoque = computed(() => `${process.env.ESTOQUE_URL}/produto/${route.params.codproduto}`)

// o que está no campo, em unidades, por variação-loja
const digitado = ref({})
const chave = (v, cod) => `${v.codprodutovariacao}-${cod}`

function preencher() {
  const ret = {}
  for (const v of dados.value?.variacoes || []) {
    for (const l of v.lojas) {
      ret[chave(v, l.codestoquelocal)] = sGerador.unidadesDe(
        v.codprodutovariacao,
        l.codestoquelocal,
      )
    }
  }
  digitado.value = ret
}

const numero = (n) => (n === null || n === undefined ? '—' : formataNumeroInteligente(n))

const daLoja = (v) => v.lojas.find((l) => l.codestoquelocal === codestoquelocal.value)

const faltam = (v) => {
  const l = daLoja(v)
  const f = Number(l?.estoquemaximo || 0) - Number(l?.saldo || 0)
  return f > 0 ? f : 0
}

// as outras lojas, na ordem das abas do painel
const outras = (v) =>
  sGerador.lojas
    .filter((loja) => loja.codestoquelocal !== codestoquelocal.value)
    .map((loja) => ({ loja, l: v.lojas.find((x) => x.codestoquelocal === loja.codestoquelocal) }))
    .filter((x) => x.l)

const semBarra = (v) => !v.codprodutobarraembalagem && !v.codprodutobarraunidade

// com múltiplo a quantidade sobe para ele (o campo já fica nas casas da unidade); sem, o campo
// aceita 3 casas e a fração errada é recusada (nunca arredondada)
const regras = computed(() =>
  temMultiplo.value ? [] : [(val) => fracaoRecusada(val, unidade.value) ?? true],
)

// "= 1 CX c/12": em quantas embalagens vai o que está no campo
function embalagens(v, cod) {
  const u = Number(digitado.value[chave(v, cod)])
  if (!u || !temMultiplo.value || !v.codprodutobarraembalagem) return null
  const m = Number(produto.value.multiplo)
  const n = arredondar(u, m) / m
  return `= ${formataNumeroInteligente(n)} ${produto.value.siglaembalagem} C/${formataNumeroInteligente(m)}`
}

function aplicar(v, cod) {
  const k = chave(v, cod)
  const val = digitado.value[k]
  if (!temMultiplo.value && fracaoRecusada(val, unidade.value)) return
  digitado.value[k] = sGerador.definir(produto.value, v, cod, val)
}

watch(dados, preencher)

onMounted(async () => {
  await sGerador.carregarLojas()
  await sGerador.carregarProduto(route.params.codproduto)
  preencher()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div v-if="sGerador.carregandoProduto && !produto" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <template v-else-if="produto">
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <div class="col" style="min-width: 0">
              <div class="text-grey-9 text-overline">
                Reposição do {{ sGerador.nomeLoja(codestoquelocal) }}
              </div>
              <div class="text-h6 text-weight-regular">{{ produto.produto }}</div>
              <div class="text-caption text-grey-7">
                {{ produto.marca }}
                <template v-if="produto.abc"> · ABC {{ produto.abc }}</template>
                ·
                <template v-if="temMultiplo">
                  pede de {{ numero(produto.multiplo) }} em {{ numero(produto.multiplo) }}
                  {{ produto.sigla }} ({{ produto.siglaembalagem }} C/{{
                    numero(produto.multiplo)
                  }})
                </template>
                <template v-else>sem embalagem de transferência</template>
              </div>
            </div>
            <div class="col-12 col-sm-auto row items-center justify-end no-wrap">
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="open_in_new"
                :href="urlEstoque"
                target="_blank"
              >
                <q-tooltip>Abrir o produto no estoque (mínimo, máximo e embalagem)</q-tooltip>
              </q-btn>
              <GeradorRascunho />
            </div>
          </q-card-section>
        </q-card>

        <q-card
          v-for="v in dados.variacoes"
          :key="v.codprodutovariacao"
          flat
          bordered
          class="q-mb-md"
        >
          <q-card-section class="q-pb-none">
            <div class="text-subtitle1" :class="`text-${COR_SITUACAO[v.situacao] || 'grey-9'}`">
              {{ v.variacao || 'Sem variação' }}
            </div>
            <div class="text-caption text-grey-8">
              {{ sGerador.nomeLoja(codestoquelocal) }}: saldo {{ numero(daLoja(v)?.saldo) }} · mín
              {{ numero(daLoja(v)?.estoqueminimo) }} · máx
              {{ numero(daLoja(v)?.estoquemaximo) }}
              <template v-if="faltam(v)">
                · faltam {{ numero(faltam(v)) }} {{ produto.sigla }}</template
              >
            </div>
            <div v-if="semBarra(v)" class="text-caption text-red-5">
              Sem código de barras: não dá para pedir esta variação
            </div>
          </q-card-section>

          <q-markup-table flat separator="horizontal" class="q-mt-sm">
            <thead>
              <tr>
                <th class="text-left">Pedir de</th>
                <th class="text-right">Saldo</th>
                <th class="text-right">Mín</th>
                <th class="text-right">Máx</th>
                <th class="text-right">Pedido</th>
                <th class="text-right" style="width: 160px">Quantidade</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="{ loja, l } in outras(v)" :key="loja.codestoquelocal">
                <td class="text-left">{{ loja.estoquelocal }}</td>
                <td class="text-right">{{ numero(l.saldo) }}</td>
                <td class="text-right">{{ numero(l.estoqueminimo) }}</td>
                <td class="text-right">{{ numero(l.estoquemaximo) }}</td>
                <td class="text-right">{{ l.pedido ? numero(l.pedido) : '' }}</td>
                <td class="text-right">
                  <MgInputValor
                    v-model="digitado[chave(v, loja.codestoquelocal)]"
                    :decimals="temMultiplo ? produto.decimais : 3"
                    :grouping="false"
                    :min="0"
                    :suffix="produto.sigla"
                    :rules="regras"
                    :readonly="semBarra(v)"
                    :hint="embalagens(v, loja.codestoquelocal) || undefined"
                    @blur="aplicar(v, loja.codestoquelocal)"
                  />
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card>
      </template>
    </div>
  </q-page>
</template>
