<script setup>
import { ref, computed, onMounted } from 'vue'
import { formataData, formataNumero } from '@components/formatters'
import { useIbptStore } from '../stores/ibptStore'
import { notificarSucesso, notificarErro, extrairErro } from '../utils/notify'

const store = useIbptStore()

const arquivoInput = ref(null)
const enviando = ref(false)
const progresso = ref({ atual: 0, total: 0, uf: null })
// UF -> { ok, mensagem, ncms }; alimenta a grade e o reenvio das que falharam
const resultados = ref({})
// UF -> File, guardado para permitir reenviar só o que deu errado
const arquivos = ref({})

const estados = computed(() => store.estados)
const diasparavencer = computed(() => store.diasparavencer)

const situacao = computed(() => {
  if (!store.loaded || store.carregadas === 0) {
    return { cor: 'grey-7', icone: 'help', texto: 'Tabela ainda não importada' }
  }
  const dias = diasparavencer.value
  if (dias === null) {
    return { cor: 'grey-7', icone: 'help', texto: 'Sem vigência informada' }
  }
  if (dias < 0) {
    return {
      cor: 'red-5',
      icone: 'error',
      texto: `Vencida há ${Math.abs(dias)} dia(s) — importe a tabela nova`,
    }
  }
  if (dias <= 10) {
    return {
      cor: 'orange-5',
      icone: 'warning',
      texto: `Vence em ${dias} dia(s) — baixe a tabela nova no site do IBPT`,
    }
  }
  return { cor: 'green-5', icone: 'done', texto: `Válida por mais ${dias} dia(s)` }
})

const falhas = computed(() =>
  Object.entries(resultados.value)
    .filter(([, r]) => !r.ok)
    .map(([uf]) => uf),
)

const percentual = computed(() =>
  progresso.value.total ? progresso.value.atual / progresso.value.total : 0,
)

const abrirSeletor = () => arquivoInput.value?.click()

// O CSV não diz a que UF pertence — só o nome do arquivo (TabelaIBPTaxMT26.1.L.csv)
const ufDoArquivo = (nome) => {
  const m = nome.match(/TabelaIBPTax([A-Za-z]{2})/)
  return m ? m[1].toUpperCase() : null
}

const selecionar = async (lista) => {
  if (!lista?.length) return

  const selecionados = []
  const ignorados = []
  for (const arquivo of lista) {
    const uf = ufDoArquivo(arquivo.name)
    if (uf) {
      arquivos.value[uf] = arquivo
      selecionados.push(uf)
    } else {
      ignorados.push(arquivo.name)
    }
  }

  if (ignorados.length) {
    notificarErro(
      { message: `Ignorados (não são tabelas do IBPT): ${ignorados.join(', ')}` },
      'Arquivos ignorados',
    )
  }
  if (!selecionados.length) return

  resultados.value = {}
  await enviar(selecionados)
}

const reenviarFalhas = () => enviar(falhas.value)

// Envia em série: um request por UF, para o usuário ver onde está e para uma UF
// com problema não derrubar o lote inteiro.
const enviar = async (ufs) => {
  enviando.value = true
  progresso.value = { atual: 0, total: ufs.length, uf: null }

  for (const uf of ufs) {
    progresso.value.uf = uf
    try {
      const r = await store.importar(uf, arquivos.value[uf])
      resultados.value[uf] = { ok: true, ncms: r.ncms + r.aproximados }
    } catch (error) {
      resultados.value[uf] = { ok: false, mensagem: extrairErro(error, 'Falha na importação') }
    }
    progresso.value.atual++
  }

  enviando.value = false
  progresso.value.uf = null

  const erros = falhas.value.length
  if (erros) {
    notificarErro(
      { message: `${ufs.length - erros} de ${ufs.length} importadas. ${erros} com erro.` },
      'Importação parcial',
    )
  } else {
    notificarSucesso(`${ufs.length} estado(s) importado(s)`)
  }

  await store.fetchStatus(true).catch((e) => notificarErro(e, 'Falha ao atualizar a situação'))
}

const corDoEstado = (estado) => {
  const r = resultados.value[estado.sigla]
  if (r) return r.ok ? 'green-5' : 'red-5'
  if (enviando.value && progresso.value.uf === estado.sigla) return 'blue-5'
  return estado.ncms > 0 ? 'grey-7' : 'grey-4'
}

const iconeDoEstado = (estado) => {
  const r = resultados.value[estado.sigla]
  if (r) return r.ok ? 'done' : 'error'
  return estado.ncms > 0 ? 'check_circle' : 'radio_button_unchecked'
}

onMounted(() => {
  store.fetchStatus().catch((e) => notificarErro(e, 'Falha ao carregar a situação da tabela'))
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Situação atual -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section>
          <div class="row items-center q-col-gutter-md">
            <div class="col-12 col-sm">
              <div class="text-h6">Tabela IBPT</div>
              <div class="text-caption text-grey-7">
                Base dos tributos aproximados impressos na nota (Lei 12.741)
              </div>
            </div>
            <div class="col-12 col-sm-auto">
              <q-chip :color="situacao.cor" text-color="white" :icon="situacao.icone">
                {{ situacao.texto }}
              </q-chip>
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Versão</div>
              <div class="text-subtitle1">{{ store.versao || '—' }}</div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Vigência até</div>
              <div class="text-subtitle1">
                {{ store.vigenciafim ? formataData(store.vigenciafim) : '—' }}
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Estados carregados</div>
              <div class="text-subtitle1">{{ store.carregadas }} de {{ estados.length }}</div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">NCMs</div>
              <div class="text-subtitle1">
                {{
                  formataNumero(
                    estados.reduce((t, e) => t + e.ncms, 0),
                    0,
                  )
                }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Importação -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section>
          <div class="text-subtitle1 q-mb-sm">Importar</div>
          <div class="text-body2 text-grey-8">
            Baixe o arquivo no site
            <a href="https://deolhonoimposto.ibpt.org.br" target="_blank" rel="noopener">
              De Olho no Imposto
            </a>
            , descompacte o ZIP e selecione aqui os 27 arquivos
            <strong>TabelaIBPTax&lt;UF&gt;.csv</strong>. Pode repetir a importação quantas vezes
            precisar.
          </div>
        </q-card-section>

        <q-card-section>
          <div class="row items-center q-col-gutter-md">
            <div class="col-auto">
              <q-btn
                color="primary"
                icon="upload_file"
                label="Selecionar arquivos"
                :loading="enviando"
                :disable="enviando"
                @click="abrirSeletor"
              />
              <input
                ref="arquivoInput"
                type="file"
                accept=".csv"
                multiple
                style="display: none"
                @change="(e) => selecionar(e.target.files)"
              />
            </div>
            <div v-if="falhas.length && !enviando" class="col-auto">
              <q-btn
                flat
                color="negative"
                icon="refresh"
                :label="`Reenviar ${falhas.length} com erro`"
                @click="reenviarFalhas"
              />
            </div>
          </div>

          <div v-if="enviando" class="q-mt-md">
            <div class="text-caption text-grey-7 q-mb-sm">
              {{ progresso.atual }} de {{ progresso.total }}
              <span v-if="progresso.uf">— enviando {{ progresso.uf }}</span>
            </div>
            <q-linear-progress :value="percentual" color="primary" size="10px" rounded />
          </div>
        </q-card-section>

        <q-separator inset />

        <!-- Grade das UFs -->
        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div v-for="estado in estados" :key="estado.codestado" class="col-4 col-sm-3 col-md-2">
              <q-item>
                <q-item-section avatar>
                  <q-icon :name="iconeDoEstado(estado)" :color="corDoEstado(estado)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ estado.sigla }}</q-item-label>
                  <q-item-label caption>
                    {{ estado.ncms ? formataNumero(estado.ncms, 0) : '—' }}
                  </q-item-label>
                </q-item-section>
                <q-tooltip v-if="resultados[estado.sigla] && !resultados[estado.sigla].ok">
                  {{ resultados[estado.sigla].mensagem }}
                </q-tooltip>
                <q-tooltip v-else-if="estado.vigenciafim">
                  {{ estado.estado }} — vigência até {{ formataData(estado.vigenciafim) }}
                </q-tooltip>
              </q-item>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>
