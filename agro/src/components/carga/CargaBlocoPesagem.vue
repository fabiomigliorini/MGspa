<script setup>
import { ref, computed, inject } from 'vue'
import { calcularCarga } from 'src/utils/desconto'
import { etapasDaCarga, indiceEtapa, fmtNumero as fmt } from 'src/utils/carga'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  novo: { type: Boolean, default: false },
})

// Providos pelo CargaForm.vue — mesmos computeds que o form usa pra validar/
// imprimir, reaproveitados aqui em vez de recalculados do zero.
const persistirBloco = inject('persistirBloco')
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
const mostrarResultado = computed(() => calc.value.bruto !== null && calc.value.bruto !== undefined)

const dialogAberto = ref(false)
const edicao = ref({})

function abrir() {
  edicao.value = { pbt: carga.value.pbt, tara: carga.value.tara }
  dialogAberto.value = true
}

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

async function salvar() {
  Object.assign(carga.value, { pbt: edicao.value.pbt, tara: edicao.value.tara })
  try {
    const ok = await persistirBloco()
    if (ok) dialogAberto.value = false
  } catch {
    // erro já notificado por quem persiste (CargaPage) — mantém o dialog aberto
  }
}
</script>

<template>
  <q-card v-if="mostrarPbt || mostrarTara" flat bordered>
    <q-card-section>
      <div class="row items-center q-mb-sm">
        <div class="text-subtitle2 text-grey-8">Pesagem</div>
        <q-space />
        <q-btn flat round dense icon="edit" size="sm" color="grey-7" @click="abrir" />
      </div>

      <div v-if="mostrarResultado" class="row text-center bg-grey-1 rounded-borders q-pa-sm">
        <div class="col">
          <div class="text-caption text-grey-7">Bruto</div>
          <div class="text-weight-medium">{{ fmt(calc.bruto) }} kg</div>
        </div>
        <div class="col">
          <div class="text-caption text-grey-7">Desconto</div>
          <div class="text-weight-medium text-orange-9">{{ fmt(calc.desconto) }} kg</div>
        </div>
        <div class="col">
          <div class="text-caption text-grey-7">Líquido</div>
          <div class="text-weight-medium text-green-9">{{ fmt(calc.liquido) }} kg</div>
        </div>
        <div class="col">
          <div class="text-caption text-grey-7">Sacas</div>
          <div class="text-weight-medium">{{ fmt(sacasLiquido, 1) }}</div>
        </div>
      </div>
      <div v-else class="row q-col-gutter-md">
        <div v-if="mostrarPbt" class="col-6">
          <div class="text-caption text-grey-6">Peso bruto total</div>
          <div>{{ carga.pbt != null ? `${fmt(carga.pbt)} kg` : '—' }}</div>
        </div>
        <div v-if="mostrarTara" class="col-6">
          <div class="text-caption text-grey-6">Tara</div>
          <div>{{ carga.tara != null ? `${fmt(carga.tara)} kg` : '—' }}</div>
        </div>
      </div>
    </q-card-section>
  </q-card>

  <q-dialog v-model="dialogAberto">
    <q-card style="width: 500px; max-width: 90vw">
      <q-form @submit="salvar">
        <q-card-section>
          <div class="text-subtitle1 q-mb-md">Pesagem</div>
          <div class="row q-col-gutter-md">
            <MgInputValor
              v-if="mostrarPbt"
              v-model="edicao.pbt"
              :decimals="0"
              suffix="kg"
              label="Peso bruto total (caminhão + carga)"
              class="col-12"
              autofocus
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
              label="Tara (caminhão vazio)"
              class="col-12"
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
          </div>
          <div v-if="mostrarPreview" class="row text-center bg-grey-1 rounded-borders q-pa-sm q-mt-sm">
            <div class="col">
              <div class="text-caption text-grey-7">Bruto</div>
              <div class="text-weight-medium">{{ fmt(calcPreview.bruto) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Desconto</div>
              <div class="text-weight-medium text-orange-9">{{ fmt(calcPreview.desconto) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Líquido</div>
              <div class="text-weight-medium text-green-9">{{ fmt(calcPreview.liquido) }} kg</div>
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
