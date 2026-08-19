<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro, statusColaboradorLabel, statusColaboradorColor } from 'src/utils/rhFormatters'
import MgSelectUnidadeNegocio from '@components/MgSelectUnidadeNegocio.vue'
import MgSelectTipoSetor from '@components/MgSelectTipoSetor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  setor: { type: Object, default: null },
  codunidadenegocio: { type: [Number, String], default: null },
  codperiodo: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'salvo'])

const $q = useQuasar()
const sRh = rhStore()

const dialog = ref(false)
const cad = ref({})
const salvando = ref(false)

// Lista local: `props.setor` é uma cópia rasa congelada no clique do lápis, o pai
// nunca a reatribui. Sem cópia local o diálogo nunca veria a lista mudar.
const colaboradores = ref([])
const ocupado = ref(null) // codperiodocolaborador em operação
const alterado = ref(false) // houve remoção → pai precisa recarregar

const isNovo = computed(() => !cad.value.codsetor)

watch(
  () => props.modelValue,
  (v) => {
    dialog.value = v
    if (v) {
      cad.value = props.setor
        ? { ...props.setor }
        : {
            setor: '',
            codunidadenegocio: props.codunidadenegocio ?? null,
            codtiposetor: null,
            indicadorvendedor: false,
            indicadorcaixa: false,
            indicadorcoletivo: false,
          }
      colaboradores.value = props.setor?.colaboradores ? [...props.setor.colaboradores] : []
      alterado.value = false
    }
  },
)
// O `salvo` sai no FECHAMENTO, não a cada lixeira: a remoção é imediata e o
// diálogo continua aberto para várias — avisar o pai a cada clique recarregaria
// a unidade inteira à toa por cima de uma lista local já correta.
watch(dialog, (v) => {
  emit('update:modelValue', v)
  if (!v && alterado.value) {
    alterado.value = false
    emit('salvo')
  }
})

const submit = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    const payload = {
      setor: cad.value.setor,
      codunidadenegocio: cad.value.codunidadenegocio,
      codtiposetor: cad.value.codtiposetor,
      indicadorvendedor: !!cad.value.indicadorvendedor,
      indicadorcaixa: !!cad.value.indicadorcaixa,
      indicadorcoletivo: !!cad.value.indicadorcoletivo,
    }
    if (isNovo.value) {
      await sRh.criarSetor(payload)
    } else {
      await sRh.atualizarSetor(cad.value.codsetor, payload)
    }
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: isNovo.value ? 'Setor criado' : 'Setor atualizado',
    })
    // Zera antes de fechar: o watch(dialog) emitiria um segundo `salvo`.
    alterado.value = false
    dialog.value = false
    emit('salvo')
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao salvar setor'),
    })
  } finally {
    salvando.value = false
  }
}

const notificar = (color, icon, message) => $q.notify({ color, textColor: 'white', icon, message })

const tirarDaLista = (c) => {
  colaboradores.value = colaboradores.value.filter(
    (x) => x.codperiodocolaborador !== c.codperiodocolaborador,
  )
  alterado.value = true
}

const removerColaborador = (c) => {
  $q.dialog({
    title: 'Remover Colaborador',
    message: `Remover "${c.nome}" do período? Ele poderá ser adicionado em outro setor; metas e saldos de indicadores não são afetados. As rubricas configuradas para ele neste período serão perdidas.`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Remover', color: 'red-5', flat: true },
  }).onOk(async () => {
    ocupado.value = c.codperiodocolaborador
    try {
      await sRh.excluirColaborador(props.codperiodo, c.codperiodocolaborador)
      tirarDaLista(c)
      notificar('green-5', 'done', 'Colaborador removido')
    } catch (error) {
      notificar('red-5', 'error', extrairErro(error, 'Erro ao remover colaborador'))
    } finally {
      ocupado.value = null
    }
  })
}
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <template v-if="isNovo">NOVO SETOR</template>
        <template v-else>EDITAR SETOR</template>
        <q-space />
        <MgInfoCriacao v-if="!isNovo" :registro="cad" />
      </q-card-section>

      <q-form @submit.prevent="submit()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                outlined
                v-model="cad.setor"
                label="Descrição"
                autofocus
                :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectUnidadeNegocio
                v-model="cad.codunidadenegocio"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectTipoSetor
                v-model="cad.codtiposetor"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <small class="text-grey">Indicadores gerados:</small>
              <div class="row items-center">
                <q-toggle v-model="cad.indicadorvendedor" label="Vendedor" />
                <q-toggle v-model="cad.indicadorcaixa" label="Caixa" />
                <q-toggle v-model="cad.indicadorcoletivo" label="Coletivo" />
              </div>
            </div>
          </div>
        </q-card-section>

        <!-- COLABORADORES DO SETOR — daqui pra baixo a ação é IMEDIATA, não
             depende do Salvar. O separador marca essa fronteira. -->
        <template v-if="!isNovo">
          <q-separator inset />

          <q-card-section>
            <div class="row items-center q-mb-sm">
              <small class="text-grey">Colaboradores do setor:</small>
              <q-space />
              <span class="text-caption text-grey-7">{{ colaboradores.length }}</span>
            </div>

            <q-list bordered separator v-if="colaboradores.length > 0">
              <q-item v-for="c in colaboradores" :key="c.codperiodocolaborador">
                <q-item-section>
                  <q-item-label>{{ c.nome }}</q-item-label>
                  <q-item-label caption v-if="c.cargo">{{ c.cargo }}</q-item-label>
                </q-item-section>

                <!-- O badge é quem explica por que a lixeira está travada: o
                     Quasar suprime q-tooltip em botão desabilitado. -->
                <q-item-section side v-if="c.status === 'E'">
                  <q-badge
                    :color="statusColaboradorColor(c.status)"
                    :label="statusColaboradorLabel(c.status)"
                  />
                </q-item-section>

                <q-item-section side>
                  <q-btn
                    flat
                    dense
                    round
                    type="button"
                    icon="delete"
                    size="sm"
                    color="grey-7"
                    :disable="c.status === 'E'"
                    :loading="ocupado === c.codperiodocolaborador"
                    @click="removerColaborador(c)"
                  >
                    <q-tooltip>Remover do período</q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>

            <div v-else class="q-pa-md text-center text-grey">Nenhum colaborador neste setor</div>
          </q-card-section>
        </template>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
