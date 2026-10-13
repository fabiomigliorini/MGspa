<script setup>
import { ref, computed, watch } from 'vue'
import { dispositivoStore } from 'stores/dispositivo'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectSetor from '@components/MgSelectSetor.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgSelectEstoqueLocal from '@components/MgSelectEstoqueLocal.vue'
import MgSelectNaturezaOperacao from '@components/MgSelectNaturezaOperacao.vue'
import MgSelectImpressora from '@components/MgSelectImpressora.vue'
import MgSelectMaquineta from '@components/MgSelectMaquineta.vue'

// Editar o dispositivo (TASK-46): um formulário só, numa tela, agrupado por contexto — o
// dispositivo (apelido, observações), os negócios (local de estoque, setor, natureza,
// impressora), o pagamento (gaveta de dinheiro, maquineta e PIX pré-selecionados) e o
// monitoramento. A filial não aparece: é a do local de estoque.
// Administrador ou Gerente da filial alteram tudo; no próprio PDV o usuário altera só os padrões
// dos negócios (local de estoque da mesma filial, natureza, impressora, maquineta e PIX), o resto
// fica desabilitado. O servidor recusa o que ele não pode.
const props = defineProps({
  modelValue: Boolean,
  pdv: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue'])

const sDispositivo = dispositivoStore()

const model = ref({})
// a filial do local de estoque: filtra a gaveta e as maquinetas
const codfilial = ref(null)

const cadastro = computed(() => !!sDispositivo.pode.cadastro)
const obrigatorio = (val) => !!val || 'Obrigatório'

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return
    codfilial.value = props.pdv.codfilial
    model.value = {
      codpdv: props.pdv.codpdv,
      apelido: props.pdv.apelido,
      codestoquelocal: props.pdv.codestoquelocal,
      codsetor: props.pdv.codsetor,
      codnaturezaoperacao: props.pdv.codnaturezaoperacao,
      impressora: props.pdv.impressora,
      codportador: props.pdv.codportador,
      codmaquineta: props.pdv.codmaquineta,
      codportadorpix: props.pdv.codportadorpix,
      monitoramento: props.pdv.monitoramento,
      minutosesquecido: props.pdv.minutosesquecido,
      observacoes: props.pdv.observacoes,
    }
  },
)

// local de estoque de outra filial muda a filial: a gaveta e a maquineta de antes deixam de valer
function trocarEstoque(estoque) {
  if (!estoque || Number(estoque.codfilial) === Number(codfilial.value)) return
  codfilial.value = estoque.codfilial
  model.value.codportador = null
  model.value.codmaquineta = null
}

async function salvar() {
  if (await sDispositivo.salvar(model.value)) {
    emit('update:modelValue', false)
  }
}
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-h6">Editar dispositivo</q-card-section>
        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-md">
            <div class="col-12 text-subtitle2 text-grey-8">Dispositivo</div>
            <div class="col-12 col-sm-6">
              <MgInput
                v-model="model.apelido"
                label="Apelido"
                maxlength="100"
                :autofocus="cadastro"
                :disable="!cadastro"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInput
                v-model="model.observacoes"
                label="Observações"
                type="textarea"
                autogrow
                :disable="!cadastro"
              />
            </div>

            <div class="col-12 text-subtitle2 text-grey-8">Negócios</div>
            <div class="col-12 col-sm-6">
              <MgSelectEstoqueLocal
                v-model="model.codestoquelocal"
                :codfilial="cadastro ? null : pdv.codfilial"
                :autofocus="!cadastro"
                :rules="[obrigatorio]"
                hide-bottom-space
                @select="trocarEstoque"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectSetor
                v-model="model.codsetor"
                :rules="cadastro ? [obrigatorio] : []"
                hide-bottom-space
                :disable="!cadastro"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectNaturezaOperacao
                v-model="model.codnaturezaoperacao"
                :rules="[obrigatorio]"
                hide-bottom-space
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectImpressora v-model="model.impressora" clearable />
            </div>

            <div class="col-12 text-subtitle2 text-grey-8">Pagamento</div>
            <div class="col-12">
              <MgSelectPortador
                v-model="model.codportador"
                label="Portador da gaveta de dinheiro"
                :tipos="['E']"
                :filiais="codfilial ? [codfilial] : null"
                clearable
                :disable="!cadastro"
              >
                <template #prepend><q-icon name="mdi-cash" color="green-8" /></template>
              </MgSelectPortador>
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectMaquineta
                v-model="model.codmaquineta"
                label="Maquineta padrão"
                :codfilial="codfilial"
                integradas
                clearable
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectPortador v-model="model.codportadorpix" label="PIX padrão" pix clearable />
            </div>

            <!-- livro de ocorrências (TASK-205): sem data, o PDV não é monitorado -->
            <div class="col-12 text-subtitle2 text-grey-8">Monitoramento</div>
            <div class="col-12 col-sm-6">
              <MgInputData
                v-model="model.monitoramento"
                label="Monitorar a partir de"
                hint="Vazio = não monitora"
                :disable="!cadastro"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="model.minutosesquecido"
                label="Minutos até o negócio esquecido"
                :decimals="0"
                :min="10"
                :rules="[(val) => !val || val >= 10 || 'Mínimo de 10 minutos']"
                hide-bottom-space
                :disable="!cadastro"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            flat
            label="Salvar"
            type="submit"
            color="primary"
            :loading="sDispositivo.salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
