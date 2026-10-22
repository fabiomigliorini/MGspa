<script setup>
import { computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataCodigo, tempoRelativo } from '@components/formatters'
import { useConsultaAutomatica } from '@components/cobranca/useConsultaAutomatica'
import SelectEstoqueLocal from 'components/selects/SelectEstoqueLocal.vue'
import { requisicaoStore, STATUS } from 'stores/requisicao'
import { negocioStore } from 'stores/negocio'

// Painel de requisições (TASK-55, doc-6): o que a loja tem para atender ou o que ela pediu,
// por status, das mais antigas para as mais novas. Atualiza sozinho a cada 30 s.
const sRequisicao = requisicaoStore()
const sNegocio = negocioStore()
const { lado, codestoquelocal, finalizadas } = storeToRefs(sRequisicao)

const LADOS = [
  { value: 'atender', label: 'Para atender' },
  { value: 'requisitar', label: 'Que pedi' },
]

const ORDEM = ['A', 'S', 'P', 'T', 'D', 'E', 'C']

const grupos = computed(() =>
  ORDEM.map((status) => ({
    status,
    ...STATUS[status],
    itens: sRequisicao.lista.filter((r) => r.status === status),
  })).filter((g) => g.itens.length),
)

// a outra loja da linha: no "para atender", quem pediu; no "que pedi", quem atende
const outraLoja = (r) =>
  lado.value === 'atender'
    ? `pedida por ${r.requisitante}`
    : `atendida por ${r.requisitada || 'Compras'}`

const DESTINO = { R: 'cliente retira', E: 'entrega no endereço', L: 'reposição' }

const { iniciar } = useConsultaAutomatica({
  consultar: () => sRequisicao.carregarPainel(),
  pendente: () => true,
  intervalo: 30000,
})

watch([lado, codestoquelocal, finalizadas], () => sRequisicao.carregarPainel())

onMounted(() => {
  if (!codestoquelocal.value) codestoquelocal.value = sNegocio.padrao.codestoquelocal
  sRequisicao.carregarPainel()
  iniciar()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <div class="row q-col-gutter-md items-center">
            <div class="col-12 col-sm-5">
              <SelectEstoqueLocal v-model="codestoquelocal" label="Loja" outlined />
            </div>
            <div class="col-12 col-sm-4">
              <q-option-group v-model="lado" :options="LADOS" type="radio" inline />
            </div>
            <div class="col-12 col-sm-3">
              <q-toggle v-model="finalizadas" label="Entregues e canceladas" />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <MgEmptyState
        v-if="!sRequisicao.carregandoPainel && !grupos.length"
        icon="move_down"
        class="q-mb-md"
      >
        Nenhuma requisição {{ lado === 'atender' ? 'para atender' : 'pedida' }} nesta loja.
      </MgEmptyState>

      <q-card v-for="g in grupos" :key="g.status" flat bordered class="q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar :icon="g.icone" :color="g.cor" text-color="white" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1 text-weight-medium">{{ g.label }}</q-item-label>
            <q-item-label caption>
              {{ g.itens.length }} {{ g.itens.length === 1 ? 'requisição' : 'requisições' }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item
            v-for="r in g.itens"
            :key="r.codrequisicao"
            clickable
            :to="`/requisicao/${r.codrequisicao}`"
          >
            <q-item-section>
              <q-item-label class="ellipsis">
                #{{ formataCodigo(r.codrequisicao) }} · {{ r.siglarequisitante }} →
                {{ r.siglarequisitada || 'Compras' }}
                <template v-if="r.nome"> · {{ r.nome }}</template>
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ outraLoja(r) }} · {{ DESTINO[r.destino] }} · {{ r.itens }}
                {{ r.itens == 1 ? 'item' : 'itens' }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label caption>{{ tempoRelativo(r.criacao) }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" to="/requisicao/nova">
        <q-tooltip anchor="center left" self="center right">Nova requisição</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
