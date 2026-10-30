<script setup>
import { computed, onMounted, ref } from 'vue'
import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { db } from 'boot/db'
import MgEmptyState from '@components/MgEmptyState.vue'
import { tempoRelativo } from '@components/formatters'
import { useConsultaAutomatica } from '@components/cobranca/useConsultaAutomatica'
import { requisicaoStore, lojaDaRequisicao, STATUS } from 'stores/requisicao'
import { negocioStore } from 'stores/negocio'
import RequisicaoLinha from 'components/requisicao/RequisicaoLinha.vue'

// Painel de requisições (TASK-55, doc-6): uma aba por loja; nela, os cards Atender, Separados,
// Pedidos e Saiu para entrega, cada um por situação e, na mesma situação, da mais urgente para a
// menos e das mais antigas para as mais novas (essa ordem vem do backend). Só as abertas:
// entregues e canceladas ficam na listagem. Atualiza sozinho a cada 30 s. A loja da aba vem do
// endereço (/requisicao/painel/101001); sem ela, a do PDV.
const route = useRoute()
const router = useRouter()
const sRequisicao = requisicaoStore()
const sNegocio = negocioStore()
const { codestoquelocal } = storeToRefs(sRequisicao)

// as lojas das abas: ativas e que controlam estoque (controlaestoque ausente = PDV ainda não
// sincronizou o campo; mostra como antes)
const lojas = ref([])

// a situação é coluna da linha (TASK-261), e o card vem em blocos dela: A separar, Em separação,
// Separado, Saiu para entrega. P está em separação como o S, e D está separado como o T; o sort é
// estável, então dentro da situação fica a ordem do backend. Quem separou acompanha nas duas
// lojas até a entrega: o separado (T ou D) no card Separados e, na entrega no endereço e na
// reposição, o que já saiu (R) no Saiu para entrega, até quem pediu dar o Recebido ou entregar
// ao cliente (TASK-261)
const separada = (r) => ['T', 'D'].includes(r.status)
const ORDEM = ['A', 'S', 'T', 'R']
const ordemDe = (r) => ORDEM.indexOf({ P: 'S', D: 'T' }[r.status] || r.status)
const ordenar = (lista) => [...lista].sort((a, b) => ordemDe(a) - ordemDe(b))

// quatro cards: o que a loja tem para separar, o que ela separou e não saiu, o que ela pediu
// (de qualquer loja, ou Compras: a linha diz para quem) e, por último, o que ela separou e já
// saiu para entrega
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
      itens: ordenar(minhas.filter((r) => !separada(r) && r.status !== 'R')),
    },
    {
      chave: 'separados',
      titulo: 'Separados',
      icone: 'local_shipping',
      itens: ordenar(minhas.filter(separada)),
    },
    {
      chave: 'pedidos',
      titulo: `${nome} pediu`,
      icone: 'outbox',
      itens: ordenar(pedidas),
    },
    {
      chave: 'saiu',
      titulo: STATUS.R.label,
      icone: STATUS.R.icone,
      itens: ordenar(minhas.filter((r) => r.status === 'R')),
    },
  ].filter((c) => c.itens.length)
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

      <!-- uma lista por card: o título do card e a linha têm o ícone na mesma coluna e o texto
           no mesmo recuo (TASK-261) -->
      <q-card v-for="c in cards" :key="c.chave" flat bordered class="q-mb-md">
        <q-list separator>
          <q-item>
            <q-item-section avatar>
              <q-icon :name="c.icone" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-subtitle1 text-weight-medium">{{ c.titulo }}</q-item-label>
              <q-item-label caption>{{ quantas(c.itens.length) }}</q-item-label>
            </q-item-section>
          </q-item>

          <RequisicaoLinha
            v-for="r in c.itens"
            :key="r.codrequisicao"
            :requisicao="r"
            :quando="tempoRelativo(r.criacao)"
          />
        </q-list>
      </q-card>

      <!-- espaço para o botão + não cobrir a última requisição -->
      <div class="q-py-xl" />
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" to="/requisicao/nova">
        <q-tooltip anchor="center left" self="center right">Nova requisição</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
