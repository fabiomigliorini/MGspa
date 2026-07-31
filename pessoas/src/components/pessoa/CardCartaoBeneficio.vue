<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { colaboradorStore } from 'stores/colaborador'
import { useAuthStore } from 'src/stores'
import { formataTimestamp } from '@components/formatters'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const $q = useQuasar()
const sPessoa = pessoaStore()
const sColaborador = colaboradorStore()
const route = useRoute()
const user = useAuthStore()

const filtroCartao = ref('ativos')
const cartoesFiltrados = computed(() => {
  const lista = sPessoa.item?.ColaboradorCartaoS || []
  if (filtroCartao.value === 'ativos') return lista.filter((x) => !x.inativo)
  return lista
})

// O vínculo é sempre exatamente um (garantia do negócio) — o card resolve o
// colaborador ativo sozinho, sem select. Sem vínculo ativo → add desabilitado.
const colaboradorAtivo = computed(() => sColaborador.colaboradores.find((c) => !c.rescisao) || null)

// E-mail pessoal do perfil (PessoaEmailS), pulando o genérico nfe@ — pré-preenche
// o cadastro do cartão (editável). Usado p/ ativação do cartão Bee.
const emailPerfil = computed(() => {
  const emails = (sPessoa.item?.PessoaEmailS || []).filter((e) => !e.inativo)
  const pessoal = emails.find((e) => e.email && e.email !== 'nfe@mgpapelaria.com.br')
  return pessoal?.email || emails[0]?.email || sPessoa.item?.email || ''
})

const dialogCartao = ref(false)
const isNovo = ref(true)
const salvando = ref(false)
const codcolaboradorcartao = ref(null)
const modelCartao = ref({})

// Carrega os vínculos p/ resolver o colaborador ativo (idempotente).
onMounted(() => {
  if (route.params.id) sColaborador.getColaboradores(route.params.id)
})
watch(
  () => route.params.id,
  (id) => {
    if (id) sColaborador.getColaboradores(id)
  },
)

const abrirNovo = () => {
  isNovo.value = true
  codcolaboradorcartao.value = null
  modelCartao.value = {
    codcolaborador: colaboradorAtivo.value?.codcolaborador,
    numero: '',
    validade: '',
    email: emailPerfil.value,
    observacao: '',
  }
  dialogCartao.value = true
}

// O número do cartão NÃO entra no model da edição: é imutável (o back rejeita
// a chave). Número errado se resolve inativando o cartão e cadastrando outro.
const abrirEditar = (cartao) => {
  isNovo.value = false
  codcolaboradorcartao.value = cartao.codcolaboradorcartao
  modelCartao.value = {
    validade:
      String(cartao.validademes).padStart(2, '0') + String(cartao.validadeano).padStart(2, '0'),
    email: cartao.email || emailPerfil.value,
    observacao: cartao.observacao || '',
  }
  dialogCartao.value = true
}

const submit = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    const validade = modelCartao.value.validade || ''
    const payload = {
      validademes: Number(validade.slice(0, 2)),
      validadeano: Number(validade.slice(2, 4)),
      email: modelCartao.value.email || null,
      observacao: modelCartao.value.observacao || null,
    }
    // número só existe no cadastro — o PUT nunca leva `numero`.
    if (isNovo.value && modelCartao.value.numero) payload.numero = modelCartao.value.numero

    if (isNovo.value) {
      payload.codcolaborador = modelCartao.value.codcolaborador
      await sPessoa.cartaoNovo(route.params.id, payload)
    } else {
      await sPessoa.cartaoSalvar(route.params.id, codcolaboradorcartao.value, payload)
    }
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: isNovo.value ? 'Cartão cadastrado!' : 'Cartão alterado!',
    })
    dialogCartao.value = false
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao salvar cartão',
    })
  } finally {
    salvando.value = false
  }
}

const inativar = async (cod) => {
  try {
    await sPessoa.cartaoInativar(cod, route.params.id)
    $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Inativado!' })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao inativar',
    })
  }
}

const ativar = async (cod) => {
  try {
    await sPessoa.cartaoAtivar(cod, route.params.id)
    $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Ativado!' })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao ativar',
    })
  }
}
</script>

<template>
  <!-- Dialog novo/editar cartão -->
  <q-dialog v-model="dialogCartao">
    <q-card flat style="width: 460px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <template v-if="isNovo">NOVO CARTÃO</template>
        <template v-else>EDITAR CARTÃO</template>
      </q-card-section>

      <q-form @submit.prevent="submit">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- Número só no cadastro: é imutável, não se edita nunca. -->
            <div class="col-12" v-if="isNovo">
              <q-input
                outlined
                autofocus
                v-model="modelCartao.numero"
                label="Número do cartão"
                mask="#### #### #### ####"
                unmasked-value
                :rules="[(v) => !!v && v.length >= 13]"
              />
            </div>

            <div class="col-4">
              <q-input
                outlined
                :autofocus="!isNovo"
                v-model="modelCartao.validade"
                label="Validade"
                mask="##/##"
                unmasked-value
                hint="MM/AA"
                :rules="[
                  (v) =>
                    (!!v &&
                      v.length === 4 &&
                      Number(v.slice(0, 2)) >= 1 &&
                      Number(v.slice(0, 2)) <= 12) ||
                    'Validade inválida',
                ]"
              />
            </div>

            <div class="col-8">
              <q-input
                outlined
                v-model="modelCartao.email"
                label="E-mail"
                type="email"
                :rules="[(v) => !v || /.+@.+\..+/.test(v) || 'E-mail inválido']"
              />
            </div>

            <div class="col-12">
              <q-input
                outlined
                v-model="modelCartao.observacao"
                label="Observação"
                type="textarea"
                autogrow
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" :disable="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row items-center">
      CARTÃO BENEFÍCIO
      <q-space />
      <q-btn-toggle
        v-model="filtroCartao"
        color="grey-3"
        toggle-color="primary"
        text-color="grey-7"
        toggle-text-color="grey-3"
        unelevated
        no-caps
        size="sm"
        :options="[
          { label: 'Ativos', value: 'ativos' },
          { label: 'Todos', value: 'todos' },
        ]"
      />
      <q-btn
        flat
        round
        icon="add"
        size="sm"
        color="primary"
        v-if="user.temPermissao('Recursos Humanos')"
        :disable="!colaboradorAtivo"
        @click="abrirNovo"
      >
        <q-tooltip v-if="!colaboradorAtivo">Cadastre o vínculo de colaborador primeiro</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-list v-if="cartoesFiltrados.length > 0">
      <template v-for="element in cartoesFiltrados" v-bind:key="element.codcolaboradorcartao">
        <q-separator inset />
        <q-item>
          <q-item-section avatar>
            <q-btn round flat icon="credit_card" color="primary" />
          </q-item-section>

          <q-item-section>
            <q-item-label :class="element.inativo ? 'text-strike' : null">
              {{ element.numero }}
              <MgInfoCriacao :registro="element" />
            </q-item-label>
            <q-item-label caption> Validade {{ element.validade }} </q-item-label>
            <q-item-label caption v-if="element.colaborador?.empresa">
              {{ element.colaborador.empresa
              }}<span v-if="element.colaborador?.filial"> · {{ element.colaborador.filial }}</span>
            </q-item-label>
            <q-item-label caption v-if="element.email">
              {{ element.email }}
            </q-item-label>
            <q-item-label caption v-if="element.observacao">
              {{ element.observacao }}
            </q-item-label>
            <q-item-label caption class="text-red-14" v-if="element.inativo">
              Inativo desde: {{ formataTimestamp(element.inativo) }}
            </q-item-label>
          </q-item-section>

          <q-item-section side v-if="user.temPermissao('Recursos Humanos')">
            <q-item-label caption>
              <!-- EDITAR -->
              <q-btn flat round size="sm" color="grey-7" icon="edit" @click="abrirEditar(element)">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>

              <!-- INATIVAR -->
              <q-btn
                v-if="!element.inativo"
                flat
                round
                size="sm"
                color="grey-7"
                icon="pause"
                @click="inativar(element.codcolaboradorcartao)"
              >
                <q-tooltip>Inativar</q-tooltip>
              </q-btn>

              <!-- ATIVAR -->
              <q-btn
                v-if="element.inativo"
                flat
                round
                size="sm"
                color="grey-7"
                icon="play_arrow"
                @click="ativar(element.codcolaboradorcartao)"
              >
                <q-tooltip>Ativar</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
    <div v-else class="q-pa-md text-center text-grey">Nenhum cartão cadastrado</div>
  </q-card>
</template>
