import { ref, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'

// Encapsula o reprocessamento assíncrono de indicadores de um período:
// dispara o job, faz o polling do progresso e notifica no fim.
// `getCodperiodo` é uma função que devolve o codperiodo atual (reativo).
// `onConcluido` é chamado após conclusão/cancelamento para recarregar a tela.
export function useReprocessamentoPeriodo(getCodperiodo, onConcluido) {
  const $q = useQuasar()
  const sRh = rhStore()

  const reprocessando = ref(false)
  const progresso = ref(null)
  let pollingTimer = null

  const pararPolling = () => {
    reprocessando.value = false
    if (pollingTimer) {
      clearInterval(pollingTimer)
      pollingTimer = null
    }
  }

  const verificarProgresso = async () => {
    try {
      const data = await sRh.progressoReprocessamento(getCodperiodo())
      if (!data.status) {
        pararPolling()
        return
      }
      progresso.value = data
      if (data.status === 'concluido') {
        pararPolling()
        $q.notify({
          color: 'green-5',
          textColor: 'white',
          icon: 'done',
          message: data.mensagem || 'Reprocessamento concluído',
        })
        if (onConcluido) await onConcluido()
      } else if (data.status === 'erro') {
        pararPolling()
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: data.mensagem || 'Erro no reprocessamento',
        })
      } else if (data.status === 'cancelado') {
        pararPolling()
        $q.notify({
          color: 'orange',
          textColor: 'white',
          icon: 'cancel',
          message: 'Reprocessamento cancelado',
        })
        if (onConcluido) await onConcluido()
      }
    } catch {
      pararPolling()
    }
  }

  const iniciarPolling = () => {
    reprocessando.value = true
    progresso.value = { status: 'processando', progresso: 0, mensagem: 'Iniciando...' }
    pollingTimer = setInterval(verificarProgresso, 3000)
  }

  const reprocessarPeriodo = () => {
    $q.dialog({
      title: 'Reprocessar Indicadores',
      message: 'Reprocessar todas as vendas do período nos indicadores?',
      options: {
        type: 'checkbox',
        model: [],
        items: [{ label: 'Limpar lançamentos automáticos antes de reprocessar', value: 'limpar' }],
      },
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Reprocessar', color: 'primary', flat: true },
    }).onOk(async (opcoes) => {
      try {
        await sRh.reprocessarPeriodo(getCodperiodo(), opcoes.includes('limpar'))
        iniciarPolling()
      } catch (error) {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: extrairErro(error, 'Erro ao iniciar reprocessamento'),
        })
      }
    })
  }

  const cancelarReprocessamento = () => {
    $q.dialog({
      title: 'Cancelar Reprocessamento',
      message: 'Tem certeza que deseja cancelar o reprocessamento em andamento?',
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Confirmar', color: 'primary', flat: true },
    }).onOk(async () => {
      try {
        await sRh.cancelarReprocessamento(getCodperiodo())
      } catch (error) {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: extrairErro(error, 'Erro ao cancelar'),
        })
      }
    })
  }

  // Verifica se já há um reprocessamento em andamento (ex: ao abrir a tela)
  const checarEmAndamento = async () => {
    try {
      const data = await sRh.progressoReprocessamento(getCodperiodo())
      if (data.status === 'processando') {
        iniciarPolling()
        progresso.value = data
      }
    } catch {
      // ignora
    }
  }

  onUnmounted(pararPolling)

  return {
    reprocessando,
    progresso,
    reprocessarPeriodo,
    cancelarReprocessamento,
    pararPolling,
    checarEmAndamento,
  }
}
