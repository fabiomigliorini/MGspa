import { defineStore } from 'pinia'
import inutilizacaoService from '../services/inutilizacaoService'
import notaFiscalService from '../services/notaFiscalService'
import { inicioMes } from '@components/utils/datas'

/**
 * A data que posiciona a faixa no tempo.
 *
 * Normalmente e a do ato na SEFAZ. Mas a linha e gravada ANTES da chamada (para nao perder o
 * rastro se a resposta se perder num timeout), entao a faixa ainda nao confirmada tem
 * protocolodata vazia — e `new Date(null)` cai no epoch, jogando ela num card "Dez/1969" no
 * fim da pagina, justamente a que precisa ser vista primeiro. O backend usa o mesmo
 * coalesce para agrupar e ordenar (InutilizacaoService::anos).
 */
export const dataEfetiva = (faixa) => faixa?.protocolodata || faixa?.criacao

export const useInutilizacaoStore = defineStore('inutilizacao', {
  state: () => ({
    codfilial: null,
    ano: null,
    filiais: [],
    anos: [],
    faixas: [],
    loading: false,
    salvando: false,
  }),

  getters: {
    /**
     * Agrupa as faixas do ano em meses (um card cada, do mais recente para o mais antigo) e,
     * dentro do mes, por modelo — NF-e e NFC-e sao numeracoes independentes e viram uma
     * tabela cada, senao os numeros de uma se misturam com os da outra.
     */
    faixasPorMes: (state) => {
      const meses = new Map()

      state.faixas.forEach((faixa) => {
        // chave YYYY-MM-01 no fuso LOCAL: fatiar a string do JSON não dá, o backend serializa em
        // UTC (28/12 23:00 vira "2018-12-29T02:00:00Z") e o fim do mês iria para o mês seguinte
        const mes = inicioMes(dataEfetiva(faixa))
        if (!meses.has(mes)) {
          meses.set(mes, { mes, modelos: new Map(), totalFaixas: 0, totalNumeros: 0 })
        }
        const grupo = meses.get(mes)
        grupo.totalFaixas += 1
        grupo.totalNumeros += faixa.quantidade

        if (!grupo.modelos.has(faixa.modelo)) {
          grupo.modelos.set(faixa.modelo, {
            modelo: faixa.modelo,
            faixas: [],
            totalFaixas: 0,
            totalNumeros: 0,
          })
        }
        const porModelo = grupo.modelos.get(faixa.modelo)
        porModelo.faixas.push(faixa)
        porModelo.totalFaixas += 1
        porModelo.totalNumeros += faixa.quantidade
      })

      return [...meses.values()]
        .sort((a, b) => b.mes.localeCompare(a.mes))
        .map((grupo) => ({
          ...grupo,
          modelos: [...grupo.modelos.values()].sort((a, b) => a.modelo - b.modelo),
        }))
    },

    totalFaixasAno: (state) => state.faixas.length,

    totalNumerosAno: (state) => state.faixas.reduce((soma, f) => soma + f.quantidade, 0),
  },

  actions: {
    /**
     * Abre a tela pelo ano mais recente. O ANO e o filtro principal (drawer): ele manda em
     * quais filiais aparecem nas abas e nos numeros que elas mostram.
     */
    async inicializar() {
      await this.carregarAnos()
      if (this.anos.length) {
        await this.selecionarAno(this.anos[0].ano)
      }
    },

    async carregarAnos() {
      this.anos = await inutilizacaoService.anos()
    },

    /**
     * Troca o ano e refaz as abas. Mantem a filial aberta se ela tambem tem faixa no ano novo
     * — trocar de ano nao deveria fazer o usuario perder a filial que estava olhando.
     */
    async selecionarAno(ano) {
      this.ano = ano
      await this.carregarFiliais()

      const continua = this.filiais.some((f) => f.codfilial === this.codfilial)
      const codfilial = continua ? this.codfilial : (this.filiais[0]?.codfilial ?? null)

      await this.selecionarFilial(codfilial)
    },

    async carregarFiliais() {
      this.filiais = this.ano ? await inutilizacaoService.filiais(this.ano) : []
    },

    async selecionarFilial(codfilial) {
      this.codfilial = codfilial
      await this.carregarFaixas()
    },

    async carregarFaixas() {
      if (!this.codfilial || !this.ano) {
        this.faixas = []
        return
      }
      this.loading = true
      try {
        this.faixas = await inutilizacaoService.listar({
          codfilial: this.codfilial,
          ano: this.ano,
        })
      } finally {
        this.loading = false
      }
    },

    /**
     * Inutiliza uma faixa e refaz a navegacao inteira: a faixa nova pode estrear um ano no
     * drawer, ou uma filial nas abas. Quem chamou decide se navega ate ela (o dialog manual
     * navega, o de lacunas nao, porque varre varias filiais de uma vez).
     */
    async inutilizar(payload) {
      this.salvando = true
      try {
        const inut = await inutilizacaoService.inutilizar(payload)
        await this.carregarAnos()
        await this.carregarFiliais()
        await this.carregarFaixas()
        return inut
      } finally {
        this.salvando = false
      }
    },

    /**
     * Leva a tela ate uma faixa recem inutilizada, mesmo que ela esteja em outro ano/filial.
     */
    async navegarPara(codfilial, protocolodata) {
      const ano = protocolodata ? new Date(protocolodata).getFullYear() : this.ano
      if (ano !== this.ano) {
        this.ano = ano
        await this.carregarFiliais()
      }
      await this.selecionarFilial(codfilial)
    },

    // O detector varre todas as filiais de uma vez, entao nao usa a filial da aba.
    async detectarLacunas() {
      return await notaFiscalService.detectarLacunas()
    },
  },
})
