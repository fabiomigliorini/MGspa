<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'

const safras = ref([])
const codsafra = ref(null)
const kpis = ref(null)
const saldos = ref([])
const extrato = ref([])
const unidades = ref([])
const contratos = ref([])
const plantios = ref([])
const carregando = ref(false)

const safraSel = computed(() => safras.value.find((s) => s.codsafra === codsafra.value) || null)
const pesosaca = computed(() => safraSel.value?.Cultura?.pesosaca || 60)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function sc(kg) {
  return fmt((Number(kg) || 0) / pesosaca.value, 0)
}

async function carregarSafras() {
  const { data } = await api.get('v1/safra')
  safras.value = data.data ?? data
  if (!codsafra.value && safras.value.length) {
    codsafra.value = (safras.value.find((s) => !s.inativo) || safras.value[0]).codsafra
  }
}

async function carregarDados() {
  if (!codsafra.value) return
  carregando.value = true
  try {
    const [k, s, e] = await Promise.all([
      api.get(`v1/safra/${codsafra.value}/comercial`),
      api.get('v1/movimento-grao/saldos-unidades', { params: { codsafra: codsafra.value } }),
      api.get('v1/movimento-grao', {
        params: { codsafra: codsafra.value, sort: '-data,-codmovimentograo' },
      }),
    ])
    kpis.value = k.data
    saldos.value = s.data
    extrato.value = e.data.data ?? e.data
  } catch (err) {
    notifyError(err)
  } finally {
    carregando.value = false
  }
}

async function carregarCadastros() {
  const [u, c] = await Promise.all([api.get('v1/unidade-armazenadora'), api.get('v1/contrato')])
  unidades.value = u.data.data ?? u.data
  contratos.value = c.data.data ?? c.data
}

watch(codsafra, async (cod) => {
  if (!cod) return
  const { data } = await api.get(`v1/safra/${cod}/plantio`)
  plantios.value = data.data ?? data
  carregarDados()
})

// ---- Ajuste manual ----
const dialog = ref(false)
const salvando = ref(false)
const form = ref({})

function rotuloConta(m) {
  if (m.contatipo === 'UNIDADE')
    return m.UnidadeArmazenadora?.unidadearmazenadora || `Unidade ${m.codunidadearmazenadora}`
  if (m.contatipo === 'CONTRATO')
    return m.Contrato
      ? `${m.Contrato.contrato} — ${m.Contrato.Pessoa?.fantasia || m.Contrato.Pessoa?.pessoa || ''}`
      : `Contrato ${m.codcontrato}`
  if (m.contatipo === 'PLANTIO') return m.Plantio?.talhao || `Talhão ${m.codplantio}`
  return m.contatipo
}

function novoAjuste() {
  form.value = {
    codsafra: codsafra.value,
    papel: 'DESTINO',
    contatipo: 'UNIDADE',
    codplantio: null,
    codunidadearmazenadora: null,
    codcontrato: null,
    bruto: 0,
    desconto: 0,
    observacao: null,
  }
  dialog.value = true
}

const liquidoForm = computed(() => Number(form.value.bruto || 0) - Number(form.value.desconto || 0))

async function salvarAjuste() {
  salvando.value = true
  try {
    await api.post('v1/movimento-grao', { ...form.value, liquido: liquidoForm.value })
    notifySuccess('Ajuste lançado')
    dialog.value = false
    await carregarDados()
  } catch (e) {
    notifyError(e)
  } finally {
    salvando.value = false
  }
}

async function estornar(m) {
  try {
    await api.post(`v1/movimento-grao/${m.codmovimentograo}/inativo`)
    notifySuccess('Lançamento estornado')
    await carregarDados()
  } catch (e) {
    notifyError(e)
  }
}

onMounted(async () => {
  await carregarSafras()
  await carregarCadastros()
  await carregarDados()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div class="row items-center q-col-gutter-md q-mb-md">
        <div class="text-h5 col-auto">Estoque &amp; Extrato</div>
        <q-select
          v-model="codsafra"
          :options="safras"
          option-value="codsafra"
          option-label="safra"
          emit-value
          map-options
          outlined
          label="Safra"
          class="col"
        />
        <q-btn flat round size="sm" color="primary" icon="add" @click="novoAjuste">
          <q-tooltip>Ajuste manual</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          size="sm"
          color="grey-7"
          icon="refresh"
          :loading="carregando"
          @click="carregarDados"
        >
          <q-tooltip>Atualizar</q-tooltip>
        </q-btn>
      </div>

      <!-- KPIs -->
      <div v-if="kpis" class="row q-col-gutter-md q-mb-md">
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">Estoque depositado</div>
              <div class="text-h6 text-amber-9">{{ sc(kpis.estoquekg) }} sc</div>
              <div class="text-caption text-grey-6">{{ fmt(kpis.estoquekg) }} kg</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">A colher</div>
              <div class="text-h6 text-light-green-8">{{ sc(kpis.acolherkg) }} sc</div>
              <div class="text-caption text-grey-6">{{ fmt(kpis.acolherkg) }} kg</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">Disponível p/ negociar</div>
              <div
                class="text-h6"
                :class="kpis.disponivelkg < 0 ? 'text-negative' : 'text-primary'"
              >
                {{ sc(kpis.disponivelkg) }} sc
              </div>
              <div class="text-caption text-grey-6">{{ fmt(kpis.disponivelkg) }} kg</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">Entregue</div>
              <div class="text-h6 text-teal-8">{{ sc(kpis.entreguekg) }} sc</div>
              <div class="text-caption text-grey-6">{{ fmt(kpis.entreguekg) }} kg</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Estoque por unidade -->
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="text-subtitle2">Estoque por unidade armazenadora</q-card-section>
        <q-list separator>
          <q-item v-for="u in saldos" :key="u.codunidadearmazenadora">
            <q-item-section avatar><q-icon name="warehouse" color="amber-8" /></q-item-section>
            <q-item-section>
              <q-item-label>{{ u.unidadearmazenadora }}</q-item-label>
              <q-item-label caption>{{ u.tipo }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-weight-medium">{{ sc(u.saldokg) }} sc</q-item-label>
              <q-item-label caption>{{ fmt(u.saldokg) }} kg</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="!saldos.length">
            <q-item-section class="text-grey-6 text-center q-pa-md">Sem estoque</q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Extrato -->
      <q-card flat bordered>
        <q-card-section class="text-subtitle2">Extrato de movimentos</q-card-section>
        <q-list separator>
          <q-item v-for="m in extrato" :key="m.codmovimentograo">
            <q-item-section avatar>
              <q-icon
                :name="m.manual ? 'edit_note' : 'sync_alt'"
                :color="m.manual ? 'deep-purple-6' : 'grey-7'"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ m.contatipo }} · {{ rotuloConta(m) }}
                <q-badge v-if="m.manual" color="deep-purple-6" label="manual" class="q-ml-xs" />
              </q-item-label>
              <q-item-label caption>
                {{ m.papel }} · {{ new Date(m.data).toLocaleDateString('pt-BR') }}
                <span v-if="m.observacao"> · {{ m.observacao }}</span>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label
                :class="Number(m.liquido) < 0 ? 'text-negative' : 'text-green-9'"
                class="text-weight-medium"
              >
                {{ Number(m.liquido) > 0 ? '+' : '' }}{{ fmt(m.liquido) }} kg
              </q-item-label>
              <q-item-label caption>{{ sc(Math.abs(m.liquido)) }} sc</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn
                v-if="m.manual"
                flat
                round
                size="sm"
                color="grey-7"
                icon="undo"
                @click="estornar(m)"
              >
                <q-tooltip>Estornar lançamento manual</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
          <q-item v-if="!extrato.length">
            <q-item-section class="text-grey-6 text-center q-pa-md">Sem movimentos</q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <!-- Ajuste manual -->
    <q-dialog v-model="dialog">
      <q-card flat style="width: 520px; max-width: 90vw">
        <q-form @submit.prevent="salvarAjuste">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">Ajuste manual</div>
            <div class="text-caption">Lançamento complementar (não altera os automáticos)</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <div class="row q-col-gutter-md">
              <div class="col-6">
                <q-select
                  v-model="form.contatipo"
                  label="Conta"
                  outlined
                  :options="['UNIDADE', 'CONTRATO', 'PLANTIO']"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div class="col-6">
                <q-select
                  v-model="form.papel"
                  label="Papel"
                  outlined
                  :options="['ORIGEM', 'DESTINO']"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div v-if="form.contatipo === 'UNIDADE'" class="col-12">
                <q-select
                  v-model="form.codunidadearmazenadora"
                  label="Unidade"
                  outlined
                  :options="unidades"
                  option-value="codunidadearmazenadora"
                  option-label="unidadearmazenadora"
                  emit-value
                  map-options
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div v-else-if="form.contatipo === 'CONTRATO'" class="col-12">
                <q-select
                  v-model="form.codcontrato"
                  label="Contrato"
                  outlined
                  :options="contratos"
                  option-value="codcontrato"
                  :option-label="
                    (c) => `${c.contrato} — ${c.Pessoa?.fantasia || c.Pessoa?.pessoa || ''}`
                  "
                  emit-value
                  map-options
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div v-else class="col-12">
                <q-select
                  v-model="form.codplantio"
                  label="Talhão"
                  outlined
                  :options="plantios"
                  option-value="codplantio"
                  option-label="talhao"
                  emit-value
                  map-options
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div class="col-6">
                <MgInputValor v-model="form.bruto" :decimals="0" suffix="kg" label="Bruto" />
              </div>
              <div class="col-6">
                <MgInputValor v-model="form.desconto" :decimals="0" suffix="kg" label="Desconto" />
              </div>
              <div class="col-12">
                <q-banner dense class="bg-grey-2 text-grey-9">
                  Líquido = bruto − desconto = <b>{{ fmt(liquidoForm) }} kg</b>
                </q-banner>
              </div>
              <div class="col-12">
                <q-input
                  v-model="form.observacao"
                  label="Observação"
                  type="textarea"
                  autogrow
                  outlined
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn type="submit" flat label="Lançar" color="primary" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
