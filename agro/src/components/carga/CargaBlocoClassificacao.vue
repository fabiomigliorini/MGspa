<script setup>
import { ref, computed, inject } from 'vue'
import { calcularCarga } from 'src/utils/desconto'
import { etapasDaCarga, indiceEtapa, fmtNumero as fmt } from 'src/utils/carga'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  novo: { type: Boolean, default: false },
})

// Providos pelo CargaForm.vue.
const persistirBloco = inject('persistirBloco')
const calc = inject('calc')
const itensCarga = inject('itensCarga')
const avisoClassificacao = inject('avisoClassificacao')

// A classificação entra no líquido em qualquer sentido, então o que já foi
// preenchido continua à vista mesmo que o fluxo do sentido atual não passe
// pela etapa CLASSIFICACAO (troca de sentido depois de já ter lido).
const temLeitura = computed(() =>
  (props.carga.classificacao || []).some(
    (c) => c.leitura !== null && c.leitura !== undefined && c.leitura !== '',
  ),
)
const idxEtapa = computed(() => indiceEtapa(props.carga))
const ordem = computed(() => etapasDaCarga(props.carga))
const mostrarClassificacao = computed(
  () =>
    (props.carga.sentido === 'ENTRADA' &&
      idxEtapa.value >= ordem.value.indexOf('CLASSIFICACAO')) ||
    temLeitura.value,
)

function linhaDe(codparam) {
  return (
    (props.carga.classificacao || []).find((c) => c.codparametroclassificacao === codparam) || {}
  )
}
function descontoParam(codparam) {
  return (
    (calc.value.classificacao || []).find((c) => c.codparametroclassificacao === codparam)
      ?.desconto || null
  )
}
function hintItem(item) {
  const partes = [`Tol. ${fmt(item.tolerancia, 1)}%`]
  if (item.metodo === 'FATOR' && Number(item.fator)) {
    partes.push(`fator ${fmt(item.fator, 1)}`)
  } else if (Number(item.desagio)) {
    partes.push(`deságio ${fmt(item.desagio, 1)}%`)
  }
  return partes.join(' · ')
}
function foraTolerancia(item, leitura) {
  if (leitura === null || leitura === undefined || leitura === '') return false
  return Number(leitura) > (Number(item.tolerancia) || 0)
}

const dialogAberto = ref(false)
const edicao = ref({ classificacao: [] })

function abrir() {
  edicao.value = { classificacao: (props.carga.classificacao || []).map((c) => ({ ...c })) }
  dialogAberto.value = true
}
function linhaEdicao(codparam) {
  return edicao.value.classificacao.find((c) => c.codparametroclassificacao === codparam) || {}
}
// Prévia ao vivo com as leituras em edição (ainda não salvas).
const calcPreview = computed(() =>
  calcularCarga({ ...props.carga, classificacao: edicao.value.classificacao }, itensCarga.value),
)
function descontoPreview(codparam) {
  return (
    (calcPreview.value.classificacao || []).find((c) => c.codparametroclassificacao === codparam)
      ?.desconto || null
  )
}

async function salvar() {
  // Mescla só a leitura de volta na carga (desconto é derivado, não se grava).
  for (const c of edicao.value.classificacao) {
    const alvo = (props.carga.classificacao || []).find(
      (x) => x.codparametroclassificacao === c.codparametroclassificacao,
    )
    if (alvo) alvo.leitura = c.leitura
  }
  try {
    const ok = await persistirBloco()
    if (ok) dialogAberto.value = false
  } catch {
    // erro já notificado por quem persiste (CargaPage) — mantém o dialog aberto
  }
}
</script>

<template>
  <q-card v-if="mostrarClassificacao" flat bordered>
    <q-card-section>
      <div class="row items-center q-mb-sm">
        <div class="text-subtitle2 text-grey-8">Classificação</div>
        <q-space />
        <q-btn flat round dense icon="edit" size="sm" color="grey-7" @click="abrir" />
      </div>
      <q-banner v-if="avisoClassificacao" dense rounded class="bg-orange-1 text-orange-9 q-mb-sm">
        <template #avatar><q-icon name="warning" color="orange-8" /></template>
        {{ avisoClassificacao.titulo }}
        <div class="text-caption">{{ avisoClassificacao.dica }}</div>
      </q-banner>
      <div class="row q-col-gutter-md">
        <div
          v-for="item in itensCarga"
          :key="item.codparametroclassificacao"
          class="col-6 col-sm-4 col-md-3"
        >
          <div class="text-caption text-grey-6">
            {{ item.ordem }}. {{ item.parametroclassificacao }}
          </div>
          <div>
            <template v-if="linhaDe(item.codparametroclassificacao).leitura != null">
              {{ fmt(linhaDe(item.codparametroclassificacao).leitura, 1) }}%
            </template>
            <template v-else>—</template>
          </div>
          <div
            v-if="foraTolerancia(item, linhaDe(item.codparametroclassificacao).leitura)"
            class="text-caption text-orange-9"
          >
            acima da tolerância
          </div>
          <div v-if="descontoParam(item.codparametroclassificacao)" class="text-caption text-orange-8">
            − {{ fmt(descontoParam(item.codparametroclassificacao)) }} kg
          </div>
        </div>
      </div>
    </q-card-section>
  </q-card>

  <q-dialog v-model="dialogAberto">
    <q-card style="width: 700px; max-width: 90vw">
      <q-form @submit="salvar">
        <q-card-section>
          <div class="text-subtitle1 q-mb-md">Classificação</div>
          <q-banner v-if="avisoClassificacao" dense rounded class="bg-orange-1 text-orange-9 q-mb-sm">
            <template #avatar><q-icon name="warning" color="orange-8" /></template>
            {{ avisoClassificacao.titulo }}
            <div class="text-caption">{{ avisoClassificacao.dica }}</div>
          </q-banner>
          <div class="row q-col-gutter-md">
            <div
              v-for="item in itensCarga"
              :key="item.codparametroclassificacao"
              class="col-6 col-sm-4 col-md-3"
            >
              <MgInputValor
                v-model="linhaEdicao(item.codparametroclassificacao).leitura"
                :decimals="1"
                suffix="%"
                :label="`${item.ordem}. ${item.parametroclassificacao}`"
                :hint="hintItem(item)"
                lazy-rules
                :rules="[
                  (v) => v == null || (v >= 0 && v <= 100) || 'Leitura deve ficar entre 0 e 100%.',
                ]"
              />
              <div
                v-if="foraTolerancia(item, linhaEdicao(item.codparametroclassificacao).leitura)"
                class="text-caption text-orange-9 q-pl-sm"
              >
                acima da tolerância
              </div>
              <div
                v-if="descontoPreview(item.codparametroclassificacao)"
                class="text-caption text-orange-8 q-pl-sm"
              >
                − {{ fmt(descontoPreview(item.codparametroclassificacao)) }} kg
              </div>
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn label="Cancelar" flat color="grey-8" v-close-popup tabindex="-1" />
          <q-btn label="Salvar" type="submit" flat color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
