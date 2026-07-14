<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from 'src/services/api'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

// Form único de novo/editar contrato (recebe :cad). Cultura e safra não
// aparecem: o contrato vive dentro da safra, então o pai força esse vínculo
// via :fixar. Usado na grid da safra (ContratosSafra) e na edição do
// detalhe (ContratoDetailPage). Precificação (preço/moeda/isenção) vive nas
// fixações; NF (natureza/pessoa) no plano de notas — ambos na tela do contrato.
const props = defineProps({
  cad: { type: Object, required: true },
  // { codsafra, codcultura } — fixa o vínculo no salvar (criação dentro da safra)
  fixar: { type: Object, default: null },
})
const emit = defineEmits(['saved'])

// Indireção (padrão SafraForm): v-model escreve no objeto reativo do cad sem
// disparar vue/no-mutating-props.
const cad = computed(() => props.cad)

// Tabela de classificação do contrato (opcional): opções = as tabelas da cultura
// do contrato. A entrega deste contrato usa esta tabela como padrão de desconto.
const opcoesTabela = ref([])
onMounted(async () => {
  const codcultura = props.fixar?.codcultura ?? props.cad.form?.codcultura
  if (!codcultura) return
  try {
    const { data } = await api.get('v1/tabela-classificacao', { params: { codcultura } })
    opcoesTabela.value = (data.data ?? data).map((t) => ({
      label: t.tabelaclassificacao,
      value: t.codtabelaclassificacao,
    }))
  } catch {
    // silencioso — a resolução cai no padrão da cultura se não escolher
  }
})

const operacoes = [
  { label: 'Venda', value: 'VENDA' },
  { label: 'Compra', value: 'COMPRA' },
]
const comissaoTipos = [
  { label: '%', value: 'PERCENTUAL' },
  { label: 'R$/sc', value: 'SACA' },
  { label: 'R$ total', value: 'TOTAL' },
]

// Comissão total resolvida do tipo (R$/sc, R$ total) — info gerencial. O tipo %
// depende do preço, que agora vive na fixação; nesse caso fica a cargo do
// preenchimento manual (sem preço único no contrato).
const comissaoTotal = computed(() => {
  const v = Number(cad.value.form.comissaovalor) || 0
  const q = Number(cad.value.form.quantidade) || 0
  switch (cad.value.form.comissaotipo) {
    case 'TOTAL':
      return v
    case 'SACA':
      return v * q
    default:
      return cad.value.form.comissaototal || 0
  }
})

// Datas ISO (YYYY-MM-DD): primeiro e último dia do mês.
function inicioDoMes(iso) {
  return `${String(iso).slice(0, 7)}-01`
}
function fimDoMes(iso) {
  const [a, m] = String(iso).slice(0, 10).split('-').map(Number)
  const ultimo = new Date(a, m, 0).getDate()
  return `${a}-${String(m).padStart(2, '0')}-${String(ultimo).padStart(2, '0')}`
}

// Auto-completa o par de embarque SÓ na digitação do usuário (não na carga da
// edição, que antes disparava watcher e mexia no dado gravado): ao escolher um
// lado com o OUTRO vazio, sugere o mês inteiro. Um lado invertido NÃO é corrigido
// em silêncio — a regra embarqueInvertido é quem sinaliza.
function onEmbarqueInicio(val) {
  cad.value.form.embarqueinicio = val
  if (val && !cad.value.form.embarquefim) {
    cad.value.form.embarquefim = fimDoMes(val)
  }
}
function onEmbarqueFim(val) {
  cad.value.form.embarquefim = val
  if (val && !cad.value.form.embarqueinicio) {
    cad.value.form.embarqueinicio = inicioDoMes(val)
  }
}

// Espelho da trava do backend (embarquefim after_or_equal:embarqueinicio). Lê o
// ISO do v-model direto (não passa pelos :rules em display BR do MgInputData).
const embarqueInvertido = computed(() => {
  const i = cad.value.form.embarqueinicio
  const f = cad.value.form.embarquefim
  return !!i && !!f && String(f).slice(0, 10) < String(i).slice(0, 10)
})

async function salvar() {
  const saved = await props.cad.salvar((f) => ({
    ...f,
    comissaototal: comissaoTotal.value,
    ...(props.fixar || {}),
  }))
  // saved (com codcontrato) sobe pro pai — na criação, ele navega pra tela do contrato.
  if (!props.cad.dialog) emit('saved', saved)
}
</script>

<template>
  <q-dialog v-model="cad.dialog">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="bg-primary text-white q-py-sm">
          <div class="text-h6">{{ cad.isNovo ? 'Novo Contrato' : 'Editar Contrato' }}</div>
        </q-card-section>

        <!-- NOVO: rascunho mínimo (só identificação). Quantidade, preço, fixação,
             entrega e fiscal configuram-se depois na tela do contrato. -->
        <q-card-section class="row q-col-gutter-md q-pt-md">
          <div class="col-12 text-overline text-grey-7">Identificação</div>

          <!-- DATA -->
          <div class="col-12 col-sm-3">
            <MgInputData v-model="cad.form.datacontrato" label="Data do contrato" type="date" />
          </div>

          <!-- OPERACAO -->
          <div class="col-12 col-sm-3">
            <q-select
              v-model="cad.form.operacao"
              :options="operacoes"
              emit-value
              map-options
              outlined
              label="Operação"
            />
          </div>

          <!-- FILIAL -->
          <div class="col-12 col-sm-3">
            <MgSelectFilial
              v-model="cad.form.codfilial"
              label="Filial"
              autofocus
              lazy-rules
              :rules="[(v) => !!v || 'Informe a filial']"
            />
          </div>

          <!-- NUMERO -->
          <div class="col-12 col-sm-3">
            <q-input
              v-model="cad.form.contrato"
              label="Nº Nosso"
              outlined
              lazy-rules
              :rules="[(v) => !!v || 'Informe o nº do contrato']"
            />
          </div>

          <!-- QTD (vazio = volume em aberto / leva o saldo do silo) -->
          <div class="col-12 col-sm-3">
            <MgInputValor
              v-model="cad.form.quantidade"
              :decimals="0"
              suffix="sc"
              label="Quantidade"
              hint="Vazio = volume em aberto"
              lazy-rules
              :rules="[(v) => v == null || v > 0 || 'Quantidade inválida']"
            />
          </div>

          <!-- INICIO (opcional: backend nullable; rascunho mínimo só identifica) -->
          <div class="col-6 col-sm-3">
            <MgInputData
              :model-value="cad.form.embarqueinicio"
              label="Embarque de"
              type="date"
              @update:model-value="onEmbarqueInicio"
            />
          </div>

          <!-- FIM (opcional; cross-field: fim >= início quando ambos preenchidos) -->
          <div class="col-6 col-sm-3">
            <MgInputData
              :model-value="cad.form.embarquefim"
              label="Embarque até"
              type="date"
              lazy-rules
              :rules="[() => !embarqueInvertido || 'Fim do embarque antes do início']"
              @update:model-value="onEmbarqueFim"
            />
          </div>

          <!-- LOCAL -->
          <div class="col-12 col-sm-3">
            <q-input v-model="cad.form.localentrega" label="Local / FOB-CIF" outlined />
          </div>

          <!-- CONTRAPARTE -->
          <div class="col-12 col-sm-9">
            <MgSelectPessoa
              v-model="cad.form.codpessoa"
              label="Contraparte"
              lazy-rules
              :rules="[(v) => !!v || 'Informe a contraparte']"
            />
          </div>

          <!-- NUMERO DA CONTRAPARTE -->
          <div class="col-12 col-sm-3">
            <q-input v-model="cad.form.numerocontraparte" label="Nº Contraparte" outlined />
          </div>

          <!-- CORRETORA -->
          <div class="col-12 col-sm-9">
            <MgSelectPessoa v-model="cad.form.codpessoacorretora" label="Corretora" />
          </div>

          <!-- NUMERO DA CORRETORA -->
          <div class="col-12 col-sm-3">
            <q-input v-model="cad.form.numerocorretora" label="Nº Corretora" outlined />
          </div>

          <template v-if="cad.form.codpessoacorretora">
            <!-- TIPO COMISSÃO (required_with corretora) -->
            <div class="col-6 col-sm-4">
              <q-select
                v-model="cad.form.comissaotipo"
                :options="comissaoTipos"
                emit-value
                map-options
                outlined
                label="Tipo de comissão"
                lazy-rules
                :rules="[(v) => !!v || 'Informe o tipo']"
              />
            </div>

            <!-- VALOR COMISSAO (required_with corretora) -->
            <div class="col-6 col-sm-3">
              <MgInputValor
                v-model="cad.form.comissaovalor"
                :decimals="2"
                label="Comissão"
                lazy-rules
                :rules="[(v) => v != null || 'Informe a comissão']"
              />
            </div>

            <!-- TOTAL COMISSAO -->
            <div class="col-12 col-sm-5">
              <MgInputValor
                :model-value="comissaoTotal"
                label="Comissão total"
                input-class="text-right"
                prefix="R$"
                readonly
                outlined
              />
            </div>
          </template>

          <!-- COOPERATIVA -->
          <div class="col-12 col-sm-9">
            <MgSelectPessoa v-model="cad.form.codpessoacooperativa" label="Cooperativa" />
          </div>

          <!-- NUMERO DA COOPERATIVA -->
          <div class="col-12 col-sm-3">
            <q-input v-model="cad.form.numerocooperativa" label="Nº Cooperativa" outlined />
          </div>

          <!-- BARTER (settlement em insumos: troca por insumos) -->
          <div class="col-12 col-sm-12">
            <q-toggle
              v-model="cad.form.barter"
              color="deep-purple-6"
              label="Contrato de Barter (troca de grãos por insumos)"
            />
          </div>

          <!-- TABELA DE CLASSIFICAÇÃO (opcional; senão usa a padrão da cultura) -->
          <div class="col-12 col-sm-6">
            <q-select
              v-model="cad.form.codtabelaclassificacao"
              :options="opcoesTabela"
              emit-value
              map-options
              outlined
              clearable
              label="Tabela de classificação"
              hint="Padrão de desconto da entrega deste contrato"
            />
          </div>

          <!-- OBSERVACOES -->
          <div class="col-12">
            <q-input
              v-model="cad.form.observacao"
              label="Observações"
              type="textarea"
              autogrow
              outlined
            />
          </div>
        </q-card-section>

        <q-separator />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn type="submit" flat label="Salvar" color="primary" :loading="cad.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
