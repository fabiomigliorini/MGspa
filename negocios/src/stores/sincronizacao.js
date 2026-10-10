import { defineStore } from 'pinia'
import { api } from 'boot/axios'
import { db } from 'boot/db'
import { uid } from 'quasar'
import { Platform } from 'quasar'
import { Notify } from 'quasar'

// o negocio.js chama sincronizacaoStore() no topo do modulo: importar ele daqui de forma
// estatica fecha um ciclo que quebra quando a sincronizacao carrega primeiro (Meu Dispositivo
// como primeira tela). Por isso o negocio vem por import dinamico, so' na hora de usar.
const negocio = async () => (await import('stores/negocio')).negocioStore()

export const DISPOSITIVO_NAO_CADASTRADO =
  'Este navegador não está cadastrado como dispositivo. Abra o Meu Dispositivo e clique em Cadastrar.'
export const DISPOSITIVO_INATIVO =
  'Dispositivo inativo. Abra o Meu Dispositivo e peça a um administrador para ativá-lo.'

const erro = (message) => {
  Notify.create({
    type: 'negative',
    message,
    timeout: 0,
    actions: [{ icon: 'close', color: 'white' }],
  })
  new Audio('/erro.mp3').play().catch(() => {})
}

export const sincronizacaoStore = defineStore('sincronizacao', {
  persist: {
    pick: ['ultimaSincronizacao', 'pdv', 'configuracaoMigrada'],
  },

  state: () => ({
    sincronizacao: {
      config: true,
      pessoa: true,
      produto: true,
      prancheta: true,
      valeModelo: true,
      completa: true,
    },
    ultimaSincronizacao: {
      impressora: null,
      formaPagamento: null,
      estoqueLocal: null,
      naturezaOperacao: null,
      valeModelo: null,
      pessoa: null,
      produto: null,
      prancheta: null,
      completa: null,
    },
    labelSincronizacao: '',
    pdv: {
      // envia para api
      uuid: null,
      latitude: null,
      longitude: null,
      precisao: null,
      plataforma: null,
      navegador: null,
      versaonavegador: null,
      desktop: null,
      // backend retorna (inativo preenchido: nao vende nem sincroniza; ativar e' autorizar)
      inativo: null,
      apelido: null,
      codfilial: null,
      filial: null,
      codpdv: null,
      codsetor: null,
      observacoes: null,
      setor: null,
    },
    // a configuracao que ficava so' no navegador ja foi enviada ao backend (TASK-46)
    configuracaoMigrada: false,
    importacao: {
      totalRegistros: null,
      totalSincronizados: null,
      progresso: 0,
      rodando: false,
      erro: false,
      dialog: false,
      requisicoes: null,
      tempoTotal: null,
      maxRequisicoes: 1000,
      limiteRequisicao: 3000,
    },
  }),

  actions: {
    // o que o navegador sabe de si: uuid, localizacao (obrigatoria) e plataforma
    async coletarDispositivo(acao) {
      if (!this.pdv.uuid) {
        this.pdv.uuid = uid()
      }

      try {
        const pos = await new Promise((resolve, reject) => {
          // sem timeout o Chrome pode nunca responder, mesmo com a permissao concedida
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            timeout: 10000,
            maximumAge: 10 * 60 * 1000,
          })
        })
        this.pdv.latitude = pos.coords.latitude
        this.pdv.longitude = pos.coords.longitude
        this.pdv.precisao = pos.coords.accuracy
      } catch (error) {
        // localizacao e obrigatoria: sem ela o dispositivo nao cadastra nem sincroniza
        erro(`Sem a localização do dispositivo não é possível ${acao}: ${error.message}`)
        throw error
      }

      const plat = Platform.is
      this.pdv.plataforma = plat.platform
      this.pdv.navegador = plat.name
      this.pdv.versaonavegador = plat.version
      this.pdv.desktop = plat.desktop ? 1 : 0
      return {
        uuid: this.pdv.uuid,
        latitude: this.pdv.latitude,
        longitude: this.pdv.longitude,
        precisao: this.pdv.precisao,
        desktop: this.pdv.desktop,
        navegador: this.pdv.navegador,
        versaonavegador: this.pdv.versaonavegador,
        plataforma: this.pdv.plataforma,
      }
    },

    // Meu Dispositivo > Cadastrar: o navegador vira um dispositivo, que nasce inativo
    async cadastrar() {
      try {
        const params = await this.coletarDispositivo('cadastrar')
        const { data } = await api.post('/v1/pdv/dispositivo', params)
        await this.aplicarDispositivo(data.data)
        return true
      } catch (error) {
        if (error?.response) {
          erro(error.response.data?.message || error.message)
        }
        return false
      }
    },

    // sincronizacao: atualiza o que o backend sabe do navegador e traz de volta o cadastro e a
    // configuracao; navegador nao cadastrado leva 404
    async dispositivo() {
      const params = await this.coletarDispositivo('sincronizar')
      // uma vez so': o backend preenche com isto as colunas de configuracao ainda vazias
      if (!this.configuracaoMigrada) {
        const padrao = (await negocio()).padrao
        params.legado = {
          codestoquelocal: padrao.codestoquelocal,
          codnaturezaoperacao: padrao.codnaturezaoperacao,
          impressora: padrao.impressora,
          codportador: padrao.codportador,
          maquineta: padrao.maquineta,
          codpagarmepos: padrao.codpagarmepos,
          codsauruspos: padrao.codsauruspos,
        }
      }
      try {
        let { data } = await api.put('/v1/pdv/dispositivo', params)
        this.configuracaoMigrada = true
        await this.aplicarDispositivo(data.data)
      } catch (error) {
        erro(error?.response?.data?.message || error.message)
        throw error
      }
    },

    // o que o backend guarda do dispositivo: cadastro aqui, configuracao no padrao do negocio.
    // Antes da 1a sincronizacao o padrao ainda e' o do navegador, que a sincronizacao envia
    // como legado: nao pode ser trocado antes disso.
    async aplicarDispositivo(pdv) {
      this.pdv.inativo = pdv.inativo
      this.pdv.apelido = pdv.apelido
      this.pdv.codfilial = pdv.codfilial
      this.pdv.filial = pdv.filial
      this.pdv.codpdv = pdv.codpdv
      this.pdv.codsetor = pdv.codsetor
      this.pdv.observacoes = pdv.observacoes
      this.pdv.setor = pdv.setor
      if (this.configuracaoMigrada) {
        const sNegocio = await negocio()
        sNegocio.aplicarConfiguracao(pdv)
      }
    },

    async sincronizar() {
      // desabilita o botao ja no clique, antes do dispositivo() responder
      this.importacao.rodando = true
      this.importacao.erro = false

      // verifica se PDV pode acessar API (o dispositivo() ja avisou o erro)
      try {
        await this.dispositivo()
      } catch (error) {
        console.log(error)
        this.importacao.rodando = false
        return
      }
      if (this.pdv.inativo) {
        this.importacao.rodando = false
        erro(DISPOSITIVO_INATIVO)
        return
      }

      // roda as importacoes
      try {
        if (this.sincronizacao.config) {
          await this.sincronizarImpressora()
          await this.sincronizarFormaPagamento()
          await this.sincronizarEstoqueLocal()
          await this.sincronizarNaturezaOperacao()
        }
        if (this.sincronizacao.pessoa) {
          await this.sincronizarPessoa()
        }
        if (this.sincronizacao.produto) {
          await this.sincronizarProduto()
        }
        if (this.sincronizacao.prancheta) {
          await this.sincronizarPrancheta()
        }
        if (this.sincronizacao.valeModelo) {
          await this.sincronizarValeModelo()
        }
      } catch (error) {
        console.log(error)
        this.importacao.erro = true
      }

      // esconde janela de progresso; com erro ela fica aberta, junto dos avisos
      // (cancelada pelo usuario, rodando ja e false e a janela segue fechada)
      const manterAberta = this.importacao.erro && this.importacao.rodando
      this.inicializaVars()
      if (manterAberta) {
        this.importacao.dialog = true
      }
      await this.avisarSincronizacaoCompleta()
    },

    // a pagina do dispositivo mostra a mesma data que o botao Sincronizar (a completa: a mais
    // antiga entre os cadastros baixados). Falhar aqui nao desfaz a sincronizacao
    async avisarSincronizacaoCompleta() {
      if (!this.ultimaSincronizacao.completa) {
        return
      }
      try {
        await api.put('/v1/pdv/dispositivo/sincronizacao-completa', {
          pdv: this.pdv.uuid,
          completa: this.ultimaSincronizacao.completa,
        })
      } catch (error) {
        console.log(error)
      }
    },

    async inicializaVars() {
      this.inicializaProgresso(null)
      this.importacao.dialog = false
      this.importacao.rodando = false
      this.ultimaSincronizacao.completa = [
        this.ultimaSincronizacao.impressora,
        this.ultimaSincronizacao.formaPagamento,
        this.ultimaSincronizacao.estoqueLocal,
        this.ultimaSincronizacao.naturezaOperacao,
        this.ultimaSincronizacao.valeModelo,
        this.ultimaSincronizacao.pessoa,
        this.ultimaSincronizacao.produto,
        this.ultimaSincronizacao.prancheta,
      ].sort()[0]
    },

    async abortarSincronizacao() {
      this.importacao.rodando = false
      this.importacao.dialog = false
    },

    inicializaProgresso(label) {
      this.importacao.progresso = 0
      this.importacao.totalRegistros = 0
      this.importacao.totalSincronizados = 0
      this.importacao.requisicoes = 0
      this.importacao.tempoTotal = 0
      this.labelSincronizacao = label
    },

    async sincronizarImpressora() {
      if (!this.importacao.rodando) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Impressoras')
      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/impressora', {
          params: { pdv: this.pdv.uuid },
        })

        // insere dados no banco local indexeddb
        await db.impressora.bulkPut(data)

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado
        db.impressora.where('sincronizado').below(sincronizado).delete()

        //registra data de Sincronizacao
        this.ultimaSincronizacao.impressora = sincronizado
      } catch (error) {
        console.log(error)
        console.log('Impossível sincronizar Impressoras')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    async sincronizarFormaPagamento() {
      if (!this.importacao.rodando) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Formas de Pagamento')
      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/forma-pagamento', {
          params: { pdv: this.pdv.uuid },
        })

        // insere dados no banco local indexeddb
        await db.formaPagamento.bulkPut(data)

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado
        db.formaPagamento.where('sincronizado').below(sincronizado).delete()

        //registra data de Sincronizacao
        this.ultimaSincronizacao.formaPagamento = sincronizado
      } catch (error) {
        console.log(error)
        console.log('Impossível sincronizar Formas de Pagamento')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    async sincronizarEstoqueLocal() {
      if (!this.importacao.rodando) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Locais de Estoque')
      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/estoque-local', {
          params: { pdv: this.pdv.uuid },
        })

        // insere dados no banco local indexeddb
        await db.estoqueLocal.bulkPut(data)

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado
        db.estoqueLocal.where('sincronizado').below(sincronizado).delete()

        //registra data de Sincronizacao
        this.ultimaSincronizacao.estoqueLocal = sincronizado
      } catch (error) {
        console.log(error)
        console.log('Impossível sincronizar Locais de Estoque')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    async silentSincronizarEstoqueLocal() {
      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/estoque-local', {
          params: { pdv: this.pdv.uuid },
        })

        // insere dados no banco local indexeddb
        await db.estoqueLocal.bulkPut(data)

        // exclui registros que nao vieram na importacao
        let sincronizado = data[0].sincronizado

        db.estoqueLocal.where('sincronizado').below(sincronizado).delete()

        //registra data de Sincronizacao
        this.ultimaSincronizacao.estoqueLocal = sincronizado

        return true
      } catch (error) {
        console.log(error)
        console.log('Impossível sincronizar Locais de Estoque')
        return false
      }
    },

    async sincronizarNaturezaOperacao() {
      if (!this.importacao.rodando) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Natureza De Operação')
      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/natureza-operacao', {
          params: { pdv: this.pdv.uuid },
        })

        // insere dados no banco local indexeddb
        await db.naturezaOperacao.bulkPut(data)

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado
        db.naturezaOperacao.where('sincronizado').below(sincronizado).delete()

        //registra data de Sincronizacao
        this.ultimaSincronizacao.naturezaOperacao = sincronizado
      } catch (error) {
        console.log(error)
        console.log('Impossível sincronizar Natureza Operacao')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    // Catalogo de modelos de vale compras (kit escolar).
    //
    // Fora do padrao das outras num ponto: o catalogo e' SAZONAL e pode
    // voltar vazio. O template das demais le data[0].sincronizado sem olhar
    // se veio alguma coisa, e com lista vazia isso estoura um TypeError que
    // o catch nao sabe tratar (mexe em error.response, que nao existe) --
    // derrubando a sincronizacao inteira dali pra frente. Por isso a guarda
    // logo na entrada.
    async sincronizarValeModelo() {
      if (!this.importacao.rodando) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Modelos de Vale')
      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/vale-modelo', {
          params: { pdv: this.pdv.uuid },
        })

        // catalogo vazio: limpa o cache local (nenhum modelo ativo la') e
        // sai sem carimbar data, que so' existe quando veio registro.
        if (!data.length) {
          await db.valeModelo.clear()
          return
        }

        // insere dados no banco local indexeddb
        await db.valeModelo.bulkPut(data)

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado
        db.valeModelo.where('sincronizado').below(sincronizado).delete()

        //registra data de Sincronizacao
        this.ultimaSincronizacao.valeModelo = sincronizado
      } catch (error) {
        console.log(error)
        console.log('Impossível sincronizar Modelos de Vale')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error?.response?.data?.message ?? error?.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    async sincronizarPessoa() {
      if (!this.importacao.rodando) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Pessoas')

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get('/v1/pdv/pessoa-count', {
          params: { pdv: this.pdv.uuid },
        })
        this.importacao.totalRegistros = data.count
        this.importacao.limiteRequisicao = Math.round(this.importacao.totalRegistros / 10)
      } catch (error) {
        console.log(error)
        console.log('Impossível acessar API')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }

      let sincronizado = null
      let inicio = performance.now()
      let codpessoa = 0

      do {
        // busca dados na api
        var { data } = await api.get('/v1/pdv/pessoa', {
          params: {
            pdv: this.pdv.uuid,
            codpessoa: codpessoa,
            limite: this.importacao.limiteRequisicao,
          },
        })

        // se nao veio nada interrompe o loop
        if (data.length == 0) {
          break
        }

        // incrementa numero de requisicoes
        this.importacao.requisicoes++

        // insere dados no banco local indexeddb
        try {
          await db.pessoa.bulkPut(data)
        } catch (error) {
          console.log(error.stack || error)
        }

        if (sincronizado == null) {
          sincronizado = data[0].sincronizado
        }

        // busca codigo do ultimo registro
        codpessoa = data.slice(-1)[0].codpessoa

        //monta status de progresso
        this.importacao.totalSincronizados += data.length
        this.importacao.progresso = Math.round(
          (this.importacao.totalSincronizados * 100) / this.importacao.totalRegistros,
        )
        this.importacao.tempoTotal = Math.round((performance.now() - inicio) / 1000)

        // loop enquanto nao tiver buscado menos registros que o limite
      } while (
        data.length >= this.importacao.limiteRequisicao &&
        this.importacao.requisicoes <= this.importacao.maxRequisicoes &&
        this.importacao.rodando
      )

      // exclui registros que nao vieram na importacao
      if (this.importacao.rodando) {
        db.pessoa.where('sincronizado').below(sincronizado).delete()
        this.ultimaSincronizacao.pessoa = sincronizado
      }
    },

    async sincronizarProduto() {
      if (!this.importacao.rodando) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Produtos')

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get('/v1/pdv/produto-count', {
          params: { pdv: this.pdv.uuid },
        })
        this.importacao.totalRegistros = data.count
        this.importacao.limiteRequisicao = Math.round(this.importacao.totalRegistros / 50)
      } catch (error) {
        console.log(error)
        console.log('Impossível acessar API')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }

      let sincronizado = null
      let inicio = performance.now()
      let codprodutobarra = 0

      do {
        // busca dados na api
        var { data } = await api.get('/v1/pdv/produto', {
          params: {
            pdv: this.pdv.uuid,
            codprodutobarra: codprodutobarra,
            limite: this.importacao.limiteRequisicao,
          },
        })

        // se nao veio nada interrompe o loop
        if (data.length == 0) {
          break
        }

        // incrementa numero de requisicoes
        this.importacao.requisicoes++

        // insere dados no banco local indexeddb
        try {
          await db.produto.bulkPut(data)
        } catch (error) {
          console.log(error.stack || error)
        }

        if (sincronizado == null) {
          sincronizado = data[0].sincronizado
        }

        // busca codigo do ultimo registro
        codprodutobarra = data.slice(-1)[0].codprodutobarra

        //monta status de progresso
        this.importacao.totalSincronizados += data.length
        this.importacao.progresso = Math.round(
          (this.importacao.totalSincronizados * 100) / this.importacao.totalRegistros,
        )
        this.importacao.tempoTotal = Math.round((performance.now() - inicio) / 1000)

        // loop enquanto nao tiver buscado menos registros que o limite
      } while (
        data.length >= this.importacao.limiteRequisicao &&
        this.importacao.requisicoes <= this.importacao.maxRequisicoes &&
        this.importacao.rodando
      )

      // exclui registros que nao vieram na importacao
      if (this.importacao.rodando) {
        db.produto.where('sincronizado').below(sincronizado).delete()
        this.ultimaSincronizacao.produto = sincronizado
      }
    },

    async sincronizarPrancheta(forcar = false) {
      if (!this.importacao.rodando && !forcar) {
        return
      }

      // inicializa progresso
      this.inicializaProgresso('Prancheta')
      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/prancheta', {
          params: { pdv: this.pdv.uuid },
        })

        // insere dados no banco local indexeddb
        await db.prancheta.bulkPut(data)

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado
        db.prancheta.where('sincronizado').below(sincronizado).delete()

        //registra data de Sincronizacao
        this.ultimaSincronizacao.prancheta = sincronizado
      } catch (error) {
        console.log(error)
        console.log('Impossível sincronizar Prancheta')
        this.importacao.erro = true
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    async putNegocio(negocio) {
      const params = {
        pdv: this.pdv.uuid,
        negocio: negocio,
      }
      try {
        const { data } = await api.put('/v1/pdv/negocio', params)
        return data.data
      } catch (error) {
        console.log(error)
        let message = ''
        switch (error.code) {
          case 'ERR_NETWORK':
            message = 'Erro ao comunicar com Servidor. Operando Offline!'
            break

          default:
            message = error?.response?.data?.message
            if (!message) {
              message = error?.message
            }
            break
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async getNegocio(codOrUuid) {
      try {
        const { data } = await api.get('/v1/pdv/negocio/' + codOrUuid, {
          params: {
            pdv: this.pdv.uuid,
          },
        })
        return data.data
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async postApropriar(codnegocio) {
      try {
        const { data } = await api.post('/v1/pdv/negocio/' + codnegocio + '/apropriar', {
          pdv: this.pdv.uuid,
        })
        return data.data
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async fecharNegocio(codnegocio, impressora) {
      try {
        // com impressora, o backend imprime sozinho os vales e contra vales com saldo
        const { data } = await api.post('/v1/pdv/negocio/' + codnegocio + '/fechar', {
          pdv: this.pdv.uuid,
          impressora,
        })
        return data.data
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    // venda fechada reaberta pelo gerente (TASK-30)
    async reabrirNegocio(codnegocio) {
      try {
        const { data } = await api.post('/v1/pdv/negocio/' + codnegocio + '/reabrir', {
          pdv: this.pdv.uuid,
        })
        return data.data
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async cancelarNegocio(codnegocio, justificativa) {
      try {
        const { data } = await api.delete('/v1/pdv/negocio/' + codnegocio, {
          params: {
            pdv: this.pdv.uuid,
            justificativa: justificativa,
          },
        })
        return data.data
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async unificarComanda(codnegocio, codnegociocomanda, escolhas = {}) {
      try {
        const { data } = await api.post(
          `/v1/pdv/negocio/${codnegocio}/unificar/${codnegociocomanda}`,
          {
            pdv: this.pdv.uuid,
            ...escolhas,
          },
        )
        return data
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async negocioDevolucao(codnegocio, arrDevolucao, impressora) {
      try {
        // com impressora, o backend imprime sozinho o vale de credito da devolucao
        const ret = await api.post('/v1/pdv/negocio/' + codnegocio + '/devolucao', {
          pdv: this.pdv.uuid,
          devolucao: arrDevolucao,
          impressora,
        })
        return ret
      } catch (error) {
        console.log(error)
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    // Consumo de vale por escopo (escola / turma). Online, como o buscarVale:
    // o saldo do credito mora no servidor e nao no cache do PDV.
    async valeEscopoFavorecidos(busca = null) {
      return this.getValeEscopo('/v1/pdv/vale-escopo/favorecido', { busca })
    },

    async valeEscopoTurmas(codpessoafavorecido) {
      return this.getValeEscopo(`/v1/pdv/vale-escopo/favorecido/${codpessoafavorecido}/turma`)
    },

    async valeEscopoSelecionar(params) {
      return this.getValeEscopo('/v1/pdv/vale-escopo/selecionar', params)
    },

    async getValeEscopo(url, params = {}) {
      try {
        const ret = await api.get(url, { params })
        return ret.data
      } catch (error) {
        console.log(error)
        Notify.create({
          type: 'negative',
          message: error.response?.data?.message ?? 'Falha ao consultar os vales da escola!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return null
      }
    },

    async buscarVale(codtitulo) {
      try {
        const ret = await api.get('/v1/pdv/vale/' + codtitulo)
        return ret
      } catch (error) {
        console.log(error)
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return null
      }
    },

    async uploadAnexo(codnegocio, pasta, ratio, anexoBase64) {
      try {
        const ret = await api.post('/v1/negocio/' + codnegocio + '/anexo', {
          pdv: this.pdv.uuid,
          pasta: pasta,
          ratio: ratio,
          anexoBase64: anexoBase64,
        })
        return ret
      } catch (error) {
        console.log(error)
        let message = error.message
        if (error.response) {
          message = error.response.data.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async deleteAnexo(codnegocio, pasta, anexo) {
      try {
        const { data } = await api.delete(
          '/v1/negocio/' + codnegocio + '/anexo/' + pasta + '/' + anexo,
          {
            params: {
              pdv: this.pdv.uuid,
            },
          },
        )
        return data
      } catch (error) {
        console.log(error)
        Notify.create({
          type: 'negative',
          message: error.response.data.message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async postPessoa(pessoa) {
      try {
        pessoa.pdv = this.pdv.uuid
        var { data } = await api.post('/v1/pdv/pessoa', pessoa)
        await db.pessoa.bulkPut(data)
        return data[0].codpessoa
      } catch (error) {
        let message = 'Falha ao salvar a pessoa!'
        try {
          message = error.response.data.message
        } catch (e) {
          console.log(e)
        }
        try {
          Object.values(error.response.data.errors).forEach((e) => {
            e.forEach((m) => {
              message = m
            })
          })
        } catch (e) {
          console.log(e)
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },

    async pessoaPeloCnpj(cnpj) {
      try {
        var { data } = await api.get('/v1/pdv/pessoa/cnpj/' + cnpj, {
          params: { pdv: this.pdv.uuid },
        })
        await db.pessoa.bulkPut(data)
        return data.length
      } catch (error) {
        console.log(error)
        let message = 'Falha ao pesquisar CNPJ/CPF no servidor! Operando Offline!'
        try {
          message = error.response.data.message
        } catch (e) {
          console.log(e)
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
    },
  },
})
