<script setup>
// Painel dos portadores (doc-4): os portadores em que o usuário é operador ou gestor, por filial,
// o saldo da espécie (banco, adquirente e cartão sem saldo até a conciliação), a situação do
// período da espécie, os pendentes e as transferências a confirmar. A linha abre o portador e o
// período. Cadastro e OFX: Financeiro e Admin.
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import PortadorDialog from 'components/portador/PortadorDialog.vue'
import OfxDialog from 'components/portador/OfxDialog.vue'
import { usePortadorStore } from 'src/stores/portadorStore'
import { useAuth } from 'src/composables/useAuth'
import { portadorTipoColor } from 'src/constants/portadorTipo'

const store = usePortadorStore()
const { filiais, carregando } = storeToRefs(store)
const financeiro = useAuth().temPermissao('Financeiro')

const ICONE = {
  E: 'savings',
  B: 'account_balance',
  A: 'contactless',
  C: 'credit_card',
  O: 'wallet',
}

const aberta = (p) => p.ehCaixa && !!p.sessao?.aberta

const situacao = (p) => {
  if (!p.ehCaixa) return null
  const s = p.sessao
  if (!s) return 'Nunca aberto'
  if (s.situacao === 'aberto') {
    return (
      `Aberto desde ${formataTimestamp(s.inicio, 0)}` +
      (s.usuarioabertura ? ` por ${s.usuarioabertura}` : '')
    )
  }
  if (s.situacao === 'pendente') return `Pendente desde ${formataTimestamp(s.fim, 0)}`
  return (
    `Fechado em ${formataTimestamp(s.fim, 0)}` +
    (s.usuariofechamento ? ` por ${s.usuariofechamento}` : '')
  )
}

onMounted(() => store.buscarPainel())
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div v-if="financeiro" class="row justify-end q-mb-sm">
        <q-btn
          flat
          no-caps
          color="primary"
          icon="upload_file"
          label="Importar OFX"
          @click="store.dialogOfx = true"
        >
          <q-tooltip>Extrato do banco (arquivo OFX)</q-tooltip>
        </q-btn>
      </div>

      <q-card v-for="f in filiais" :key="f.codfilial ?? 0" flat bordered class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1 text-weight-medium">{{ f.filial }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label caption>espécie</q-item-label>
            <q-item-label class="text-weight-bold" :class="f.total < 0 ? 'text-red-8' : ''">
              R$ {{ formataNumero(f.total) }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-list v-for="t in f.tipos" :key="t.tipo" separator>
          <q-separator />
          <q-item-label
            header
            class="q-py-sm text-weight-medium"
            :class="`text-${portadorTipoColor(t.tipo)}`"
          >
            {{ t.label }}
          </q-item-label>
          <q-item
            v-for="p in t.portadores"
            :key="p.codportador"
            clickable
            :to="{ name: 'portador-detalhe', params: { codportador: p.codportador } }"
          >
            <q-item-section avatar>
              <q-icon
                :name="p.ehGaveta ? 'point_of_sale' : ICONE[p.tipo]"
                :color="portadorTipoColor(p.tipo)"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label :class="p.inativo ? 'text-strike text-grey-6' : ''">
                {{ p.portador }}
                <q-badge v-if="aberta(p)" color="green-7" class="q-ml-sm" label="Aberto" />
              </q-item-label>
              <q-item-label v-if="situacao(p)" caption>{{ situacao(p) }}</q-item-label>
              <q-item-label
                v-if="p.pendentes || p.chegando.quantidade || p.saindo.quantidade"
                caption
              >
                <q-badge
                  v-if="p.pendentes"
                  color="amber-10"
                  class="q-mr-xs"
                  :label="`${p.pendentes} período(s) pendente(s)`"
                />
                <q-badge
                  v-if="p.chegando.quantidade"
                  color="amber-8"
                  class="q-mr-xs"
                  :label="`${p.chegando.quantidade} chegando a confirmar · R$ ${formataNumero(p.chegando.valor)}`"
                />
                <q-badge
                  v-if="p.saindo.quantidade"
                  color="amber-8"
                  :label="`${p.saindo.quantidade} saindo a confirmar · R$ ${formataNumero(p.saindo.valor)}`"
                />
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label
                v-if="p.saldo !== null"
                class="text-weight-bold"
                :class="p.saldo < 0 ? 'text-red-8' : 'text-grey-9'"
              >
                R$ {{ formataNumero(p.saldo) }}
              </q-item-label>
              <q-item-label v-else class="text-grey-5">—</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-5" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <MgEmptyState v-if="!carregando && !filiais.length" icon="account_balance_wallet">
        Nenhum portador nesta filial.
      </MgEmptyState>
    </div>

    <q-inner-loading :showing="carregando" color="primary" />

    <q-page-sticky v-if="financeiro" position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="store.novo()">
        <q-tooltip anchor="center left" self="center right">Novo portador</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <PortadorDialog />
    <OfxDialog />
  </q-page>
</template>
