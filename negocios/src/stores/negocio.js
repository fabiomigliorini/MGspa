import { formataDataIso, formataTimestampIso } from '@components/formatters'
import { defineStore } from 'pinia'
import { toRaw } from 'vue'
import { db } from 'boot/db'
import { Notify, uid } from 'quasar'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { cobrancaStore } from '@components/stores/cobrancaStore'
import bandeirasCartao from '../data/bandeiras-cartao.json'
import { falar } from '../utils/falar.js'
import { CONDICOES, MEIO, MEIOS, totalPagamento } from '@components/cobranca/pagamento.js'

const sSinc = sincronizacaoStore()
const sCobranca = cobrancaStore()

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

// Serializa operações concorrentes por uuid de negócio.
// Evita race entre cliques rápidos, scanner duplo e listener multi-aba.
const _mutexes = new Map()
async function comLock(uuid, fn) {
  if (!uuid) return fn()
  const previo = _mutexes.get(uuid) || Promise.resolve()
  let liberar
  const novo = new Promise((r) => (liberar = r))
  _mutexes.set(uuid, novo)
  try {
    await previo
    return await fn()
  } finally {
    liberar()
    if (_mutexes.get(uuid) === novo) _mutexes.delete(uuid)
  }
}

// BroadcastChannel para sincronizar entre abas/janelas do mesmo origin.
// Cada salvar() posta o uuid; outras abas recarregam se estão no mesmo negócio.
const _bc = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('mgspa-negocio') : null
let _bcListenerStore = null
function instalarListenerMultiAba(store) {
  if (!_bc || _bcListenerStore === store) return
  _bcListenerStore = store
  _bc.onmessage = (e) => {
    const uuid = e.data?.uuid
    if (uuid && uuid === store.negocio?.uuid) {
      comLock(uuid, () => store.recarregar())
    }
  }
}

// Resposta do servidor que chegou fora de ordem e reabriria o negócio que está na tela.
//
// O status só anda para frente (1 aberto → 2 fechado → 3 cancelado) e `fechar()` só marca
// 2 depois que o servidor confirma, então um retorno com 1 para um negócio local já
// fechado é sempre resposta velha — em geral o PUT do sincronizar ou um GET do polling de
// PIX/maquininha que demorou. Aplicá-la devolvia a tela para "aberto" no meio da emissão,
// desmontando o card da nota que estava transmitindo (TASK-146).
function respostaAtrasada(atual, ret) {
  const atrasada =
    atual?.uuid === ret?.uuid && atual?.codnegociostatus > 1 && ret?.codnegociostatus == 1
  if (atrasada) {
    console.warn(
      `[negócio ${ret.uuid}] resposta atrasada descartada: servidor devolveu status 1 com o negócio já em ${atual.codnegociostatus}`,
    )
  }
  return atrasada
}

// Compara dois valores numéricos com tolerância, tratando
// null/undefined/''/NaN como "sem valor". Tolerância 0.0001.
function numerosIguais(a, b) {
  const norm = (x) => {
    if (x === null || x === undefined || x === '') return null
    const n = parseFloat(x)
    return isNaN(n) ? null : n
  }
  const na = norm(a)
  const nb = norm(b)
  if (na === null && nb === null) return true
  if (na === null || nb === null) return false
  return Math.abs(na - nb) < 0.0001
}

// Compara descontos: 0/null/undefined/''/NaN são todos "sem desconto" e
// considerados iguais entre si. Demais valores comparados com tolerância.
function descontosIguais(a, b) {
  const norm = (x) => {
    if (x === null || x === undefined || x === '') return 0
    const n = parseFloat(x)
    return isNaN(n) ? 0 : n
  }
  return Math.abs(norm(a) - norm(b)) < 0.0001
}

export const negocioStore = defineStore('negocio', {
  persist: {
    pick: ['padrao', 'paginaAtual', 'ultimos'],
  },

  state: () => ({
    negocio: null,
    negocios: [],
    ultimos: [],
    dialog: {
      valores: false,
      pagamento: false,
      vale: false,
    },
    // uuid do vale aberto no dialog de vale compras; null = vale novo
    valeEditando: null,
    // pagamento (ou grupo de parcelas, com `condicao`) aberto no dialog de detalhe
    pagamentoDetalhe: null,
    padrao: {
      codestoquelocal: 101001, //Deposito
      codpessoa: 1, //Consumidor
      codnaturezaoperacao: 1, //Venda
      codoperacao: 2, //Saída
      venda: true, //Saída
      impressora: null,
      codpagarmepos: null,
      codsauruspos: null,
      maquineta: null,
      codportador: null,
    },
    paginaAtual: 1,
    dialogVerificarDuplicados: false,
  }),

  getters: {
    quantidadeProdutosAtivos() {
      return this.itensAtivos.length
    },
    itensAtivos() {
      if (!this.negocio) {
        return []
      }
      return this.negocio.itens
        .filter((item) => item.inativo == null)
        .sort((a, b) => b.ordenacao.localeCompare(a.ordenacao))
    },
    itensInativos() {
      if (!this.negocio) {
        return []
      }
      return this.negocio.itens
        .filter((item) => item.inativo != null)
        .sort((a, b) => b.inativo.localeCompare(a.inativo))
    },
    // Os vales do negocio, na ordem em que foram lancados -- e' essa ordem
    // que da' nome a cada secao na tela ("Vale A", "Vale B").
    // `itensAtivos` continua significando SO' mercadoria: nada aqui encosta
    // em negocio.itens (decisao 18).
    valesAtivos() {
      if (!this.negocio?.vales) {
        return []
      }
      return this.negocio.vales
        .filter((vale) => vale.inativo == null)
        .sort((a, b) => String(a.criacao).localeCompare(String(b.criacao)))
    },
    podeEditar() {
      return this.negocio?.codnegociostatus == 1 && this.negocio?.codpdv == sSinc.pdv?.codpdv
    },
    // o que falta receber; negativo = troco (o dinheiro entregue entra inteiro: total + troco)
    valorapagar() {
      const pagamentos = (this.negocio.pagamentos ?? []).reduce(
        (soma, pag) => soma + (pag.total || 0) + (pag.valortroco || 0),
        0,
      )
      const parcelas = (this.negocio.parcelas ?? []).reduce(
        (soma, np) => soma + (parseFloat(np.valor) || 0),
        0,
      )
      return arredonda(this.negocio.valortotal - pagamentos - parcelas)
    },
    // mesma regra do PdvNegocioService::fechar: venda >= 1.000 sem CPF/CNPJ não fecha
    faltaIdentificarCliente() {
      return (
        !!this.negocio?.venda &&
        this.negocio.valortotal >= 1000 &&
        !this.negocio.Pessoa?.cnpj &&
        !this.negocio.cpf
      )
    },
    gruposDuplicadosPorBarras() {
      if (!this.negocio || !this.negocio.itens) {
        return []
      }
      const mapa = new Map()
      for (const item of this.negocio.itens) {
        if (item.inativo != null) continue
        const chave = String(item.barras ?? '').trim()
        if (!chave) continue
        if (!mapa.has(chave)) mapa.set(chave, [])
        mapa.get(chave).push(item)
      }
      const grupos = []
      for (const [barras, itens] of mapa) {
        if (itens.length < 2) continue
        const ref = itens[0]
        const divergentes = []
        if (!itens.every((i) => numerosIguais(i.valorunitario, ref.valorunitario))) {
          divergentes.push('preço')
        }
        for (const [campo, label] of [
          ['percentualdesconto', 'desconto'],
          ['valorfrete', 'frete'],
          ['valorseguro', 'seguro'],
          ['valoroutras', 'outras'],
        ]) {
          if (!itens.every((i) => descontosIguais(i[campo], ref[campo]))) {
            divergentes.push(label)
          }
        }
        grupos.push({
          barras,
          itens: [...itens].sort((a, b) => String(a.criacao).localeCompare(String(b.criacao))),
          podeJuntar: divergentes.length === 0,
          camposDivergentes: divergentes,
        })
      }
      return grupos
    },
  },

  actions: {
    async salvarPadrao(padrao) {
      this.padrao = { ...padrao }
      const nat = await db.naturezaOperacao.get(padrao.codnaturezaoperacao)
      this.padrao.codoperacao = nat.codoperacao
      this.padrao.venda = nat.venda
    },

    async atualizarListagem() {
      // busca todos negocios abertos do PDV
      let negs = await db.negocio
        .where('[codnegociostatus+codpdv]')
        .equals([1, sSinc.pdv.codpdv])
        .reverse()
        .sortBy('criacao')
      this.negocios = negs

      // verifica se tem negocio aberto
      if (this.negocio) {
        // verifica se o negocio está na listagem de abertos
        const iNegocio = negs.findIndex((neg) => {
          return neg.codnegocio == this.negocio.codnegocio
        })

        // verifica se o negocio está na listagem dos ultimos
        var ultimos = this.ultimos
        const iUltimo = ultimos.findIndex((u) => {
          return u.codnegocio == this.negocio.codnegocio
        })

        if (iUltimo != -1) {
          if (iNegocio != -1) {
            // se esta nos abertos remove dos ultimos
            ultimos.splice(iUltimo, 1)
          } else {
            // senao atualiza ele na listagem de ultimos
            ultimos[iUltimo] = { ...this.negocio }
          }
        } else if (iNegocio == -1) {
          // se nao esta nem nos ultimos nem nos abertos, adiciona nos ultimos
          ultimos.unshift({ ...this.negocio })
        }

        //se listagem de ultimos maior que 10 registros filtra os 10 primeiros
        if (ultimos.length > 10) {
          ultimos = ultimos.slice(0, 10)
        }

        // atualiza listagem dos ultimos
        this.ultimos = ultimos
      }
    },

    async reconsultarAbertos() {
      // mostra notificacao
      const dismiss = Notify.create({
        type: 'ongoing',
        message: 'Reconsultando meus negócios abertos no Servidor!',
        timeout: 0,
      })

      // percorre todos negocios abertos do pdv
      var iNeg = 1
      for (const neg of this.negocios) {
        // se nao está sincronizado, não reconsulta no servidor
        if (!neg.sincronizado) {
          continue
        }

        try {
          // log pra saber se deu algum erro
          console.log('reconsultnado ' + iNeg + '/' + this.negocios.length + ' - ' + neg.codnegocio)
          iNeg++

          // consulta no servidor
          const ret = await sSinc.getNegocio(neg.uuid)

          // salva no banco local
          await db.negocio.put(ret)

          // se for o negocio aberto, atualiza o objeto da tela
          if (this.negocio.uuid == ret.uuid) {
            this.negocio = { ...ret }
          }
        } catch (error) {
          console.log('Erro ao buscar ' + neg.uuid)
          console.log(error)
        }
      }

      // atualiza listagem de negocios abertos (drawer)
      this.atualizarListagem()

      // fecha notificacao
      dismiss()
    },

    async carregarPeloCodnegocio(codnegocio) {
      instalarListenerMultiAba(this)
      // busca no indexedDB
      const negocio = await db.negocio.where('codnegocio').equals(codnegocio).first()

      // se nao tem offline busca na api
      if (negocio == undefined) {
        try {
          await this.recarregarDaApi(codnegocio, true)
          return this.negocio
        } catch (error) {
          console.log(error)
          Notify.create({
            type: 'negative',
            message: 'Falha ao buscar dados no Servidor!',
            timeout: 3000, // 3 segundos
            actions: [{ icon: 'close', color: 'white' }],
          })
          return false
        }
      }

      // se negocio esta sincronizado busca da API para
      // caso o negocio tenha sido alterado em outro computador
      if (!negocio.sincronizado) {
        this.negocio = { ...negocio }
        await this.carregarChavesEstrangeiras()
      } else {
        try {
          await this.recarregarDaApi(codnegocio, true)
        } catch (error) {
          this.negocio = { ...negocio }
          await this.carregarChavesEstrangeiras()
          console.log(error)
        }
      }
      await this.atualizarListagem()
      return this.negocio
    },

    async carregarPeloUuid(uuid) {
      instalarListenerMultiAba(this)
      const negocio = await db.negocio.get(uuid)

      // verifica se deve recarregar da api
      if (negocio == undefined) {
        try {
          await this.recarregarDaApi(uuid, true)
          return this.negocio
        } catch (error) {
          console.log(error)
          Notify.create({
            type: 'negative',
            message: 'Falha ao buscar dados no Servidor!',
            timeout: 3000, // 3 segundos
            actions: [{ icon: 'close', color: 'white' }],
          })
          return false
        }
      }

      // se negocio esta sincronizado busca da API para
      // caso o negocio tenha sido alterado em outro computador
      if (!negocio.sincronizado) {
        this.negocio = { ...negocio }
        await this.carregarChavesEstrangeiras()
      } else {
        try {
          await this.recarregarDaApi(uuid, true)
        } catch (error) {
          this.negocio = { ...negocio }
          await this.carregarChavesEstrangeiras()
          console.log(error)
        }
      }
      return this.negocio
    },

    async recarregar() {
      const neg = await db.negocio.get(this.negocio.uuid)
      this.negocio = { ...neg }
      return true
    },

    async criar() {
      const uuid = uid()
      const negocio = {
        uuid: uuid,
        codnegocio: null,
        codestoquelocal: this.padrao.codestoquelocal,
        codestoquelocaldestino: null,
        codnaturezaoperacao: this.padrao.codnaturezaoperacao,
        codoperacao: this.padrao.codoperacao,
        venda: this.padrao.venda,
        naturezaoperacao: null,
        financeiro: false,
        codnegociostatus: 1, //aberto
        codpessoa: this.padrao.codpessoa,
        pessoa: null,
        codpessoatransportador: null,
        codpessoavendedor: null,
        codusuario: null,
        codusuarioacertoentrega: null,
        codusuariorecebimento: null,
        cpf: null,
        entrega: false,
        lancamento: formataTimestampIso(new Date()),
        observacoes: null,
        recebimento: null,
        valorprodutos: 0,
        valorvales: 0,
        percentualdesconto: null,
        valordesconto: null,
        valorfrete: null,
        valorseguro: null,
        valoroutras: null,
        valorjuros: null,
        valortotal: 0,
        criacao: formataTimestampIso(new Date()),
        alteracao: formataTimestampIso(new Date()),
        codusuarioalteracao: null,
        codusuariocriacao: null,
        sincronizado: false,
        itens: [],
        vales: [],
        pagamentos: [],
        parcelas: [],
        titulos: [],
        notas: [],
        codpdv: sSinc.pdv.codpdv,
        Pdv: { ...sSinc.pdv },
      }
      db.negocio.add(negocio, uuid)
      this.negocio = { ...negocio }
      await this.atualizarListagem()
      return negocio
    },

    async recalcularValorTotal() {
      // desconto dado na forma de pagamento entra no desconto dos itens e vales
      this.ratearDescontoPagamento()

      let valorprodutos = 0
      let valordesconto = 0
      let valorfrete = 0
      let valorseguro = 0
      let valoroutras = 0

      // soma totais dos itens
      this.negocio.itens
        .filter((item) => {
          return item.inativo == null
        })
        .forEach((item) => {
          valorprodutos += parseFloat(item.valorprodutos)
          if (item.valordesconto > 0) {
            valordesconto += parseFloat(item.valordesconto)
          }
          if (item.valorfrete > 0) {
            valorfrete += parseFloat(item.valorfrete)
          }
          if (item.valorseguro > 0) {
            valorseguro += parseFloat(item.valorseguro)
          }
          if (item.valoroutras > 0) {
            valoroutras += parseFloat(item.valoroutras)
          }
        })

      // soma a FACE dos vales compras (decisao 20: valorvales e' bruto,
      // simetrico ao valorprodutos) e a fatia do desconto que coube a cada
      // vale. Frete, seguro e "outras" NAO sao rateados para o vale -- nao
      // se cobra frete de vale compras -- entao continuam so' da mercadoria.
      // Negocio sem vale passa reto por aqui e a conta fica identica.
      let valorvales = 0
      ;(this.negocio.vales ?? [])
        .filter((vale) => {
          return vale.inativo == null
        })
        .forEach((vale) => {
          valorvales += parseFloat(vale.valorvale)
          if (vale.valordesconto > 0) {
            valordesconto += parseFloat(vale.valordesconto)
          }
        })

      // juros dos pagamentos (parcelamento no cartão) e das parcelas (crediário)
      const valorjuros =
        (this.negocio.pagamentos ?? []).reduce((soma, pag) => soma + (pag.juros || 0), 0) +
        (this.negocio.parcelas ?? []).reduce((soma, np) => soma + (parseFloat(np.juros) || 0), 0)

      let valortotal =
        valorprodutos +
        valorvales -
        valordesconto +
        valorfrete +
        valorseguro +
        valoroutras +
        valorjuros

      this.negocio.valorprodutos = Math.round(valorprodutos * 100) / 100
      this.negocio.valorvales = Math.round(valorvales * 100) / 100
      this.negocio.valordesconto = Math.round(valordesconto * 100) / 100
      this.negocio.valorfrete = Math.round(valorfrete * 100) / 100
      this.negocio.valorseguro = Math.round(valorseguro * 100) / 100
      this.negocio.valoroutras = Math.round(valoroutras * 100) / 100
      this.negocio.valorjuros = Math.round(valorjuros * 100) / 100
      this.negocio.valortotal = Math.round(valortotal * 100) / 100

      this.ratearJuros()
      await this.recalcularTroco()
    },

    // Distribui o juros do parcelamento entre os itens e os vales.
    //
    // O juros nasce no pagamento, que e' do negocio inteiro, mas a nota
    // fiscal precisa dele por item (vira "outras" no item da NF-e). Ratear
    // aqui, e nao na hora de emitir, deixa o numero gravado e igual para
    // quem emitir depois -- inclusive numa reemissao.
    //
    // NAO entra no valortotal do item nem do vale: a conferencia do servidor
    // continua comparando "valortotal do negocio menos o juros" contra a
    // soma dos itens. Por isso este metodo so' escreve o campo de juros e
    // nao chama nenhum recalculo -- se chamasse, entraria em recursao com o
    // recalcularValorTotal que o chama.
    //
    // Base e pesos sao os mesmos do rateio de desconto: os valores BRUTOS
    // (valorprodutos do item, face do vale). A sobra do arredondamento fica
    // no ultimo, como no rateio de desconto.
    ratearJuros() {
      const itens = this.negocio.itens.filter((item) => item.inativo == null)
      const vales = (this.negocio.vales ?? []).filter((vale) => vale.inativo == null)

      const arredonda = (num) => Math.round((parseFloat(num) || 0) * 100) / 100
      const total = arredonda(this.negocio.valorjuros)
      const pesos = [
        ...itens.map((item) => arredonda(item.valorprodutos)),
        ...vales.map((vale) => arredonda(vale.valorvale)),
      ]
      const base = arredonda(pesos.reduce((soma, peso) => soma + peso, 0))
      const alvos = [...itens, ...vales]

      // sem juros, ou sem nada em que ratear, ninguem guarda fatia
      if (total == 0 || base == 0) {
        alvos.forEach((alvo) => (alvo.valorjuros = null))
        return
      }

      const fatias = pesos.map((peso) => arredonda((total * peso) / base))
      const sobra = arredonda(total - fatias.reduce((soma, fatia) => soma + fatia, 0))
      fatias[fatias.length - 1] = arredonda(fatias[fatias.length - 1] + sobra)
      alvos.forEach((alvo, i) => (alvo.valorjuros = fatias[i] || null))
    },

    // O dinheiro entregue (total + troco) não muda; o troco é redistribuído entre os pagamentos
    // em dinheiro (o maior primeiro) quando o saldo muda, e o principal acompanha
    // Rateia o desconto dado no pagamento (dinheiro, M5 doc-3) no valordesconto dos itens e
    // vales, pelos mesmos pesos do juros. Cada um guarda a fatia em valordescontopagamento
    // (o servidor devolve a mesma conta, por uuid) para refazer o rateio sem perder o
    // desconto digitado. Ordem por uuid, sobra no último: igual ao
    // PdvNegocioPagamentoService::ratearDesconto.
    ratearDescontoPagamento() {
      const r2 = (num) => Math.round((parseFloat(num) || 0) * 100) / 100
      const porUuid = (a, b) => (a.uuid < b.uuid ? -1 : a.uuid > b.uuid ? 1 : 0)
      const total = r2(
        (this.negocio.pagamentos ?? []).reduce((soma, pag) => soma + (pag.desconto || 0), 0),
      )
      const itens = this.negocio.itens.filter((i) => i.inativo == null).sort(porUuid)
      const vales = (this.negocio.vales ?? []).filter((v) => v.inativo == null).sort(porUuid)
      const alvos = [...itens, ...vales]
      const pesos = [...itens.map((i) => r2(i.valorprodutos)), ...vales.map((v) => r2(v.valorvale))]
      const base = r2(pesos.reduce((soma, peso) => soma + peso, 0))
      const fatias = new Map()
      if (total > 0 && base > 0) {
        let soma = 0
        alvos.forEach((alvo, i) => {
          const fatia = i == alvos.length - 1 ? r2(total - soma) : r2((total * pesos[i]) / base)
          soma = r2(soma + fatia)
          fatias.set(alvo.uuid, fatia)
        })
      }
      // inativos também: devolvem a fatia que tinham
      const todos = [...this.negocio.itens, ...(this.negocio.vales ?? [])]
      todos.forEach((alvo) => {
        const anterior = r2(alvo.valordescontopagamento)
        const fatia = fatias.get(alvo.uuid) ?? 0
        if (anterior == fatia) {
          return
        }
        alvo.valordesconto = r2(r2(alvo.valordesconto) - anterior + fatia) || null
        alvo.valordescontopagamento = fatia || null
        if (alvo.valorvale !== undefined) {
          alvo.valortotal = r2(r2(alvo.valorvale) - r2(alvo.valordesconto))
        } else {
          alvo.valortotal = r2(
            r2(alvo.valorprodutos) -
              r2(alvo.valordesconto) +
              r2(alvo.valorfrete) +
              r2(alvo.valorseguro) +
              r2(alvo.valoroutras),
          )
        }
      })
    },

    async recalcularTroco() {
      const pagar = this.valorapagar
      let troco = pagar < 0 ? Math.abs(pagar) : 0
      ;(this.negocio.pagamentos ?? [])
        .filter((pag) => pag.meio == MEIO.DINHEIRO && !pag.integracao)
        .sort((a, b) => b.total + (b.valortroco || 0) - (a.total + (a.valortroco || 0)))
        .forEach((pag) => {
          const entregue = arredonda(pag.total + (pag.valortroco || 0))
          const valortroco = arredonda(Math.min(troco, entregue))
          troco = arredonda(troco - valortroco)
          pag.valortroco = valortroco || null
          pag.total = arredonda(entregue - valortroco)
          pag.principal = arredonda(pag.total + (pag.desconto || 0) - (pag.juros || 0))
        })
    },

    async carregarPrimeiroVazio() {
      const negocios = await db.negocio
        .where({
          codnegociostatus: 1,
        })
        .filter((neg) => {
          if (neg.valortotal) {
            return false
          }
          if (neg.codestoquelocal != this.padrao.codestoquelocal) {
            return false
          }
          if (neg.codpessoa != this.padrao.codpessoa) {
            return false
          }
          if (neg.codnaturezaoperacao != this.padrao.codnaturezaoperacao) {
            return false
          }
          if (neg.codpdv != sSinc.pdv.codpdv) {
            return false
          }
          return true
        })
        .sortBy('lancamento')
      if (negocios.length > 0) {
        this.negocio = { ...negocios[0] }
        return negocios[0]
      }
      return false
    },

    async carregarPrimeiroVazioOuCriar() {
      if (!sSinc.pdv.codpdv) {
        Notify.create({
          type: 'negative',
          message: 'Registro de PDV ainda não criado no servidor!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      if (!sSinc.ultimaSincronizacao.completa) {
        Notify.create({
          type: 'negative',
          message: 'Ainda não foi feita nenhuma sincronização!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      let negocio = await this.carregarPrimeiroVazio()
      if (negocio != false) {
        await this.carregarChavesEstrangeiras()
        return negocio
      }
      negocio = await this.criar()
      if (negocio != false) {
        await this.carregarChavesEstrangeiras()
        await this.salvar()
        return negocio
      }
      return false
    },

    async duplicar() {
      const uuid = uid()
      // const negocio = { ...this.negocio };
      // let negocio = Object.assign({}, this.negocio);
      let negocio = JSON.parse(JSON.stringify(this.negocio))
      negocio.codnegocio = null
      negocio.uuid = uuid
      negocio.codnegociostatus = 1
      negocio.justificativa = null
      negocio.lancamento = formataTimestampIso(new Date())
      negocio.criacao = formataTimestampIso(new Date())
      negocio.alteracao = formataTimestampIso(new Date())
      negocio.sincronizado = false
      negocio.codpdv = sSinc.pdv.codpdv
      negocio.Pdv = { ...sSinc.pdv }
      negocio.pagamentos = []
      negocio.parcelas = []
      negocio.titulos = []
      negocio.notas = []
      negocio.PagarMePedidoS = []
      negocio.SaurusPedidoS = []
      negocio.pixCob = []
      negocio.itens = negocio.itens.filter((i) => {
        return i.inativo == null
      })
      negocio.itens.forEach((i) => {
        i.codnegocioprodutobarra = null
        i.codnegocio = null
        i.uuid = uid()
      })
      // vales: uuid novo no vale e em cada item, senao o servidor recebe o
      // uuid de um vale que ja e de outro negocio e recusa o PUT
      negocio.vales = (negocio.vales ?? []).filter((v) => {
        return v.inativo == null
      })
      negocio.vales.forEach((v) => {
        v.codnegociovale = null
        v.codnegocio = null
        v.codtitulo = null
        v.uuid = uid()
        v.itens = v.itens.filter((i) => {
          return i.inativo == null
        })
        v.itens.forEach((i) => {
          i.codnegociovaleprodutobarra = null
          i.codnegociovale = null
          i.uuid = uid()
        })
      })
      db.negocio.add(negocio, uuid)
      this.negocio = { ...negocio }
      await this.carregarChavesEstrangeiras()
      await this.salvar()
      await this.atualizarListagem()
      return negocio
    },

    async isNegocioIntegro() {
      try {
        const neg = this.negocio
        if (neg.codoperacao == null) {
          return false
        }
        if (neg.codestoquelocal == null) {
          return false
        }
        if (neg.codnaturezaoperacao == null) {
          return false
        }
        if (neg.codnegociostatus == null) {
          return false
        }
        if (neg.codpessoa == null) {
          return false
        }
        if (neg.Pessoa == null) {
          return false
        }
        return true
      } catch (error) {
        console.log(error)
        return false
      }
    },

    async consertarNegocioCorrompido() {
      if (this.negocio.codestoquelocal == null) {
        this.negocio.codestoquelocal = this.padrao.codestoquelocal
      }
      if (this.negocio.codnaturezaoperacao == null) {
        this.negocio.codnaturezaoperacao = this.padrao.codnaturezaoperacao
      }
      if (this.negocio.codoperacao == null) {
        this.negocio.codoperacao = this.padrao.codoperacao
      }
      if (this.negocio.venda == null) {
        this.negocio.venda = this.padrao.venda
      }
      if (this.negocio.financeiro == null) {
        this.negocio.financeiro = false
      }
      if (this.negocio.codnegociostatus == null) {
        this.negocio.codnegociostatus = 1
      }
      if (this.negocio.codpessoa == null) {
        this.negocio.codpessoa = this.padrao.codpessoa
      }
      if (this.negocio.itens == null) {
        this.negocio.itens = []
      }
      // negocio gravado no IndexedDB antes do vale compras existir
      if (this.negocio.vales == null) {
        this.negocio.vales = []
      }
      if (this.negocio.pagamentos == null) {
        this.negocio.pagamentos = []
      }
      if (this.negocio.parcelas == null) {
        this.negocio.parcelas = []
      }
      if (this.negocio.titulos == null) {
        this.negocio.titulos = []
      }
      if (this.negocio.notas == null) {
        this.negocio.notas = []
      }
      if (this.negocio.codpdv == null) {
        this.negocio.codpdv = sSinc.pdv.codpdv
      }
      await this.carregarChavesEstrangeiras()
    },

    async carregarChavesEstrangeiras() {
      var naturezaoperacao = 'Natureza Indefinida'
      var codoperacao = this.padrao.codoperacao
      var venda = true
      var operacao = 'Saída'
      var negociostatus = 'Aberto'
      var estoquelocal = 'Local Indefinido'
      var fantasia = 'Pessoa Indefinida'
      var fantasiavendedor = ''
      var financeiro = false

      if (!this.negocio) {
        return false
      }

      // natureza
      if (this.negocio.codnaturezaoperacao) {
        const nat = await db.naturezaOperacao.get(this.negocio.codnaturezaoperacao)
        if (nat) {
          naturezaoperacao = nat.naturezaoperacao
          financeiro = nat.financeiro
          codoperacao = nat.codoperacao
          venda = nat.venda
          if (nat.codoperacao == 1) {
            operacao = 'Entrada'
          } else {
            operacao = 'Saída'
          }
        }
      }

      // status
      switch (parseInt(this.negocio.codnegociostatus)) {
        case 2:
          negociostatus = 'Fechado'
          break
        case 3:
          negociostatus = 'Cancelado'
          break
        case 1:
        default:
          negociostatus = 'Aberto'
          break
      }

      // estoquelocal
      if (this.negocio.codestoquelocal) {
        const loc = await db.estoqueLocal.get(this.negocio.codestoquelocal)
        if (loc) {
          estoquelocal = loc.estoquelocal
        }
      }

      // Pessoa
      if (this.negocio.codpessoa) {
        const pes = await db.pessoa.get(this.negocio.codpessoa)
        if (pes) {
          if (pes.codformapagamento) {
            const fp = await db.formaPagamento.get(pes.codformapagamento)
            pes.formapagamento = fp.formapagamento
          }
          this.negocio.Pessoa = pes
          fantasia = pes.fantasia
        }
      }

      // Vendedor
      if (this.negocio.codpessoavendedor) {
        const vnd = await db.pessoa.get(this.negocio.codpessoavendedor)
        if (vnd) {
          fantasiavendedor = vnd.fantasia
        }
      }

      this.negocio.naturezaoperacao = naturezaoperacao
      this.negocio.financeiro = financeiro
      this.negocio.codoperacao = codoperacao
      this.negocio.venda = venda
      this.negocio.operacao = operacao
      this.negocio.negociostatus = negociostatus
      this.negocio.estoquelocal = estoquelocal
      this.negocio.fantasia = fantasia
      this.negocio.fantasiavendedor = fantasiavendedor
    },

    async salvar(sincronizar = true) {
      // marca alteracao
      if (sincronizar) {
        if (this.negocio.codnegociostatus == 1) {
          this.negocio.alteracao = formataTimestampIso(new Date())
          this.negocio.lancamento = formataTimestampIso(new Date())
        }
        this.negocio.sincronizado = false
      }
      const ret = await db.negocio.put(toRaw(this.negocio))
      _bc?.postMessage({ uuid: this.negocio.uuid })
      if (sincronizar) {
        this.sincronizar(this.negocio.uuid)
      } else {
        await this.atualizarListagem()
      }
      return ret
    },

    async itemAdicionar(
      codprodutobarra,
      barras,
      codproduto,
      produto,
      codimagem,
      quantidade,
      valorunitario,
    ) {
      return comLock(this.negocio?.uuid, async () => {
        // busca versao do IndexedDB para
        // garantir que nao foi adicionado nada em outra aba
        await this.recarregar()

        //verifica se o item já existe no negocio
        const index = this.negocio.itens.findIndex(function (item) {
          return (
            item.inativo === null && parseInt(item.codprodutobarra) === parseInt(codprodutobarra)
          )
        })

        // se ja existe adiciona a quantiade, se nao cria um novo item
        let item
        if (index >= 0) {
          item = this.negocio.itens.splice(index, 1)[0]
          item.quantidade = parseFloat(item.quantidade) + parseFloat(quantidade)
          item.alteracao = formataTimestampIso(new Date())
          item.ordenacao = item.alteracao
        } else {
          item = {
            uuid: uid(),
            codprodutobarra,
            barras,
            codproduto,
            produto,
            codimagem,
            quantidade: parseFloat(quantidade),
            valorunitario: parseFloat(valorunitario),
            valorprodutos: 0,
            percentualdesconto: null,
            valordesconto: null,
            valorfrete: null,
            valorseguro: null,
            valoroutras: null,
            valorjuros: null,
            valortotal: null,
            criacao: formataTimestampIso(new Date()),
            alteracao: formataTimestampIso(new Date()),
            ordenacao: formataTimestampIso(new Date()),
            inativo: null,
          }
          try {
            if (this.negocio.Pessoa.desconto) {
              item.percentualdesconto = this.negocio.Pessoa.desconto
            }
          } catch (error) {
            console.log(error)
            item.percentualdesconto = null
          }
        }

        const palavras = item.produto.split(' ')
        var texto = ''
        if (palavras.length >= 2) {
          texto = palavras[0] + ' ' + palavras[1]
        } else {
          texto = palavras[0]
        }
        falar(texto)

        // adiciona o item no inicio do array
        this.negocio.itens.unshift(item)

        // recalcula os totais
        this.itemRecalcularValorProdutos(item)

        // confirma se tem alguma coisa pra consertar
        if (!(await this.isNegocioIntegro())) {
          await this.consertarNegocioCorrompido()
        }

        // salva no IndexedDB
        await this.salvar()
      })
    },

    async juntarItensPorBarras(barras) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const grupo = this.negocio.itens.filter(
          (i) => i.inativo == null && String(i.barras ?? '') === String(barras),
        )
        if (grupo.length < 2) {
          return false
        }
        const info = this.gruposDuplicadosPorBarras.find((g) => g.barras === String(barras))
        if (info && !info.podeJuntar) {
          Notify.create({
            type: 'negative',
            message:
              'Divergente em: ' + info.camposDivergentes.join(', ') + ' — ajuste manualmente.',
            timeout: 3000,
            actions: [{ icon: 'close', color: 'white' }],
          })
          return false
        }
        // alvo = mais antigo do grupo
        grupo.sort((a, b) => String(a.criacao).localeCompare(String(b.criacao)))
        const alvo = grupo[0]
        const outros = grupo.slice(1)
        const carimbo = formataTimestampIso(new Date())
        // inativa os outros antes de recalcular para que recalcularValorTotal
        // (cascateado por itemRecalcularValorProdutos) só some o alvo
        for (const x of outros) {
          x.inativo = carimbo
        }
        alvo.quantidade =
          parseFloat(alvo.quantidade) + outros.reduce((s, x) => s + parseFloat(x.quantidade), 0)
        alvo.alteracao = carimbo
        alvo.ordenacao = carimbo
        this.itemRecalcularValorProdutos(alvo)
        await this.salvar()
        return true
      })
    },

    async itemAdicionarQuantidade(uuid, quantidade) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const item = this.negocio.itens.find(function (item) {
          return item.inativo === null && item.uuid == uuid
        })
        if (!item) {
          return false
        }
        const total = parseFloat(item.quantidade) + parseFloat(quantidade)
        if (total <= 0) {
          return
        }
        item.quantidade = total
        this.itemRecalcularValorProdutos(item)
        // salva no IndexedDB
        await this.salvar()
      })
    },

    async itemSalvar(
      uuid,
      codprodutobarra,
      quantidade,
      valorunitario,
      valorprodutos,
      percentualdesconto,
      valordesconto,
      valorfrete,
      valorseguro,
      valoroutras,
      valortotal,
    ) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const item = this.negocio.itens.find(function (item) {
          return item.inativo === null && item.uuid == uuid
        })
        if (!item) {
          return false
        }
        item.codprodutobarra = codprodutobarra
        item.quantidade = quantidade
        item.valorunitario = valorunitario
        item.valorprodutos = valorprodutos
        item.percentualdesconto = percentualdesconto
        // o dialog edita só o desconto digitado; a fatia do pagamento volta no rateio
        item.valordesconto = valordesconto
        item.valordescontopagamento = null
        item.valorfrete = valorfrete
        item.valorseguro = valorseguro
        item.valoroutras = valoroutras
        item.valortotal = valortotal
        this.recalcularValorTotal()
        await this.salvar()
      })
    },

    async itemInativar(uuid) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const inativar = this.negocio.itens.find(function (item) {
          return item.inativo === null && item.uuid == uuid
        })
        if (inativar) {
          inativar.inativo = formataTimestampIso(new Date())
          this.recalcularValorTotal()
          await this.salvar()
        }
      })
    },

    async itemRecalcularValorProdutos(item) {
      let total =
        Math.round(parseFloat(item.quantidade) * parseFloat(item.valorunitario) * 100) / 100
      item.valorprodutos = total
      this.itemRecalcularValorDesconto(item)
    },

    async itemRecalcularValorDesconto(item) {
      if (item.percentualdesconto <= 0) {
        item.valordesconto = null
      } else {
        item.valordesconto = Math.round(item.valorprodutos * item.percentualdesconto) / 100
      }
      // desconto refeito do zero: a fatia do pagamento volta no rateio
      item.valordescontopagamento = null
      this.itemRecalcularValorTotal(item)
    },

    async itemRecalcularValorTotal(item) {
      let total = parseFloat(item.valorprodutos)
      if (item.valordesconto) {
        total -= parseFloat(item.valordesconto)
      }
      if (item.valorfrete) {
        total += parseFloat(item.valorfrete)
      }
      if (item.valorseguro) {
        total += parseFloat(item.valorseguro)
      }
      if (item.valoroutras) {
        total += parseFloat(item.valoroutras)
      }
      item.valortotal = Math.round(total * 100) / 100
      this.recalcularValorTotal()
    },

    // =================================================================
    // VALE COMPRAS
    //
    // O vale e' um bloco proprio do negocio, com itens proprios: nada
    // daqui encosta em negocio.itens nem nas actions de mercadoria
    // (decisao 18 do plano). Duplicidade aceita de proposito -- da' pra
    // apagar o vale inteiro sem tocar no PDV.
    // =================================================================

    // uuid = editar um vale que ja' existe; sem uuid, vale novo
    abrirVale(uuid = null) {
      this.valeEditando = uuid
      this.dialog.vale = true
    },

    // Quantidade x preco, e so' (decisao 19): item de vale nao tem
    // desconto, frete, seguro nem outras.
    valeItemRecalcularValorProdutos(item) {
      item.valorprodutos =
        Math.round(parseFloat(item.quantidade) * parseFloat(item.valorunitario) * 100) / 100
    },

    // valorprodutos = soma dos itens ativos
    // valorvale     = produtos + avulso  <- a FACE, o credito que sera emitido
    // valortotal    = a fatia PAGA: a face menos o desconto que o cabecalho
    //                 ratear para este vale (milestone 4). Hoje = face.
    valeRecalcularValores(vale) {
      let valorprodutos = 0
      vale.itens
        .filter((item) => {
          return item.inativo == null
        })
        .forEach((item) => {
          valorprodutos += parseFloat(item.valorprodutos)
        })
      vale.valorprodutos = Math.round(valorprodutos * 100) / 100
      vale.valorvale =
        Math.round((vale.valorprodutos + parseFloat(vale.valoravulso || 0)) * 100) / 100

      // a fatia paga = face menos o desconto rateado. So o desconto: frete,
      // seguro e "outras" nao existem no vale.
      let valortotal = vale.valorvale
      if (vale.valordesconto) {
        valortotal -= parseFloat(vale.valordesconto)
      }
      vale.valortotal = Math.round(valortotal * 100) / 100
    },

    // Semeia os itens do vale a partir do kit do catalogo.
    // A descricao, o codigo de barras e a imagem saem do cache de produtos
    // (o catalogo so' guarda codprodutobarra / quantidade / preco), e o
    // preco e' o do MODELO -- foi ele que a escola validou.
    async valeItensDoModelo(codvalemodelo) {
      if (!codvalemodelo) {
        return []
      }
      const modelo = await db.valeModelo.get(parseInt(codvalemodelo))
      if (!modelo) {
        return []
      }
      const agora = formataTimestampIso(new Date())
      const itens = []
      for (const im of modelo.itens ?? []) {
        const prod = await db.produto.get(parseInt(im.codprodutobarra))
        const item = {
          uuid: uid(),
          codprodutobarra: parseInt(im.codprodutobarra),
          barras: prod?.barras ?? null,
          codproduto: prod?.codproduto ?? null,
          produto: prod?.produto ?? 'Produto fora do cache do PDV',
          codimagem: prod?.codimagem ?? null,
          quantidade: parseFloat(im.quantidade),
          valorunitario: parseFloat(im.valorunitario),
          valorprodutos: 0,
          criacao: agora,
          alteracao: agora,
          ordenacao: agora,
          inativo: null,
        }
        this.valeItemRecalcularValorProdutos(item)
        itens.push(item)
      }
      return itens
    },

    // valoravulso = null significa "usa o do modelo". Zero e' uma escolha
    // legitima (operador limpou o campo) e por isso nao serve de default:
    // sem isso o avulso do kit sumia para quem chamasse a action sem
    // informar o valor.
    async valeAdicionar({
      codvalemodelo = null,
      codpessoafavorecido = null,
      favorecido = null,
      aluno = null,
      turma = null,
      valoravulso = null,
    }) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        if (this.negocio.vales == null) {
          this.negocio.vales = []
        }

        // sem escola informada o vale nasce ao portador, em nome do
        // Consumidor (decisao 13)
        const codpessoa = parseInt(codpessoafavorecido) || 1
        const pessoa = await db.pessoa.get(codpessoa)
        const modelo = codvalemodelo ? await db.valeModelo.get(parseInt(codvalemodelo)) : null

        // o modelo semeia os itens E o valor avulso (decisao do plano)
        const avulso =
          valoravulso === null || valoravulso === undefined
            ? parseFloat(modelo?.valoravulso) || 0
            : parseFloat(valoravulso) || 0

        // validade informativa: 1 ano da emissao (decisao 8)
        const validade = new Date()
        validade.setFullYear(validade.getFullYear() + 1)

        const agora = formataTimestampIso(new Date())
        const vale = {
          uuid: uid(),
          codnegociovale: null,
          codtitulo: null,
          codvalemodelo: codvalemodelo ? parseInt(codvalemodelo) : null,
          modelo: modelo?.modelo ?? null,
          codpessoafavorecido: codpessoa,
          // o nome do catalogo e a rede para pessoa fora do cache do PDV
          favorecido: pessoa?.fantasia ?? favorecido ?? null,
          aluno,
          turma,
          valorprodutos: 0,
          valoravulso: avulso,
          valorvale: 0,
          valordesconto: null,
          valorfrete: null,
          valorseguro: null,
          valoroutras: null,
          valorjuros: null,
          valortotal: 0,
          validade: formataDataIso(validade),
          codvalecompra: null,
          observacoes: null,
          criacao: agora,
          alteracao: agora,
          inativo: null,
          itens: await this.valeItensDoModelo(codvalemodelo),
        }
        this.valeRecalcularValores(vale)
        this.negocio.vales.push(vale)

        // confirma se tem alguma coisa pra consertar
        if (!(await this.isNegocioIntegro())) {
          await this.consertarNegocioCorrompido()
        }

        await this.recalcularValorTotal()
        await this.salvar()
        return vale
      })
    },

    // Cabecalho do vale (escola, aluno, turma e valor avulso). O kit de
    // origem nao muda depois de criado: quem errou o modelo exclui o vale
    // e lanca outro.
    async valeSalvar(uuid, { codpessoafavorecido, aluno, turma, valoravulso, valordesconto }) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const vale = (this.negocio.vales ?? []).find((v) => {
          return v.inativo == null && v.uuid == uuid
        })
        if (!vale) {
          return false
        }
        const codpessoa = parseInt(codpessoafavorecido) || 1
        const pessoa = await db.pessoa.get(codpessoa)
        vale.codpessoafavorecido = codpessoa
        vale.favorecido = pessoa?.fantasia ?? null
        vale.aluno = aluno
        vale.turma = turma
        vale.valoravulso = parseFloat(valoravulso) || 0
        // Desconto do vale: muda o que o cliente PAGA (valortotal), nunca a
        // face -- o credito emitido e sempre o valor de face (decisao 5c).
        // undefined = quem chamou nem mexeu no campo; deixa como esta.
        if (valordesconto !== undefined) {
          vale.valordesconto = parseFloat(valordesconto) || null
          vale.valordescontopagamento = null
        }
        vale.alteracao = formataTimestampIso(new Date())
        this.valeRecalcularValores(vale)
        await this.recalcularValorTotal()
        await this.salvar()
        return vale
      })
    },

    async valeExcluir(uuid) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const vale = (this.negocio.vales ?? []).find((v) => {
          return v.inativo == null && v.uuid == uuid
        })
        if (!vale) {
          return false
        }
        // soft-delete, como o item de mercadoria: a linha continua indo pro
        // servidor para o uuid nunca ser recriado
        vale.inativo = formataTimestampIso(new Date())
        await this.recalcularValorTotal()
        await this.salvar()
      })
    },

    // Na grade do vale so' se tira e se ajusta -- nao se acrescenta
    // (decisao 16). O universo do vale e' o kit do modelo; item de fora o
    // cliente leva como mercadoria.
    async valeItemSalvar(valeUuid, itemUuid, quantidade, valorunitario) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const vale = (this.negocio.vales ?? []).find((v) => {
          return v.inativo == null && v.uuid == valeUuid
        })
        if (!vale) {
          return false
        }
        const item = vale.itens.find((i) => {
          return i.inativo == null && i.uuid == itemUuid
        })
        if (!item) {
          return false
        }
        item.quantidade = parseFloat(quantidade)
        item.valorunitario = parseFloat(valorunitario)
        item.alteracao = formataTimestampIso(new Date())
        this.valeItemRecalcularValorProdutos(item)
        this.valeRecalcularValores(vale)
        await this.recalcularValorTotal()
        await this.salvar()
      })
    },

    async valeItemInativar(valeUuid, itemUuid) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const vale = (this.negocio.vales ?? []).find((v) => {
          return v.inativo == null && v.uuid == valeUuid
        })
        if (!vale) {
          return false
        }
        const item = vale.itens.find((i) => {
          return i.inativo == null && i.uuid == itemUuid
        })
        if (!item) {
          return false
        }
        item.inativo = formataTimestampIso(new Date())
        this.valeRecalcularValores(vale)
        await this.recalcularValorTotal()
        await this.salvar()
      })
    },

    // Rateia os valores de cabecalho (desconto / frete / seguro / outras) entre
    // a MERCADORIA e os VALES, na proporcao do bruto de cada lado: o
    // valorprodutos da mercadoria contra a FACE (valorvale) de cada vale
    // (decisoes 5 e 20 do plano).
    //
    // A mercadoria continua sendo rateada pelo aplicarValores() INTACTO -- o
    // que muda e' so' quanto chega la'. A fatia do vale para em
    // tblnegociovale e NAO desce ao item dele (decisao 19): item de vale e'
    // quantidade x preco, e so'.
    //
    // A FACE do vale nao se mexe: o desconto muda o que o cliente paga
    // (valortotal), nunca o credito que a escola recebe (decisao 5c).
    //
    // Negocio SEM vale passa reto: a base e' so' a mercadoria e a chamada e'
    // literalmente a de hoje.
    async aplicarValoresCabecalho(valordesconto, valorfrete, valorseguro, valoroutras) {
      await this.recarregar()

      const vales = this.valesAtivos
      if (!vales.length) {
        return this.aplicarValores(valordesconto, valorfrete, valorseguro, valoroutras)
      }

      const arredonda = (num) => Math.round((parseFloat(num) || 0) * 100) / 100

      const totalMercadoria = arredonda(this.negocio.valorprodutos)
      const faces = vales.map((vale) => arredonda(vale.valorvale))
      const base = arredonda(totalMercadoria + faces.reduce((soma, face) => soma + face, 0))

      // Sem mercadoria ativa nao ha' onde jogar a sobra do arredondamento:
      // quem absorve passa a ser o ultimo vale.
      const temMercadoria = this.itensAtivos.length > 0

      // Para cada valor de cabecalho devolve [fatiaDaMercadoria, fatiasDosVales]
      const ratear = (valor) => {
        const total = arredonda(Math.abs(valor || 0))
        if (total == 0 || base == 0) {
          return [0, faces.map(() => 0)]
        }
        const fatias = faces.map((face) => arredonda((total * face) / base))
        let sobra = arredonda(total - fatias.reduce((soma, f) => soma + f, 0))
        if (!temMercadoria) {
          fatias[fatias.length - 1] = arredonda(fatias[fatias.length - 1] + sobra)
          sobra = 0
        }
        return [sobra, fatias]
      }

      // So o DESCONTO e' rateado entre mercadoria e vales. Frete, seguro e
      // "outras" vao inteiros para a mercadoria: nao se cobra frete nem
      // seguro de um vale compras, e o vale nem tem coluna para eles.
      const [mercDesconto, valeDesconto] = ratear(valordesconto)

      // O uuid e' a identidade que sobrevive ao recarregar() de dentro do
      // aplicarValores(): guardar a referencia do objeto nao serviria.
      const fatiaPorUuid = {}
      vales.forEach((vale, i) => {
        fatiaPorUuid[vale.uuid] = {
          valordesconto: valeDesconto[i] || null,
        }
      })

      // mercadoria primeiro: o aplicarValores() recarrega do IndexedDB, entao
      // qualquer fatia gravada nos vales antes desta linha seria descartada
      await this.aplicarValores(mercDesconto, valorfrete, valorseguro, valoroutras)

      for (const vale of this.valesAtivos) {
        const fatia = fatiaPorUuid[vale.uuid]
        if (!fatia) {
          continue
        }
        vale.valordesconto = fatia.valordesconto
        vale.valordescontopagamento = null
        vale.alteracao = formataTimestampIso(new Date())
        this.valeRecalcularValores(vale)
      }

      await this.recalcularValorTotal()
      await this.salvar()
    },

    async aplicarValores(valordesconto, valorfrete, valorseguro, valoroutras) {
      // Função auxiliar para garantir arredondamento preciso de ponto flutuante
      const roundToTwoDecimals = (num) => {
        if (num === null || num === undefined) return 0
        // Utiliza a técnica de multiplicar/arredondar/dividir para precisão
        return parseFloat((Math.round(num * 100) / 100).toFixed(2))
      }

      await this.recarregar()

      const totalProdutos = this.negocio.valorprodutos

      // 1. ORDENAÇÃO: Cria uma cópia e ordena os itens do menor para o maior valor do produto.
      // O maior item (ou um dos maiores) irá absorver o erro de arredondamento.
      const itensOrdenados = [...this.itensAtivos].sort((a, b) => a.valorprodutos - b.valorprodutos)
      const ultimoIndex = itensOrdenados.length - 1

      // 2. Inicialização de Valores e Saldos (Garantindo Absoluto e Arredondamento)
      const valoresTotais = {
        desconto: roundToTwoDecimals(Math.abs(valordesconto || 0)),
        frete: roundToTwoDecimals(Math.abs(valorfrete || 0)),
        seguro: roundToTwoDecimals(Math.abs(valorseguro || 0)),
        outras: roundToTwoDecimals(Math.abs(valoroutras || 0)),
      }

      const saldos = { ...valoresTotais }

      // 3. Cálculo dos Percentuais
      const percentuais = {
        desconto: totalProdutos > 0 ? valoresTotais.desconto / totalProdutos : 0,
        frete: totalProdutos > 0 ? valoresTotais.frete / totalProdutos : 0,
        seguro: totalProdutos > 0 ? valoresTotais.seguro / totalProdutos : 0,
        outras: totalProdutos > 0 ? valoresTotais.outras / totalProdutos : 0,
      }

      // --- Função Auxiliar para Rateio Robusto ---
      const aplicarRateio = (item, index, percentual, saldoKey, valorKey, percentualKey = null) => {
        const saldoAtual = saldos[saldoKey]
        const totalRateavel = valoresTotais[saldoKey]

        if (totalRateavel === 0 || totalProdutos === 0) {
          item[valorKey] = 0
          if (percentualKey) item[percentualKey] = 0
          return
        }

        if (index === ultimoIndex) {
          // NO ÚLTIMO ITEM: Atribui o saldo restante (o valor final exato).
          // Arredonda para garantir 0.00 se o saldo for um número negativo de precisão flutuante.
          item[valorKey] = roundToTwoDecimals(saldoAtual)
        } else {
          // Nos itens anteriores:
          // 1. Calcula o valor proporcional.
          let valorRateadoBruto = item.valorprodutos * percentual

          // 2. Controla o Saldo: O valor rateado nunca pode ser maior que o saldo restante.
          let valorRateadoControlado = Math.min(valorRateadoBruto, saldoAtual)

          // 3. Arredonda o valor a ser atribuído.
          let valorAtribuido = roundToTwoDecimals(valorRateadoControlado)

          item[valorKey] = valorAtribuido

          // 4. Subtrai o valor ARREDONDADO do saldo e arredonda o próprio saldo.
          saldos[saldoKey] -= valorAtribuido
          saldos[saldoKey] = roundToTwoDecimals(saldos[saldoKey])
        }

        if (percentualKey) {
          item[percentualKey] = roundToTwoDecimals(percentual * 100)
        }
      }

      // 4. Loop Principal (usando itens ordenados)
      for (let index = 0; index <= ultimoIndex; index++) {
        const item = itensOrdenados[index] // Usa o item ORDENADO

        // o desconto digitado substitui o do item; a fatia do pagamento volta no rateio
        item.valordescontopagamento = null
        aplicarRateio(
          item,
          index,
          percentuais.desconto,
          'desconto',
          'valordesconto',
          'percentualdesconto',
        )
        aplicarRateio(item, index, percentuais.frete, 'frete', 'valorfrete')
        aplicarRateio(item, index, percentuais.seguro, 'seguro', 'valorseguro')
        aplicarRateio(item, index, percentuais.outras, 'outras', 'valoroutras')

        // Note: Se o 'item' é uma referência ao objeto original, ele será atualizado.
        // Se a referência foi perdida (dependendo da sua implementação de this.itensAtivos),
        // pode ser necessário atualizar a lista original após o loop.
        // Assumo que a atualização do 'item' altera a referência dentro de 'this.itensAtivos'.
        this.itemRecalcularValorTotal(item)
      }

      // 5. Opcional: Se 'itemRecalcularValorTotal' for o método que atualiza a lista principal,
      // esta etapa final de recálculo e salvamento é crucial.
      await this.recalcularValorTotal()
      this.salvar()
    },

    async informarPessoa(codpessoa, cpf) {
      // atribui o cliente/cpf no negocio
      this.negocio.codpessoa = codpessoa
      if (codpessoa == 1) {
        this.negocio.cpf = cpf
      } else {
        this.negocio.cpf = null
      }

      // pega desconto do cliente antigo e do novo
      const descontoClienteAntigo = parseFloat(this.negocio.Pessoa.desconto)
      await this.carregarChavesEstrangeiras()
      const descontoClienteNovo = parseFloat(this.negocio.Pessoa.desconto)

      // se negocio nao estiver aberto retorna
      if (this.negocio.codnegociostatus != 1) {
        await this.salvar()
        return
      }

      // se novo cliente tem desconto, aplica o desconto dele
      if (descontoClienteNovo > 0) {
        this.negocio.itens.forEach((item) => {
          item.percentualdesconto = descontoClienteNovo
          this.itemRecalcularValorProdutos(item)
        })
      } else if (descontoClienteAntigo > 0) {
        this.negocio.itens.forEach((item) => {
          // se o desconto dos itens era o desconto do cliente antigo, retira o desconto
          if (item.percentualdesconto == descontoClienteAntigo) {
            item.percentualdesconto = null
            this.itemRecalcularValorProdutos(item)
          }
        })
      }
      await this.salvar()
    },

    async informarNatureza(codestoquelocal, codnaturezaoperacao, observacoes) {
      await this.recarregar()
      this.negocio.codestoquelocal = codestoquelocal
      this.negocio.codnaturezaoperacao = codnaturezaoperacao
      this.negocio.observacoes = observacoes
      await this.carregarChavesEstrangeiras()
      await this.salvar()
    },

    async informarVendedor(codpessoavendedor) {
      await this.recarregar()
      this.negocio.codpessoavendedor = codpessoavendedor
      await this.carregarChavesEstrangeiras()
      await this.salvar()
    },

    async sincronizar(uuid) {
      const negocio = await db.negocio.get(uuid)
      let retorno = false
      try {
        const ret = await sSinc.putNegocio(negocio)
        if (ret) {
          // Compara com o estado mais fresco que temos: se o negócio é o da tela, ele
          // pode ter sido fechado enquanto este PUT estava em voo.
          const atual = this.negocio?.uuid == ret.uuid ? this.negocio : negocio
          const atrasada = respostaAtrasada(atual, ret)

          // O Dexie precisa da mesma proteção da store: `atualizarListagem` lê dele pelo
          // índice [codnegociostatus+codpdv] e `recarregar()` copia dele para a tela, então
          // gravar 1 aqui reabria o negócio fechado pelos dois caminhos.
          db.negocio.update(ret.uuid, {
            codnegocio: ret.codnegocio,
            ...(atrasada ? {} : { codnegociostatus: ret.codnegociostatus }),
          })
          if (this.negocio?.uuid == ret.uuid) {
            this.negocio.codnegocio = ret.codnegocio
            if (!atrasada) {
              this.negocio.codnegociostatus = ret.codnegociostatus
            }
            if (
              this.negocio.valortotal == ret.valortotal &&
              this.negocio.valordesconto == ret.valordesconto &&
              this.negocio.valorprodutos == ret.valorprodutos &&
              this.negocio.valorjuros == ret.valorjuros &&
              this.negocio.valoroutras == ret.valoroutras
            ) {
              this.negocio.sincronizado = true
              db.negocio.update(ret.uuid, {
                sincronizado: true,
              })
            }
          }
          retorno = true
        }
      } catch (error) {
        console.log(error)
      }
      await this.atualizarListagem()
      return retorno
    },

    // `forcar` = o servidor manda, sem a proteção contra resposta atrasada. É para a
    // recarga explícita (botão "Recarregar do servidor") e para o carregamento da tela,
    // onde o GET é fresco e recém-pedido. Os polls de PIX/maquininha, que rodam em
    // segundo plano, ficam no padrão protegido.
    async recarregarDaApi(codOrUuid, forcar = false) {
      try {
        const ret = await sSinc.getNegocio(codOrUuid)
        if (!ret.codnegocio) {
          return false
        }
        return await this.atualizarNegocioPeloObjeto(ret, forcar)
      } catch (error) {
        console.log(error)
        return false
      }
    },

    async apropriar(codOrUuid) {
      try {
        const ret = await sSinc.postApropriar(codOrUuid)
        if (!ret.codnegocio) {
          return false
        }
        await this.atualizarNegocioPeloObjeto(ret, true)
        return true
      } catch (error) {
        console.log(error)
        return false
      }
    },

    // Devolve false quando descarta o objeto, para quem chamou não anunciar um
    // recarregamento que não aconteceu.
    async atualizarNegocioPeloObjeto(neg, forcar = false) {
      // Resposta atrasada nao reabre negocio fechado na tela (ver respostaAtrasada)
      if (!forcar && respostaAtrasada(this.negocio, neg)) {
        return false
      }
      this.negocio = { ...neg }
      db.negocio.put(neg)
      await this.atualizarListagem()
      return true
    },

    // abre o wizard de cobrança para o negócio; forma/codtituloVale pulam direto para o passo
    // da forma (bipagem VAL…)
    async abrirReceber({ forma = null, codtituloVale = null } = {}) {
      if (this.valorapagar <= 0) {
        Notify.create({
          type: 'negative',
          message: 'Não há nada a receber!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return
      }
      // venda >= 1.000 exige CPF/CNPJ (o fechamento no backend recusa); o vale traz a pessoa dele
      if (!codtituloVale && this.faltaIdentificarCliente) {
        Notify.create({
          type: 'negative',
          message:
            'Obrigatório identificar o CPF para vendas acima de R$ 1.000,00! Informe o cliente (F10).',
          timeout: 5000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return
      }
      sCobranca.abrir({
        valor: this.valorapagar,
        total: this.negocio.valortotal,
        saldo: this.valorapagar,
        sentido: this.negocio.codoperacao == 1 ? 'saida' : 'entrada',
        pessoa: { codpessoa: this.negocio.codpessoa, fantasia: this.negocio.Pessoa?.fantasia },
        documento: this.documentoCobranca(),
        contexto: await this.contextoCobranca(this.negocio.codestoquelocal),
        padrao: this.padrao,
        forma,
        codtituloVale,
      })
    },

    // o PDV como contexto do wizard de cobrança (também no Receber título): a filial e as
    // maquinetas vêm do estoque local; online, as maquinetas são buscadas de novo
    async contextoCobranca(codestoquelocal) {
      const local = async () => (await db.estoqueLocal.get(codestoquelocal)) ?? {}
      return {
        pdv: sSinc.pdv.uuid,
        codfilial: (await local()).codfilial ?? null,
        carregarMaquinetas: async (aoAtualizar) => {
          const antes = (await local()).MaquinetaS ?? []
          sSinc.silentSincronizarEstoqueLocal().then(async (ok) => {
            if (ok && aoAtualizar) {
              aoAtualizar((await local()).MaquinetaS ?? [])
            }
          })
          return antes
        },
        buscarVale: (codtitulo) => sSinc.buscarVale(codtitulo),
      }
    },

    // o negócio como documento do wizard de cobrança
    documentoCobranca() {
      const uuid = this.negocio.uuid
      return {
        tipo: 'negocio',
        codnegocio: this.negocio.codnegocio,
        codestoquelocal: this.negocio.codestoquelocal,
        sincronizado: !!this.negocio.sincronizado,
        valesUsados: (this.negocio.pagamentos ?? [])
          .filter((p) => p.codtitulo)
          .map((p) => p.codtitulo),
        preparar: async () => {
          if (this.negocio?.uuid != uuid) {
            return false
          }
          return (await this.garantirSincronizado()) ? this.negocio.codnegocio : false
        },
        atualizar: async () => {
          if (this.negocio?.uuid == uuid && this.negocio.codnegocio) {
            await this.recarregarDaApi(this.negocio.codnegocio)
          }
        },
      }
    },

    // Pagamento vindo do wizard (formato novo): completa uuid, total e os nomes da tela
    async adicionarPagamento(dados) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()

        // vale identifica o cliente quando a venda está no consumidor final
        const { codpessoavale, ...pag } = dados
        if (codpessoavale && this.negocio.codpessoa == 1) {
          await this.informarPessoa(codpessoavale, null)
        }

        let parceiro = null
        if (pag.codpessoa) {
          const pes = await db.pessoa.get(pag.codpessoa)
          parceiro = pes?.fantasia ?? null
        }
        const nomebandeira = pag.bandeira
          ? (bandeirasCartao.find((el) => el.bandeira == pag.bandeira)?.nome ?? null)
          : null

        const pagamento = {
          codpagamento: null,
          uuid: uid(),
          meio: pag.meio,
          meiodescricao: MEIOS[pag.meio] ?? null,
          estado: 'P',
          principal: arredonda(pag.principal),
          juros: arredonda(pag.juros),
          multa: 0,
          desconto: arredonda(pag.desconto),
          total: 0,
          valortroco: pag.valortroco ? arredonda(pag.valortroco) : null,
          integracao: false,
          codpessoa: pag.codpessoa ?? null,
          parceiro,
          bandeira: pag.bandeira ?? null,
          nomebandeira,
          autorizacao: pag.autorizacao ?? null,
          parcelas: pag.parcelas ?? null,
          codmaquineta: pag.codmaquineta ?? null,
          maquineta: pag.maquineta ?? null,
          codtitulo: pag.codtitulo ?? null,
          cmc7: pag.cmc7 ?? null,
          chequevencimento: pag.chequevencimento ?? null,
          chequecnpj: pag.chequecnpj ?? null,
          chequeemitente: pag.chequeemitente ?? null,
          criacao: formataTimestampIso(new Date()),
          alteracao: formataTimestampIso(new Date()),
        }
        pagamento.total = totalPagamento(pagamento)
        this.negocio.pagamentos.push(pagamento)

        // recalcula total por causa dos juros e do desconto
        await this.recalcularValorTotal()
        await this.salvar()
        return pagamento
      })
    },

    // Parcelas vindas do wizard (condição, vencimento e valor já ajustados). Outra parcela da
    // mesma condição no negócio continua; a numeração é refeita por vencimento.
    async adicionarParcelas(lista) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        for (const np of lista) {
          this.negocio.parcelas.push({
            codnegocioparcela: null,
            uuid: uid(),
            condicao: np.condicao,
            condicaodescricao: CONDICOES[np.condicao] ?? null,
            numero: np.numero,
            vencimento: np.vencimento,
            valor: arredonda(np.valor),
            juros: arredonda(np.juros),
            codtitulo: null,
            criacao: formataTimestampIso(new Date()),
            alteracao: formataTimestampIso(new Date()),
          })
        }
        this.renumerarParcelas()
        await this.recalcularValorTotal()
        await this.salvar()
      })
    },

    renumerarParcelas() {
      const porCondicao = {}
      for (const np of this.negocio.parcelas) {
        ;(porCondicao[np.condicao] ??= []).push(np)
      }
      Object.values(porCondicao).forEach((lista) => {
        lista
          .sort((a, b) => String(a.vencimento).localeCompare(b.vencimento))
          .forEach((np, i) => (np.numero = i + 1))
      })
    },

    async excluirPagamento(uuid) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        const index = this.negocio.pagamentos.findIndex(function (item) {
          return item.uuid == uuid
        })
        if (index > -1) {
          this.negocio.pagamentos.splice(index, 1)
          // recalcula total por causa dos juros
          await this.recalcularValorTotal()
        }
        await this.salvar()
      })
    },

    // exclui todas as parcelas (ainda sem título) de uma condição
    async excluirParcelas(condicao) {
      return comLock(this.negocio?.uuid, async () => {
        await this.recarregar()
        this.negocio.parcelas = this.negocio.parcelas.filter(
          (np) => np.condicao != condicao || np.codtitulo,
        )
        await this.recalcularValorTotal()
        await this.salvar()
      })
    },

    // cobrança integrada mudou (PixCobDialog, PagarMePedidoDialog, SaurusPedidoDialog): se é
    // deste negócio, recarrega do servidor (o pagamento nasce lá quando o banco confirma)
    async cobrancaAtualizada({ codnegocio, pixCob = null }) {
      if (!codnegocio || !this.negocio || this.negocio.codnegocio != codnegocio) {
        return
      }
      if (!this.negocio.sincronizado) {
        return
      }
      // PIX com o mesmo status: só substitui, nem recarrega
      if (pixCob) {
        const index = (this.negocio.pixCob ?? []).findIndex((c) => c.codpixcob === pixCob.codpixcob)
        if (index > -1 && this.negocio.pixCob[index].codpixcobstatus == pixCob.codpixcobstatus) {
          this.negocio.pixCob[index] = pixCob
          this.salvar(false)
          return
        }
      }
      await this.recarregarDaApi(this.negocio.codnegocio)
    },

    // se o negocio ainda nao subiu pro servidor, tenta sincronizar antes de desistir
    async garantirSincronizado() {
      if (!this.negocio.sincronizado) {
        await this.sincronizar(this.negocio.uuid)
      }
      return this.negocio.sincronizado
    },

    async fechar() {
      if (!(await this.garantirSincronizado())) {
        Notify.create({
          type: 'negative',
          message: 'Impossível fechar um negócio não sincronizado com o servidor!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      try {
        const ret = await sSinc.fecharNegocio(this.negocio.codnegocio, this.padrao.impressora)
        if (ret.codnegocio) {
          Notify.create({
            type: 'positive',
            message: 'Negócio Fechado!',
            timeout: 1000, // 1 segundo
            actions: [{ icon: 'close', color: 'white' }],
          })
          await this.atualizarNegocioPeloObjeto(ret)
        }
      } catch (error) {
        console.log(error)
      }
    },

    async cancelar(justificativa) {
      if (!(await this.garantirSincronizado())) {
        Notify.create({
          type: 'negative',
          message: 'Impossível cancelar um negócio não sincronizado com o servidor!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      try {
        const ret = await sSinc.cancelarNegocio(this.negocio.codnegocio, justificativa)
        if (ret.codnegocio) {
          Notify.create({
            type: 'positive',
            message: 'Negócio cancelado!',
            timeout: 1000, // 1 segundo
            actions: [{ icon: 'close', color: 'white' }],
          })
          await this.atualizarNegocioPeloObjeto(ret)
        }
      } catch (error) {
        console.log(error)
      }
    },

    async uploadAnexo(pasta, ratio, anexoBase64) {
      try {
        // asdasd();
        const ret = await sSinc.uploadAnexo(this.negocio.codnegocio, pasta, ratio, anexoBase64)
        if (!ret) {
          return false
        }
        Notify.create({
          type: 'positive',
          message: 'Anexo Adicionado!',
          timeout: 1000, // 1 segundo
          actions: [{ icon: 'close', color: 'white' }],
        })
        this.negocio.anexos = ret.data
        return true
      } catch (error) {
        console.log(error)
        return false
      }
    },

    async deleteAnexo(pasta, anexo) {
      try {
        // asdasd();
        const data = await sSinc.deleteAnexo(this.negocio.codnegocio, pasta, anexo)
        if (!data) {
          return false
        }
        Notify.create({
          type: 'positive',
          message: 'Anexo Excluído!',
          timeout: 1000, // 1 segundo
          actions: [{ icon: 'close', color: 'white' }],
        })
        this.negocio.anexos = data
        return true
      } catch (error) {
        console.log(error)
        return false
      }
    },

    async unificarComanda(codnegociocomanda, escolhas = {}) {
      if (!(await this.garantirSincronizado())) {
        Notify.create({
          type: 'negative',
          message: 'Impossível ler Comandas em um negócio não sincronizado com o servidor!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      try {
        const ret = await sSinc.unificarComanda(
          this.negocio.codnegocio,
          codnegociocomanda,
          escolhas,
        )
        if (!ret) {
          return false
        }
        Notify.create({
          type: 'positive',
          message: 'Comanda Lida!',
          timeout: 1000, // 1 segundo
          actions: [{ icon: 'close', color: 'white' }],
        })
        if (ret.negocio.codnegocio) {
          await this.atualizarNegocioPeloObjeto(ret.negocio)
        }
        if (ret.comanda.codnegocio) {
          db.negocio.put(ret.comanda)
        }
        return true
      } catch (error) {
        console.log(error)
        return false
      }
    },

    async Devolucao(arrDevolucao) {
      const postDevolucao = await sSinc.negocioDevolucao(
        this.negocio.codnegocio,
        arrDevolucao,
        this.padrao.impressora,
      )

      return postDevolucao
    },

    async buscarVale(codtitulo) {
      const vale = await sSinc.buscarVale(codtitulo)
      return vale
    },

    // =================================================================
    // CONSUMO DE VALE POR ESCOPO (escola / turma)
    //
    // O resgate por bipagem continua sendo o caminho de quem chega com o
    // papel na mao. Este e' o outro caso: o pai chega sem papel nenhum e o
    // caixa consome o credito da turma do filho, em FIFO.
    // =================================================================

    async valeEscopoFavorecidos(busca = null) {
      return sSinc.valeEscopoFavorecidos(busca)
    },

    async valeEscopoTurmas(codpessoafavorecido) {
      return sSinc.valeEscopoTurmas(codpessoafavorecido)
    },

    // Os vales ja usados NESTE negocio saem da escolha: o servidor ainda nao
    // sabe deles (o pagamento so' baixa no fechamento), entao sem isso o
    // FIFO devolveria o mesmo vale duas vezes.
    async valeEscopoSelecionar(codpessoafavorecido, turma, valor) {
      const usados = (this.negocio.pagamentos ?? [])
        .filter((p) => p.codtitulo)
        .map((p) => p.codtitulo)
      return sSinc.valeEscopoSelecionar({
        codpessoafavorecido,
        turma: turma || null,
        valor,
        usados: usados.join(','),
      })
    },

    // Um pagamento por vale: e' assim que o servidor amortiza cada credito e
    // que o cancelamento sabe o que estornar de cada um. A tela e a nota
    // agrupam (decisao 6), o banco nao.
    async adicionarPagamentosVale(vales) {
      for (const vale of vales) {
        await this.adicionarPagamento({
          meio: MEIO.VALE,
          codtitulo: vale.codtitulo,
          principal: parseFloat(vale.usar),
        })
      }
      return true
    },
  },
})
