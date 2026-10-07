<script setup>
// Fechamentos (M9 doc-3): tudo que o caixa movimentou e ainda não foi conferido na filial —
// lotes de maquineta, cheques, vales, duplicatas, vendas com diferença e, para o
// financeiro, PIX a confirmar. O gerente abre um, digita às cegas e confirma. Pensada no celular.
import { ref, computed, onMounted, watch } from 'vue'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import { confissaoStore } from '@components/stores/confissaoStore'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgConfissaoScanner from '@components/MgConfissaoScanner.vue'

const store = useConferenciaStore()
confissaoStore().configurar({ fixos: {} })

const GRUPOS = [
  { tipo: 'lote', label: 'Maquinetas', icone: 'credit_card', cor: 'deep-orange-7' },
  { tipo: 'cheque', label: 'Cheques', icone: 'money', cor: 'teal-7' },
  { tipo: 'vale', label: 'Vales recebidos', icone: 'card_giftcard', cor: 'pink-6' },
  { tipo: 'duplicata', label: 'Duplicatas a prazo', icone: 'draw', cor: 'indigo-6' },
  { tipo: 'venda', label: 'Vendas com diferença', icone: 'report_problem', cor: 'red-6' },
  { tipo: 'pix', label: 'PIX e depósitos a confirmar', icone: 'pix', cor: 'cyan-8' },
]

const grupos = computed(() =>
  GRUPOS.map((g) => ({ ...g, itens: store.pendencias.filter((p) => p.tipo === g.tipo) })).filter(
    (g) => g.itens.length,
  ),
)

const destino = (p) => {
  switch (p.tipo) {
    case 'lote':
      return { name: 'fechamento-lote', params: { id: p.id } }
    case 'venda':
      return { name: 'fechamento-venda', params: { id: p.id } }
    case 'cheque':
    case 'vale':
      return { name: 'pagamento-detalhe', params: { id: p.id } }
    case 'pix':
      return { name: 'titulo-detalhe', params: { codtitulo: p.id } }
  }
  return undefined
}

// duplicata: escaneia a confissão assinada
const dialogScanner = ref(false)
const duplicata = ref(null)
const abrirScanner = (p) => {
  duplicata.value = p
  dialogScanner.value = true
}
const anexada = () => {
  dialogScanner.value = false
  store.buscarPendencias()
}

watch(() => store.codfilial, store.buscarPendencias)
onMounted(store.buscarPendencias)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div class="row q-col-gutter-md items-center q-mb-md">
        <div class="col-grow">
          <MgSelectFilial v-model="store.codfilial" label="Filial" clearable />
        </div>
        <div class="col-auto">
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="refresh"
            :loading="store.carregando"
            @click="store.buscarPendencias"
          >
            <q-tooltip>Atualizar</q-tooltip>
          </q-btn>
        </div>
      </div>

      <q-card v-for="g in grupos" :key="g.tipo" bordered flat class="q-mb-md">
        <q-card-section class="row items-center q-pb-sm">
          <q-icon :name="g.icone" :color="g.cor" size="sm" class="q-mr-sm" />
          <div class="text-subtitle1 col">{{ g.label }}</div>
          <q-badge :color="g.cor" :label="g.itens.length" />
        </q-card-section>
        <q-list separator>
          <q-item
            v-for="p in g.itens"
            :key="`${p.tipo}-${p.id}`"
            :to="destino(p)"
            :clickable="!!destino(p) || p.tipo === 'duplicata'"
            @click="p.tipo === 'duplicata' && abrirScanner(p)"
          >
            <q-item-section>
              <q-item-label class="ellipsis">{{ p.titulo }}</q-item-label>
              <q-item-label caption class="ellipsis">{{ p.subtitulo }}</q-item-label>
              <q-item-label v-if="!store.codfilial" caption>{{ p.filial }}</q-item-label>
            </q-item-section>
            <q-item-section v-if="['cheque', 'vale'].includes(p.tipo)" side>
              <q-btn
                flat
                round
                size="sm"
                color="green-7"
                icon="done"
                :loading="store.salvando"
                @click.prevent.stop="store.conferirPagamento(p.id)"
              >
                <q-tooltip>Está aqui, conferido</q-tooltip>
              </q-btn>
            </q-item-section>
            <q-item-section v-else-if="p.tipo === 'duplicata'" side>
              <q-icon name="photo_camera" color="grey-7" />
            </q-item-section>
            <q-item-section v-else side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <MgEmptyState v-if="!store.carregando && !grupos.length" icon="task_alt">
        Nada pendente de conferência.
      </MgEmptyState>
    </div>

    <q-dialog v-model="dialogScanner">
      <q-card flat style="width: 500px; max-width: 95vw">
        <q-card-section class="text-h6">
          Confissão de {{ duplicata?.titulo?.replace('Duplicata de ', '') }}
        </q-card-section>
        <q-card-section>
          <MgConfissaoScanner @anexada="anexada" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="grey-8" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
