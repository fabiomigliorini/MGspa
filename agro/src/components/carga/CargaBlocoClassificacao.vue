<script setup>
import { ref, computed, inject } from 'vue'
import { calcularCarga } from 'src/utils/desconto'
import { ETAPA_META, etapasDaCarga, indiceEtapa, fmtNumero as fmt } from 'src/utils/carga'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  novo: { type: Boolean, default: false },
})

// Providos pelo CargaForm.vue.
const persistirBloco = inject('persistirBloco')
const concluirEtapa = inject('concluirEtapa')
const proximoPasso = inject('proximoPasso')
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
// Na expedição a classificação é OPCIONAL: não é etapa do fluxo, o card fica à
// vista com o "+" e as leituras não são obrigatórias (o dialog abre sem etapa).
// Cultura sem parâmetro não tem o que classificar — o card some em vez de
// mostrar o aviso laranja em toda expedição.
const opcional = computed(() => props.carga.sentido === 'SAIDA')
const mostrarClassificacao = computed(
  () =>
    (props.carga.sentido === 'ENTRADA' && idxEtapa.value >= ordem.value.indexOf('CLASSIFICACAO')) ||
    (opcional.value && itensCarga.value.length > 0) ||
    temLeitura.value,
)

function linhaDe(codparam) {
  return (
    (props.carga.classificacao || []).find((c) => c.codparametroclassificacao === codparam) || {}
  )
}
// null = ainda sem kg (falta a tara); 0 = leitura dentro da tolerância.
function descontoParam(codparam) {
  return (
    (calc.value.classificacao || []).find((c) => c.codparametroclassificacao === codparam)
      ?.desconto ?? null
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

// Card: só o que o grão tem — leitura vazia ou 0 não aparece.
const leituras = computed(() =>
  itensCarga.value
    .map((item) => ({ item, leitura: linhaDe(item.codparametroclassificacao).leitura }))
    .filter((l) => Number(l.leitura) > 0),
)

// Detalhes: todo parâmetro lido (0 inclusive) com o desconto em kg do calc.
const detalhesAberto = ref(false)
const detalhes = computed(() =>
  itensCarga.value
    .map((item) => ({
      item,
      leitura: linhaDe(item.codparametroclassificacao).leitura,
      desconto: descontoParam(item.codparametroclassificacao),
    }))
    .filter((d) => !leituraVazia(d.leitura)),
)

const dialogAberto = ref(false)
const formRef = ref(null)
const edicao = ref({ classificacao: [] })
// Aberto pelo botão "Classificar": toda leitura é obrigatória (0 quando o grão
// não tem aquele defeito) e confirmar AVANÇA a carga (concluirEtapa). Pelo lápis
// segue livre, como correção.
const etapaAtual = ref(null)
const rotuloConfirmar = ref('Salvar')

function abrir({ etapa = null, rotulo = null } = {}) {
  // F3 com o dialog já aberto não pode reabrir e apagar o que foi digitado.
  if (dialogAberto.value) return
  edicao.value = { classificacao: (props.carga.classificacao || []).map((c) => ({ ...c })) }
  etapaAtual.value = etapa
  rotuloConfirmar.value = rotulo || 'Salvar'
  dialogAberto.value = true
}
defineExpose({ abrir })

function leituraVazia(v) {
  return v === null || v === undefined || v === ''
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
    const ok = etapaAtual.value ? await concluirEtapa() : await persistirBloco()
    if (ok) {
      dialogAberto.value = false
      proximoPasso('classificacao')
    }
  } catch {
    // erro já notificado por quem persiste (CargaPage) — mantém o dialog aberto
  }
}
</script>

<template>
  <q-card v-if="mostrarClassificacao" flat bordered>
    <q-card-section>
      <div class="row items-center q-mb-sm">
        <q-icon
          :name="ETAPA_META.CLASSIFICACAO.icon"
          :color="ETAPA_META.CLASSIFICACAO.color"
          size="20px"
          class="q-mr-sm"
        />
        <div class="text-subtitle1 text-grey-8">Classificação</div>
        <q-space />
        <q-btn
          v-if="temLeitura"
          flat
          round
          dense
          size="sm"
          icon="info"
          color="grey-7"
          @click="detalhesAberto = true"
        >
          <q-tooltip>Detalhes</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          size="sm"
          :icon="temLeitura ? 'edit' : 'add'"
          :color="temLeitura ? 'grey-7' : 'primary'"
          @click="abrir"
        />
      </div>
      <q-banner v-if="avisoClassificacao" dense rounded class="bg-orange-1 text-orange-9 q-mb-sm">
        <template #avatar><q-icon name="warning" color="orange-8" /></template>
        {{ avisoClassificacao.titulo }}
        <div class="text-caption">{{ avisoClassificacao.dica }}</div>
      </q-banner>
      <!-- Mesmo desenho da linha da Pesagem; acima da tolerância pinta de laranja. -->
      <div v-if="leituras.length" class="row justify-center text-center rounded-borders q-pa-sm">
        <div
          v-for="l in leituras"
          :key="l.item.codparametroclassificacao"
          class="col-4 col-sm q-py-xs"
        >
          <div class="text-body2 text-grey-6 ellipsis">{{ l.item.parametroclassificacao }}</div>
          <div class="text-h6" :class="{ 'text-orange-9': foraTolerancia(l.item, l.leitura) }">
            {{ fmt(l.leitura, 1) }}%
          </div>
        </div>
      </div>
      <div v-else-if="itensCarga.length" class="text-grey-6">
        {{
          temLeitura
            ? 'Todas as leituras em 0%.'
            : opcional
              ? 'Classificação opcional na expedição.'
              : 'Ainda sem leitura.'
        }}
      </div>
    </q-card-section>
  </q-card>

  <q-dialog v-model="dialogAberto" :maximized="$q.screen.lt.sm">
    <!-- F3 aqui confirma ESTE dialog; o .stop segura o F3 da página. -->
    <q-card
      flat
      class="column no-wrap"
      :class="{ 'carga-dialog': !$q.screen.lt.sm }"
      @keydown.f3.prevent.stop="formRef.submit($event)"
    >
      <q-form ref="formRef" class="col column no-wrap" @submit="salvar">
        <q-card-section class="col scroll">
          <div class="row items-center text-subtitle1 q-mb-md">
            <q-icon
              :name="ETAPA_META.CLASSIFICACAO.icon"
              :color="ETAPA_META.CLASSIFICACAO.color"
              size="24px"
              class="q-mr-sm"
            />
            Classificação
          </div>
          <q-banner
            v-if="avisoClassificacao"
            dense
            rounded
            class="bg-orange-1 text-orange-9 q-mb-sm"
          >
            <template #avatar><q-icon name="warning" color="orange-8" /></template>
            {{ avisoClassificacao.titulo }}
            <div class="text-caption">{{ avisoClassificacao.dica }}</div>
          </q-banner>
          <div class="row q-col-gutter-md">
            <div
              v-for="(item, i) in itensCarga"
              :key="item.codparametroclassificacao"
              class="col-6 col-sm-4"
            >
              <MgInputValor
                v-model="linhaEdicao(item.codparametroclassificacao).leitura"
                :decimals="1"
                suffix="%"
                :label="`${item.ordem}. ${item.parametroclassificacao}`"
                :hint="hintItem(item)"
                class="q-field--auto-height"
                :input-class="`text-h4 text-weight-bold ${
                  foraTolerancia(item, linhaEdicao(item.codparametroclassificacao).leitura)
                    ? 'text-orange-9'
                    : 'text-primary'
                }`"
                :autofocus="i === 0"
                lazy-rules
                :rules="[
                  (v) => !etapaAtual || !leituraVazia(v) || 'Informe a leitura (0 se não houver).',
                  (v) => v == null || (v >= 0 && v <= 100) || 'Leitura deve ficar entre 0 e 100%.',
                ]"
              />
              <!-- Só o desconto, quando há: acima da tolerância a leitura já fica laranja. -->
              <div
                v-if="descontoPreview(item.codparametroclassificacao)"
                class="text-caption text-orange-8 q-pl-sm"
              >
                − {{ fmt(descontoPreview(item.codparametroclassificacao)) }} kg
              </div>
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right" class="col-auto">
          <q-btn label="Cancelar (Esc)" flat color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            :label="`${rotuloConfirmar} (Enter)`"
            type="submit"
            :flat="!etapaAtual"
            :unelevated="!!etapaAtual"
            color="primary"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Detalhes (só leitura). O .stop segura o F3 da página, que avançaria a
       etapa por baixo do dialog. -->
  <q-dialog v-model="detalhesAberto" :maximized="$q.screen.lt.sm">
    <q-card
      flat
      class="column no-wrap"
      :class="{ 'carga-dialog': !$q.screen.lt.sm }"
      @keydown.f3.prevent.stop
    >
      <q-card-section class="col-auto row items-center text-subtitle1">
        <q-icon
          :name="ETAPA_META.CLASSIFICACAO.icon"
          :color="ETAPA_META.CLASSIFICACAO.color"
          size="24px"
          class="q-mr-sm"
        />
        Descontos da classificação
      </q-card-section>
      <q-card-section class="col scroll q-pt-none">
        <!-- Mesmas colunas da ficha do romaneio (CargaDetailPage). -->
        <q-markup-table flat class="tabela-descontos">
          <thead>
            <tr>
              <th class="text-left">Parâmetro</th>
              <th class="text-right">Leitura</th>
              <th class="text-right">Tolerância</th>
              <th class="text-right">Desconto</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in detalhes" :key="d.item.codparametroclassificacao">
              <td class="text-left">{{ d.item.parametroclassificacao }}</td>
              <td class="text-right">{{ fmt(d.leitura, 1) }} %</td>
              <td class="text-right text-grey-6">{{ fmt(d.item.tolerancia, 1) }} %</td>
              <td class="text-right" :class="d.desconto ? 'text-orange-9' : 'text-grey-6'">
                {{ fmt(d.desconto) }} <template v-if="d.desconto != null">kg</template>
              </td>
            </tr>
            <tr class="text-weight-bold">
              <td class="text-left" colspan="3">Total</td>
              <td class="text-right" :class="calc.desconto ? 'text-orange-9' : 'text-grey-6'">
                {{ fmt(calc.desconto) }} <template v-if="calc.desconto != null">kg</template>
              </td>
            </tr>
          </tbody>
        </q-markup-table>
        <div v-if="calc.desconto == null" class="text-grey-6 q-mt-sm">
          O desconto em kg sai depois da tara.
        </div>
      </q-card-section>
      <q-card-actions align="right" class="col-auto">
        <q-btn label="Fechar (Esc)" flat color="grey-8" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped>
/* O q-table fixa 12px no cabeçalho e 13px nas células, e essa regra vence as
   classes de tipografia do Quasar. No diálogo de descontos a tabela é o
   conteúdo todo: cabeçalho no tamanho dos rótulos dos cards, linhas maiores. */
.tabela-descontos :deep(th) {
  font-size: 14px;
}
.tabela-descontos :deep(td) {
  font-size: 16px;
}

/* No celular o diálogo abre maximizado e a tabela fica com ~330px: com o
   respiro padrão do q-table (16px de cada lado) a coluna Desconto saía cortada. */
@media (max-width: 599px) {
  .tabela-descontos :deep(th),
  .tabela-descontos :deep(td) {
    padding-left: 6px;
    padding-right: 6px;
  }
  .tabela-descontos :deep(th) {
    font-size: 13px;
  }
  .tabela-descontos :deep(td) {
    font-size: 15px;
  }
}
</style>
