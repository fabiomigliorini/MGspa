<script setup>
// Dialog do vale compras dentro do negócio.
//
// Fluxo: modelo (opcional) -> favorecido -> aluno/turma -> valor avulso.
// O modelo semeia os itens e o valor avulso, ambos editáveis depois; sem
// modelo o vale é só o valor avulso; sem favorecido ele nasce ao portador,
// em nome do Consumidor.
//
// Os itens não aparecem aqui: eles viram a grade da seção do vale, onde se
// tira item e se ajusta quantidade.
import { ref, computed } from 'vue'
import { db } from 'boot/db'
import { negocioStore } from 'stores/negocio'
import { formataNumero } from '@components/formatters'
import SelectPessoa from 'components/selects/SelectPessoa.vue'
import MgInputValor from '@components/MgInputValor.vue'

const sNegocio = negocioStore()

const form = ref({
  codvalemodelo: null,
  codpessoafavorecido: null,
  aluno: null,
  turma: null,
  valoravulso: 0,
})

const modelos = ref([])
const modeloEscolhido = ref(null)
const salvando = ref(false)

const isNovo = computed(() => !sNegocio.valeEditando)

// O que o vale vai valer: o kit do modelo mais o valor digitado.
const valorVale = computed(() => {
  const kit = parseFloat(modeloEscolhido.value?.valorprodutos ?? 0)
  return Math.round((kit + (parseFloat(form.value.valoravulso) || 0)) * 100) / 100
})

const preparar = async () => {
  if (isNovo.value) {
    form.value = {
      codvalemodelo: null,
      codpessoafavorecido: null,
      aluno: null,
      turma: null,
      valoravulso: 0,
    }
    modeloEscolhido.value = null
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
    aluno: vale.aluno,
    turma: vale.turma,
    valoravulso: vale.valoravulso,
  }
  // na edição o kit já está lançado: o valor do kit vem dos itens do vale
  modeloEscolhido.value = { modelo: vale.modelo, valorprodutos: vale.valorprodutos }
}

// Catálogo sincronizado no cache local (até ~200 modelos por temporada).
const pesquisarModelo = (texto, update) => {
  update(async () => {
    const busca = (texto ?? '').trim().toLowerCase()
    let lista = await db.valeModelo.orderBy('modelo').limit(50).toArray()
    if (busca.length >= 2) {
      lista = await db.valeModelo
        .filter((m) =>
          String(m.modelo ?? '')
            .toLowerCase()
            .includes(busca),
        )
        .limit(50)
        .toArray()
    }
    modelos.value = lista
  })
}

// Escolher o kit já traz a escola e o valor avulso dele, os dois ainda editáveis.
const escolherModelo = async (codvalemodelo) => {
  form.value.codvalemodelo = codvalemodelo
  modeloEscolhido.value = codvalemodelo ? await db.valeModelo.get(codvalemodelo) : null
  if (!modeloEscolhido.value) {
    return
  }
  if (modeloEscolhido.value.codpessoafavorecido) {
    form.value.codpessoafavorecido = parseInt(modeloEscolhido.value.codpessoafavorecido)
  }
  form.value.valoravulso = parseFloat(modeloEscolhido.value.valoravulso) || 0
}

// Sem kit escolhido, o valor tem que vir digitado — senão o vale nasce zerado.
const valorObrigatorioRule = [
  (val) =>
    !!form.value.codvalemodelo || parseFloat(val) > 0 || 'Escolha um modelo ou informe um valor',
]

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
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar()">
        <q-card-section>
          <div class="text-h6 q-mb-md">
            {{ isNovo ? 'NOVO VALE COMPRAS' : 'EDITAR VALE COMPRAS' }}
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-select
                v-if="isNovo"
                outlined
                autofocus
                clearable
                use-input
                fill-input
                hide-selected
                input-debounce="300"
                emit-value
                map-options
                option-value="codvalemodelo"
                option-label="modelo"
                label="Modelo (kit)"
                :options="modelos"
                :model-value="form.codvalemodelo"
                @filter="pesquisarModelo"
                @update:model-value="escolherModelo"
              />
              <q-input
                v-else
                outlined
                readonly
                :tabindex="-1"
                label="Modelo (kit)"
                :model-value="modeloEscolhido?.modelo ?? 'Sem modelo'"
              />
            </div>

            <div class="col-12">
              <select-pessoa
                outlined
                clearable
                label="Favorecido (escola)"
                hint="Em branco: vale ao portador"
                v-model="form.codpessoafavorecido"
              />
            </div>

            <div class="col-12 col-sm-7">
              <q-input outlined clearable label="Aluno" maxlength="50" v-model="form.aluno" />
            </div>

            <div class="col-12 col-sm-5">
              <q-input outlined clearable label="Turma" maxlength="40" v-model="form.turma" />
            </div>

            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="form.valoravulso"
                prefix="R$"
                label="Valor avulso"
                :min="0"
                lazy-rules
                :rules="valorObrigatorioRule"
              />
            </div>

            <div class="col-12 col-sm-6 flex items-center justify-end">
              <div class="text-right">
                <div class="text-caption text-grey-7">Valor do vale</div>
                <div class="text-h6 text-primary">{{ formataNumero(valorVale) }}</div>
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
