<script setup>
// Passo do wizard: vale compras, por dois caminhos.
//
// BIPAR  — o cliente chegou com o papel: lê o VAL…, usa min(saldo, valor). É o
//          caminho que já rodava em produção e não mudou.
// ESCOLA — o cliente chegou sem papel nenhum, que é o comum na temporada: escolhe
//          a escola (e a turma, se quiser) e o servidor monta o lote em FIFO,
//          gastando primeiro o crédito que vence antes. Só o último vale do lote
//          entra parcial.
//
// Vale ao portador não aparece no modo ESCOLA de propósito: são todos do mesmo
// favorecido (Consumidor), e consumir "o crédito do Consumidor" gastaria o vale
// de um estranho.
import { ref, computed, watch, onMounted } from 'vue'
import { Notify, debounce } from 'quasar'
import { negocioStore } from 'stores/negocio'
import MgInput from '@components/MgInput.vue'
import { formataNumero } from '@components/formatters'

const emit = defineEmits(['concluido'])

const sNegocio = negocioStore()

const modo = ref('bipar')

// ---------------------------------------------------------------- bipar
const codigo = ref(null)
const titulo = ref(null)
const buscando = ref(false)

const valor = computed(() => sNegocio.receber.valor)

// quanto do vale vai ser usado neste pagamento
const valorUsar = computed(() => {
  if (!titulo.value) {
    return null
  }
  return Math.min(parseFloat(titulo.value.creditosaldo), valor.value)
})

const buscar = debounce(async () => {
  titulo.value = null
  const cod = parseInt(String(codigo.value ?? '').replace(/\D/g, ''))
  if (!cod) {
    return
  }
  const jaUsado = (sNegocio.negocio.pagamentos ?? []).some((p) => p.codtitulo == cod)
  if (jaUsado) {
    Notify.create({
      type: 'negative',
      message: 'Este vale já foi usado neste negócio!',
      timeout: 3000, // 3 segundos
      actions: [{ icon: 'close', color: 'white' }],
    })
    return
  }
  buscando.value = true
  const ret = await sNegocio.buscarVale(cod)
  buscando.value = false
  if (!ret) {
    return
  }
  titulo.value = ret.data.data
}, 500)

watch(codigo, () => buscar())

// ---------------------------------------------------------------- escola
const escolas = ref([])
const turmas = ref([])
const escola = ref(null)
const turma = ref(null)
const lote = ref(null)
const carregando = ref(false)

const carregarEscolas = async () => {
  carregando.value = true
  escolas.value = (await sNegocio.valeEscopoFavorecidos()) ?? []
  carregando.value = false
}

const escolherEscola = async (opcao) => {
  escola.value = opcao
  turma.value = null
  lote.value = null
  carregando.value = true
  turmas.value = (await sNegocio.valeEscopoTurmas(opcao.codpessoafavorecido)) ?? []
  carregando.value = false
  await montarLote()
}

const escolherTurma = async (opcao) => {
  turma.value = opcao
  await montarLote()
}

const montarLote = async () => {
  if (!escola.value) {
    return
  }
  carregando.value = true
  lote.value = await sNegocio.valeEscopoSelecionar(
    escola.value.codpessoafavorecido,
    turma.value?.turma ?? null,
    valor.value,
  )
  carregando.value = false
}

const voltarEscolas = () => {
  ;((escola.value = null), (turma.value = null), (lote.value = null))
}

// ---------------------------------------------------------------- comum
const usarTotal = computed(() => {
  if (modo.value == 'bipar') {
    return valorUsar.value ?? 0
  }
  return lote.value?.total ?? 0
})

const falta = computed(() => {
  if (!usarTotal.value) {
    return 0
  }
  return Math.round((valor.value - usarTotal.value) * 100) / 100
})

onMounted(() => {
  if (sNegocio.receber.codtituloVale) {
    codigo.value = String(sNegocio.receber.codtituloVale)
    return
  }
  carregarEscolas()
})

watch(modo, (novo) => {
  if (novo == 'escola' && !escolas.value.length) {
    carregarEscolas()
  }
})

const salvar = async () => {
  if (!usarTotal.value) {
    return false
  }

  if (modo.value == 'bipar') {
    // vale identifica o cliente quando a venda está no consumidor final
    if (sNegocio.negocio.codpessoa == 1) {
      await sNegocio.informarPessoa(titulo.value.codpessoa, null)
    }
    await sNegocio.adicionarPagamento({
      codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_VALE),
      tipo: 12, // tPag Vale Presente
      codtitulo: titulo.value.codtitulo,
      valorpagamento: valorUsar.value,
    })
    emit('concluido')
    return true
  }

  await sNegocio.adicionarPagamentosVale(lote.value.vales)
  emit('concluido')
  return true
}

// devolve true quando consumiu a tecla; o resto é digitação no campo do código
const tecla = (e) => {
  if (e.key === 'Enter') {
    return salvar() !== false
  }
  return false
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <q-btn-toggle
      v-model="modo"
      spread
      no-caps
      unelevated
      toggle-color="primary"
      color="grey-3"
      text-color="grey-8"
      class="q-mb-md"
      :options="[
        { label: 'Bipar o vale', value: 'bipar', icon: 'mdi-barcode-scan' },
        { label: 'Pela escola', value: 'escola', icon: 'school' },
      ]"
    />

    <!-- BIPAR: o cliente trouxe o papel -->
    <template v-if="modo == 'bipar'">
      <MgInput
        v-model="codigo"
        label="Código do vale"
        autofocus
        inputmode="numeric"
        :loading="buscando"
        class="q-mb-md"
      />

      <q-list v-if="titulo" class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label caption>Vale</q-item-label>
            <q-item-label>{{ titulo.numero }} · {{ titulo.fantasia }}</q-item-label>
          </q-item-section>
        </q-item>
        <q-item>
          <q-item-section>
            <q-item-label caption>Saldo do vale</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label class="text-h6 text-grey-8">
              R$ {{ formataNumero(titulo.creditosaldo) }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </template>

    <!-- ESCOLA: o cliente chegou sem papel -->
    <template v-else>
      <!-- escolha da escola -->
      <template v-if="!escola">
        <q-list v-if="escolas.length" separator class="q-mb-md">
          <q-item
            v-for="e in escolas"
            :key="e.codpessoafavorecido"
            clickable
            v-ripple
            @click="escolherEscola(e)"
          >
            <q-item-section avatar>
              <q-avatar color="purple-6" text-color="white" icon="school" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="ellipsis">{{ e.favorecido }}</q-item-label>
              <q-item-label caption>{{ e.vales }} vales em aberto</q-item-label>
            </q-item-section>
            <q-item-section side class="text-subtitle1 text-weight-bold text-grey-9">
              {{ formataNumero(e.saldo) }}
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else-if="!carregando" class="text-grey-7 q-mb-md">
          Nenhuma escola com crédito de vale em aberto.
        </div>
      </template>

      <!-- escola escolhida: turma e lote -->
      <template v-else>
        <q-item class="q-px-none q-mb-sm">
          <q-item-section avatar>
            <q-btn flat round size="sm" color="grey-7" icon="arrow_back" @click="voltarEscolas()" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="ellipsis">{{ escola.favorecido }}</q-item-label>
            <q-item-label caption>
              {{ turma ? turma.turma || 'Sem turma' : 'Escola inteira' }} · saldo R$
              {{ formataNumero(lote?.saldoescopo ?? escola.saldo) }}
            </q-item-label>
          </q-item-section>
        </q-item>

        <div class="q-mb-md" v-if="turmas.length > 1">
          <q-chip
            clickable
            :outline="turma != null"
            color="primary"
            text-color="white"
            label="Escola inteira"
            @click="escolherTurma(null)"
          />
          <q-chip
            v-for="t in turmas"
            :key="t.turma"
            clickable
            :outline="turma?.turma != t.turma"
            color="primary"
            text-color="white"
            :label="`${t.turma || 'Sem turma'} · ${formataNumero(t.saldo)}`"
            @click="escolherTurma(t)"
          />
        </div>

        <q-list v-if="lote?.vales?.length" separator class="q-mb-md">
          <q-item v-for="v in lote.vales" :key="v.codtitulo">
            <q-item-section>
              <q-item-label class="ellipsis">{{ v.aluno || v.numero }}</q-item-label>
              <q-item-label caption class="ellipsis">
                {{ v.numero }}{{ v.turma ? ' · ' + v.turma : '' }} · saldo
                {{ formataNumero(v.saldo) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side class="text-subtitle1 text-weight-bold text-primary">
              {{ formataNumero(v.usar) }}
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else-if="!carregando" class="text-grey-7 q-mb-md">
          Nenhum vale com saldo neste escopo.
        </div>
      </template>
    </template>

    <!-- TOTAL E SALDO: iguais nos dois modos -->
    <template v-if="usarTotal > 0">
      <q-item class="rounded-borders bg-grey-2 q-mb-md">
        <q-item-section>
          <q-item-label caption>Usar neste pagamento</q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-item-label class="text-h5 text-primary text-weight-bolder">
            R$ {{ formataNumero(usarTotal) }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-banner v-if="falta > 0" rounded class="bg-orange-1 text-orange-10 q-mb-md">
        <div class="row items-center">
          <div class="col text-subtitle1 text-weight-bold">FALTA</div>
          <div class="col-auto text-h5 text-weight-bolder">R$ {{ formataNumero(falta) }}</div>
        </div>
      </q-banner>
      <div class="text-caption text-grey-7">
        Enter lança {{ modo == 'escola' && lote?.vales?.length > 1 ? 'os vales' : 'o vale' }}
      </div>
    </template>
  </div>
</template>
