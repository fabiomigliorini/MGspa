<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import {
  formataCodigo,
  formataNumeroInteligente,
  formataTimestamp,
  formataTelefone,
} from '@components/formatters'
import { requisicaoStore, STATUS } from 'stores/requisicao'

// Detalhe da requisição (TASK-55, doc-6). O código de retirada só aparece no dispositivo
// da loja que pediu; o backend nem manda para os outros.
const $q = useQuasar()
const route = useRoute()
const sRequisicao = requisicaoStore()
const { requisicao: r } = storeToRefs(sRequisicao)

const status = computed(() => STATUS[r.value?.status] || STATUS.A)
const itensPedidos = computed(() => (r.value?.itens || []).filter((i) => i.quantidade > 0))

function cancelar() {
  $q.dialog({
    title: 'Cancelar requisição',
    message: `Cancelar a requisição #${formataCodigo(r.value.codrequisicao)}?`,
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar requisição', color: 'red-5', flat: true },
  }).onOk(() => sRequisicao.cancelar(r.value))
}

watch(
  () => route.params.codrequisicao,
  (cod) => cod && sRequisicao.carregar(cod),
  { immediate: true },
)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div v-if="sRequisicao.carregando && !r" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <template v-else-if="r">
        <!-- CABEÇALHO -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center no-wrap">
            <q-avatar :icon="status.icone" :color="status.cor" text-color="white" class="q-mr-md" />
            <div class="col">
              <div class="text-h5 ellipsis">Requisição #{{ formataCodigo(r.codrequisicao) }}</div>
              <div class="text-caption text-grey-7">
                {{ r.requisitante }} pede para {{ r.requisitada || 'Compras' }} ·
                {{ r.destinodescricao }}
              </div>
            </div>
            <div class="row items-center no-wrap">
              <MgInfoCriacao :registro="r" />
              <q-btn
                v-if="r.podeeditar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                :to="`/requisicao/${r.codrequisicao}/editar`"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.podecancelar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="block"
                @click="cancelar"
              >
                <q-tooltip>Cancelar requisição</q-tooltip>
              </q-btn>
            </div>
          </q-card-section>
          <q-separator />
          <q-list>
            <q-item>
              <q-item-section avatar>
                <q-icon :name="status.icone" :color="status.cor" />
              </q-item-section>
              <q-item-section>
                <q-item-label caption>Situação</q-item-label>
                <q-item-label>{{ status.label }}</q-item-label>
                <q-item-label v-if="r.cancelamento" caption>
                  Cancelada em {{ formataTimestamp(r.cancelamento) }} por
                  {{ r.usuariocancelamento }}
                </q-item-label>
                <q-item-label v-if="r.finalizacao" caption>
                  Finalizada em {{ formataTimestamp(r.finalizacao) }} por {{ r.usuariofinalizacao }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="r.codigoretirada">
              <q-item-section avatar><q-icon name="key" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Código de retirada (passe só para o cliente)</q-item-label>
                <q-item-label class="text-h5 text-weight-bold text-primary">
                  {{ r.codigoretirada }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="r.nome || r.pessoa">
              <q-item-section avatar><q-icon name="person" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Cliente</q-item-label>
                <q-item-label>{{ r.nome || r.pessoa }}</q-item-label>
                <q-item-label v-if="r.telefone" caption>{{
                  formataTelefone(r.telefone)
                }}</q-item-label>
                <q-item-label v-if="r.endereco" caption>{{ r.endereco }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="r.codnegocio" clickable :to="`/negocio/${r.codnegocio}`">
              <q-item-section avatar><q-icon name="shopping_cart" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Venda de origem</q-item-label>
                <q-item-label>#{{ formataCodigo(r.codnegocio) }}</q-item-label>
              </q-item-section>
              <q-item-section side><q-icon name="chevron_right" /></q-item-section>
            </q-item>
            <q-item v-if="r.observacoes">
              <q-item-section avatar><q-icon name="notes" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Observações</q-item-label>
                <q-item-label style="white-space: pre-line">{{ r.observacoes }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- ITENS -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline q-pb-none"
            >Produtos pedidos</q-card-section
          >
          <q-list separator>
            <q-item v-for="i in itensPedidos" :key="i.codrequisicaoitem">
              <q-item-section avatar>
                <q-avatar rounded size="48px">
                  <img v-if="i.produto.imagem" :src="i.produto.imagem" />
                  <q-icon v-else name="inventory_2" color="grey-5" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ i.produto.descricao }}</q-item-label>
                <q-item-label caption>{{ i.produto.barras }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label class="text-subtitle1">
                  {{ formataNumeroInteligente(i.quantidade) }} {{ i.produto.sigla }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>
  </q-page>
</template>
