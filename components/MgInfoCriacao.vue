<script>
import { reactive } from 'vue'
import { api } from 'src/services/api'

// Cache singleton em memória (módulo carregado 1x): codusuario -> { nome, imagem } | null.
// Compartilhado entre todas as instâncias de MgInfoCriacao (recarrega sob demanda).
const cache = reactive({})
// Requisições em voo, para deduplicar (N linhas com o mesmo codusuario = 1 request).
const inflight = {}

function usuarioCache(cod) {
  if (!cod) return null
  return cache[cod] ?? null
}

async function carregarUsuario(cod) {
  if (!cod || cod in cache) return
  if (inflight[cod]) return inflight[cod]
  inflight[cod] = api
    .get(`v1/usuario/${cod}/autor`)
    .then(({ data }) => {
      cache[cod] = data
        ? { nome: data.usuario ?? null, pessoa: data.pessoa ?? null, imagem: data.imagem ?? null }
        : null
    })
    .catch(() => {
      cache[cod] = null
    })
    .finally(() => {
      delete inflight[cod]
    })
  return inflight[cod]
}
</script>

<script setup>
import { computed, watch } from 'vue'
import { formataTimestamp } from '@components/formatters'

const props = defineProps({
  registro: { type: Object, default: null },
})

const criacao = computed(() => props.registro?.criacao ?? null)
const alteracao = computed(() => props.registro?.alteracao ?? null)
const codCriacao = computed(() => props.registro?.codusuariocriacao ?? null)
const codAlteracao = computed(() => props.registro?.codusuarioalteracao ?? null)

const usuarioCriacao = computed(() => usuarioCache(codCriacao.value))
const usuarioAlteracao = computed(() => usuarioCache(codAlteracao.value))

watch(
  [codCriacao, codAlteracao],
  ([cod, codAlt]) => {
    carregarUsuario(cod)
    carregarUsuario(codAlt)
  },
  { immediate: true },
)
</script>

<template>
  <q-btn flat round dense icon="info" size="sm" color="grey-7">
    <q-popup-proxy :breakpoint="600">
      <q-card>
        <q-list separator>
          <q-item v-if="criacao">
            <q-item-section avatar>
              <q-avatar color="grey-5" text-color="white" icon="person">
                <img v-if="usuarioCriacao?.imagem" :src="usuarioCriacao.imagem" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label overline>Criado por</q-item-label>
              <q-item-label class="text-weight-bolder text-grey-9" v-if="usuarioCriacao?.nome">{{
                usuarioCriacao.nome
              }}</q-item-label>
              <q-item-label v-if="usuarioCriacao?.pessoa" caption>{{
                usuarioCriacao.pessoa
              }}</q-item-label>
              <q-item-label caption>{{ formataTimestamp(criacao) }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="alteracao">
            <q-item-section avatar>
              <q-avatar color="grey-5" text-color="white" icon="person">
                <img v-if="usuarioAlteracao?.imagem" :src="usuarioAlteracao.imagem" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label overline>Alterado por</q-item-label>
              <q-item-label class="text-weight-bolder text-grey-9" v-if="usuarioAlteracao?.nome">{{
                usuarioAlteracao.nome
              }}</q-item-label>
              <q-item-label v-if="usuarioAlteracao?.pessoa" caption>{{
                usuarioAlteracao.pessoa
              }}</q-item-label>
              <q-item-label caption>{{ formataTimestamp(alteracao) }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </q-popup-proxy>
  </q-btn>
</template>
