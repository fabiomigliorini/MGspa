<script setup>
// O item do caixa e quanto tem dele em cada caixa que já mexeu com ele (a contagem final do último
// período fechado de cada um mais as entradas e saídas depois dele, como o saldo do dinheiro; com
// o total): só os com saldo, ou também os zerados. Clicar no caixa abre os períodos dele em que o
// item mexeu, com as entradas e saídas. Embaixo, os tipos (descrição + preço) já lançados, cada um
// com o editar da descrição, que muda em tudo. Criação, editar, inativar e excluir no cabeçalho. A
// maquineta de parceiro não tem saldo nos caixas: mostra a conta corrente (o que devemos ao
// parceiro).
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import CaixaItemDialog from 'components/caixaItem/CaixaItemDialog.vue'
import CaixaItemFechamentosDialog from 'components/caixaItem/CaixaItemFechamentosDialog.vue'
import CaixaItemTipoDialog from 'components/caixaItem/CaixaItemTipoDialog.vue'
import CaixaItemContaCorrente from 'components/caixaItem/CaixaItemContaCorrente.vue'
import CaixaItemTituloDialog from 'components/caixaItem/CaixaItemTituloDialog.vue'
import CaixaItemAjusteDialog from 'components/caixaItem/CaixaItemAjusteDialog.vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const store = useCaixaItemStore()
const { item, saldos, tipos, conta, loading } = storeToRefs(store)

const codcaixaitem = computed(() => Number(route.params.codcaixaitem))
const maquineta = computed(() => item.value?.modo === 'M')
const semSaldo = ref(false)
const caixas = computed(() =>
  semSaldo.value ? saldos.value : saldos.value.filter((s) => s.quantidade > 0),
)
const totalSaldos = computed(() => ({
  quantidade: caixas.value.reduce((t, s) => t + s.quantidade, 0),
  total: caixas.value.reduce((t, s) => t + s.total, 0),
}))

const excluir = () => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o item "${item.value.item}"?`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    if (await store.excluir(item.value)) router.push({ name: 'caixa-item' })
  })
}

onMounted(() => store.carregar(codcaixaitem.value))
watch(codcaixaitem, (cod) => cod && store.carregar(cod))
</script>

<template>
  <q-page>
    <div v-if="item" class="q-pa-md" style="max-width: 1086px; margin: auto">
      <!-- voltar, nome e ações, como no portador; a maquineta mostra o saldo a pagar ao parceiro.
           No celular o saldo e as ações descem para a linha de baixo -->
      <div class="row items-center q-col-gutter-x-sm q-mb-sm">
        <div class="col-auto">
          <q-btn flat round icon="arrow_back" color="grey-7" :to="{ name: 'caixa-item' }" />
        </div>
        <div class="col" style="min-width: 0">
          <div class="text-h5 text-grey-9 ellipsis">{{ item.item }}</div>
          <div v-if="maquineta" class="text-caption text-grey-7 ellipsis">
            Maquineta de {{ item.pessoa }} · {{ item.filial }} · {{ item.contacontabil }}
          </div>
          <div v-if="item.inativo" class="text-caption text-grey-7">
            Inativo desde {{ formataTimestamp(item.inativo) }}
          </div>
        </div>
        <div class="col-12 col-sm-auto">
          <div class="row items-center no-wrap">
            <div
              v-if="maquineta && conta"
              class="text-h6 q-mr-sm"
              :class="conta.saldo < 0 ? 'text-red-8' : 'text-grey-9'"
            >
              R$ {{ formataNumero(conta.saldo) }}
              <q-tooltip>Saldo a pagar ao parceiro</q-tooltip>
            </div>
            <q-space />
            <MgInfoCriacao :registro="item" />
            <q-btn flat round size="sm" color="grey-7" icon="edit" @click="store.abrirEditar(item)">
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              :icon="item.inativo ? 'play_arrow' : 'pause'"
              @click="store.alternarInativo(item)"
            >
              <q-tooltip>{{ item.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluir">
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </div>
        </div>
      </div>

      <CaixaItemContaCorrente v-if="maquineta" class="q-mb-md" />

      <q-card v-else-if="saldos.length" flat bordered class="q-mb-md">
        <q-list separator>
          <q-item>
            <q-item-section>
              <q-item-label class="text-grey-7">Saldo nos caixas</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-toggle v-model="semSaldo" label="Mostrar caixas sem saldo" left-label />
            </q-item-section>
          </q-item>
          <q-item
            v-for="s in caixas"
            :key="s.codportador"
            clickable
            @click="store.abrirFechamentos(s)"
          >
            <q-item-section>
              <q-item-label>{{ s.portador }}</q-item-label>
              <q-item-label caption>{{ s.detalhe || 'Nenhum' }}</q-item-label>
              <q-item-label caption>
                <template v-if="s.contado">
                  Contado no fechamento de {{ formataTimestamp(s.fim) }}
                  <template v-if="s.entradas"> + entradas e saídas depois</template>
                </template>
                <template v-else-if="s.fim && s.entradas">
                  Entradas e saídas depois do fechamento de {{ formataTimestamp(s.fim) }}
                </template>
                <template v-else-if="s.entradas">
                  Entradas e saídas desde a abertura, ainda sem fechamento
                </template>
                <template v-else-if="s.fim">
                  Zerado no fechamento de {{ formataTimestamp(s.fim) }}
                </template>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label>{{ formataNumero(s.total) }}</q-item-label>
              <q-item-label caption>{{ s.quantidade }} un.</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="!caixas.length">
            <q-item-section class="text-grey-7">Nenhum caixa com saldo.</q-item-section>
          </q-item>
          <q-item v-else>
            <q-item-section>
              <q-item-label class="text-weight-bold">Total</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-weight-bold">
                {{ formataNumero(totalSaldos.total) }}
              </q-item-label>
              <q-item-label caption>{{ totalSaldos.quantidade }} un.</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
      <MgEmptyState v-else-if="!loading" icon="inventory_2" class="q-mb-md">
        Nenhum caixa mexeu com este item.
      </MgEmptyState>

      <q-card v-if="tipos.length" flat bordered class="q-mb-md">
        <q-list separator>
          <q-item>
            <q-item-section>
              <q-item-label class="text-grey-7">Descrições</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-for="t in tipos" :key="`${t.preco}|${t.descricao}`">
            <q-item-section>
              <q-item-label :class="t.descricao ? '' : 'text-grey-6'">
                {{ t.descricao || '(sem descrição)' }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label>{{ formataNumero(t.preco) }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn flat round size="sm" color="grey-7" icon="edit" @click="store.abrirTipo(t)">
                <q-tooltip>Editar descrição</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <q-inner-loading :showing="loading" color="primary" />
    <CaixaItemDialog />
    <CaixaItemFechamentosDialog />
    <CaixaItemTipoDialog />
    <CaixaItemTituloDialog />
    <CaixaItemAjusteDialog />
  </q-page>
</template>
