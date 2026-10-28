<script setup>
import { computed, onMounted, ref } from 'vue'
import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { db } from 'boot/db'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataCodigo, tempoRelativo } from '@components/formatters'
import { useConsultaAutomatica } from '@components/cobranca/useConsultaAutomatica'
import {
  requisicaoStore,
  STATUS,
  DESTINO,
  PRIORIDADE,
  lojaDaRequisicao,
  trajeto,
  legenda,
} from 'stores/requisicao'
import { negocioStore } from 'stores/negocio'

// Painel de requisições (TASK-55, doc-6): uma aba por loja; nela, os cards Atender, Separados e
// Pedidos, cada um por grupo, da mais urgente para a menos e, na mesma prioridade, das mais
// antigas para as mais novas (a ordem vem do backend). Só as abertas: entregues e canceladas
// ficam na listagem. Atualiza sozinho a cada 30 s. A loja da aba vem do endereço (/requisicao/painel/101001); sem ela, a do PDV.
const route = useRoute()
const router = useRouter()
const sRequisicao = requisicaoStore()
const sNegocio = negocioStore()
const { codestoquelocal } = storeToRefs(sRequisicao)

// as lojas das abas: ativas e que controlam estoque (controlaestoque ausente = PDV ainda não
// sincronizou o campo; mostra como antes)
const lojas = ref([])

// os grupos são os nomes da situação, os mesmos em todo lugar (STATUS): P está em separação
// como o S, e D está separado como o T. Separada (T ou D) fica nas duas lojas até sair: quem
// separou acompanha no card Separados; na entrega no endereço, depois que o entregador de quem
// pediu retira (R), o painel só manda a requisição para quem pediu
const separada = (r) => ['T', 'D'].includes(r.status)
const GRUPOS = ['A', 'S', 'T', 'R'].map((chave) => ({ ...STATUS[chave], chave }))
const grupoDe = (r) => ({ P: 'S', D: 'T' })[r.status] || r.status

const agrupar = (requisicoes) =>
  GRUPOS.map((g) => ({ ...g, itens: requisicoes.filter((r) => grupoDe(r) === g.chave) })).filter(
    (g) => g.itens.length,
  )

// três cards: o que a loja tem para separar, o que ela separou e falta entregar, e o que ela
// pediu (de qualquer loja, ou Compras: a linha diz para quem)
const cards = computed(() => {
  const loja = codestoquelocal.value
  const nome = lojas.value.find((l) => l.codestoquelocal === loja)?.estoquelocal
  const minhas = sRequisicao.lista.filter((r) => r.codestoquelocalrequisitada === loja)
  const pedidas = sRequisicao.lista.filter((r) => r.codestoquelocalrequisitada !== loja)
  return [
    {
      chave: 'atender',
      titulo: 'Atender',
      icone: 'move_to_inbox',
      grupos: agrupar(minhas.filter((r) => !separada(r))),
    },
    {
      chave: 'separados',
      titulo: 'Separados',
      icone: 'local_shipping',
      grupos: agrupar(minhas.filter(separada)),
      // o grupo é um só (Separado) e o título do card já diz: sem subtítulo
      semSubtitulo: true,
    },
    {
      chave: 'pedidos',
      titulo: `${nome} pediu`,
      icone: 'outbox',
      grupos: agrupar(pedidas),
    },
  ]
    .map((c) => ({ ...c, total: c.grupos.reduce((n, g) => n + g.itens.length, 0) }))
    .filter((c) => c.total)
})

const quantas = (n) => `${n} ${n === 1 ? 'requisição' : 'requisições'}`

const { iniciar } = useConsultaAutomatica({
  consultar: () => sRequisicao.carregarPainel(),
  pendente: () => true,
  intervalo: 30000,
})

const existe = (cod) => lojas.value.some((l) => l.codestoquelocal === cod)

// Abre a loja do endereço. Sem ela, ou com uma loja que não está nas abas, devolve o endereço
// da do PDV (ou da primeira aba) para onde redirecionar.
function abrir(param) {
  const cod = Number(param)
  if (!existe(cod)) {
    const padrao = existe(sNegocio.padrao.codestoquelocal)
      ? sNegocio.padrao.codestoquelocal
      : lojas.value[0]?.codestoquelocal
    return padrao ? `/requisicao/painel/${padrao}` : true
  }
  // trocar de aba limpa a lista: a da loja anterior não aparece pela metade nos cards da nova
  if (cod !== codestoquelocal.value) sRequisicao.lista = []
  codestoquelocal.value = cod
  sRequisicao.carregarPainel()
  return true
}

onBeforeRouteUpdate((to) => abrir(to.params.codestoquelocal))

onMounted(async () => {
  lojas.value = await db.estoqueLocal
    .orderBy('[codfilial+sigla]')
    .filter((l) => l.inativo == null && lojaDaRequisicao(l))
    .toArray()
  const destino = abrir(route.params.codestoquelocal)
  if (destino !== true) router.replace(destino)
  iniciar()
})
</script>

<template>
  <q-page>
    <q-tabs inline-label outside-arrows mobile-arrows class="bg-primary text-white">
      <q-route-tab
        v-for="l in lojas"
        :key="l.codestoquelocal"
        :to="`/requisicao/painel/${l.codestoquelocal}`"
        :label="l.estoquelocal"
      />
    </q-tabs>

    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div class="row justify-end q-mb-sm">
        <q-btn flat color="primary" icon="auto_awesome" label="Gerador" to="/requisicao/gerador" />
        <q-btn flat color="primary" icon="list" label="Todas" to="/requisicao" />
      </div>

      <MgEmptyState
        v-if="!sRequisicao.carregandoPainel && !cards.length"
        icon="move_down"
        class="q-mb-md"
      >
        Nenhuma requisição nesta loja.
      </MgEmptyState>

      <q-card v-for="c in cards" :key="c.chave" flat bordered class="q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar :icon="c.icone" color="primary" text-color="white" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1 text-weight-medium">{{ c.titulo }}</q-item-label>
            <q-item-label caption>{{ quantas(c.total) }}</q-item-label>
          </q-item-section>
        </q-item>

        <template v-for="g in c.grupos" :key="g.chave">
          <q-separator />
          <q-item-label
            v-if="!c.semSubtitulo"
            header
            class="row items-center text-weight-medium"
            :class="`text-${g.cor}`"
          >
            <q-icon :name="g.icone" size="xs" class="q-mr-sm" />
            {{ g.label }} · {{ g.itens.length }}
          </q-item-label>
          <q-list separator>
            <q-item
              v-for="r in g.itens"
              :key="r.codrequisicao"
              clickable
              :to="`/requisicao/${r.codrequisicao}`"
            >
              <q-item-section avatar>
                <q-avatar
                  :icon="DESTINO[r.destino].icone"
                  :color="DESTINO[r.destino].cor"
                  text-color="white"
                />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  {{ formataCodigo(r.codrequisicao) }} · {{ trajeto(r) }}
                </q-item-label>
                <q-item-label caption class="ellipsis">{{ legenda(r) }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-chip
                  size="sm"
                  :color="PRIORIDADE[r.prioridade]?.cor"
                  text-color="white"
                  :label="PRIORIDADE[r.prioridade]?.label"
                />
              </q-item-section>
              <q-item-section side>
                <q-item-label caption>{{ tempoRelativo(r.criacao) }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" />
              </q-item-section>
            </q-item>
          </q-list>
        </template>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" to="/requisicao/nova">
        <q-tooltip anchor="center left" self="center right">Nova requisição</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
