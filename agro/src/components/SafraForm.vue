<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import MgInputValor from '@components/MgInputValor.vue'
import RadioCultura from 'components/RadioCultura.vue'

// Formulário único de safra — serve tanto pra criar quanto pra editar. Recebe o
// objeto reativo do form (da store do domínio) e a lista de safras já
// cadastradas (pra sugerir o próximo ano de plantio na criação). isNovo é
// derivado da ausência da PK.
const props = defineProps({
  form: { type: Object, required: true },
  safras: { type: Array, default: () => [] },
})

const form = computed(() => props.form)
const isNovo = computed(() => !form.value.codsafra)

// Cultura escolhida no radio: guardada pra saber o ciclo na hora de derivar o
// ano de colheita e montar a descrição.
const culturaSel = ref(null)
// Vira true quando o usuário mexe na descrição — aí paramos de sobrescrevê-la.
const safraEditada = ref(false)

// Maior ano de plantio já cadastrado pra cultura (lista vem ordenada por
// -anoplantio, então o último está na primeira página).
function ultimoAnoPlantio(codcultura) {
  const anos = props.safras
    .filter((s) => s.codcultura === codcultura && s.anoplantio)
    .map((s) => s.anoplantio)
  return anos.length ? Math.max(...anos) : null
}

// Folga em anos entre plantio e colheita: soja (ciclo 2) colhe no ano seguinte,
// milho (ciclo 1) no mesmo ano. Sempre entre 0 e 1. Sem cultura conhecida
// (edição), preserva a folga atual.
function gapColheita(anoAnterior) {
  if (culturaSel.value) {
    return Math.min(1, Math.max(0, Number(culturaSel.value.cicloanos || 1) - 1))
  }
  const g = Number(form.value.anocolheita) - Number(anoAnterior)
  return Number.isFinite(g) ? Math.min(1, Math.max(0, g)) : 1
}

// Ao escolher a cultura numa safra nova, sugere o ano de plantio (último + 1, ou
// ano atual na primeira safra da cultura). Colheita e descrição saem dos watches.
function onCultura(cultura) {
  culturaSel.value = cultura
  if (!isNovo.value || !cultura) return
  const ultimo = ultimoAnoPlantio(cultura.codcultura)
  const plantio = ultimo ? ultimo + 1 : new Date().getFullYear()
  // Seta plantio E colheita explicitamente — não dá pra deixar a colheita só no
  // watch do plantio, porque ao trocar de cultura o plantio pode cair no mesmo
  // ano (watch não dispara) e a colheita ficaria com o ciclo da cultura anterior.
  form.value.anoplantio = plantio
  form.value.anocolheita = plantio + gapColheita(plantio)
}

// Usuário mexeu no plantio na mão → recalcula a colheita pelo ciclo da cultura.
watch(
  () => form.value.anoplantio,
  (plantio, anterior) => {
    if (plantio === null || plantio === undefined || plantio === '') return
    form.value.anocolheita = Number(plantio) + gapColheita(anterior)
  },
)

// Regras de validação (bloqueiam o submit). A colheita nunca pode ser antes do
// plantio nem mais de 1 ano depois — trava de verdade, no salvar.
// Obrigatório → boolean puro (campo vermelho + foco basta). Colheita min/max são
// cross-field (dependem do ano de plantio) → mantêm mensagem curta.
const regraObrigatorio = (v) => v !== null && v !== undefined && v !== ''
const regraColheitaMin = (v) => {
  const p = Number(form.value.anoplantio)
  return !p || Number(v) >= p || 'Colheita não pode ser antes do plantio'
}
const regraColheitaMax = (v) => {
  const p = Number(form.value.anoplantio)
  return !p || Number(v) <= p + 1 || 'Colheita no máximo 1 ano após o plantio'
}

// Descrição montada com o que já foi preenchido (ex.: "Soja 2025/2026",
// "Milho 2026") enquanto o usuário não a editar na mão.
function montarDescricao() {
  if (safraEditada.value) return
  const nome = culturaSel.value?.cultura
  const p = form.value.anoplantio
  if (!nome || !p) return
  const c = form.value.anocolheita
  form.value.safra = c && Number(c) !== Number(p) ? `${nome} ${p}/${c}` : `${nome} ${p}`
}
watch(() => [form.value.anoplantio, form.value.anocolheita, culturaSel.value], montarDescricao)

function onSafraInput(v) {
  form.value.safra = v
  safraEditada.value = true
}

onMounted(() => {
  // na edição a descrição já existe — não sobrescrever
  safraEditada.value = !isNovo.value
})
</script>

<template>
  <div class="row q-col-gutter-md">
    <div class="col-12">
      <RadioCultura v-model="form.codcultura" autofocus @change="onCultura" />
    </div>

    <div class="col-6">
      <MgInputValor
        v-model="form.anoplantio"
        label="Ano de plantio"
        :decimals="0"
        :grouping="false"
        align="left"
        stack-label
        :min="2000"
        :max="2100"
        :rules="[regraObrigatorio]"
        lazy-rules
      />
    </div>

    <div class="col-6">
      <MgInputValor
        v-model="form.anocolheita"
        label="Ano de colheita"
        :decimals="0"
        :grouping="false"
        align="left"
        stack-label
        :min="2000"
        :max="2100"
        :rules="[regraObrigatorio, regraColheitaMin, regraColheitaMax]"
        lazy-rules
      />
    </div>

    <div class="col-12">
      <q-input
        :model-value="form.safra"
        label="Descrição"
        hint="Gerada automaticamente — pode ajustar"
        outlined
        @update:model-value="onSafraInput"
      />
    </div>
  </div>
</template>
