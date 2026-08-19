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
// Remoção é PENDENTE: a lixeira só marca, o DELETE sai no Salvar. Assim o
// Cancelar realmente desfaz.
const marcados = ref([])
// Só vira true quando um DELETE de fato passou — aí o pai precisa recarregar
// mesmo que o diálogo continue aberto por causa de alguma falha.
const alterado = ref(false)

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
      marcados.value = []
      alterado.value = false
    }
  },
)
// Rede de segurança: se algum DELETE passou mas o diálogo ficou aberto (outro
// falhou), o pai ainda precisa recarregar quando o usuário fechar no Cancelar.
watch(dialog, (v) => {
  emit('update:modelValue', v)
  if (!v && alterado.value) {
    alterado.value = false
    emit('salvo')
  }
})

const notificar = (color, icon, message) => $q.notify({ color, textColor: 'white', icon, message })

const marcado = (c) => marcados.value.includes(c.codperiodocolaborador)

// Só alterna a marca — nenhuma chamada à API aqui.
const toggleRemover = (c) => {
  marcados.value = marcado(c)
    ? marcados.value.filter((x) => x !== c.codperiodocolaborador)
    : [...marcados.value, c.codperiodocolaborador]
}

// Aplica as remoções pendentes. Uma a uma: não há transação entre requisições,
// então quem passa some da lista e quem falha é devolvido (desmarcado) com o
// motivo — tipicamente o 422 de acerto ativo.
const aplicarRemocoes = async () => {
  const falhas = []
  for (const codpc of [...marcados.value]) {
    const c = colaboradores.value.find((x) => x.codperiodocolaborador === codpc)
    if (!c) continue
    try {
      await sRh.excluirColaborador(props.codperiodo, codpc)
      colaboradores.value = colaboradores.value.filter((x) => x.codperiodocolaborador !== codpc)
      marcados.value = marcados.value.filter((x) => x !== codpc)
      alterado.value = true
    } catch (error) {
      marcados.value = marcados.value.filter((x) => x !== codpc)
      falhas.push(`${c.nome}: ${extrairErro(error, 'erro ao remover')}`)
    }
  }
  return falhas
}

const submit = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    // As remoções vêm primeiro: se alguma falhar, o diálogo fica aberto com o
    // motivo e o usuário decide, em vez de fechar deixando o erro passar batido.
    const falhas = isNovo.value ? [] : await aplicarRemocoes()

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

    if (falhas.length > 0) {
      notificar('red-5', 'error', falhas.join(' | '))
      return
    }

    notificar('green-5', 'done', isNovo.value ? 'Setor criado' : 'Setor atualizado')
    // Zera antes de fechar: o watch(dialog) emitiria um segundo `salvo`.
    alterado.value = false
    dialog.value = false
    emit('salvo')
  } catch (error) {
    notificar('red-5', 'error', extrairErro(error, 'Erro ao salvar setor'))
  } finally {
    salvando.value = false
  }
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

        <!-- COLABORADORES DO SETOR — a lixeira só MARCA; o DELETE sai no Salvar,
             para o Cancelar poder desfazer. -->
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
                  <q-item-label :class="marcado(c) ? 'text-strike text-grey-6' : ''">
                    {{ c.nome }}
                  </q-item-label>
                  <q-item-label
                    caption
                    v-if="c.cargo"
                    :class="marcado(c) ? 'text-strike text-grey-6' : ''"
                  >
                    {{ c.cargo }}
                  </q-item-label>
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
                    :icon="marcado(c) ? 'undo' : 'delete'"
                    size="sm"
                    :color="marcado(c) ? 'red-5' : 'grey-7'"
                    :disable="c.status === 'E'"
                    @click="toggleRemover(c)"
                  >
                    <q-tooltip>
                      {{ marcado(c) ? 'Desfazer remoção' : 'Remover do período' }}
                    </q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>

            <div v-else class="q-pa-md text-center text-grey">Nenhum colaborador neste setor</div>

            <div v-if="marcados.length > 0" class="text-caption text-red-7 q-mt-sm">
              {{ marcados.length }} colaborador(es) será(ão) removido(s) do período ao salvar. Metas
              e saldos de indicadores não são afetados; as rubricas configuradas para eles neste
              período serão perdidas.
            </div>
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
