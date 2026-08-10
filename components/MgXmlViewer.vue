<script setup>
import { computed, markRaw, provide, ref, watch } from 'vue'
import { copyToClipboard, Notify } from 'quasar'
import MgXmlNo from './MgXmlNo.vue'
import MgEmptyState from './MgEmptyState.vue'
import { baixarArquivo } from './baixarArquivo'

const props = defineProps({
  xml: { type: String, default: '' },
  nomeArquivo: { type: String, default: 'documento.xml' },
  // Ate que profundidade a arvore ja abre. Conta so "nivel util" (ver abaixo).
  profundidade: { type: Number, default: 2 },
})

// Acima disso, "expandir tudo" montaria milhares de nos de uma vez e travaria a aba.
const LIMITE_EXPANDIR = 1500

const analise = computed(() => {
  const texto = (props.xml || '').trim()
  if (!texto) return { vazio: true }

  // Precisa do trim acima: a declaracao <?xml ?> so vale no offset 0.
  const doc = new DOMParser().parseFromString(texto, 'application/xml')
  if (doc.getElementsByTagName('parsererror')[0]) return { erro: true }

  let seq = 0
  const colapsar = []

  // "Nivel util" so avanca quando o pai ramifica. Assim a cadeia de filho unico
  // Envelope > Body > nfeResultMsg > retEnviNFe abre inteira e o corte de
  // profundidade cai onde a informacao de fato comeca.
  const converter = (el, nivelUtil) => {
    const elementos = Array.from(el.children)
    const proximo = nivelUtil + (elementos.length > 1 ? 1 : 0)
    const filhos = elementos.map((filho) => converter(filho, proximo))
    const no = {
      id: ++seq,
      nome: el.nodeName,
      atributos: Array.from(el.attributes, (a) => ({ nome: a.name, valor: a.value })),
      texto: filhos.length ? '' : (el.textContent || '').trim(),
      filhos,
    }
    if (filhos.length && nivelUtil >= props.profundidade) colapsar.push(no.id)
    return no
  }

  // markRaw: sao milhares de objetos imutaveis, nao vale o custo de proxiar cada um.
  return { raiz: markRaw(converter(doc.documentElement, 0)), total: seq, colapsar }
})

const colapsados = ref(new Set())
provide('mgXmlColapsados', colapsados)

watch(
  analise,
  (nova) => {
    colapsados.value = new Set(nova.colapsar || [])
  },
  { immediate: true },
)

const expandirTudo = () => colapsados.value.clear()

const recolherTudo = () => {
  const set = new Set()
  const varrer = (no) => {
    if (!no.filhos.length) return
    set.add(no.id)
    no.filhos.forEach(varrer)
  }
  if (analise.value.raiz) varrer(analise.value.raiz)
  colapsados.value = set
}

// Modo texto e o fallback de XML invalido e tambem a saida para busca: o Ctrl+F do
// navegador acha inclusive o que estaria colapsado na arvore.
const modo = ref('arvore')
const modoEfetivo = computed(() => (analise.value.erro ? 'texto' : modo.value))

const copiar = async () => {
  try {
    await copyToClipboard(props.xml)
    Notify.create({ color: 'green-5', textColor: 'white', icon: 'done', message: 'XML copiado' })
  } catch {
    Notify.create({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: 'Não foi possível copiar',
    })
  }
}

const baixar = () => baixarArquivo(props.xml, props.nomeArquivo, 'application/xml;charset=utf-8')
</script>

<template>
  <div class="column no-wrap">
    <div class="row items-center no-wrap q-mb-sm">
      <div class="col text-caption text-grey-7 ellipsis">
        <span v-if="analise.erro" class="text-orange-9">
          <q-icon name="warning" class="q-mr-xs" />Conteúdo não é um XML válido
        </span>
        <span v-else-if="analise.total">{{ analise.total }} elementos</span>
      </div>

      <template v-if="modoEfetivo === 'arvore' && !analise.vazio">
        <q-btn
          flat
          round
          size="sm"
          color="grey-7"
          icon="unfold_more"
          :disable="analise.total > LIMITE_EXPANDIR"
          @click="expandirTudo"
        >
          <q-tooltip>
            {{ analise.total > LIMITE_EXPANDIR ? 'XML muito grande' : 'Expandir tudo' }}
          </q-tooltip>
        </q-btn>
        <q-btn flat round size="sm" color="grey-7" icon="unfold_less" @click="recolherTudo">
          <q-tooltip>Recolher tudo</q-tooltip>
        </q-btn>
      </template>

      <q-btn
        v-if="!analise.erro && !analise.vazio"
        flat
        round
        size="sm"
        color="grey-7"
        :icon="modo === 'arvore' ? 'notes' : 'account_tree'"
        @click="modo = modo === 'arvore' ? 'texto' : 'arvore'"
      >
        <q-tooltip>{{ modo === 'arvore' ? 'Ver como texto' : 'Ver como árvore' }}</q-tooltip>
      </q-btn>

      <q-btn
        v-if="!analise.vazio"
        flat
        round
        size="sm"
        color="grey-7"
        icon="content_copy"
        @click="copiar"
      >
        <q-tooltip>Copiar</q-tooltip>
      </q-btn>

      <q-btn
        v-if="!analise.vazio"
        flat
        round
        size="sm"
        color="grey-7"
        icon="download"
        @click="baixar"
      >
        <q-tooltip>Baixar XML</q-tooltip>
      </q-btn>
    </div>

    <MgEmptyState v-if="analise.vazio" plain icon="code_off">Sem conteúdo</MgEmptyState>

    <div
      v-else-if="modoEfetivo === 'texto'"
      class="col scroll text-caption bg-grey-1 rounded-borders q-pa-sm"
      style="font-family: monospace; white-space: pre-wrap; overflow-wrap: anywhere"
      v-text="xml"
    />

    <!-- overflow-wrap: base64 de certificado/assinatura tem ~2000 chars numa linha so -->
    <div
      v-else
      class="col scroll text-caption"
      style="font-family: monospace; overflow-wrap: anywhere"
    >
      <MgXmlNo :no="analise.raiz" />
    </div>
  </div>
</template>
