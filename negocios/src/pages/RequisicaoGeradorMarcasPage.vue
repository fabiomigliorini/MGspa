<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataReal, removerAcentos } from '@components/formatters'
import GeradorRascunho from 'components/requisicao/GeradorRascunho.vue'
import GeradorFiltros from 'components/requisicao/GeradorFiltros.vue'
import { requisicaoGeradorStore } from 'stores/requisicaoGerador'

// Gerador de requisição (TASK-258): as marcas da loja, cada uma com "12 de 140 · R$ 3.450"
// (variações abaixo do mínimo, de quantas a loja trabalha · o furo). "Todas as marcas" pula a
// escolha. A pesquisa pelo nome e a ordem são aqui; urgência e ABC recarregam do servidor.
const route = useRoute()
const sGerador = requisicaoGeradorStore()

const codestoquelocal = computed(() => Number(route.params.codestoquelocal))
const busca = ref('')

const normaliza = (texto) => removerAcentos(texto || '').toLowerCase()

const marcas = computed(() => {
  const termo = normaliza(busca.value).trim()
  const lista = termo
    ? sGerador.marcas.filter((m) => normaliza(m.marca).includes(termo))
    : [...sGerador.marcas]
  if (sGerador.ordem === 'furo') {
    lista.sort(
      (a, b) =>
        b.furo - a.furo ||
        b.abaixo - a.abaixo ||
        String(a.marca).localeCompare(String(b.marca), 'pt-BR'),
    )
  }
  return lista
})

const legenda = (m) => `${m.abaixo} de ${m.trabalha} · ${formataReal(m.furo)}`

watch(
  () => [sGerador.abc, sGerador.situacao],
  () => sGerador.carregarMarcas(),
)

onMounted(async () => {
  await sGerador.carregarLojas()
  sGerador.carregarMarcas()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card flat bordered>
        <q-card-section class="row items-center q-pb-none">
          <div class="col">
            <div class="text-grey-9 text-overline">Gerador de requisição</div>
            <div class="text-h6 text-weight-regular">
              Reposição do {{ sGerador.nomeLoja(codestoquelocal) }}: qual marca?
            </div>
          </div>
          <GeradorRascunho />
        </q-card-section>
        <q-card-section>
          <GeradorFiltros v-model:busca="busca" rotulo-busca="Pesquisar marca" />
        </q-card-section>

        <q-list separator>
          <q-item clickable :to="`/requisicao/gerador/${codestoquelocal}/marca`">
            <q-item-section avatar>
              <q-avatar icon="select_all" color="primary" text-color="white" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Todas as marcas</q-item-label>
              <q-item-label caption>Os produtos de todas as marcas, com a pesquisa</q-item-label>
            </q-item-section>
            <q-item-section side><q-icon name="chevron_right" /></q-item-section>
          </q-item>
        </q-list>
        <q-separator />

        <!-- são mais de mil marcas: só as da tela ficam desenhadas -->
        <q-virtual-scroll v-slot="{ item: m }" :items="marcas" separator>
          <q-item
            :key="m.codmarca"
            clickable
            :to="`/requisicao/gerador/${codestoquelocal}/marca/${m.codmarca}`"
          >
            <q-item-section>
              <q-item-label class="ellipsis">{{ m.marca }}</q-item-label>
              <q-item-label caption :class="m.abaixo ? 'text-orange-9' : ''">
                {{ legenda(m) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side><q-icon name="chevron_right" /></q-item-section>
          </q-item>
        </q-virtual-scroll>

        <div v-if="sGerador.carregandoMarcas" class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
        <MgEmptyState v-else-if="!marcas.length" icon="sell" plain class="q-ma-md">
          Nenhuma marca nestes filtros.
        </MgEmptyState>
      </q-card>
    </div>
  </q-page>
</template>
