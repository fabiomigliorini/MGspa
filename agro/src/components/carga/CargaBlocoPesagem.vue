<script setup>
import { ref, computed, inject } from 'vue'
import { calcularCarga, sacas } from 'src/utils/desconto'
import { useCargaStore } from 'src/stores/carga'
import { ETAPA_META, etapasDaCarga, indiceEtapa, fmtNumero as fmt } from 'src/utils/carga'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  novo: { type: Boolean, default: false },
})

// Providos pelo CargaForm.vue — mesmos computeds que o form usa pra validar/
// imprimir, reaproveitados aqui em vez de recalculados do zero.
const store = useCargaStore()
const persistirBloco = inject('persistirBloco')
const concluirEtapa = inject('concluirEtapa')
const proximoPasso = inject('proximoPasso')
const calc = inject('calc')
const itensCarga = inject('itensCarga')
const sacasLiquido = inject('sacasLiquido')

// Indireção (padrão ContratoForm/SafraForm): muta o objeto reativo compartilhado
// sem disparar vue/no-mutating-props — é a MESMA referência que o CargaForm.vue
// conhece como `local`.
const carga = computed(() => props.carga)

const idxEtapa = computed(() => indiceEtapa(carga.value))
const ordem = computed(() => etapasDaCarga(carga.value))
// `|| != null` segura o caso de troca de sentido: um peso já lido continua à
// vista mesmo que o novo fluxo ainda não tenha alcançado a etapa dele.
const mostrarPbt = computed(
  () => idxEtapa.value >= ordem.value.indexOf('PBT') || carga.value.pbt != null,
)
const mostrarTara = computed(
  () => idxEtapa.value >= ordem.value.indexOf('TARA') || carga.value.tara != null,
)
const temDados = computed(() => carga.value.pbt != null || carga.value.tara != null)
const mostrarResultado = computed(() => calc.value.bruto !== null && calc.value.bruto !== undefined)

const dialogAberto = ref(false)
const formRef = ref(null)
const edicao = ref({})
// Aberto pelo botão da etapa (PBT/TARA) em vez do lápis: o peso da etapa é
// obrigatório e confirmar AVANÇA a carga (concluirEtapa), não só grava.
const etapaAtual = ref(null)
const rotuloConfirmar = ref('Salvar')

function abrir({ etapa = null, rotulo = null } = {}) {
  // F3 com o dialog já aberto não pode reabrir e apagar o que foi digitado.
  if (dialogAberto.value) return
  edicao.value = { pbt: carga.value.pbt, tara: carga.value.tara }
  etapaAtual.value = etapa
  rotuloConfirmar.value = rotulo || 'Salvar'
  dialogAberto.value = true
}
defineExpose({ abrir })

// Foco no peso que a balança acabou de dar: na tara quando é ela a etapa.
const focoTara = computed(() => mostrarTara.value && carga.value.etapa === 'TARA')

// Prévia ao vivo com o que está sendo digitado no dialog (ainda não salvo).
const calcPreview = computed(() =>
  calcularCarga(
    { ...carga.value, pbt: edicao.value.pbt, tara: edicao.value.tara },
    itensCarga.value,
  ),
)
const mostrarPreview = computed(
  () => calcPreview.value.bruto !== null && calcPreview.value.bruto !== undefined,
)
// Só leitura da cultura da carga (peso da saca) — nada é gravado pela store aqui.
const sacasPreview = computed(() =>
  sacas(calcPreview.value.liquido, store.pesosacaDaCarga(carga.value)),
)
const liquidoInvalido = computed(() => !(Number(calcPreview.value.liquido) > 0))

async function salvar() {
  Object.assign(carga.value, { pbt: edicao.value.pbt, tara: edicao.value.tara })
  try {
    const ok = etapaAtual.value ? await concluirEtapa() : await persistirBloco()
    if (ok) {
      dialogAberto.value = false
      proximoPasso('pesagem')
    }
  } catch {
    // erro já notificado por quem persiste (CargaPage) — mantém o dialog aberto
  }
}
</script>

<template>
  <q-card v-if="mostrarPbt || mostrarTara" flat bordered>
    <q-card-section>
      <div class="row items-center q-mb-sm">
        <q-icon
          :name="ETAPA_META.PBT.icon"
          :color="ETAPA_META.PBT.color"
          size="20px"
          class="q-mr-sm"
        />
        <div class="text-subtitle1 text-grey-8">Pesagem</div>
        <q-space />
        <q-btn
          flat
          round
          dense
          size="sm"
          :icon="temDados ? 'edit' : 'add'"
          :color="temDados ? 'grey-7' : 'primary'"
          @click="abrir"
        />
      </div>

      <div v-if="mostrarResultado" class="row text-center bg-grey-1 rounded-borders q-pa-sm">
        <div class="col">
          <div class="text-body2 text-grey-6">Bruto</div>
          <div class="text-h6">{{ fmt(calc.bruto) }} <small>kg</small></div>
        </div>
        <div class="col">
          <div class="text-body2 text-grey-6">Desconto</div>
          <div class="text-h6 text-orange-9">{{ fmt(calc.desconto) }} <small>kg</small></div>
        </div>
        <div class="col">
          <div class="text-body2 text-grey-6">Líquido</div>
          <div class="text-h6 text-green-9">{{ fmt(calc.liquido) }} <small>kg</small></div>
        </div>
        <div class="col">
          <div class="text-body2 text-grey-6">Sacas</div>
          <div class="text-h6">{{ fmt(sacasLiquido, 1) }}</div>
        </div>
      </div>
      <div v-else class="row q-col-gutter-md">
        <div v-if="mostrarPbt" class="col-6">
          <div class="text-body2 text-grey-6">Peso bruto total</div>
          <div class="row items-center no-wrap">
            <q-icon
              :name="ETAPA_META.PBT.icon"
              :color="ETAPA_META.PBT.color"
              size="24px"
              class="q-mr-sm"
            />
            <span class="text-h6 text-weight-medium">
              {{ carga.pbt != null ? `${fmt(carga.pbt)} kg` : '—' }}
            </span>
          </div>
        </div>
        <div v-if="mostrarTara" class="col-6">
          <div class="text-body2 text-grey-6">Tara</div>
          <div class="row items-center no-wrap">
            <q-icon
              :name="ETAPA_META.TARA.icon"
              :color="ETAPA_META.TARA.color"
              size="24px"
              class="q-mr-sm"
            />
            <span class="text-h6 text-weight-medium">
              {{ carga.tara != null ? `${fmt(carga.tara)} kg` : '—' }}
            </span>
          </div>
        </div>
      </div>
    </q-card-section>
  </q-card>

  <q-dialog v-model="dialogAberto" :maximized="$q.screen.lt.sm">
    <!-- F3 aqui confirma ESTE dialog; o .stop segura o F3 da página, que
         dispararia o Registrar/avançar por baixo com o peso ainda não aplicado. -->
    <q-card
      flat
      class="column no-wrap"
      :class="{ 'carga-dialog': !$q.screen.lt.sm }"
      @keydown.f3.prevent.stop="formRef.submit($event)"
    >
      <q-form ref="formRef" class="col column no-wrap" @submit="salvar">
        <q-card-section class="col-auto q-pb-none">
          <div class="row items-center text-subtitle1">
            <template v-if="etapaAtual">
              <q-icon
                :name="ETAPA_META[etapaAtual].icon"
                :color="ETAPA_META[etapaAtual].color"
                size="24px"
                class="q-mr-sm"
              />
              {{ ETAPA_META[etapaAtual].label }}
            </template>
            <template v-else>Pesagem</template>
          </div>
        </q-card-section>
        <!-- Mesmo desenho do "Receber" do /negocios (ReceberDialog, passo 2):
             número grande no campo, resultado grande embaixo, à direita,
             centralizado na vertical. -->
        <q-card-section class="col scroll column no-wrap">
          <div class="q-my-auto">
            <MgInputValor
              v-if="mostrarPbt"
              v-model="edicao.pbt"
              :decimals="0"
              suffix="kg"
              label="Peso bruto (PBT)"
              hint="Caminhão + carga"
              class="q-mb-md q-field--auto-height"
              input-class="text-h2 text-weight-bold text-primary"
              :autofocus="!focoTara"
              lazy-rules
              :rules="[
                (v) => novo || carga.etapa !== 'PBT' || v > 0 || 'Informe o peso bruto (PBT).',
              ]"
            />
            <MgInputValor
              v-if="mostrarTara"
              v-model="edicao.tara"
              :decimals="0"
              suffix="kg"
              label="Tara"
              hint="Caminhão vazio"
              class="q-mb-md q-field--auto-height"
              input-class="text-h2 text-weight-bold text-primary"
              :autofocus="focoTara"
              lazy-rules
              :rules="[
                (v) => novo || carga.etapa !== 'TARA' || v > 0 || 'Informe a tara.',
                (v) =>
                  v == null ||
                  edicao.pbt == null ||
                  v < Number(edicao.pbt) ||
                  'A tara deve ser menor que o PBT.',
                () =>
                  edicao.pbt == null ||
                  edicao.tara == null ||
                  Number(calcPreview.liquido) > 0 ||
                  'Líquido (PBT − tara − desconto) deve ser maior que zero.',
              ]"
            />
            <div v-if="mostrarPreview" class="text-right">
              <div class="text-h6 text-grey-8">
                Bruto {{ fmt(calcPreview.bruto) }} kg ·
                <span class="text-orange-9">Desconto {{ fmt(calcPreview.desconto) }} kg</span>
              </div>
              <div
                class="text-h2 text-weight-bold"
                :class="liquidoInvalido ? 'text-orange-10' : 'text-green-9'"
              >
                {{ fmt(calcPreview.liquido) }} kg
              </div>
              <div
                class="text-subtitle1"
                :class="liquidoInvalido ? 'text-orange-10' : 'text-green-9'"
              >
                Líquido
                <template v-if="!liquidoInvalido && sacasPreview != null">
                  · {{ fmt(sacasPreview, 1) }} sacas
                </template>
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
</template>
