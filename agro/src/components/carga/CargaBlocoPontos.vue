<script setup>
import { ref, computed, inject } from 'vue'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import {
  CONTATIPO_PADRAO,
  novoPonto,
  pontosPorPapel,
  somaPercentual,
  somaPercBate,
  distribuirPercentual,
  etapasDaCarga,
  indiceEtapa,
  contatipoMeta,
  fmtNumero as fmt,
} from 'src/utils/carga'
import MgInputValor from '@components/MgInputValor.vue'
import SelectContaTipo from 'components/SelectContaTipo.vue'
import SelectTalhao from 'components/SelectTalhao.vue'
import SelectUnidade from 'components/SelectUnidade.vue'
import SelectContrato from 'components/SelectContrato.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  novo: { type: Boolean, default: false },
})

const store = useCargaStore()
const { unidadesAtivas } = storeToRefs(store)

// Providos pelo CargaForm.vue.
const persistirBloco = inject('persistirBloco')
const calc = inject('calc')
const finalizando = inject('finalizando')

// Indireção (padrão ContratoForm/SafraForm): muta o objeto reativo compartilhado
// sem disparar vue/no-mutating-props — é a MESMA referência que o CargaForm.vue
// conhece como `local`.
const carga = computed(() => props.carga)

const origens = computed(() => pontosPorPapel(carga.value, 'ORIGEM'))
const destinos = computed(() => pontosPorPapel(carga.value, 'DESTINO'))
const somaPercOrigens = computed(() => somaPercentual(origens.value))
const somaPercDestinos = computed(() => somaPercentual(destinos.value))

const temNf = computed(() => (carga.value.pontos || []).some((p) => !!p.numeronf || p.valornf != null))
const idxEtapa = computed(() => indiceEtapa(carga.value))
const ordem = computed(() => etapasDaCarga(carga.value))
const mostrarFiscal = computed(
  () => (carga.value.sentido === 'SAIDA' && idxEtapa.value >= ordem.value.indexOf('FISCAL')) || temNf.value,
)

// kg estimado de um ponto — só depois de pesar (líquido já salvo × %). Serve
// tanto pro card de leitura quanto pro dialog (a % em edição já reflete aqui).
function kgDoPonto(p) {
  const liq = Number(calc.value.liquido)
  if (!(liq > 0)) return null
  return Math.round((liq * (Number(p.percentual) || 0)) / 100)
}
function saldoContrato(cod) {
  return store.saldoContratoOffline(cod)
}
function rotuloPlantio(cod) {
  return store.plantioPorId(cod)?.rotulo || null
}
function rotuloUnidade(cod) {
  return unidadesAtivas.value.find((o) => o.codunidadearmazenadora === cod)?.unidadearmazenadora || null
}

// ---- Edição (cópia local — clone do array de pontos + codsafra, que pode
// mudar junto se o talhão escolhido pertencer a outra safra) ----
const dialogAberto = ref(false)
const edicao = ref({ pontos: [], codsafra: null })

function abrir() {
  edicao.value = {
    pontos: (carga.value.pontos || []).map((p) => ({ ...p })),
    codsafra: carga.value.codsafra,
  }
  dialogAberto.value = true
}

const origensEdicao = computed(() => edicao.value.pontos.filter((p) => p.papel === 'ORIGEM'))
const destinosEdicao = computed(() => edicao.value.pontos.filter((p) => p.papel === 'DESTINO'))

function grupoDoPonto(p) {
  return p.papel === 'ORIGEM' ? origensEdicao.value : destinosEdicao.value
}
function addPonto(papel) {
  const contatipo = CONTATIPO_PADRAO[carga.value.sentido]?.[papel] || 'UNIDADE'
  edicao.value.pontos.push(novoPonto(papel, contatipo))
  distribuirPercentual(papel === 'ORIGEM' ? origensEdicao.value : destinosEdicao.value)
}
function removerPonto(p) {
  const i = edicao.value.pontos.indexOf(p)
  if (i >= 0) edicao.value.pontos.splice(i, 1)
  distribuirPercentual(grupoDoPonto(p))
}
function onTipoChange(p) {
  p.codplantio = null
  p.codunidadearmazenadora = null
  p.codcontrato = null
  p.rotulo = null
}
function onEntidade(p, val) {
  if (p.contatipo === 'PLANTIO') p.codplantio = val
  else if (p.contatipo === 'UNIDADE') p.codunidadearmazenadora = val
  else if (p.contatipo === 'CONTRATO') p.codcontrato = val
  setRotuloPonto(p)
}
function setRotuloPonto(p) {
  if (p.contatipo === 'PLANTIO') p.rotulo = rotuloPlantio(p.codplantio)
  else if (p.contatipo === 'UNIDADE') p.rotulo = rotuloUnidade(p.codunidadearmazenadora)
  else if (p.contatipo === 'CONTRATO') p.rotulo = store.rotuloContrato(p.codcontrato)
}
// Talhão escolhido pode ser de OUTRA safra (soja × milho): a carga segue a
// safra do talhão. CargaPage.persistir já troca a safra ativa ao salvar.
function onPlantioSelecionado(plantio) {
  if (plantio?.codsafra && plantio.codsafra !== edicao.value.codsafra) {
    edicao.value.codsafra = plantio.codsafra
  }
}

async function salvar() {
  carga.value.pontos = edicao.value.pontos
  carga.value.codsafra = edicao.value.codsafra
  try {
    const ok = await persistirBloco()
    if (ok) dialogAberto.value = false
  } catch {
    // erro já notificado por quem persiste (CargaPage) — mantém o dialog aberto
  }
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section>
      <div class="row items-center q-mb-sm">
        <div class="text-subtitle2 text-grey-8">Origem / Destino do grão</div>
        <q-space />
        <q-btn flat round dense icon="edit" size="sm" color="grey-7" @click="abrir" />
      </div>
      <div class="row q-col-gutter-lg">
        <div class="col-12 col-md-6">
          <!-- login/logout: os mesmos ícones que o CargaResumo usa pra origem e
               destino no drawer da direita. -->
          <div class="text-caption text-grey-6 q-mb-xs">
            <q-icon name="login" size="16px" class="q-mr-xs" />Origem
          </div>
          <div v-if="!origens.length" class="text-grey-5">Nenhuma origem informada.</div>
          <div v-for="(p, i) in origens" :key="'o' + i" class="row items-center no-wrap q-mb-sm">
            <q-icon
              :name="contatipoMeta(p.contatipo).icon"
              :color="contatipoMeta(p.contatipo).color"
              size="24px"
              class="q-mr-sm"
            >
              <q-tooltip>{{ contatipoMeta(p.contatipo).label }}</q-tooltip>
            </q-icon>
            <div class="col">
              <div class="text-body1 text-weight-medium ellipsis">
                {{ p.rotulo || 'Não informado' }}
              </div>
              <div class="text-caption text-grey-6">
                {{ fmt(p.percentual, 1) }}%
                <span v-if="kgDoPonto(p)"> · ≈ {{ fmt(kgDoPonto(p)) }} kg</span>
              </div>
            </div>
          </div>
          <div v-if="origens.length" class="text-caption" :class="somaPercBate(origens) ? 'text-grey-7' : 'text-orange-8'">
            Soma: {{ fmt(somaPercOrigens, 1) }}%
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="text-caption text-grey-6 q-mb-xs">
            <q-icon name="logout" size="16px" class="q-mr-xs" />Destino
          </div>
          <div v-if="!destinos.length" class="text-grey-5">Nenhum destino informado.</div>
          <div v-for="(p, i) in destinos" :key="'d' + i" class="row items-center no-wrap q-mb-sm">
            <q-icon
              :name="contatipoMeta(p.contatipo).icon"
              :color="contatipoMeta(p.contatipo).color"
              size="24px"
              class="q-mr-sm"
            >
              <q-tooltip>{{ contatipoMeta(p.contatipo).label }}</q-tooltip>
            </q-icon>
            <div class="col">
              <div class="text-body1 text-weight-medium ellipsis">
                {{ p.rotulo || 'Não informado' }}
              </div>
              <div class="text-caption text-grey-6">
                {{ fmt(p.percentual, 1) }}%
                <span v-if="kgDoPonto(p)"> · ≈ {{ fmt(kgDoPonto(p)) }} kg</span>
                <span v-if="mostrarFiscal && p.numeronf">
                  · NF {{ p.numeronf }}<span v-if="p.valornf"> · R$ {{ fmt(p.valornf, 2) }}</span>
                </span>
              </div>
            </div>
          </div>
          <div v-if="destinos.length" class="text-caption" :class="somaPercBate(destinos) ? 'text-grey-7' : 'text-orange-8'">
            Soma: {{ fmt(somaPercDestinos, 1) }}%
          </div>
        </div>
      </div>
    </q-card-section>
  </q-card>

  <q-dialog v-model="dialogAberto">
    <q-card style="width: 900px; max-width: 95vw">
      <q-form @submit="salvar">
        <q-card-section>
          <div class="text-subtitle1 q-mb-md">Origem / Destino do grão</div>
          <div class="row q-col-gutter-lg">
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 text-grey-8 q-mb-xs">Origem do grão</div>
              <div
                v-for="(p, i) in origensEdicao"
                :key="'o' + i"
                class="row q-col-gutter-sm items-center q-mb-xs"
              >
                <!-- `bottom-slots`: os campos ao lado têm :rules e o Quasar reserva 20px
                     embaixo deles (q-field--with-bottom). Sem reservar aqui também, este
                     select desce 10px em relação ao resto da linha. -->
                <SelectContaTipo
                  v-model="p.contatipo"
                  papel="ORIGEM"
                  label="Origem"
                  bottom-slots
                  class="col-4"
                  @update:model-value="onTipoChange(p)"
                />
                <SelectTalhao
                  v-if="p.contatipo === 'PLANTIO'"
                  :model-value="p.codplantio"
                  :codsafra="edicao.codsafra"
                  class="col"
                  lazy-rules
                  :rules="[(v) => !!v || 'Selecione o talhão.']"
                  @update:model-value="(v) => onEntidade(p, v)"
                  @select="onPlantioSelecionado"
                />
                <SelectUnidade
                  v-else-if="p.contatipo === 'UNIDADE'"
                  :model-value="p.codunidadearmazenadora"
                  class="col"
                  lazy-rules
                  :rules="[(v) => !!v || 'Selecione a unidade.']"
                  @update:model-value="(v) => onEntidade(p, v)"
                />
                <SelectContrato
                  v-else
                  :model-value="p.codcontrato"
                  operacao="compra"
                  class="col"
                  lazy-rules
                  :rules="[(v) => !!v || 'Selecione o contrato.']"
                  @update:model-value="(v) => onEntidade(p, v)"
                />
                <MgInputValor
                  v-model="p.percentual"
                  :decimals="1"
                  suffix="%"
                  :min="0"
                  :max="100"
                  label="%"
                  :readonly="origensEdicao.length === 1"
                  class="col-3"
                  lazy-rules
                  :rules="[
                    () => !finalizando || somaPercBate(origensEdicao) || 'Soma dos % deve ser 100',
                  ]"
                />
                <!-- Sempre visível: uma linha semeada e impreenchível (ex.: safra sem
                     talhão) travaria o registro sem saída. Remover é a válvula. -->
                <q-btn
                  flat
                  round
                  color="grey-7"
                  icon="close"
                  class="col-auto ponto-remover"
                  @click="removerPonto(p)"
                />
              </div>
              <div
                v-if="origensEdicao.length"
                class="text-caption q-mb-xs"
                :class="somaPercBate(origensEdicao) ? 'text-grey-7' : 'text-orange-8'"
              >
                Soma: {{ fmt(somaPercentual(origensEdicao), 1) }}%
              </div>
              <q-btn flat dense color="primary" icon="add" label="Origem" @click="addPonto('ORIGEM')" />
            </div>

            <div class="col-12 col-md-6">
              <div class="text-subtitle2 text-grey-8 q-mb-xs">Destino do grão</div>
              <div v-for="(p, i) in destinosEdicao" :key="'d' + i" class="q-mb-xs">
                <div class="row q-col-gutter-sm items-center">
                  <!-- `bottom-slots`: mesmo motivo da coluna de origem. -->
                  <SelectContaTipo
                    v-model="p.contatipo"
                    papel="DESTINO"
                    label="Destino"
                    bottom-slots
                    class="col-4"
                    @update:model-value="onTipoChange(p)"
                  />
                  <SelectUnidade
                    v-if="p.contatipo === 'UNIDADE'"
                    :model-value="p.codunidadearmazenadora"
                    class="col"
                    lazy-rules
                    :rules="[(v) => !!v || 'Selecione a unidade.']"
                    @update:model-value="(v) => onEntidade(p, v)"
                  />
                  <SelectContrato
                    v-else
                    :model-value="p.codcontrato"
                    operacao="venda"
                    class="col"
                    lazy-rules
                    :rules="[(v) => !!v || 'Selecione o contrato.']"
                    @update:model-value="(v) => onEntidade(p, v)"
                  />
                  <MgInputValor
                    v-model="p.percentual"
                    :decimals="1"
                    suffix="%"
                    :min="0"
                    :max="100"
                    label="%"
                    :readonly="destinosEdicao.length === 1"
                    class="col-3"
                    lazy-rules
                    :rules="[
                      () => !finalizando || somaPercBate(destinosEdicao) || 'Soma dos % deve ser 100',
                    ]"
                  />
                  <q-btn
                    flat
                    round
                    color="grey-7"
                    icon="close"
                    class="col-auto ponto-remover"
                    @click="removerPonto(p)"
                  />
                </div>
                <div v-if="p.contatipo === 'CONTRATO' && p.codcontrato" class="text-caption">
                  <span v-if="saldoContrato(p.codcontrato) === Infinity" class="text-deep-purple-7">
                    <q-icon name="all_inclusive" /> Volume em aberto
                  </span>
                  <span
                    v-else
                    :class="
                      (kgDoPonto(p) || 0) > saldoContrato(p.codcontrato) + 1
                        ? 'text-negative text-weight-medium'
                        : 'text-grey-6'
                    "
                  >
                    Saldo a entregar: {{ fmt(saldoContrato(p.codcontrato)) }} kg
                    <span v-if="kgDoPonto(p)"> · esta carga ≈ {{ fmt(kgDoPonto(p)) }} kg</span>
                  </span>
                </div>
                <div
                  v-if="mostrarFiscal && p.contatipo === 'CONTRATO'"
                  class="row q-col-gutter-sm q-mt-xs"
                >
                  <q-input v-model="p.numeronf" label="Nº NF" outlined class="col" />
                  <MgInputValor v-model="p.valornf" :decimals="2" prefix="R$" label="Valor NF" class="col" />
                </div>
              </div>
              <div
                v-if="destinosEdicao.length"
                class="text-caption q-mb-xs"
                :class="somaPercBate(destinosEdicao) ? 'text-grey-7' : 'text-orange-8'"
              >
                Soma: {{ fmt(somaPercentual(destinosEdicao), 1) }}%
              </div>
              <q-btn flat dense color="primary" icon="add" label="Destino" @click="addPonto('DESTINO')" />
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

<style scoped>
/* Os campos da linha reservam 20px embaixo pra mensagem de validação; sem o
   mesmo desconto, o botão centraliza na linha inteira e fica abaixo da caixa. */
.ponto-remover {
  margin-bottom: 20px;
}
</style>
