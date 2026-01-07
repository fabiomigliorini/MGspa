<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import {
  getStatusLabel,
  getStatusColor,
  getStatusIcon,
  getModeloLabel,
  getPagamentoIcon,
  getPagamentoColor,
  getFreteLabel,
  STATUS_OPTIONS,
} from '../constants/notaFiscal'
import {
  formatCnpjCpf,
  formatDateTime,
  formatDate,
  formatCurrency,
  formatDecimal,
  formatNumero,
  formatChave,
  formatProtocolo,
  formatCodNegocio,
} from 'src/utils/formatters'
import NotaFiscalItemCard from '../components/NotaFiscalItemCard.vue'
import NotaFiscalPagamentoDialog from '../components/dialogs/NotaFiscalPagamentoDialog.vue'
import NotaFiscalDuplicataDialog from '../components/dialogs/NotaFiscalDuplicataDialog.vue'
import NotaFiscalReferenciadaDialog from '../components/dialogs/NotaFiscalReferenciadaDialog.vue'
import NotaFiscalCartaCorrecaoDialog from '../components/dialogs/NotaFiscalCartaCorrecaoDialog.vue'
import NotaFiscalItemDialog from 'src/components/dialogs/NotaFiscalItemDialog.vue'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// Computed
const nota = computed(() => notaFiscalStore.currentNota)
const itens = computed(() => {
  const itensData = notaFiscalStore.itens
  // Ordena por ordem e depois por codnotafiscalprodutobarra
  return [...itensData].sort((a, b) => {
    // Primeiro compara por ordem
    if (a.ordem !== b.ordem) {
      return (a.ordem || 0) - (b.ordem || 0)
    }
    // Se ordem for igual, compara por codnotafiscalprodutobarra
    return (a.codnotafiscalprodutobarra || 0) - (b.codnotafiscalprodutobarra || 0)
  })
})
const pagamentos = computed(() => {
  const pagamentosData = notaFiscalStore.pagamentos
  // Ordena por codnotafiscalformapagamento
  return [...pagamentosData].sort((a, b) => {
    return (a.codnotafiscalpagamento || 0) - (b.codnotafiscalpagamento || 0)
  })
})
const duplicatas = computed(() => {
  const duplicatasData = notaFiscalStore.duplicatas
  // Ordena por vencimento e depois por codnotafiscalduplicatas
  return [...duplicatasData].sort((a, b) => {
    // Primeiro compara por vencimento
    const vencimentoA = a.vencimento ? new Date(a.vencimento).getTime() : 0
    const vencimentoB = b.vencimento ? new Date(b.vencimento).getTime() : 0
    if (vencimentoA !== vencimentoB) {
      return vencimentoA - vencimentoB
    }
    // Se vencimento for igual, compara por codnotafiscalduplicatas
    return (a.codnotafiscalduplicatas || 0) - (b.codnotafiscalduplicatas || 0)
  })
})
const referenciadas = computed(() => {
  const referenciadasData = notaFiscalStore.referenciadas
  // Ordena por nfechave
  return [...referenciadasData].sort((a, b) => {
    const chaveA = a.nfechave || ''
    const chaveB = b.nfechave || ''
    return chaveA.localeCompare(chaveB)
  })
})
const cartasCorrecao = computed(() => {
  const cartas = notaFiscalStore.cartasCorrecao || []
  // Ordena por sequência decrescente (maior primeiro)
  return [...cartas].sort((a, b) => (b.sequencia || 0) - (a.sequencia || 0))
})

// Computa a maior sequência das cartas de correção
const maiorSequenciaCartaCorrecao = computed(() => {
  if (cartasCorrecao.value.length === 0) return 0
  return Math.max(...cartasCorrecao.value.map((c) => c.sequencia || 0))
})

// URL base para negócios
const negociosUrl = import.meta.env.VITE_NEGOCIOS_URL || process.env.NEGOCIOS_URL

// Lista de negócios únicos vinculados à nota
const negociosVinculados = computed(() => {
  const itensData = notaFiscalStore.itens || []
  const negociosMap = new Map()

  itensData.forEach((item) => {
    if (item.codnegocio && !negociosMap.has(item.codnegocio)) {
      negociosMap.set(item.codnegocio, item.codnegocio)
    }
  })

  return Array.from(negociosMap.values()).sort((a, b) => a - b)
})

// Gera URL do negócio
const getNegocioUrl = (codnegocio) => {
  return `${negociosUrl}/negocio/${codnegocio}`
}

const loadingNota = computed(() => notaFiscalStore.loading.nota)

// Paginação client-side dos itens - dinâmica por breakpoint
const itensPorPagina = computed(() => {
  if ($q.screen.xs) return 1 // Desktop pequeno: 4 por linha = 3 linhas
  if ($q.screen.sm) return 3 // Tablet: 3 por linha = 4 linhas
  if ($q.screen.md) return 4 // Mobile: 1 por linha = 6 linhas
  // if ($q.screen.lg) return 6   // Mobile: 1 por linha = 6 linhas
  return 6 // Desktop grande (lg/xl): 6 por linha = 2 linhas
})
const paginaAtualItens = ref(1)

const totalPaginasItens = computed(() => Math.ceil(itens.value.length / itensPorPagina.value))

const itensPaginados = computed(() => {
  const inicio = (paginaAtualItens.value - 1) * itensPorPagina.value
  const fim = inicio + itensPorPagina.value
  return itens.value.slice(inicio, fim)
})

const mudarPaginaItens = (novaPagina) => {
  paginaAtualItens.value = novaPagina
  // Scroll suave para o topo da seção de itens
  const itensSection = document.querySelector('#itens-section')
  if (itensSection) {
    itensSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const notaBloqueada = computed(() => {
  if (!nota.value) return false
  // Mesma lógica do backend: NotaFiscalStatus::isEditable()
  return ['AUT', 'CAN', 'INU', 'ERR'].includes(nota.value.status)
})

// Methods
const loadData = async () => {
  try {
    // O backend já retorna todos os dados relacionados (itens, pagamentos, duplicatas, referenciadas)
    // em uma única requisição, então não precisamos fazer chamadas separadas
    // A verificação de cache é feita dentro do fetchNota (usa cache se o codnotafiscal for o mesmo)
    await notaFiscalStore.fetchNota(route.params.codnotafiscal)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar nota fiscal',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const handleDelete = () => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir a nota fiscal ${getModeloLabel(nota.value.modelo)} nº ${nota.value.numero}?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.deleteNota(route.params.codnotafiscal)
      $q.notify({
        type: 'positive',
        message: 'Nota fiscal excluída com sucesso',
      })
      router.push({ name: 'notas' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir nota fiscal',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// ==================== ITENS ====================
const itemDialog = ref(false)
const itemSelecionado = ref(null)

const novoItem = () => {
  itemSelecionado.value = null
  itemDialog.value = true
}

const createItem = async (prod) => {
  try {
    const ret = await notaFiscalStore.createItem(route.params.codnotafiscal, {
      codprodutobarra: prod.produto.codprodutobarra,
    })

    const adicionado = [...ret.itens].sort((a, b) => {
      // compara por codnotafiscalprodutobarra
      return (b.codnotafiscalprodutobarra || 0) - (a.codnotafiscalprodutobarra || 0)
    })[0]

    router.push({
      name: 'nota-fiscal-item-edit',
      params: {
        codnotafiscal: adicionado.codnotafiscal,
        codnotafiscalitem: adicionado.codnotafiscalprodutobarra,
      },
    })

    $q.notify({
      type: 'positive',
      message: 'Item adicionado com sucesso',
    })
  } catch (error) {
    console.log(error)
    $q.notify({
      type: 'negative',
      message: 'Erro ao excluir item',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const handleDeleteItem = (item) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir o item "${item.produtoBarra?.descricao}"?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.deleteItem(route.params.codnotafiscal, item.codnotafiscalprodutobarra)
      $q.notify({
        type: 'positive',
        message: 'Item excluído com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir item',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// ==================== FORMAS DE PAGAMENTO ====================
const pagamentoDialog = ref(false)
const pagamentoSelecionado = ref(null)

const novoPagamento = () => {
  pagamentoSelecionado.value = null
  pagamentoDialog.value = true
}

const editarPagamento = (pagamento) => {
  pagamentoSelecionado.value = pagamento
  pagamentoDialog.value = true
}

const salvarPagamento = async (data) => {
  try {
    if (data.codnotafiscalpagamento) {
      await notaFiscalStore.updatePagamento(
        route.params.codnotafiscal,
        data.codnotafiscalpagamento,
        data
      )
      $q.notify({
        type: 'positive',
        message: 'Pagamento atualizado com sucesso',
      })
    } else {
      await notaFiscalStore.createPagamento(route.params.codnotafiscal, data)
      $q.notify({
        type: 'positive',
        message: 'Pagamento adicionado com sucesso',
      })
    }
    pagamentoDialog.value = false
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao salvar pagamento',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const excluirPagamento = (pagamento) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: 'Deseja realmente excluir esta forma de pagamento?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.deletePagamento(
        route.params.codnotafiscal,
        pagamento.codnotafiscalpagamento
      )
      $q.notify({
        type: 'positive',
        message: 'Pagamento excluído com sucesso',
      })
      pagamentoDialog.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir pagamento',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// ==================== DUPLICATAS ====================
const duplicataDialog = ref(false)
const duplicataSelecionada = ref(null)

const novaDuplicata = () => {
  duplicataSelecionada.value = null
  duplicataDialog.value = true
}

const editarDuplicata = (duplicata) => {
  duplicataSelecionada.value = duplicata
  duplicataDialog.value = true
}

const salvarDuplicata = async (data) => {
  try {
    if (data.codnotafiscalduplicatas) {
      await notaFiscalStore.updateDuplicata(
        route.params.codnotafiscal,
        data.codnotafiscalduplicatas,
        data
      )
      $q.notify({
        type: 'positive',
        message: 'Duplicata atualizada com sucesso',
      })
    } else {
      await notaFiscalStore.createDuplicata(route.params.codnotafiscal, data)
      $q.notify({
        type: 'positive',
        message: 'Duplicata adicionada com sucesso',
      })
    }
    duplicataDialog.value = false
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao salvar duplicata',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const excluirDuplicata = (duplicata) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: 'Deseja realmente excluir esta duplicata?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.deleteDuplicata(
        route.params.codnotafiscal,
        duplicata.codnotafiscalduplicatas
      )
      $q.notify({
        type: 'positive',
        message: 'Duplicata excluída com sucesso',
      })
      duplicataDialog.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir duplicata',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// ==================== NOTAS REFERENCIADAS ====================
const referenciadaDialog = ref(false)
const referenciadaSelecionada = ref(null)

const novaReferenciada = () => {
  referenciadaSelecionada.value = null
  referenciadaDialog.value = true
}

const editarReferenciada = (referenciada) => {
  referenciadaSelecionada.value = referenciada
  referenciadaDialog.value = true
}

const salvarReferenciada = async (data) => {
  try {
    if (data.codnotafiscalreferenciada) {
      // Não há update para referenciadas, apenas create e delete
      $q.notify({
        type: 'warning',
        message: 'Notas referenciadas não podem ser editadas. Exclua e crie novamente.',
      })
      return
    } else {
      await notaFiscalStore.createReferenciada(route.params.codnotafiscal, data)
      $q.notify({
        type: 'positive',
        message: 'Nota referenciada adicionada com sucesso',
      })
    }
    referenciadaDialog.value = false
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao salvar nota referenciada',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const excluirReferenciada = (referenciada) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: 'Deseja realmente excluir esta nota referenciada?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.deleteReferenciada(
        route.params.codnotafiscal,
        referenciada.codnotafiscalreferenciada
      )
      $q.notify({
        type: 'positive',
        message: 'Nota referenciada excluída com sucesso',
      })
      referenciadaDialog.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir nota referenciada',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// ==================== CARTAS DE CORREÇÃO ====================
const cartaCorrecaoDialog = ref(false)
const cartaCorrecaoDialogRef = ref(null)
const cartaCorrecaoPdfDialog = ref(false)
const cartaCorrecaoPdfUrl = ref('')

const novaCartaCorrecao = () => {
  cartaCorrecaoDialog.value = true
}

const abrirCartaCorrecaoPdf = async () => {
  try {
    cartaCorrecaoPdfUrl.value = await notaFiscalStore.getCartaCorrecaoPdfUrl(
      nota.value.codnotafiscal
    )
    cartaCorrecaoPdfDialog.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao abrir PDF da Carta de Correção',
      caption: error?.response?.data?.message || error?.message || 'Erro desconhecido',
    })
  }
}

// ==================== ESPELHO ====================
const espelhoPdfDialog = ref(false)
const espelhoPdfUrl = ref('')

const abrirEspelhoPdf = async () => {
  try {
    espelhoPdfUrl.value = await notaFiscalStore.getEspelhoUrl(nota.value.codnotafiscal)
    espelhoPdfDialog.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao abrir Espelho da Nota Fiscal',
      caption: error?.response?.data?.message || error?.message || 'Erro desconhecido',
    })
  }
}

const enviarCartaCorrecao = async (texto) => {
  loadingCartaCorrecao.value = true
  try {
    const response = await notaFiscalStore.enviarCartaCorrecao(route.params.codnotafiscal, texto)

    // Sempre mostra o resultado, independente de sucesso ou erro
    const tipo = response.sucesso ? 'positive' : 'negative'
    const mensagem = `${response.cStat} - ${response.xMotivo}`

    $q.dialog({
      title: 'Carta de Correção',
      message: mensagem,
      ok: { label: 'OK', color: tipo },
    })

    if (response.sucesso) {
      cartaCorrecaoDialog.value = false
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao enviar carta de correção',
      caption: error.response?.data?.message || error.message,
    })
  } finally {
    loadingCartaCorrecao.value = false
    cartaCorrecaoDialogRef.value?.setLoading(false)
  }
}

// DUPLICAR
const duplicarNota = () => {
  $q.dialog({
    title: 'Confirma duplicação',
    message: 'Deseja realmente duplicar esta nota fsical?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Duplicar', color: 'primary' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.duplicarNota(nota.value.codnotafiscal)
      $q.notify({
        type: 'positive',
        message: 'Nota fiscal duplicada!',
      })
      router.replace({
        name: 'nota-fiscal-view',
        params: {
          codnotafiscal: nota.value.codnotafiscal,
        },
      })
    } catch (error) {
      console.log(error)
      $q.notify({
        type: 'negative',
        message: 'Erro ao duplicar nota fiscal',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// INCORPORAR VALORES
const incorporarValores = () => {
  $q.dialog({
    title: 'Incorporar Valores aos Produtos',
    message:
      'Esta ação irá incorporar os valores de desconto, frete, seguro e outras despesas no valor dos produtos, refazendo o rateio. Esta ação não pode ser desfeita. Digite INCORPORAR para confirmar:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val === 'INCORPORAR',
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'warning' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.incorporarValores(nota.value.codnotafiscal)
      $q.notify({
        type: 'positive',
        message: 'Valores incorporados com sucesso!',
      })
    } catch (error) {
      console.log(error)
      $q.notify({
        type: 'negative',
        message: 'Erro ao incorporar valores',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// RECALCULAR TRIBUTAÇÃO
const recalcularTributacao = () => {
  $q.dialog({
    title: 'Recalcular Tributação',
    message:
      'Esta ação irá recalcular todos os tributos dos produtos da nota fiscal com base nas configurações atuais de tributação. Digite RECALCULAR para confirmar:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val === 'RECALCULAR',
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'warning' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.recalcularTributacao(nota.value.codnotafiscal)
      $q.notify({
        type: 'positive',
        message: 'Tributação recalculada com sucesso!',
      })
    } catch (error) {
      console.log(error)
      $q.notify({
        type: 'negative',
        message: 'Erro ao recalcular tributação',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// ==================== NFE ACTIONS ====================
const loadingNfe = ref(false)
const loadingConsultar = ref(false)
const loadingCancelar = ref(false)
const loadingInutilizar = ref(false)
const loadingEmail = ref(false)
const loadingCartaCorrecao = ref(false)
const progressoNfe = ref({ status: '', percent: 0 })
const danfeDialog = ref(false)
const danfeUrl = ref('')

const enviarNfe = async () => {
  loadingNfe.value = true
  progressoNfe.value = { status: 'Criando Arquivo XML...', percent: 0 }

  try {
    // 1. Criar XML
    const xmlResponse = await notaFiscalStore.criarNfe(nota.value.codnotafiscal)
    progressoNfe.value = { status: 'Arquivo XML Criado...', percent: 25 }

    // Verifica se é contingência offline (tpEmis = 9)
    const parser = new DOMParser()
    const xmlDoc = parser.parseFromString(xmlResponse, 'text/xml')
    const tpEmis = xmlDoc.querySelector('tpEmis')?.textContent

    if (tpEmis === '9') {
      // Modo offline - apenas abre DANFE
      await abrirDanfe()

      // // Se for NFCe, imprime
      // if (nota.value.modelo === 65) {
      //   await notaFiscalStore.imprimirNfe(nota.value.codnotafiscal, '')
      // }
    } else {
      // 2. Enviar para SEFAZ
      progressoNfe.value = { status: 'Enviando NFe para Sefaz...', percent: 50 }
      const envioResponse = await notaFiscalStore.enviarNfeSincrono(nota.value.codnotafiscal)

      if (envioResponse.sucesso) {
        // 3. Enviar Email
        progressoNfe.value = { status: 'Enviando Email...', percent: 75 }
        await notaFiscalStore.enviarEmailNfe(nota.value.codnotafiscal)

        // // 4. Imprimir se for NFCe
        // if (nota.value.modelo === 65) {
        //   await notaFiscalStore.imprimirNfe(nota.value.codnotafiscal, '')
        // }

        // 5. Abrir DANFE
        progressoNfe.value = { status: 'Finalizado...', percent: 100 }
        await abrirDanfe()

        // Recarrega a nota
        await loadData()

        $q.notify({
          type: 'positive',
          message: 'NFe enviada com sucesso!',
        })
      } else {
        throw new Error(`${envioResponse.cStat} - ${envioResponse.xMotivo}`)
      }
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao enviar NFe',
      caption: error.response?.data?.message || error.message,
    })
  } finally {
    loadingNfe.value = false
    progressoNfe.value = { status: '', percent: 0 }
  }
}

const consultarNfe = async () => {
  loadingConsultar.value = true
  try {
    const response = await notaFiscalStore.consultarNfe(nota.value.codnotafiscal)

    // Sempre mostra o resultado, independente de sucesso ou erro
    const tipo = response.sucesso ? 'positive' : 'negative'
    const mensagem = `${response.cStat} - ${response.xMotivo}`

    $q.dialog({
      title: 'Consulta NFe',
      message: mensagem,
      ok: { label: 'OK', color: tipo },
    })
  } catch (error) {
    // Mostra erro 500 do backend no notify bottom
    const mensagem = error.response?.data?.message || error.message

    $q.notify({
      type: 'negative',
      message: mensagem,
      position: 'bottom',
    })
  } finally {
    loadingConsultar.value = false
  }
}

const cancelarNfe = async () => {
  $q.dialog({
    title: 'Cancelar NFe',
    message: 'Digite a justificativa para cancelar a NFe',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar Cancelamento', color: 'negative' },
    persistent: true,
  }).onOk(async (justificativa) => {
    loadingCancelar.value = true
    try {
      const response = await notaFiscalStore.cancelarNfe(nota.value.codnotafiscal, justificativa)

      // Sempre mostra o resultado, independente de sucesso ou erro
      const tipo = response.sucesso ? 'positive' : 'negative'
      const mensagem = `${response.cStat} - ${response.xMotivo}`

      $q.dialog({
        title: 'Cancelamento NFe',
        message: mensagem,
        ok: { label: 'OK', color: tipo },
      })
    } catch (error) {
      // Mostra erro 500 do backend no notify bottom
      const mensagem = error.response?.data?.message || error.message

      $q.notify({
        type: 'negative',
        message: mensagem,
        position: 'bottom',
      })
    } finally {
      loadingCancelar.value = false
    }
  })
}

const inutilizarNfe = async () => {
  $q.dialog({
    title: 'Inutilizar NFe',
    message: 'Digite a justificativa para inutilizar a NFe',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar Inutilização', color: 'negative' },
    persistent: true,
  }).onOk(async (justificativa) => {
    loadingInutilizar.value = true
    try {
      const response = await notaFiscalStore.inutilizarNfe(nota.value.codnotafiscal, justificativa)

      // Sempre mostra o resultado, independente de sucesso ou erro
      const tipo = response.sucesso ? 'positive' : 'negative'
      const mensagem = `${response.cStat} - ${response.xMotivo}`

      $q.dialog({
        title: 'Inutilização NFe',
        message: mensagem,
        ok: { label: 'OK', color: tipo },
      })
    } catch (error) {
      // Mostra erro 500 do backend no notify bottom
      const mensagem = error.response?.data?.message || error.message

      $q.notify({
        type: 'negative',
        message: mensagem,
        position: 'bottom',
      })
    } finally {
      loadingInutilizar.value = false
    }
  })
}

const enviarEmailNfe = async () => {
  $q.dialog({
    title: 'Enviar Email',
    message: 'Digite o endereço de e-mail',
    prompt: {
      model: nota.value.pessoa?.email || '',
      type: 'email',
      outlined: true,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Enviar', color: 'primary' },
    persistent: true,
  }).onOk(async (email) => {
    loadingEmail.value = true
    try {
      const response = await notaFiscalStore.enviarEmailNfe(nota.value.codnotafiscal, email)

      const tipo = response.sucesso ? 'positive' : 'negative'
      const mensagem = response.mensagem || 'Email enviado com sucesso'

      $q.notify({
        type: tipo,
        message: mensagem,
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao enviar email',
        caption: error.response?.data?.message || error.message,
      })
    } finally {
      loadingEmail.value = false
    }
  })
}

const abrirDanfe = async () => {
  try {
    danfeUrl.value = await notaFiscalStore.getDanfeUrl(nota.value.codnotafiscal)

    // se for celular android
    const ua = navigator.userAgent
    const isAndroidPhone = /Android/i.test(ua) && /Mobile/i.test(ua) && !/CrOS/i.test(ua)
    if (isAndroidPhone) {
      window.open(danfeUrl.value, '_blank')
    } else {
      danfeDialog.value = true
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao abrir DANFE',
      caption: error?.response?.data?.message || error?.message || 'Erro desconhecido',
    })
  }
}

const abrirXml = async () => {
  try {
    const xmlUrl = await notaFiscalStore.getXmlUrl(nota.value.codnotafiscal)
    window.open(xmlUrl, '_blank')
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao abrir XML',
      caption: error?.response?.data?.message || error?.message || 'Erro desconhecido',
    })
  }
}

// Computed para controlar visibilidade dos botões
const podeEnviar = computed(() => {
  return nota.value && ['DIG', 'ERR'].includes(nota.value.status)
})

const podeConsultar = computed(() => {
  return nota.value && ['AUT', 'CAN', 'ERR'].includes(nota.value.status)
})

const podeCancelar = computed(() => {
  return nota.value && nota.value.status === 'AUT'
})

const podeInutilizar = computed(() => {
  return nota.value && nota.value.status === 'ERR'
})

const podeEnviarEmail = computed(() => {
  return nota.value && nota.value.status === 'AUT'
})

const podeAbrirDanfe = computed(() => {
  return nota.value && ['AUT', 'CAN'].includes(nota.value.status)
})

const podeAbrirXml = computed(() => {
  return nota.value && nota.value.emitida && nota.value.nfechave
})

const podeIncorporar = computed(() => {
  return (
    nota.value && nota.value.status === 'DIG' && nota.value.valortotal != nota.value.valorprodutos
  )
})

const podeRecalcularTributacao = computed(() => {
  return nota.value && nota.value.status === 'DIG'
})

// Copiar chave da NFe
const copiarChave = () => {
  if (nota.value?.nfechave) {
    navigator.clipboard.writeText(nota.value.nfechave)
    $q.notify({
      type: 'positive',
      message: 'Chave copiada!',
      position: 'bottom',
      timeout: 1000,
    })
  }
}

// Alterar status manualmente
const statusDialog = ref(false)

const abrirDialogStatus = () => {
  statusDialog.value = true
}

const alterarStatus = async (novoStatus) => {
  const statusLabel = STATUS_OPTIONS.find((s) => s.value === novoStatus)?.label
  $q.dialog({
    title: 'Confirmar alteração de status',
    message: `Esta ação irá alterar o status da nota para "${statusLabel}". Digite ALTERAR para confirmar:`,
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val === 'ALTERAR',
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'warning' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.alterarStatusNfe(nota.value.codnotafiscal, novoStatus)

      $q.notify({
        type: 'positive',
        message: 'Status alterado com sucesso',
      })

      statusDialog.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao alterar status',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// Limpar autorização/cancelamento
const limparAutorizacao = async () => {
  $q.dialog({
    title: 'Limpar Autorização',
    message:
      'Esta ação irá limpar a autorização e cancelamento da nota. O status será alterado para "Erro". Digite LIMPAR para confirmar:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val === 'LIMPAR',
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'warning' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.alterarStatusNfe(nota.value.codnotafiscal, {
        status: 'ERR',
        nfeautorizacao: null,
        nfedataautorizacao: null,
        nfecancelamento: null,
        nfedatacancelamento: null,
      })

      $q.notify({
        type: 'positive',
        message: 'Autorização e cancelamento limpos com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao limpar autorização',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// Limpar cancelamento
const limparCancelamento = async () => {
  $q.dialog({
    title: 'Limpar Cancelamento',
    message:
      'Esta ação irá limpar o cancelamento da nota. O status será alterado para "Autorizada". Digite LIMPAR para confirmar:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val === 'LIMPAR',
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'warning' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.alterarStatusNfe(nota.value.codnotafiscal, {
        status: 'AUT',
        nfecancelamento: null,
        nfedatacancelamento: null,
      })

      $q.notify({
        type: 'positive',
        message: 'Cancelamento limpo com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao limpar cancelamento',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// Limpar inutilização
const limparInutilizacao = async () => {
  $q.dialog({
    title: 'Limpar Inutilização',
    message:
      'Esta ação irá limpar a inutilização da nota. O status será alterado para "Erro". Digite LIMPAR para confirmar:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val === 'LIMPAR',
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'warning' },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.alterarStatusNfe(nota.value.codnotafiscal, {
        status: 'ERR',
        nfeinutilizacao: null,
        nfedatainutilizacao: null,
      })

      $q.notify({
        type: 'positive',
        message: 'Inutilização limpa com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao limpar inutilização',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// Atalho F9 para enviar NFe
const handleKeyDown = (e) => {
  if (e.key === 'F9') {
    e.preventDefault()
    if (podeEnviar.value && !loadingNfe.value) {
      enviarNfe()
    }
  }
}

// Lifecycle
onMounted(() => {
  loadData()
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <q-page padding>
    <!-- Loading -->
    <div v-if="loadingNota" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Conteúdo -->
    <div v-else-if="nota">
      <!-- Header com Voltar -->
      <div class="row items-center q-mb-md" style="flex-wrap: nowrap">
        <q-btn
          flat
          dense
          round
          icon="arrow_back"
          to="/nota"
          class="q-mr-sm"
          style="flex-shrink: 0"
        />
        <div class="text-h5 ellipsis" style="flex: 1; min-width: 0">
          {{ getModeloLabel(nota.modelo) }}
          {{ formatNumero(nota.numero) }}
          - Série
          {{ nota.serie }}
        </div>
        <q-btn
          flat
          dense
          color="grey-7"
          icon="edit"
          :to="{ name: 'nota-fiscal-edit', params: { codnotafiscal: route.params.codnotafiscal } }"
          v-if="!notaBloqueada"
          class="q-mr-sm"
        >
          <q-tooltip>Editar</q-tooltip>
        </q-btn>
        <q-btn flat dense color="grey-7" icon="content_copy" @click="duplicarNota" class="q-mr-sm">
          <q-tooltip>Duplicar Nota Fiscal</q-tooltip>
        </q-btn>
        <q-btn flat dense color="grey-7" icon="print" @click="abrirEspelhoPdf" class="q-mr-sm">
          <q-tooltip>Espelho da Nota Fiscal</q-tooltip>
        </q-btn>
        <q-btn
          flat
          dense
          color="orange"
          icon="merge_type"
          @click="incorporarValores"
          v-if="podeIncorporar"
          class="q-mr-sm"
        >
          <q-tooltip>Incorporar valores (desconto, frete, seguro, outras) aos produtos</q-tooltip>
        </q-btn>
        <q-btn
          flat
          dense
          color="grey-7"
          icon="calculate"
          @click="recalcularTributacao"
          v-if="podeRecalcularTributacao"
          class="q-mr-sm"
        >
          <q-tooltip>Recalcular tributação</q-tooltip>
        </q-btn>
        <q-btn
          flat
          dense
          color="negative"
          icon="delete"
          @click="handleDelete"
          v-if="!notaBloqueada"
        >
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
      </div>

      <div class="row q-col-gutter-md">
        <!-- FILIAL -->
        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="row items-center justify-between">
                <div class="text-h6">
                  <q-icon name="business" size="1.5em" class="q-mr-sm" />
                  Filial
                </div>
              </div>
            </q-card-section>
            <q-card-section>
              <!-- FILILA / LOCAL -->
              <div class="text-caption text-grey-7">Filial / Local de Estoque</div>
              <div class="text-subtitle1 text-weight-bold">
                {{ nota.filial?.filial || '-' }}
                <span class="text-grey-7">
                  /
                  {{ nota.estoqueLocal?.estoquelocal || '-' }}
                </span>
              </div>

              <!-- NATUREZA -->
              <div class="text-caption text-grey-7">Natureza de Operação</div>
              <div class="text-body2 text-weight-medium">
                {{ nota.codoperacao === 1 ? 'Entrada' : 'Saída' }} |
                {{ nota.naturezaOperacao?.naturezaoperacao || '-' }}
              </div>

              <!-- Emissao -->
              <div class="text-caption text-grey-7">Emissão</div>
              <div class="text-body2">{{ formatDateTime(nota.emissao) }}</div>

              <!-- saida  -->
              <div class="text-caption text-grey-7">Saída/Entrada</div>
              <div class="text-body2">{{ formatDateTime(nota.saida) }}</div>

              <!-- Negócios Vinculados -->
              <template v-if="negociosVinculados.length > 0">
                <div class="text-caption text-grey-7 q-mt-sm">Negócios</div>
                <div class="text-body2">
                  <a
                    v-for="(codnegocio, index) in negociosVinculados"
                    :key="codnegocio"
                    :href="getNegocioUrl(codnegocio)"
                    target="_blank"
                    class="text-primary text-weight-medium"
                    style="text-decoration: none"
                  >
                    {{ formatCodNegocio(codnegocio) }}
                    <span v-if="index < negociosVinculados.length - 1">,</span>
                  </a>
                </div>
              </template>
            </q-card-section>
          </q-card>
        </div>

        <!-- TRANSPORTE -->
        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="q-mb-md full-height">
            <q-card-section class="bg-primary text-white">
              <div class="row items-center justify-between">
                <div class="text-h6">
                  <q-icon name="local_shipping" size="1.5em" class="q-mr-sm" />
                  Transporte
                </div>
              </div>
            </q-card-section>

            <q-card-section>
              <!-- <div class="text-subtitle1 text-weight-bold q-mb-md">TRANSPORTADOR / VOLUMES TRANSPORTADOS</div> -->
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Frete</div>
                <div class="text-body1 ellipsis">{{ getFreteLabel(nota.frete) }}</div>
              </div>

              <div class="col-12 col-md-6" v-if="nota.transportador">
                <div class="text-caption text-grey-7">Transportador</div>
                <div class="text-body1 ellipsis">{{ nota.transportador.fantasia }}</div>
              </div>

              <div class="col-6 col-md-3" v-if="nota.placa">
                <div class="text-caption text-grey-7">Placa</div>
                <div class="text-body2 ellipsis">
                  {{ nota.placa }}/{{ nota.estadoPlaca.sigla || nota.estadoPlaca }}
                </div>
              </div>

              <div class="col-6 col-md-2" v-if="nota.volumes">
                <div class="text-caption text-grey-7">Volumes</div>
                <div class="text-body2 ellipsis">
                  {{ nota.volumes }}
                  {{ nota.volumesespecie }}
                  {{ nota.volumesmarca }}
                  <template v-if="nota.volumesnumero">N {{ nota.volumesnumero }}</template>
                </div>
              </div>

              <div class="col-6 col-md-2" v-if="nota.pesobruto">
                <div class="text-caption text-grey-7">Peso Bruto</div>
                <div class="text-body2 ellipsis">{{ formatDecimal(nota.pesobruto, 3) }} kg</div>
              </div>

              <div class="col-6 col-md-2" v-if="nota.pesoliquido">
                <div class="text-caption text-grey-7">Peso Líquido</div>
                <div class="text-body2 ellipsis">{{ formatDecimal(nota.pesoliquido, 3) }} kg</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- NFE -->
        <div class="col-12 col-md-6">
          <q-card flat bordered class="full-height flex column">
            <q-card-section class="bg-primary text-white">
              <div class="row items-center justify-between">
                <div class="text-h6">
                  <q-icon name="code" size="1.5em" class="q-mr-sm" />
                  NFe
                </div>
              </div>
            </q-card-section>

            <q-card-section class="col-grow">
              <template v-if="nota.modelo">
                <div class="text-caption text-grey-7">
                  {{ getModeloLabel(nota.modelo) }}
                </div>
                <div class="text-caption" style="font-family: monospace">
                  {{ formatNumero(nota.numero) }} - Série {{ nota.serie }}
                </div>
              </template>

              <!-- Chave de Acesso -->
              <template v-if="nota.nfechave">
                <div class="text-caption text-grey-7">Chave</div>
                <div class="text-caption row items-center">
                  <span style="font-family: monospace">
                    {{ formatChave(nota.nfechave) }}
                  </span>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    icon="content_copy"
                    color="grey-7"
                    class="q-ml-xs"
                    @click="copiarChave"
                  >
                    <q-tooltip>Copiar chave</q-tooltip>
                  </q-btn>
                </div>
              </template>

              <!-- STATUS -->
              <div class="text-caption text-grey-7">Status da Nota</div>
              <div class="text-body1">
                <q-badge :color="getStatusColor(nota.status)" class="text-subtitle2">
                  <q-icon :name="getStatusIcon(nota.status)" size="sm" class="q-mr-xs" />
                  {{ getStatusLabel(nota.status) }}
                </q-badge>
              </div>

              <!-- Autorizacao  -->
              <template v-if="nota.nfeautorizacao">
                <div class="text-caption text-grey-7">Autorização</div>
                <div class="text-caption row items-center">
                  <span style="font-family: monospace">
                    {{ formatProtocolo(nota.nfeautorizacao) }}
                  </span>
                  <span class="text-grey-7">
                    |
                    {{ formatDateTime(nota.nfedataautorizacao) }}
                  </span>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    icon="clear"
                    color="negative"
                    class="q-ml-xs"
                    @click="limparAutorizacao"
                  >
                    <q-tooltip>Limpar autorização e cancelamento</q-tooltip>
                  </q-btn>
                </div>
              </template>

              <!-- Cancelamento  -->
              <template v-if="nota.nfecancelamento">
                <div class="text-caption text-grey-7">Cancelamento</div>
                <div class="text-caption row items-center">
                  <span style="font-family: monospace">
                    {{ formatProtocolo(nota.nfecancelamento) }}
                  </span>
                  <span class="text-grey-7">
                    |
                    {{ formatDateTime(nota.nfedatacancelamento) }}
                  </span>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    icon="clear"
                    color="negative"
                    class="q-ml-xs"
                    @click="limparCancelamento"
                  >
                    <q-tooltip>Limpar cancelamento</q-tooltip>
                  </q-btn>
                </div>
              </template>

              <!-- Inutilizacao  -->
              <template v-if="nota.nfeinutilizacao">
                <div class="text-caption text-grey-7">Inutilização</div>
                <div class="text-caption row items-center">
                  <span style="font-family: monospace">
                    {{ formatProtocolo(nota.nfeinutilizacao) }}
                  </span>
                  <span class="text-grey-7">
                    |
                    {{ formatDateTime(nota.nfedatainutilizacao) }}
                  </span>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    icon="clear"
                    color="negative"
                    class="q-ml-xs"
                    @click="limparInutilizacao"
                  >
                    <q-tooltip>Limpar inutilização</q-tooltip>
                  </q-btn>
                </div>
              </template>

              <!-- Justificativa  -->
              <template v-if="nota.justificativa">
                <div class="text-caption text-grey-7">Justificativa</div>
                <div class="text-caption row items-center">
                  {{ nota.justificativa }}
                </div>
              </template>
            </q-card-section>

            <!-- Botões de ação da NFe -->
            <q-separator v-if="nota?.emitida" />

            <q-card-actions align="right" v-if="nota?.emitida">
              <!-- Enviar (F9) -->
              <q-btn
                v-if="podeEnviar"
                dense
                round
                flat
                color="secondary"
                icon="send"
                @click="enviarNfe"
                :loading="loadingNfe"
              >
                <q-tooltip>Criar XML e enviar para SEFAZ</q-tooltip>
              </q-btn>

              <!-- Consultar -->
              <q-btn
                v-if="podeConsultar"
                dense
                round
                flat
                color="primary"
                icon="refresh"
                @click="consultarNfe"
                :loading="loadingConsultar"
              >
                <q-tooltip>Consultar situação na SEFAZ</q-tooltip>
              </q-btn>

              <!-- Abrir DANFE -->
              <q-btn
                v-if="podeAbrirDanfe"
                dense
                round
                flat
                color="secondary"
                icon="picture_as_pdf"
                @click="abrirDanfe"
              >
                <q-tooltip>Abrir DANFE</q-tooltip>
              </q-btn>

              <!-- Abrir XML -->
              <q-btn
                v-if="podeAbrirXml"
                dense
                round
                flat
                color="orange"
                icon="code"
                @click="abrirXml"
              >
                <q-tooltip>Abrir XML</q-tooltip>
              </q-btn>

              <!-- Enviar Email -->
              <q-btn
                v-if="podeEnviarEmail"
                dense
                round
                flat
                color="primary"
                icon="email"
                @click="enviarEmailNfe"
                :loading="loadingEmail"
              >
                <q-tooltip>Enviar por email</q-tooltip>
              </q-btn>

              <!-- Cancelar -->
              <q-btn
                v-if="podeCancelar"
                dense
                round
                flat
                color="negative"
                icon="cancel"
                @click="cancelarNfe"
                :loading="loadingCancelar"
              >
                <q-tooltip>Cancelar NFe</q-tooltip>
              </q-btn>

              <!-- Inutilizar -->
              <q-btn
                v-if="podeInutilizar"
                dense
                round
                flat
                color="warning"
                icon="block"
                @click="inutilizarNfe"
                :loading="loadingInutilizar"
              >
                <q-tooltip>Inutilizar NFe</q-tooltip>
              </q-btn>

              <!-- Alterar Status -->
              <q-btn dense round flat color="grey-7" icon="edit_note" @click="abrirDialogStatus">
                <q-tooltip>Alterar status manualmente</q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
        </div>

        <!-- Destinatário/Remetente -->
        <div class="col-12 col-lg-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="row items-center justify-between">
                <div class="text-h6">
                  <q-icon
                    :name="nota.pessoa?.fisica === true ? 'person' : 'business'"
                    size="1.5em"
                    class="q-mr-sm"
                  />
                  {{ nota.codoperacao === 1 ? 'Destinatário' : 'Remetente' }}
                </div>
              </div>
            </q-card-section>

            <q-card-section>
              <div class="row q-col-gutter-sm">
                <!-- NOME -->
                <div class="col-12 col-sm-8 col-md-5">
                  <div class="text-caption text-grey-7">Nome | Razão Social</div>
                  <div class="text-body1 text-weight-bold text-primary ellipsis">
                    {{ nota.pessoa?.fantasia || '-' }}
                    <span class="text-grey-7">
                      |
                      {{ nota.pessoa?.pessoa || '-' }}
                    </span>
                  </div>
                </div>

                <!-- CNPJ -->
                <div class="col-12 col-sm-4 col-md-2">
                  <div class="text-caption text-grey-7">
                    {{ nota.pessoa?.fisica === 1 ? 'CPF' : 'CNPJ' }}
                  </div>
                  <div class="text-body1 text-weight-bold text-primary ellipsis">
                    {{ formatCnpjCpf(nota.pessoa?.cnpj, nota.pessoa?.fisica) }}
                  </div>
                </div>

                <!-- IE -->
                <div class="col-6 col-sm-8 col-md-2" v-if="nota.pessoa?.ie">
                  <div class="text-caption text-grey-7">Inscrição Estadual</div>
                  <div class="text-body2 ellipsis">{{ nota.pessoa.ie }}</div>
                </div>

                <!-- EMAIL -->
                <div class="col-12 col-sm-4 col-md-3" v-if="nota.pessoa?.email">
                  <div class="text-caption text-grey-7">E-MAIL</div>
                  <div class="text-body2 ellipsis">{{ nota.pessoa.email }}</div>
                </div>

                <!-- ENDEREÇO -->
                <div class="col-12 col-sm-8 col-md-3" v-if="nota.pessoa?.endereco">
                  <div class="text-caption text-grey-7">Endereço Fiscal</div>
                  <div class="text-body2 ellipsis">
                    {{ nota.pessoa.endereco }},
                    {{ nota.pessoa.numero }}
                  </div>
                </div>

                <!-- BAIRRO -->
                <div class="col-12 col-sm-4 col-md-2" v-if="nota.pessoa?.bairro">
                  <div class="text-caption text-grey-7">Bairro</div>
                  <div class="text-body2 ellipsis">{{ nota.pessoa.bairro }}</div>
                </div>

                <!-- CIDADE -->
                <div class="col-12 col-sm-5 col-md-2" v-if="nota.pessoa?.cidade">
                  <div class="text-caption text-grey-7">Cidade</div>
                  <div class="text-body2 ellipsis">
                    {{ nota.pessoa.cidade }} / {{ nota.pessoa.uf }}
                  </div>
                </div>

                <!-- CEP -->
                <div class="col-12 col-sm-3 col-md-2" v-if="nota.pessoa?.cep">
                  <div class="text-caption text-grey-7">CEP</div>
                  <div class="text-body2 ellipsis">{{ nota.pessoa.cep }}</div>
                </div>

                <!-- TELEFONE -->
                <div class="col-6 col-sm-4 col-md-3" v-if="nota.pessoa?.telefone1">
                  <div class="text-caption text-grey-7">Telefone</div>
                  <div class="text-body2 ellipsis">{{ nota.pessoa.telefone1 }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Totais -->
        <div class="col-12 col-lg-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="row items-center justify-between">
                <div class="text-h6">
                  <q-icon name="calculate" size="1.5em" class="q-mr-sm" />
                  Totais
                </div>
              </div>
            </q-card-section>
            <q-card-section>
              <div class="row q-col-gutter-sm">
                <!-- ICMS -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">ICMS</div>
                  <div class="text-body2">
                    {{ formatCurrency(nota.baseicms) }} /
                    {{ formatCurrency(nota.valoricms) }}
                  </div>
                </div>

                <!-- ICMS ST -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">ICMS ST</div>
                  <div class="text-body2">
                    {{ formatCurrency(nota.baseicmsst) }} /
                    {{ formatCurrency(nota.valoricmsst) }}
                  </div>
                </div>

                <!-- IPI -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">IPI</div>
                  <div class="text-body2">
                    {{ formatCurrency(nota.valoripi) }}
                  </div>
                </div>

                <!-- PIS -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">PIS</div>
                  <div class="text-body2">
                    {{ formatCurrency(nota.valorpis) }}
                  </div>
                </div>

                <!-- COFINS -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">COFINS</div>
                  <div class="text-body2">
                    {{ formatCurrency(nota.valorcofins) }}
                  </div>
                </div>

                <!-- IBPT -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">IBPT</div>
                  <div class="text-body2">
                    {{ formatCurrency(nota.valoribpt) }}
                  </div>
                </div>

                <!-- Produtos -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">Produtos</div>
                  <div class="text-body2 text-weight-bold">
                    {{ formatCurrency(nota.valorprodutos) }}
                  </div>
                </div>

                <!-- Desconto -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">Desconto</div>
                  <div class="text-body2">{{ formatCurrency(nota.valordesconto) }}</div>
                </div>

                <!-- Frete -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">Frete</div>
                  <div class="text-body2">{{ formatCurrency(nota.valorfrete) }}</div>
                </div>

                <!-- Seguro -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">Seguro</div>
                  <div class="text-body2">{{ formatCurrency(nota.valorseguro) }}</div>
                </div>

                <!-- Outras Despesas -->
                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">Outras Despesas</div>
                  <div class="text-body2">{{ formatCurrency(nota.valoroutras) }}</div>
                </div>

                <div class="col-6 col-sm-4 col-md-2 q-py-sm">
                  <div class="text-caption text-grey-7">Total</div>
                  <div class="text-h6 text-weight-bold text-primary">
                    {{ formatCurrency(nota.valortotal) }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Itens -->
        <div class="col-12">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="row items-center justify-between">
                <div class="text-h6">
                  <q-icon name="inventory_2" size="1.5em" class="q-mr-sm" />
                  Itens
                  <q-badge
                    color="white"
                    text-color="primary"
                    class="q-ml-sm text-weight-bold text-body1"
                  >
                    {{ itens.length }}
                  </q-badge>
                  <q-btn
                    v-if="!notaBloqueada"
                    flat
                    dense
                    color="white"
                    icon="add"
                    size="md"
                    @click="novoItem"
                    class="q-ml-sm"
                  >
                    <q-tooltip>Adicionar Item</q-tooltip>
                  </q-btn>
                </div>

                <!-- Paginação no topo (mobile/desktop) -->
                <div v-if="totalPaginasItens > 1 && $q.screen.gt.xs">
                  <q-pagination
                    :size="$q.screen.lt.sm ? 'sm' : 'md'"
                    color="white"
                    active-color="blue"
                    v-model="paginaAtualItens"
                    :max="totalPaginasItens"
                    :max-pages="5"
                    direction-links
                    boundary-links
                    @update:model-value="mudarPaginaItens"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-section>
              <!-- Empty State -->
              <div v-if="itens.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
                Nenhum item adicionado
              </div>

              <!-- Cards dos Itens -->
              <div v-else class="row q-col-gutter-md">
                <NotaFiscalItemCard
                  v-for="item in itensPaginados"
                  :key="item.codnotafiscalprodutobarra"
                  :item="item"
                  :nota-bloqueada="notaBloqueada"
                  @delete="handleDeleteItem"
                />
              </div>

              <!-- PAGINACAO RODAPE -->
              <div class="row items-center justify-between" v-if="itens.length > 0">
                <!-- Info da paginação -->
                <div
                  v-if="itens.length > itensPorPagina"
                  class="text-center text-caption text-grey-7 q-mt-sm"
                >
                  Mostrando {{ (paginaAtualItens - 1) * itensPorPagina + 1 }}
                  -
                  {{ Math.min(paginaAtualItens * itensPorPagina, itens.length) }}
                  de {{ itens.length }} itens
                </div>

                <!-- Paginação no rodapé -->
                <div v-if="totalPaginasItens > 1 && $q.screen.lt.sm">
                  <q-pagination
                    :size="$q.screen.lt.sm ? 'sm' : 'md'"
                    color="primary"
                    v-model="paginaAtualItens"
                    :max="totalPaginasItens"
                    :max-pages="5"
                    direction-links
                    boundary-links
                    @update:model-value="mudarPaginaItens"
                  />
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Informações Adicionais -->
        <div class="col-12" v-if="nota.observacoes || true">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="row items-center justify-between">
                <div class="text-h6">
                  <q-icon name="speaker_notes" size="1.5em" class="q-mr-sm" />
                  Observações
                </div>
              </div>
            </q-card-section>
            <q-card-section>
              <div class="text-body2" style="white-space: pre-wrap">{{ nota.observacoes }}</div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Formas de Pagamento -->
        <div class="col-12 col-md-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                <q-icon name="payments" size="1.5em" class="q-mr-sm" />
                Formas de Pagamento
                <q-badge color="white" text-color="primary" class="q-ml-sm">
                  {{ pagamentos.length }}
                </q-badge>
                <q-btn
                  v-if="!notaBloqueada"
                  flat
                  dense
                  color="white"
                  icon="add"
                  size="md"
                  @click="novoPagamento"
                  class="q-ml-sm"
                >
                  <q-tooltip>Adicionar Forma de Pagamento</q-tooltip>
                </q-btn>
              </div>
            </q-card-section>
            <q-card-section>
              <div v-if="pagamentos.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
                Nenhuma forma de pagamento adicionada
              </div>

              <div v-else class="row q-col-gutter-md">
                <div
                  v-for="pag in pagamentos"
                  :key="pag.codnotafiscalpagamento"
                  class="col-xs-12 col-sm-4 col-md-6 col-lg-4"
                >
                  <q-card flat bordered class="full-height flex column">
                    <q-card-section class="col">
                      <div class="row items-center justify-between q-mb-md">
                        <div class="row items-center" style="min-width: 0; flex: 1">
                          <q-avatar
                            :color="getPagamentoColor(pag.tipo)"
                            text-color="white"
                            size="md"
                            class="q-mr-sm"
                            style="flex-shrink: 0"
                          >
                            <q-icon :name="getPagamentoIcon(pag.tipo)" />
                          </q-avatar>
                          <div style="min-width: 0; flex: 1">
                            <div class="text-subtitle2 text-weight-bold ellipsis">
                              {{ pag.tipodescricao || '-' }}
                            </div>
                            <div
                              v-if="pag.bandeiradescricao"
                              class="text-caption text-grey-7 ellipsis"
                            >
                              {{ pag.bandeiradescricao }}
                            </div>
                          </div>
                        </div>
                        <q-badge v-if="pag.avista" color="secondary" outline style="flex-shrink: 0">
                          À vista
                        </q-badge>
                      </div>

                      <div class="text-h5 text-primary text-weight-bold q-mb-sm">
                        {{ formatCurrency(pag.valorpagamento) }}
                      </div>

                      <div v-if="pag.fantasia" class="row items-center q-mb-xs">
                        <q-icon name="business" size="xs" class="q-mr-xs text-grey-6" />
                        <div class="text-caption text-grey-7 ellipsis">
                          {{ pag.fantasia }}
                        </div>
                      </div>

                      <div v-if="pag.autorizacao" class="row items-center q-mb-xs">
                        <q-icon name="verified" size="xs" class="q-mr-xs text-grey-6" />
                        <div class="text-caption text-grey-7">Aut: {{ pag.autorizacao }}</div>
                      </div>

                      <div v-if="pag.troco" class="row items-center">
                        <q-icon name="change_circle" size="xs" class="q-mr-xs text-grey-6" />
                        <div class="text-caption text-grey-7">
                          Troco: {{ formatCurrency(pag.troco) }}
                        </div>
                      </div>
                    </q-card-section>

                    <q-separator v-if="!notaBloqueada" />

                    <q-card-actions v-if="!notaBloqueada" align="right" class="col-auto">
                      <q-btn
                        flat
                        dense
                        icon="edit"
                        color="primary"
                        size="sm"
                        @click="editarPagamento(pag)"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        dense
                        icon="delete"
                        color="negative"
                        size="sm"
                        @click="excluirPagamento(pag)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </q-card-actions>
                  </q-card>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Duplicatas -->
        <div class="col-12 col-md-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                <q-icon name="receipt_long" size="1.5em" class="q-mr-sm" />
                Duplicatas
                <q-badge color="white" text-color="primary" class="q-ml-sm">
                  {{ duplicatas.length }}
                </q-badge>
                <q-btn
                  v-if="!notaBloqueada"
                  flat
                  dense
                  color="white"
                  icon="add"
                  size="md"
                  @click="novaDuplicata"
                  class="q-ml-sm"
                >
                  <q-tooltip>Adicionar Duplicata</q-tooltip>
                </q-btn>
              </div>
            </q-card-section>
            <q-card-section>
              <div v-if="duplicatas.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
                Nenhuma duplicata adicionada
              </div>

              <div v-else class="row q-col-gutter-md">
                <div
                  v-for="dup in duplicatas"
                  :key="dup.codnotafiscalduplicatas"
                  class="col-xs-12 col-sm-4 col-md-6 col-lg-4"
                >
                  <q-card flat bordered class="full-height flex column">
                    <q-card-section class="col">
                      <div class="row items-center q-mb-md">
                        <q-avatar
                          color="indigo"
                          text-color="white"
                          size="md"
                          class="q-mr-sm"
                          style="flex-shrink: 0"
                        >
                          <q-icon name="receipt_long" />
                        </q-avatar>
                        <div style="min-width: 0; flex: 1">
                          <div class="text-subtitle2 text-weight-bold ellipsis">
                            {{ dup.fatura || '-' }}
                          </div>
                        </div>
                      </div>

                      <div class="text-caption text-grey-7">Vencimento</div>
                      <div class="text-body2 q-mb-sm">{{ formatDate(dup.vencimento) }}</div>

                      <div class="text-caption text-grey-7">Valor</div>
                      <div class="text-h5 text-primary text-weight-bold">
                        {{ formatCurrency(dup.valor) }}
                      </div>
                    </q-card-section>

                    <q-separator v-if="!notaBloqueada" />

                    <q-card-actions v-if="!notaBloqueada" align="right" class="col-auto">
                      <q-btn
                        flat
                        dense
                        icon="edit"
                        color="primary"
                        size="sm"
                        @click="editarDuplicata(dup)"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        dense
                        icon="delete"
                        color="negative"
                        size="sm"
                        @click="excluirDuplicata(dup)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </q-card-actions>
                  </q-card>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Notas Referenciadas -->
        <div class="col-12 col-md-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                <q-icon name="link" size="1.5em" class="q-mr-sm" />
                Notas Fiscais Referenciadas
                <q-badge color="white" text-color="primary" class="q-ml-sm">
                  {{ referenciadas.length }}
                </q-badge>
                <q-btn
                  v-if="!notaBloqueada"
                  flat
                  dense
                  color="white"
                  icon="add"
                  size="md"
                  @click="novaReferenciada"
                  class="q-ml-sm"
                >
                  <q-tooltip>Adicionar Nota Referenciada</q-tooltip>
                </q-btn>
              </div>
            </q-card-section>
            <q-card-section>
              <div
                v-if="referenciadas.length === 0"
                class="text-left q-mt-none q-mb-lg text-grey-7"
              >
                Nenhuma nota fiscal referenciada
              </div>

              <div v-else class="row q-col-gutter-md">
                <div
                  v-for="ref in referenciadas"
                  :key="ref.codnotafiscalreferenciada"
                  class="col-xs-12 col-sm-6 col-md-6 col-lg-4"
                >
                  <q-card flat bordered class="full-height flex column">
                    <q-card-section class="col">
                      <div class="row items-center q-mb-sm">
                        <q-icon name="link" size="sm" color="primary" class="q-mr-sm" />
                        <div class="text-subtitle2 text-weight-bold">Nota Referenciada</div>
                      </div>

                      <div class="text-caption text-grey-7">Chave de Acesso</div>
                      <div class="text-caption" style="font-family: monospace">
                        {{ formatChave(ref.nfechave) }}
                      </div>
                    </q-card-section>

                    <q-separator v-if="!notaBloqueada" />

                    <q-card-actions v-if="!notaBloqueada" align="right" class="col-auto">
                      <q-btn
                        flat
                        dense
                        icon="edit"
                        color="primary"
                        size="sm"
                        @click="editarReferenciada(ref)"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        dense
                        icon="delete"
                        color="negative"
                        size="sm"
                        @click="excluirReferenciada(ref)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </q-card-actions>
                  </q-card>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Cartas de Correção -->
        <div class="col-12 col-sm-12 col-md-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                <q-icon name="edit_note" size="1.5em" class="q-mr-sm" />
                Cartas de Correção
                <q-badge color="white" text-color="primary" class="q-ml-sm">
                  {{ cartasCorrecao.length }}
                </q-badge>
                <q-btn
                  v-if="nota.status == 'AUT'"
                  flat
                  dense
                  color="white"
                  icon="add"
                  size="md"
                  @click="novaCartaCorrecao"
                  class="q-ml-sm"
                >
                  <q-tooltip>Adicionar Carta de Correção</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="cartasCorrecao.length > 0"
                  flat
                  dense
                  color="white"
                  icon="picture_as_pdf"
                  size="md"
                  @click="abrirCartaCorrecaoPdf"
                >
                  <q-tooltip>Abrir PDF da Carta de Correção</q-tooltip>
                </q-btn>
              </div>
            </q-card-section>
            <q-card-section>
              <div
                v-if="cartasCorrecao.length === 0"
                class="text-left q-mt-none q-mb-lg text-grey-7"
              >
                Nenhuma carta de correção emitida
              </div>

              <div v-else class="row q-col-gutter-md">
                <div
                  v-for="carta in cartasCorrecao"
                  :key="carta.codnotafiscalcartacorrecao"
                  class="col-xs-12 col-lg-6"
                >
                  <q-card flat bordered class="full-height flex column">
                    <q-card-section class="col">
                      <div class="row items-center justify-between q-mb-sm">
                        <div class="row items-center">
                          <q-icon name="edit_note" size="sm" color="primary" class="q-mr-sm" />
                          <div class="text-subtitle2 text-weight-bold">
                            Correção Seq {{ carta.sequencia }}
                          </div>
                        </div>
                        <q-badge color="secondary">Autorizada</q-badge>
                      </div>

                      <div class="row q-col-gutter-sm">
                        <div class="col-12">
                          <div class="text-caption text-grey-7">Protocolo</div>
                          <div class="text-caption ellipsis">
                            <span style="font-family: monospace">
                              {{ formatProtocolo(carta.protocolo) }}
                            </span>
                            <span class="text-grey-7">
                              | {{ formatDateTime(carta.protocolodata) }}
                            </span>
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="text-caption text-grey-7">Correção</div>
                          <div
                            class="text-caption"
                            :class="
                              carta.sequencia !== maiorSequenciaCartaCorrecao
                                ? 'text-grey-8 text-strike'
                                : ''
                            "
                            style="white-space: pre-wrap"
                          >
                            {{ carta.texto || '-' }}
                          </div>
                        </div>
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Erro -->
    <q-card v-else flat bordered class="q-pa-xl text-center">
      <q-icon name="error" size="4em" color="negative" />
      <div class="text-h6 text-grey-7 q-mt-md">Nota fiscal não encontrada</div>
    </q-card>

    <!-- Dialogs -->
    <NotaFiscalItemDialog
      v-model="itemDialog"
      :item="itemSelecionado"
      :nota-bloqueada="notaBloqueada"
      @save="createItem"
    />

    <NotaFiscalPagamentoDialog
      v-model="pagamentoDialog"
      :pagamento="pagamentoSelecionado"
      :nota-bloqueada="notaBloqueada"
      @save="salvarPagamento"
      @delete="excluirPagamento"
    />

    <NotaFiscalDuplicataDialog
      v-model="duplicataDialog"
      :duplicata="duplicataSelecionada"
      :nota-bloqueada="notaBloqueada"
      @save="salvarDuplicata"
      @delete="excluirDuplicata"
    />

    <NotaFiscalReferenciadaDialog
      v-model="referenciadaDialog"
      :referenciada="referenciadaSelecionada"
      :nota-bloqueada="notaBloqueada"
      @save="salvarReferenciada"
      @delete="excluirReferenciada"
    />

    <NotaFiscalCartaCorrecaoDialog
      ref="cartaCorrecaoDialogRef"
      v-model="cartaCorrecaoDialog"
      @save="enviarCartaCorrecao"
    />

    <!-- Dialog de Progresso NFe -->
    <q-dialog v-model="loadingNfe" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Processando NFe</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-body2 q-mb-md">{{ progressoNfe.status }}</div>
          <q-linear-progress :value="progressoNfe.percent / 100" color="primary" class="q-mt-md" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialog PDF Carta de Correção -->
    <q-dialog v-model="cartaCorrecaoPdfDialog">
      <q-card style="width: 800px; max-width: 90vw; height: 90vh">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Carta de Correção</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-md" style="height: calc(100% - 56px)">
          <iframe :src="cartaCorrecaoPdfUrl" style="width: 100%; height: 100%; border: none" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialog Espelho -->
    <q-dialog v-model="espelhoPdfDialog">
      <q-card style="width: 800px; max-width: 90vw; height: 90vh">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Espelho da Nota Fiscal</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-md" style="height: calc(100% - 56px)">
          <iframe :src="espelhoPdfUrl" style="width: 100%; height: 100%; border: none" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialog DANFE -->
    <q-dialog v-model="danfeDialog">
      <q-card
        :style="
          nota.modelo === 65
            ? 'width: 400px; max-width: 90vw; height: 90vh'
            : 'width: 800px; max-width: 90vw; height: 90vh'
        "
      >
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ nota.modelo === 65 ? 'DANFE NFCe' : 'DANFE NFe' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-md" style="height: calc(100% - 56px)">
          <iframe :src="danfeUrl" style="width: 100%; height: 100%; border: none" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialog Alterar Status -->
    <q-dialog v-model="statusDialog">
      <q-card style="min-width: 450px">
        <q-card-section>
          <div class="text-h6">Alterar Status da NFe</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-banner class="bg-warning text-grey-8 rounded-borders q-mb-sm">
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            Não altere o status sem ter CERTEZA ABSOLUTA. A alteração pode levar à perda de dados.
            Somente confirme a operação se você tem as informações da nota fiscal para reparar em
            caso de erro.
          </q-banner>
          <div class="text-body2 q-mb-md">Selecione o novo status:</div>
          <div class="row q-col-gutter-sm">
            <div
              v-for="status in STATUS_OPTIONS"
              :key="status.value"
              class="col-4"
              v-show="nota.status !== status.value"
            >
              <q-btn
                unelevated
                :color="status.color"
                class="full-width"
                stack
                @click="alterarStatus(status.value)"
              >
                <q-icon :name="status.icon" size="md" />
                <div class="text-caption q-mt-xs">{{ status.label }}</div>
              </q-btn>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
