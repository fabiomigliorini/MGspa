<script setup>
// Dialog do vale compras dentro do negócio — wizard, no formato do vale
// compras do sistema legado: escolhe a escola, escolhe o kit, informa o
// aluno. Cada passo é uma escolha só, em lista clicável.
//
//   1. Favorecido — as escolas que têm kit no catálogo.
//      Tem também a saída "vale sem modelo", que pula direto pro valor.
//   2. Modelo — os kits daquela escola.
//   3. Aluno e turma — SÓ quando tem favorecido; sem escola não há aluno.
//   4. Valor — só no caminho "sem modelo".
//
// Os itens não aparecem aqui: eles viram a grade da seção do vale, onde se
// tira item e se ajusta quantidade.
//
// Na EDIÇÃO não há wizard: escola e kit já estão lançados (trocar um deles
// é excluir o vale e lançar outro), então sobra o formulário de aluno,
// turma e valor avulso.
import { ref, computed } from 'vue'
import { Notify } from 'quasar'
import { db } from 'boot/db'
import { negocioStore } from 'stores/negocio'
import { formataNumero } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputFormatado from '@components/MgInputFormatado.vue'
import MgInputValor from '@components/MgInputValor.vue'

const sNegocio = negocioStore()

const PASSO_FAVORECIDO = 1
const PASSO_MODELO = 2
const PASSO_ALUNO = 3
const PASSO_VALOR = 4

// Miolo do wizard com altura fixa: sem isso o dialog pula de tamanho a cada
// passo (6 escolas, 10 kits, 2 campos). Os 108px descontam o header do stepper,
// para o card fechar em ~70% da tela. O conteudo rola dentro.
const ALTURA_PASSO = 'height: calc(70vh - 108px)'

const passo = ref(PASSO_FAVORECIDO)
const semModelo = ref(false)
const favorecidos = ref([])
const modelos = ref([])
const salvando = ref(false)

const form = ref({
  codvalemodelo: null,
  codpessoafavorecido: null,
  favorecido: null,
  modelo: null,
  aluno: null,
  turma: null,
  valoravulso: 0,
  valorprodutos: 0,
  percentualdesconto: null,
  valordesconto: null,
})

const isNovo = computed(() => !sNegocio.valeEditando)
// Consumidor (1) e o favorecido do vale ao portador, nao uma escola: nele
// nao ha aluno nem turma para perguntar.
const temFavorecido = computed(
  () => !!form.value.codpessoafavorecido && form.value.codpessoafavorecido != 1,
)

// Quanto o vale vai valer: o kit escolhido mais o valor digitado.
const valorVale = computed(() => {
  const kit = parseFloat(form.value.valorprodutos) || 0
  return Math.round((kit + (parseFloat(form.value.valoravulso) || 0)) * 100) / 100
})

const formVazio = () => ({
  codvalemodelo: null,
  codpessoafavorecido: null,
  favorecido: null,
  modelo: null,
  aluno: null,
  turma: null,
  valoravulso: 0,
  valorprodutos: 0,
  percentualdesconto: null,
  valordesconto: null,
})

// O que o cliente PAGA por este vale: a face menos o desconto. A face não
// muda — o crédito que a escola recebe é sempre ela (decisão 5c do plano).
// Frete, seguro e "outras" não entram: não se cobra isso de um vale.
const valorPago = computed(() => {
  const total = valorVale.value - (parseFloat(form.value.valordesconto) || 0)
  return Math.round(total * 100) / 100
})

// % e valor andam juntos, como no item de mercadoria; a base é a FACE.
const recalcularValorDesconto = () => {
  if (!(form.value.percentualdesconto > 0)) {
    form.value.valordesconto = null
    return
  }
  form.value.valordesconto = Math.round(valorVale.value * form.value.percentualdesconto) / 100
}

const recalcularPercentualDesconto = () => {
  if (!(form.value.valordesconto > 0) || !valorVale.value) {
    form.value.percentualdesconto = null
    return
  }
  form.value.percentualdesconto =
    Math.round((form.value.valordesconto * 1000) / valorVale.value) / 10
}

// As escolas saem do próprio catálogo sincronizado: só aparece quem tem kit
// para vender. Kit sem escola vira o grupo "ao portador".
const carregarFavorecidos = async () => {
  const todos = await db.valeModelo.toArray()
  const mapa = new Map()
  for (const modelo of todos) {
    const chave = modelo.codpessoafavorecido ?? 0
    if (!mapa.has(chave)) {
      mapa.set(chave, {
        codpessoafavorecido: modelo.codpessoafavorecido ?? null,
        favorecido: modelo.favorecido ?? 'Ao portador (kit sem escola)',
        kits: 0,
      })
    }
    mapa.get(chave).kits++
  }
  // escolas em ordem alfabetica; o grupo sem escola cai para o fim, junto
  // com a saida "sem modelo"
  favorecidos.value = [...mapa.values()].sort((a, b) => {
    if (!a.codpessoafavorecido) return 1
    if (!b.codpessoafavorecido) return -1
    return String(a.favorecido).localeCompare(String(b.favorecido))
  })
}

const preparar = async () => {
  salvando.value = false
  if (isNovo.value) {
    passo.value = PASSO_FAVORECIDO
    semModelo.value = false
    form.value = formVazio()
    modelos.value = []
    await carregarFavorecidos()
    return
  }
  const vale = sNegocio.valesAtivos.find((v) => v.uuid == sNegocio.valeEditando)
  if (!vale) {
    sNegocio.dialog.vale = false
    return
  }
  form.value = {
    codvalemodelo: vale.codvalemodelo,
    codpessoafavorecido: vale.codpessoafavorecido,
    favorecido: vale.favorecido,
    modelo: vale.modelo,
    aluno: vale.aluno,
    turma: vale.turma,
    valoravulso: vale.valoravulso,
    // na edição o kit já está lançado: o valor dele vem dos itens do vale
    valorprodutos: vale.valorprodutos,
    valordesconto: vale.valordesconto,
    percentualdesconto:
      vale.valordesconto > 0 && vale.valorvale > 0
        ? Math.round((vale.valordesconto / vale.valorvale) * 1000) / 10
        : null,
  }
}

const escolherFavorecido = async (fav) => {
  form.value.codpessoafavorecido = fav.codpessoafavorecido
  form.value.favorecido = fav.codpessoafavorecido ? fav.favorecido : null
  modelos.value = (await db.valeModelo.toArray())
    .filter((m) => (m.codpessoafavorecido ?? null) === (fav.codpessoafavorecido ?? null))
    .sort((a, b) => String(a.modelo).localeCompare(String(b.modelo)))
  passo.value = PASSO_MODELO
}

// Escolher o kit fecha o passo 2. Com escola ainda falta o aluno; sem
// escola não falta nada e o vale já entra no negócio.
const escolherModelo = async (modelo) => {
  const face = parseFloat(modelo.valorvale) || 0
  if (face <= 0) {
    Notify.create({
      type: 'negative',
      message: 'Este modelo está zerado! Corrija o cadastro antes de vender.',
      timeout: 3000,
      actions: [{ icon: 'close', color: 'white' }],
    })
    return
  }
  form.value.codvalemodelo = modelo.codvalemodelo
  form.value.modelo = modelo.modelo
  form.value.valorprodutos = parseFloat(modelo.valorprodutos) || 0
  form.value.valoravulso = parseFloat(modelo.valoravulso) || 0
  if (temFavorecido.value) {
    passo.value = PASSO_ALUNO
    return
  }
  await salvar()
}

// Saída do passo 1: vale de valor livre, ao portador, sem kit nenhum.
const escolherSemModelo = () => {
  semModelo.value = true
  form.value = formVazio()
  passo.value = PASSO_VALOR
}

const voltar = () => {
  if (passo.value === PASSO_VALOR) {
    semModelo.value = false
    form.value = formVazio()
    passo.value = PASSO_FAVORECIDO
    return
  }
  if (passo.value === PASSO_ALUNO) {
    passo.value = PASSO_MODELO
    return
  }
  passo.value = PASSO_FAVORECIDO
}

const valorObrigatorioRule = [(val) => parseFloat(val) > 0 || 'Informe o valor do vale']

// Com escola, o aluno e quem identifica de quem e o kit na retirada. Vale ao
// portador nao passa por aqui: sem favorecido o wizard fecha direto no
// escolherModelo(), e no form de edicao o campo esta dentro do v-if.
const alunoObrigatorioRule = [(val) => !!val]

const salvar = async () => {
  if (salvando.value) {
    return
  }
  salvando.value = true
  try {
    if (isNovo.value) {
      await sNegocio.valeAdicionar(form.value)
    } else {
      await sNegocio.valeSalvar(sNegocio.valeEditando, form.value)
    }
    sNegocio.dialog.vale = false
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="sNegocio.dialog.vale" @before-show="preparar">
    <q-card flat style="width: 600px; max-width: 90vw">
      <!-- NOVO: wizard -->
      <q-stepper v-if="isNovo" v-model="passo" flat animated color="primary">
        <!-- 1. FAVORECIDO -->
        <q-step :name="PASSO_FAVORECIDO" title="Favorecido" icon="school" :done="passo > 1">
          <div class="column" :style="ALTURA_PASSO">
            <div class="col scroll column">
              <div class="full-width" style="margin: auto 0">
                <q-list separator>
                  <q-item
                    v-for="fav in favorecidos"
                    :key="fav.codpessoafavorecido ?? 0"
                    clickable
                    v-ripple
                    @click="escolherFavorecido(fav)"
                  >
                    <q-item-section avatar>
                      <q-avatar
                        :icon="fav.codpessoafavorecido ? 'school' : 'redeem'"
                        color="primary"
                        text-color="white"
                      />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>{{ fav.favorecido }}</q-item-label>
                      <q-item-label caption>{{ fav.kits }} kit(s)</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-icon name="chevron_right" color="grey-6" />
                    </q-item-section>
                  </q-item>

                  <q-item v-if="favorecidos.length === 0">
                    <q-item-section class="text-grey-7">
                      Nenhum modelo de vale no PDV. Sincronize para trazer o catálogo.
                    </q-item-section>
                  </q-item>
                </q-list>

                <q-separator spaced />

                <q-list>
                  <q-item clickable v-ripple @click="escolherSemModelo()">
                    <q-item-section avatar>
                      <q-avatar icon="payments" color="grey-6" text-color="white" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Vale sem modelo</q-item-label>
                      <q-item-label caption>Valor livre, ao portador</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-icon name="chevron_right" color="grey-6" />
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </div>
          </div>
        </q-step>

        <!-- 2. MODELO -->
        <q-step
          v-if="!semModelo"
          :name="PASSO_MODELO"
          title="Modelo"
          icon="inventory_2"
          :done="passo > 2"
        >
          <div class="column" :style="ALTURA_PASSO">
            <div class="col scroll column">
              <div class="full-width" style="margin: auto 0">
                <div class="text-caption text-grey-7 q-mb-sm">
                  {{ form.favorecido ?? 'Ao portador' }}
                </div>
                <q-list separator>
                  <q-item
                    v-for="modelo in modelos"
                    :key="modelo.codvalemodelo"
                    clickable
                    v-ripple
                    @click="escolherModelo(modelo)"
                  >
                    <q-item-section>
                      <q-item-label>{{ modelo.modelo }}</q-item-label>
                    </q-item-section>
                    <q-item-section side class="text-subtitle1 text-primary">
                      {{ formataNumero(modelo.valorvale) }}
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </div>

            <q-stepper-navigation>
              <q-btn flat label="Voltar" color="grey-8" @click="voltar()" />
            </q-stepper-navigation>
          </div>
        </q-step>

        <!-- 3. ALUNO E TURMA (só com favorecido) -->
        <q-step v-if="!semModelo && temFavorecido" :name="PASSO_ALUNO" title="Aluno" icon="person">
          <q-form class="column" :style="ALTURA_PASSO" @submit.prevent="salvar()">
            <div class="col scroll column">
              <div class="full-width" style="margin: auto 0">
                <!-- o que esta sendo vendido: escola, kit e valor, um por linha -->
                <div class="text-center q-mb-md">
                  <div class="text-subtitle1">{{ form.favorecido }}</div>
                  <div class="text-body1 text-grey-7">{{ form.modelo }}</div>
                  <div class="text-h5 text-primary">{{ formataNumero(valorVale) }}</div>
                </div>

                <div class="row q-col-gutter-md justify-center">
                  <div class="col-12 col-sm-8">
                    <MgInputFormatado
                      outlined
                      autofocus
                      clearable
                      counter
                      label="Aluno"
                      maxlength="50"
                      lazy-rules
                      :rules="alunoObrigatorioRule"
                      v-model="form.aluno"
                    />
                  </div>
                  <div class="col-12 col-sm-8">
                    <MgInput clearable counter label="Turma" maxlength="40" v-model="form.turma" />
                  </div>
                </div>
              </div>
            </div>

            <q-stepper-navigation>
              <q-btn flat label="Voltar" color="grey-8" tabindex="-1" @click="voltar()" />
              <q-btn
                type="submit"
                flat
                label="Adicionar Vale"
                color="primary"
                :loading="salvando"
              />
            </q-stepper-navigation>
          </q-form>
        </q-step>

        <!-- 4. VALOR (caminho sem modelo) -->
        <q-step v-if="semModelo" :name="PASSO_VALOR" title="Valor" icon="payments">
          <q-form class="column" :style="ALTURA_PASSO" @submit.prevent="salvar()">
            <div class="col scroll column">
              <div class="full-width" style="margin: auto 0">
                <div class="row q-col-gutter-md justify-center">
                  <div class="col-12 col-sm-8">
                    <MgInputValor
                      autofocus
                      lazy-rules
                      :min="0"
                      v-model="form.valoravulso"
                      prefix="R$"
                      label="Valor do vale"
                      :rules="valorObrigatorioRule"
                    />
                  </div>
                </div>
                <div class="text-caption text-grey-7 text-center">
                  Vale ao portador: quem apresentar o comprovante usa o crédito.
                </div>
              </div>
            </div>

            <q-stepper-navigation>
              <q-btn flat label="Voltar" color="grey-8" tabindex="-1" @click="voltar()" />
              <q-btn
                type="submit"
                flat
                label="Adicionar Vale"
                color="primary"
                :loading="salvando"
              />
            </q-stepper-navigation>
          </q-form>
        </q-step>
      </q-stepper>

      <!-- EDIÇÃO: escola e kit já estão lançados -->
      <q-form v-else @submit.prevent="salvar()">
        <q-card-section>
          <div class="text-h6 q-mb-md">EDITAR VALE COMPRAS</div>

          <!-- mesmo resumo do passo 3: escola, kit e valor, um por linha -->
          <div class="text-center q-mb-md">
            <div class="text-subtitle1">{{ form.favorecido ?? 'Ao portador' }}</div>
            <div class="text-body1 text-grey-7" v-if="form.modelo">{{ form.modelo }}</div>
            <div class="text-h5 text-primary">{{ formataNumero(valorVale) }}</div>
          </div>

          <div class="row q-col-gutter-md justify-center">
            <template v-if="temFavorecido">
              <div class="col-12 col-sm-8">
                <MgInputFormatado
                  outlined
                  autofocus
                  clearable
                  counter
                  label="Aluno"
                  maxlength="50"
                  lazy-rules
                  :rules="alunoObrigatorioRule"
                  v-model="form.aluno"
                />
              </div>
              <div class="col-12 col-sm-8">
                <MgInput clearable counter label="Turma" maxlength="40" v-model="form.turma" />
              </div>
            </template>

            <div class="col-12 col-sm-8">
              <MgInputValor
                v-model="form.valoravulso"
                prefix="R$"
                label="Valor avulso"
                :min="0"
                :autofocus="!temFavorecido"
              />
            </div>

            <!-- Desconto do vale, igual ao do item de mercadoria: % e valor
                 andam juntos. A diferenca e que aqui ele NAO mexe na face:
                 a escola recebe o valor de face, o desconto sai do que o
                 cliente paga. -->
            <div class="col-6 col-sm-4">
              <MgInputValor
                :decimals="1"
                :min="0"
                :max="99.9"
                v-model="form.percentualdesconto"
                label="% Desc"
                suffix="%"
                @change="recalcularValorDesconto()"
              />
            </div>
            <div class="col-6 col-sm-4">
              <MgInputValor
                :min="0"
                :max="valorVale"
                v-model="form.valordesconto"
                prefix="R$"
                label="Desconto"
                @change="recalcularPercentualDesconto()"
              />
            </div>

            <div class="col-12 col-sm-8" v-if="valorPago != valorVale">
              <div class="row items-center">
                <div class="col text-caption text-grey-7">Cliente paga</div>
                <div class="col-auto text-subtitle1">{{ formataNumero(valorPago) }}</div>
              </div>
              <div class="text-caption text-grey-7">
                O crédito emitido continua sendo a face:
                {{ formataNumero(valorVale) }}
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="grey-8"
            tabindex="-1"
            @click="sNegocio.dialog.vale = false"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
