<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataNumeroInteligente } from '@components/formatters'
import { requisicaoGeradorStore } from 'stores/requisicaoGerador'
import { PRIORIDADES } from 'stores/requisicao'

// Gerador de requisição (TASK-258): a revisão do rascunho, um card por loja que atende, com a
// prioridade pré-selecionada pela pior situação dos itens na loja que pede (zerado Urgente,
// abaixo do mínimo Normal, senão Baixa). Gerar cria uma requisição de Reposição por loja; a
// que falhou fica aqui com o erro.
const route = useRoute()
const router = useRouter()
const sGerador = requisicaoGeradorStore()

const codestoquelocal = computed(() => Number(route.params.codestoquelocal))
const opcoesPrioridade = PRIORIDADES.map((p) => ({ value: p.value, label: p.label }))

// as lojas na ordem das abas do painel; os itens pelo produto
const ordemLoja = (cod) => {
  const i = sGerador.lojas.findIndex((l) => l.codestoquelocal === cod)
  return i < 0 ? Infinity : i
}
const descricao = (i) => `${i.produto} ${i.variacao || ''}`
const cards = computed(() =>
  Object.entries(sGerador.porLoja)
    .map(([cod, itens]) => ({
      codestoquelocal: Number(cod),
      nome: sGerador.nomeLoja(Number(cod)) || `Local ${cod}`,
      itens: [...itens].sort((a, b) => descricao(a).localeCompare(descricao(b), 'pt-BR')),
    }))
    .sort((a, b) => ordemLoja(a.codestoquelocal) - ordemLoja(b.codestoquelocal)),
)

async function gerar() {
  await sGerador.gerar()
  if (!sGerador.totalItens) router.push(`/requisicao/painel/${codestoquelocal.value}`)
}

onMounted(async () => {
  await sGerador.carregarLojas()
  sGerador.preSelecionarPrioridades()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="row items-center">
          <div class="col">
            <div class="text-grey-9 text-overline">Gerador de requisição</div>
            <div class="text-h6 text-weight-regular">
              Reposição do {{ sGerador.nomeLoja(codestoquelocal) }}: confira e gere
            </div>
            <div class="text-caption text-grey-7">
              Uma requisição de reposição para cada loja que atende
            </div>
          </div>
          <q-btn
            flat
            color="primary"
            icon="send"
            label="Gerar requisições"
            :loading="sGerador.gerando"
            :disable="!cards.length"
            @click="gerar"
          />
        </q-card-section>
      </q-card>

      <MgEmptyState v-if="!cards.length" icon="checklist" class="q-mb-md">
        O rascunho está vazio.
      </MgEmptyState>

      <q-card v-for="c in cards" :key="c.codestoquelocal" flat bordered class="q-mb-md">
        <q-card-section class="row items-center q-pb-none">
          <div class="col">
            <div class="text-subtitle1 text-weight-medium">Pedir do {{ c.nome }}</div>
            <div class="text-caption text-grey-7">
              {{ c.itens.length }} {{ c.itens.length === 1 ? 'item' : 'itens' }}
            </div>
          </div>
          <q-option-group
            v-model="sGerador.prioridades[c.codestoquelocal]"
            :options="opcoesPrioridade"
            type="radio"
            inline
          />
        </q-card-section>
        <q-card-section v-if="sGerador.erros[c.codestoquelocal]" class="text-red-5 q-pb-none">
          {{ sGerador.erros[c.codestoquelocal] }}
        </q-card-section>
        <q-list separator>
          <q-item
            v-for="i in c.itens"
            :key="`${i.codprodutovariacao}-${i.codestoquelocalrequisitada}`"
          >
            <q-item-section>
              <q-item-label>
                {{ i.produto }}<template v-if="i.variacao"> · {{ i.variacao }}</template>
              </q-item-label>
              <q-item-label caption>
                {{ formataNumeroInteligente(i.quantidade) }} {{ i.embalagem }}
                <template v-if="i.quantidade !== i.unidades">
                  ({{ formataNumeroInteligente(i.unidades) }} {{ i.sigla }})</template
                >
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn flat round size="sm" color="grey-7" icon="delete" @click="sGerador.remover(i)">
                <q-tooltip>Tirar do rascunho</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>
  </q-page>
</template>
