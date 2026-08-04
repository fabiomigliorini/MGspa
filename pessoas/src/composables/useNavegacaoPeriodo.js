import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'
import {
  acharColaboradorEquivalente,
  acharIndicadorEquivalente,
} from 'src/utils/rhPeriodo'
import { formataData } from '@components/formatters'

/**
 * Trocar de período mantendo a tela em que o usuário está.
 *
 * O problema: `codperiodocolaborador` e `codindicador` são PKs de tabelas
 * period-scoped — o mesmo colaborador tem IDs diferentes em cada período. Não dá
 * para só trocar o `codperiodo` da URL; é preciso reencontrar o equivalente pela
 * chave de negócio, que é estável.
 */

// As funções de casamento moram em `src/utils/rhPeriodo.js`, sem dependência de
// Vue/Quasar/store — só assim dá pra testá-las em node, sem browser.

export function useNavegacaoPeriodo() {
  const $q = useQuasar()
  const route = useRoute()
  const router = useRouter()
  const sRh = rhStore()

  // codperiodo sendo resolvido — o drawer usa pra marcar o item em carregamento.
  const navegando = ref(null)

  const rotuloPeriodo = (codperiodo) => {
    const p = (sRh.periodos || []).find((x) => String(x.codperiodo) === String(codperiodo))
    if (!p) return 'nesse período'
    return `${formataData(p.periodoinicial)} a ${formataData(p.periodofinal)}`
  }

  const avisarAusente = (oque, codperiodoDestino) => {
    $q.notify({
      color: 'blue-5',
      textColor: 'white',
      icon: 'info',
      message: `${oque} não existe no período ${rotuloPeriodo(codperiodoDestino)}.`,
    })
  }

  // Fallback: dashboard já na aba da unidade de onde o usuário estava. Se essa
  // unidade não existir no período destino, o PeriodoDashboard cai no Resumão.
  const dashboardNaUnidade = (codperiodo, codunidadenegocio) => ({
    name: 'rhDashboard',
    params: { codperiodo },
    query: codunidadenegocio ? { tab: 'un-' + codunidadenegocio } : {},
  })

  const resolverColaborador = async (codperiodoDestino) => {
    // A identidade tem que ser lida ANTES de carregar o período destino: as duas
    // listas dividem o mesmo slot na store.
    const atuais = await sRh.getColaboradores(route.params.codperiodo)
    const atual = atuais.find(
      (c) => String(c.codperiodocolaborador) === String(route.params.codperiodocolaborador),
    )

    const novos = await sRh.getColaboradores(codperiodoDestino)
    const equivalente = acharColaboradorEquivalente(novos, atual?.codcolaborador)

    if (equivalente) {
      return {
        name: 'rhColaboradorDetalhe',
        params: {
          codperiodo: codperiodoDestino,
          codperiodocolaborador: equivalente.codperiodocolaborador,
        },
      }
    }

    avisarAusente(atual?.colaborador?.pessoa?.fantasia || 'O colaborador', codperiodoDestino)
    return dashboardNaUnidade(codperiodoDestino, atual?.setor?.codunidadenegocio)
  }

  const resolverIndicador = async (codperiodoDestino) => {
    const atuais = await sRh.getIndicadores(route.params.codperiodo)
    const atual = atuais.find(
      (i) => String(i.codindicador) === String(route.params.codindicador),
    )

    const novos = await sRh.getIndicadores(codperiodoDestino)
    const equivalente = acharIndicadorEquivalente(novos, atual)

    if (equivalente) {
      return {
        name: 'rhIndicadorExtrato',
        params: { codperiodo: codperiodoDestino, codindicador: equivalente.codindicador },
      }
    }

    const rotulo =
      atual?.colaborador_nome || atual?.setor_nome || atual?.unidade_negocio_nome || 'O indicador'
    avisarAusente(rotulo, codperiodoDestino)
    return dashboardNaUnidade(codperiodoDestino, atual?.codunidadenegocio)
  }

  const resolverMeuPainelColaborador = async (codperiodoDestino) => {
    const atual = await sRh.getMeuPainelColaborador(
      route.params.codperiodo,
      route.params.codperiodocolaborador,
    )
    const codcolaborador = atual?.colaborador?.codcolaborador

    const painel = await sRh.getMeuPainel(codperiodoDestino)
    const candidatos = [painel?.colaborador, ...(painel?.equipe || [])].filter(Boolean)
    const equivalente = acharColaboradorEquivalente(candidatos, codcolaborador)

    if (equivalente) {
      return {
        name: 'rhMeuPainelColaborador',
        params: {
          codperiodo: codperiodoDestino,
          codperiodocolaborador: equivalente.codperiodocolaborador,
        },
      }
    }

    avisarAusente(
      atual?.colaborador?.colaborador?.pessoa?.fantasia || 'O colaborador',
      codperiodoDestino,
    )
    return { name: 'rhMeuPainelDashboard', params: { codperiodo: codperiodoDestino } }
  }

  /** Rota equivalente à atual, no período destino. */
  const resolverRotaNoPeriodo = async (codperiodoDestino) => {
    switch (route.name) {
      case 'rhDashboard':
        // A aba é `un-<codunidadenegocio>`, e unidade não é period-scoped —
        // preserva direto, sem request.
        return {
          name: 'rhDashboard',
          params: { codperiodo: codperiodoDestino },
          query: route.query.tab ? { tab: route.query.tab } : {},
        }

      case 'rhColaboradorDetalhe':
        return await resolverColaborador(codperiodoDestino)

      case 'rhIndicadorExtrato':
        return await resolverIndicador(codperiodoDestino)

      case 'rhMeuPainelColaborador':
        return await resolverMeuPainelColaborador(codperiodoDestino)

      case 'rhMeuPainelDashboard':
      case 'rhMeuPainelExtrato':
        return { name: 'rhMeuPainelDashboard', params: { codperiodo: codperiodoDestino } }

      default:
        return { name: 'rhDashboard', params: { codperiodo: codperiodoDestino } }
    }
  }

  /** Troca de período preservando o contexto. Usado pelos dois drawers. */
  const irParaPeriodo = async (codperiodoDestino) => {
    if (navegando.value) return
    if (String(route.params.codperiodo) === String(codperiodoDestino)) return

    navegando.value = codperiodoDestino
    try {
      router.push(await resolverRotaNoPeriodo(codperiodoDestino))
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao trocar de período'),
      })
      // Não deixa o usuário preso: cai no dashboard do período pedido.
      const meuPainel = String(route.name || '').startsWith('rhMeuPainel')
      router.push({
        name: meuPainel ? 'rhMeuPainelDashboard' : 'rhDashboard',
        params: { codperiodo: codperiodoDestino },
      })
    } finally {
      navegando.value = null
    }
  }

  return { navegando, irParaPeriodo, resolverRotaNoPeriodo }
}
