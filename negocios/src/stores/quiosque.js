import { defineStore } from 'pinia'
import { db } from 'boot/db'
import { api } from 'src/boot/axios'
import { sincronizacaoStore } from './sincronizacao'
import { produtoStore } from './produto'

const sSinc = sincronizacaoStore()

export const quiosqueStore = defineStore('quiosque', {
  state: () => ({
    // produto basico exibido (preco/descricao/imagem) - vem da base local, funciona offline
    produto: null,
    // detalhe rico (embalagens, estoque, breadcrumb, imagens) - cache + backend
    detalhe: null,
    buscando: false,
    atualizando: false,
    semResultado: false,
    barrasConsultada: null,
  }),

  actions: {
    limpar() {
      this.produto = null
      this.detalhe = null
      this.semResultado = false
      this.barrasConsultada = null
      this.buscando = false
      this.atualizando = false
    },

    async consultar(barras) {
      if (!barras) {
        return
      }
      barras = ('' + barras).trim()
      if (barras.length == 0) {
        return
      }

      this.buscando = true
      this.semResultado = false
      this.detalhe = null
      this.barrasConsultada = barras

      // 1) Base local enxuta (instantaneo, offline) -> preco/descricao/imagem
      const sProduto = produtoStore()
      const leans = await sProduto.buscarBarras(barras)
      if (leans.length >= 1) {
        this.produto = leans[0]
      } else {
        this.produto = null
      }

      // 2) Cache rico local (instantaneo) -> embalagens/estoque/breadcrumb
      if (this.produto?.codproduto != null) {
        const cache = await db.produtoDetalhe.get(this.produto.codproduto)
        if (cache) {
          this.detalhe = cache
        }
      }

      this.buscando = false

      // 3) Backend -> atualiza cache rico + tela (best-effort, degrada offline)
      this.enriquecer(barras)
    },

    async enriquecer(barras) {
      this.atualizando = true
      try {
        const { data } = await api.get('/api/v1/pdv/produto/' + barras + '/detalhe', {
          params: { pdv: sSinc.pdv.uuid },
        })

        // so aplica se ainda estamos na mesma consulta (evita corrida entre scans)
        if (this.barrasConsultada != barras) {
          return
        }

        if (data?.resultado && data.produto) {
          const detalhe = { ...data.produto, sincronizado: Date.now() }
          this.detalhe = detalhe
          await db.produtoDetalhe.put(detalhe)

          // se nao tinha base local, exibe o basico vindo do backend
          if (!this.produto) {
            this.produto = {
              codproduto: detalhe.codproduto,
              codprodutobarra: detalhe.codprodutobarra,
              barras: detalhe.barras,
              produto: detalhe.produto,
              preco: detalhe.preco,
              codimagem: detalhe.imagens?.[0] ?? null,
              sigla: detalhe.sigla,
              inativo: detalhe.inativo,
            }
          }
        } else if (!this.produto && !this.detalhe) {
          // backend respondeu que nao existe e nao tinha nada local
          this.semResultado = true
        }
      } catch (error) {
        // offline ou backend fora: mantem o que veio do local/cache
        console.log('Quiosque: falha ao enriquecer ' + barras + ' no backend.', error)
      } finally {
        if (this.barrasConsultada == barras) {
          this.atualizando = false
          // se depois de tudo nada foi encontrado, sinaliza
          if (!this.produto) {
            this.semResultado = true
          }
        }
      }
    },
  },
})
