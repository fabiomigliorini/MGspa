import { watch } from 'vue'
import { round } from 'src/utils/formatters'

// CSTs de ICMS onde não há ICMS próprio (base, percentual e valor devem ser zero)
const ICMS_CST_SEM_ICMS_PROPRIO = [30, 40, 41, 50, 60]

/**
 * Composable para cálculos de impostos de itens da nota fiscal
 * Baseado no sistema legado
 */
export function useNotaFiscalItemCalculos(form, store = null) {
  // Armazena o valor anterior do total final para cálculos
  let valorTotalFinalAnterior = 0

  /**
   * Compara dois valores float com tolerância
   */
  const isEqual = (a, b, tolerance = 0.01) => {
    return Math.abs(a - b) < tolerance
  }

  /**
   * Calcula valor total (quantidade * valor unitário)
   */
  const atualizaTotal = () => {
    // Não recalcula se quantidade ou valor unitário estão vazios (usuário digitando)
    const quantidade = form.value.quantidade
    const valorunitario = form.value.valorunitario
    if (quantidade === null || quantidade === undefined || quantidade === '' ||
        valorunitario === null || valorunitario === undefined || valorunitario === '') {
      return
    }

    form.value.valortotal = round((quantidade || 0) * (valorunitario || 0), 2)

    // Recalcula impostos por KG quando a quantidade muda
    atualizaImpostoKg('fethab', 'kg')
    atualizaImpostoKg('iagro', 'kg')

    atualizaTotalFinal()
  }

  /**
   * Calcula valor unitário (total / quantidade)
   */
  const atualizaUnitario = () => {
    // Não recalcula se valortotal está vazio (usuário digitando)
    const valortotal = form.value.valortotal
    if (valortotal === null || valortotal === undefined || valortotal === '') {
      return
    }

    if ((form.value.quantidade || 0) < 0.01) {
      form.value.quantidade = 1
    }
    form.value.valorunitario = round((valortotal || 0) / (form.value.quantidade || 1), 6)
    atualizaTotalFinal()
  }

  /**
   * Calcula o valor total final (total - desconto + frete + seguro + outras)
   */
  const atualizaTotalFinal = () => {
    // Guarda valor anterior ANTES de recalcular
    valorTotalFinalAnterior = form.value.valortotalfinal || 0

    // Calcula novo valor total final
    const valortotal = Number(form.value.valortotal || 0)
    const valordesconto = Number(form.value.valordesconto || 0)
    const valorfrete = Number(form.value.valorfrete || 0)
    const valorseguro = Number(form.value.valorseguro || 0)
    const valoroutras = Number(form.value.valoroutras || 0)

    const novoTotalFinal = valortotal - valordesconto + valorfrete + valorseguro + valoroutras

    form.value.valortotalfinal = round(novoTotalFinal, 2)

    // Só atualiza bases se o total final mudou
    if (Math.abs(novoTotalFinal - valorTotalFinalAnterior) > 0.001) {
      atualizaBaseImpostos()
    }
  }

  /**
   * Atualiza as bases de todos os impostos quando o total final muda
   */
  const atualizaBaseImpostos = () => {
    const totalNovo = form.value.valortotalfinal || 0
    const totalAntigo = valorTotalFinalAnterior

    atualizaBaseImposto('icms', totalNovo, totalAntigo)
    atualizaBaseImposto('icmsst', totalNovo, totalAntigo)
    atualizaBaseImposto('ipi', totalNovo, totalAntigo)
    atualizaBaseImposto('pis', totalNovo, totalAntigo)
    atualizaBaseImposto('cofins', totalNovo, totalAntigo)
    atualizaBaseImposto('csll', totalNovo, totalAntigo)
    atualizaBaseImposto('irpj', totalNovo, totalAntigo)

    // Recalcula FUNRURAL e SENAR mantendo o percentual e atualizando o valor
    atualizaImpostoPercentual('funrural')
    atualizaImpostoPercentual('senar')

    atualizaImpostoKg('fethab', 'kg')
    atualizaImpostoKg('iagro', 'kg')

    // Atualiza bases dos tributos da reforma tributária
    atualizaBasesTributosReforma(totalNovo, totalAntigo)
  }

  /**
   * Atualiza as bases dos tributos da reforma tributária quando o total muda
   */
  const atualizaBasesTributosReforma = (totalNovo, totalAntigo) => {
    if (!store || !store.editingItem?.tributos || store.editingItem?.tributos.length === 0) {
      return
    }

    store.editingItem?.tributos.forEach((tributo) => {
      // Se não tem base nem alíquota, não faz nada
      if ((tributo.base || 0) <= 0 && (tributo.aliquota || 0) <= 0) {
        return
      }

      let novaBase = tributo.base || 0
      const basereducaopercentual = tributo.basereducaopercentual || 0

      // Se tem percentual de redução de base, sempre recalcula baseado nele
      if (basereducaopercentual > 0 && basereducaopercentual < 100) {
        // Base = Total * (100 - %Redução) / 100
        const percentualBaseCalculo = 100 - basereducaopercentual
        novaBase = (totalNovo * percentualBaseCalculo) / 100
        tributo.basereducao = round(totalNovo - novaBase, 2)
      } else {
        // Sem redução de base, segue a lógica padrão
        // Se a base era igual ao total anterior OU era zero, atualiza para o novo total
        // Caso contrário, mantém a proporção
        if (totalAntigo === 0) {
          novaBase = totalNovo
        } else if (isEqual(tributo.base, totalAntigo) || tributo.base === 0) {
          novaBase = totalNovo
        } else {
          novaBase = (totalNovo / totalAntigo) * tributo.base
        }
        tributo.basereducao = 0
      }

      // Atualiza a base do tributo
      tributo.base = round(novaBase, 2)

      // Recalcula o valor do tributo com base na nova base e alíquota
      tributo.valor = round((novaBase * (tributo.aliquota || 0)) / 100, 2)

      // Se gera crédito, atualiza o valor do crédito também
      if (tributo.geracredito) {
        tributo.valorcredito = tributo.valor
      }
    })
  }

  /**
   * Atualiza impostos baseados apenas em percentual (FUNRURAL, SENAR)
   * Mantém o percentual e recalcula o valor com base no valortotalfinal
   */
  const atualizaImpostoPercentual = (imposto) => {
    const campoPercentual = `${imposto}percentual`
    const campoValor = `${imposto}valor`
    const valorprodutos = form.value.valortotalfinal || 0
    const percentual = form.value[campoPercentual] || 0

    // Recalcula o valor mantendo o percentual
    const valor = (valorprodutos * percentual) / 100

    form.value[campoValor] = round(valor, 2)
  }

  /**
   * Atualiza a base de um imposto específico quando o total muda
   */
  const atualizaBaseImposto = (imposto, totalNovo, totalAntigo) => {
    const campoBase = `${imposto}base`
    const campoPercentual = `${imposto}percentual`

    const valorbase = form.value[campoBase] || 0
    const valorpercentual = form.value[campoPercentual] || 0

    // Se não tem nem base nem percentual, não faz nada
    if (valorbase <= 0 && valorpercentual <= 0) {
      return
    }

    let novaBase = valorbase

    if (imposto === 'icms') {
      // Se CST não tem ICMS próprio, não recalcula base
      if (ICMS_CST_SEM_ICMS_PROPRIO.includes(Number(form.value.icmscst))) {
        return
      }
      // Para ICMS, sempre recalcula baseado no basepercentual
      const campoBasePercentual = `${imposto}basepercentual`
      const valorbasepercentual = form.value[campoBasePercentual] || 0
      if (valorbasepercentual > 0) {
        novaBase = (totalNovo * valorbasepercentual) / 100
      }
    } else if (imposto === 'icmsst') {
      // Para ICMS ST, segue a mesma lógica dos outros impostos
      if (totalAntigo === 0) {
        novaBase = totalNovo
      } else if (isEqual(valorbase, totalAntigo) || valorbase === 0) {
        novaBase = totalNovo
      } else {
        novaBase = (totalNovo / totalAntigo) * valorbase
      }
    } else {
      // Para outros impostos (IPI, PIS, COFINS, CSLL, IRPJ)
      // Se a base era igual ao total anterior OU era zero, atualiza para o novo total
      // Caso contrário, mantém a proporção
      if (totalAntigo === 0) {
        novaBase = totalNovo
      } else if (isEqual(valorbase, totalAntigo) || valorbase === 0) {
        novaBase = totalNovo
      } else {
        novaBase = (totalNovo / totalAntigo) * valorbase
      }
    }

    form.value[campoBase] = round(novaBase, 2)
    atualizaImposto(imposto, 'base')
  }

  /**
   * Atualiza os valores de um imposto (base/percentual/valor)
   * @param {string} imposto - Nome do imposto (icms, ipi, pis, cofins, etc)
   * @param {string} campoalterado - Campo que foi alterado (base, basepercentual, percentual, valor)
   */
  const atualizaImposto = (imposto, campoalterado) => {
    const temBase = !['funrural', 'senar'].includes(imposto)

    const campoBase = `${imposto}base`
    const campoBasePercentual = imposto === 'icms' ? `${imposto}basepercentual` : null
    const campoPercentual = `${imposto}percentual`
    const campoValor = `${imposto}valor`

    const valorprodutos = form.value.valortotalfinal || 0

    let base = temBase ? form.value[campoBase] || 0 : valorprodutos
    let basepercentual = campoBasePercentual ? form.value[campoBasePercentual] || 100 : 100
    let percentual = form.value[campoPercentual] || 0
    let valor = form.value[campoValor] || 0

    switch (campoalterado) {
      case 'basepercentual':
        if (basepercentual > 0) {
          base = (valorprodutos * basepercentual) / 100
        }
      // eslint-disable-next-line no-fallthrough
      case 'percentual':
        if (base === 0 && percentual > 0) {
          base = (valorprodutos * basepercentual) / 100
        }
      // eslint-disable-next-line no-fallthrough
      case 'base':
        valor = (base * percentual) / 100
        break

      case 'valor':
        // Para FUNRURAL e SENAR, não zera o percentual ao alterar o valor
        // pois o valor pode ter sido recalculado automaticamente
        if (!['funrural', 'senar'].includes(imposto)) {
          percentual = 0
        }
        if (base === 0 && valor > 0) {
          base = valorprodutos
        }
        if (base > 0 && valor > 0) {
          percentual = (valor / base) * 100
        }
        break
    }

    // Atualiza os campos do formulário
    if (temBase) {
      form.value[campoBase] = round(base || 0, 2)
    }
    // if (campoBasePercentual) {
    // form.value[campoBasePercentual] = round(basepercentual || 0, 2)
    // }
    // form.value[campoPercentual] = round(percentual || 0, 2)
    form.value[campoValor] = round(valor || 0, 2)
  }

  /**
   * Atualiza impostos calculados por KG (FETHAB e IAGRO)
   */
  const atualizaImpostoKg = (imposto, campoalterado) => {
    const quantidade = form.value.quantidade || 0
    const campoKg = `${imposto}kg`
    const campoValor = `${imposto}valor`

    let kg = form.value[campoKg] || 0
    let valor = form.value[campoValor] || 0

    switch (campoalterado) {
      case 'kg':
        valor = quantidade * kg
        break

      case 'valor':
        kg = 0
        if (quantidade > 0 && valor > 0) {
          kg = valor / quantidade
        }
        break
    }

    // form.value[campoKg] = round(kg || 0, 6)
    form.value[campoValor] = round(valor || 0, 2)
  }

  /**
   * Configura os watchers para campos de valores
   */
  const setupWatchers = () => {
    // Watchers para cálculo de total
    watch(() => form.value.quantidade, atualizaTotal)
    watch(() => form.value.valorunitario, atualizaTotal)
    watch(() => form.value.valortotal, atualizaUnitario)

    // Watchers para cálculo de total final
    watch(() => form.value.valordesconto, atualizaTotalFinal)
    watch(() => form.value.valorfrete, atualizaTotalFinal)
    watch(() => form.value.valorseguro, atualizaTotalFinal)
    watch(() => form.value.valoroutras, atualizaTotalFinal)

    // Watcher para CST do ICMS - zera campos quando CST não tem ICMS próprio
    watch(() => form.value.icmscst, (novoCst) => {
      if (ICMS_CST_SEM_ICMS_PROPRIO.includes(Number(novoCst))) {
        form.value.icmsbase = null
        form.value.icmsbasepercentual = null
        form.value.icmspercentual = null
        form.value.icmsvalor = null
      }
    })

    // Watchers para ICMS
    watch(
      () => form.value.icmsbase,
      () => atualizaImposto('icms', 'base'),
    )
    watch(
      () => form.value.icmsbasepercentual,
      () => atualizaImposto('icms', 'basepercentual'),
    )
    watch(
      () => form.value.icmspercentual,
      () => atualizaImposto('icms', 'percentual'),
    )
    watch(
      () => form.value.icmsvalor,
      () => atualizaImposto('icms', 'valor'),
    )

    // Watchers para ICMS ST
    watch(
      () => form.value.icmsstbase,
      () => atualizaImposto('icmsst', 'base'),
    )
    watch(
      () => form.value.icmsstpercentual,
      () => atualizaImposto('icmsst', 'percentual'),
    )
    watch(
      () => form.value.icmsstvalor,
      () => atualizaImposto('icmsst', 'valor'),
    )

    // Watchers para IPI
    watch(
      () => form.value.ipibase,
      () => atualizaImposto('ipi', 'base'),
    )
    watch(
      () => form.value.ipipercentual,
      () => atualizaImposto('ipi', 'percentual'),
    )
    watch(
      () => form.value.ipivalor,
      () => atualizaImposto('ipi', 'valor'),
    )

    // Watchers para PIS
    watch(
      () => form.value.pisbase,
      () => atualizaImposto('pis', 'base'),
    )
    watch(
      () => form.value.pispercentual,
      () => atualizaImposto('pis', 'percentual'),
    )
    watch(
      () => form.value.pisvalor,
      () => atualizaImposto('pis', 'valor'),
    )

    // Watchers para COFINS
    watch(
      () => form.value.cofinsbase,
      () => atualizaImposto('cofins', 'base'),
    )
    watch(
      () => form.value.cofinspercentual,
      () => atualizaImposto('cofins', 'percentual'),
    )
    watch(
      () => form.value.cofinsvalor,
      () => atualizaImposto('cofins', 'valor'),
    )

    // Watchers para CSLL
    watch(
      () => form.value.csllbase,
      () => atualizaImposto('csll', 'base'),
    )
    watch(
      () => form.value.csllpercentual,
      () => atualizaImposto('csll', 'percentual'),
    )
    watch(
      () => form.value.csllvalor,
      () => atualizaImposto('csll', 'valor'),
    )

    // Watchers para IRPJ
    watch(
      () => form.value.irpjbase,
      () => atualizaImposto('irpj', 'base'),
    )
    watch(
      () => form.value.irpjpercentual,
      () => atualizaImposto('irpj', 'percentual'),
    )
    watch(
      () => form.value.irpjvalor,
      () => atualizaImposto('irpj', 'valor'),
    )

    // Watchers para FETHAB
    watch(
      () => form.value.fethabkg,
      () => atualizaImpostoKg('fethab', 'kg'),
    )
    watch(
      () => form.value.fethabvalor,
      () => atualizaImpostoKg('fethab', 'valor'),
    )

    // Watchers para IAGRO
    watch(
      () => form.value.iagrokg,
      () => atualizaImpostoKg('iagro', 'kg'),
    )
    watch(
      () => form.value.iagrovalor,
      () => atualizaImpostoKg('iagro', 'valor'),
    )

    // Watchers para FUNRURAL
    watch(
      () => form.value.funruralpercentual,
      () => atualizaImposto('funrural', 'percentual'),
    )
    watch(
      () => form.value.funruralvalor,
      () => atualizaImposto('funrural', 'valor'),
    )

    // Watchers para SENAR
    watch(
      () => form.value.senarpercentual,
      () => atualizaImposto('senar', 'percentual'),
    )
    watch(
      () => form.value.senarvalor,
      () => atualizaImposto('senar', 'valor'),
    )
  }

  /**
   * Inicializa o valor total final na primeira vez
   */
  const inicializaValorTotalFinal = () => {
    if (!form.value.valortotalfinal) {
      atualizaTotalFinal()
    } else {
      valorTotalFinalAnterior = form.value.valortotalfinal
    }
  }

  /**
   * Sanitiza campos de impostos condicionais ao CST antes de enviar ao backend
   */
  const sanitizaImpostosParaSubmit = () => {
    if (ICMS_CST_SEM_ICMS_PROPRIO.includes(Number(form.value.icmscst))) {
      form.value.icmsbase = null
      form.value.icmsbasepercentual = null
      form.value.icmspercentual = null
      form.value.icmsvalor = null
    }
  }

  return {
    atualizaTotal,
    atualizaUnitario,
    atualizaTotalFinal,
    atualizaBaseImpostos,
    atualizaImposto,
    atualizaImpostoKg,
    setupWatchers,
    inicializaValorTotalFinal,
    sanitizaImpostosParaSubmit,
  }
}
