import { defineStore } from 'pinia'
import { api } from 'src/services/api'
import { db } from 'boot/db'
import { uid } from 'quasar'
import { Platform } from 'quasar'
import { Notify } from 'quasar'
import { formataDataIso } from '@components/formatters'
import { hoje, paraDate } from '@components/utils/datas'

// o negocio.js chama sincronizacaoStore() no topo do modulo: importar ele daqui de forma
// estatica fecha um ciclo que quebra quando a sincronizacao carrega primeiro (Meu Dispositivo
// como primeira tela). Por isso o negocio vem por import dinamico, so' na hora de usar.
const negocio = async () => (await import('stores/negocio')).negocioStore()

// a sincronizacao ao abrir roda uma vez por carga do app, nao a cada tela que mostra a janela
let verificadaAoAbrir = false

// Catalogo (TASK-197): produto e pessoa vem do json/diff que o job pdv:catalogo gera no
// servidor. Full baixa o json atual em fluxo; incremental aplica os diffs depois da versao
// local. chave = tabela local; tabela = nome no servidor; pk = primeiro campo de cada linha
const CATALOGO = {
  produto: { tabela: 'produtos', pk: 'codprodutobarra' },
  pessoa: { tabela: 'pessoas', pk: 'codpessoa' },
}
// a full grava e mostra o progresso a cada LOTE registros: lote grande parece travado
const LOTE = 2000
// etapas da sincronizacao: cada uma e' uma linha do dialog e roda em paralelo com as outras
export const ETAPAS = {
  config: 'Configurações',
  pessoa: 'Pessoas',
  produto: 'Produtos',
  prancheta: 'Prancheta',
  valeModelo: 'Modelos de Vale',
}
const etapaVazia = (label) => ({
  label,
  rodando: false,
  concluida: false,
  erro: false,
  modo: null, // 'full' | 'diff'
  total: 0, // registros esperados na full, diffs a aplicar no incremental
  registros: 0, // gravados na full, diffs aplicados no incremental
  alterados: 0,
  excluidos: 0,
})
// '20261017-135607' -> '2026-10-17 13:56:07', o formato que o dialog e o backend ja usam
const dataDaVersao = (v) =>
  v.replace(/^(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})(\d{2})$/, '$1-$2-$3 $4:$5:$6')
// aborta os downloads de todas as etapas de uma vez (Cancelar, ESC, clique fora)
let abortador = null
// verificacao da incremental em andamento: abrir a janela e a sincronizacao ao abrir pedem juntas
let verificacao = null
// dias desde a ultima completa que a incremental ainda vale. O servidor guarda os diffs 1 dia
// alem disto (DIAS_DIFF no api/app/Mg/Pdv/PdvCatalogoService.php): quem mexer num mexe no outro
const DIAS_INCREMENTAL = 7
// no maximo n downloads ao mesmo tempo; cada chamada devolve a promessa do seu download
const limitar = (n) => {
  let ativos = 0
  const fila = []
  const proximo = () => {
    if (ativos >= n || !fila.length) {
      return
    }
    ativos++
    const { fn, resolve, reject } = fila.shift()
    fn()
      .then(resolve, reject)
      .finally(() => {
        ativos--
        proximo()
      })
  }
  return (fn) =>
    new Promise((resolve, reject) => {
      fila.push({ fn, resolve, reject })
      proximo()
    })
}

export const DISPOSITIVO_NAO_CADASTRADO =
  'Este navegador não está cadastrado como dispositivo. Abra o Meu Dispositivo e clique em Cadastrar.'
// trava do Web Locks: uma sincronizacao por vez entre as janelas do navegador
const TRAVA = 'sincronizacao'
const EM_ANDAMENTO = 'Já existe uma sincronização em andamento, nesta ou em outra janela.'

export const DISPOSITIVO_INATIVO =
  'Dispositivo inativo. Abra o Meu Dispositivo e peça a um administrador para ativá-lo.'

// Localizacao (obrigatoria): vale a posicao de ate' 1 min, nunca mais velha. Cada tentativa
// espera o navegador por 10 s; a resposta que chegar depois fica guardada so' em memoria e serve
// para a proxima tentativa enquanto tiver ate' 1 min (TASK-197)
const IDADE_POSICAO = 60 * 1000
const ESPERA_POSICAO = 10 * 1000
let posicao = null
let pedido = null

// pedido sem timeout: quem desiste e' o localizar(), e a resposta atrasada nao se perde. Um
// pedido por vez: a janela ao abrir e a sincronizacao que abre sozinha pedem juntas
const pedirPosicao = () => {
  if (!pedido) {
    pedido = new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        (p) => {
          posicao = p
          resolve(p)
        },
        reject,
        { maximumAge: IDADE_POSICAO },
      )
    }).finally(() => (pedido = null))
  }
  return pedido
}

const posicaoRecente = () => posicao && Date.now() - posicao.timestamp <= IDADE_POSICAO

// desiste em 10 s ou quando a sincronizacao e' cancelada (signal)
const localizar = async (signal) => {
  if (posicaoRecente()) {
    return posicao
  }
  let espera
  try {
    return await Promise.race([
      pedirPosicao(),
      new Promise((_, reject) => {
        espera = setTimeout(
          () => reject(new Error('o navegador não respondeu em 10 segundos')),
          ESPERA_POSICAO,
        )
        signal?.addEventListener('abort', () => reject(signal.reason), { once: true })
      }),
    ])
  } finally {
    clearTimeout(espera)
  }
}

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
    pick: ['ultimaSincronizacao', 'versao', 'pdv', 'configuracaoMigrada'],
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
    // versao do catalogo que cada tabela local tem (TASK-197); vazia = nunca baixou = full
    versao: {
      produto: null,
      pessoa: null,
    },
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
      progresso: 0,
      rodando: false,
      erro: false,
      dialog: false,
      tempoTotal: 0,
      incremental: null, // a janela oferece a incremental? null = verificando
      // a janela mostra a busca da localizacao abaixo da ultima sincronizacao; erro = o motivo
      localizacao: { buscando: false, erro: null, latitude: null, longitude: null, precisao: null },
      etapas: Object.fromEntries(Object.entries(ETAPAS).map(([k, l]) => [k, etapaVazia(l)])),
    },
  }),

  actions: {
    // o que o navegador sabe de si: uuid, localizacao (obrigatoria) e plataforma
    async coletarDispositivo(acao, signal) {
      if (!this.pdv.uuid) {
        this.pdv.uuid = uid()
      }

      try {
        const pos = await this.localizarDispositivo(signal)
        this.pdv.latitude = pos.coords.latitude
        this.pdv.longitude = pos.coords.longitude
        this.pdv.precisao = pos.coords.accuracy
      } catch (error) {
        // localizacao e obrigatoria: sem ela o dispositivo nao cadastra nem sincroniza.
        // Cancelada no meio nao e' erro e nao avisa
        if (!signal?.aborted) {
          erro(`Sem a localização do dispositivo não é possível ${acao}: ${error.message}`)
        }
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

    // a janela Sincronizar mostra a busca: ao abrir (os botoes so' habilitam com a posicao) e na
    // sincronizacao, quando a de ate' 1 min ja' venceu
    async localizarDispositivo(signal) {
      const loc = this.importacao.localizacao
      Object.assign(loc, {
        buscando: true,
        erro: null,
        latitude: null,
        longitude: null,
        precisao: null,
      })
      try {
        const pos = await localizar(signal)
        loc.latitude = pos.coords.latitude
        loc.longitude = pos.coords.longitude
        loc.precisao = pos.coords.accuracy
        return pos
      } catch (error) {
        if (!signal?.aborted) {
          loc.erro = error.message
        }
        throw error
      } finally {
        loc.buscando = false
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
    async dispositivo(signal) {
      const params = await this.coletarDispositivo('sincronizar', signal)
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

    // Ao abrir o sistema (TASK-197): sem ter sincronizado hoje (ultima de outro dia, ou nunca
    // feita), abre a janela e ja sincroniza. Quem chama e' a janela de sincronizar ao aparecer
    // (PDV e quiosque). Sem cadastro ou inativo nao sincroniza: o router manda pro Meu Dispositivo.
    async sincronizarAoAbrir() {
      if (verificadaAoAbrir) {
        return
      }
      verificadaAoAbrir = true
      if (!this.pdv.codpdv || this.pdv.inativo) {
        return
      }
      const completa = this.ultimaSincronizacao.completa
      if (completa && formataDataIso(completa) === hoje()) {
        return
      }
      if (!(await this.abrirJanela())) {
        return
      }
      await this.verificarIncremental()
      await this.sincronizar(!this.importacao.incremental)
    },

    // com outra janela sincronizando, a janela Sincronizar nem abre: recusa com o mesmo aviso do
    // sincronizar(), consultando a mesma trava. Na corrida, o sincronizar() ainda recusa no clique
    async abrirJanela() {
      if (navigator.locks) {
        const { held } = await navigator.locks.query()
        if (held.some((t) => t.name === TRAVA)) {
          erro(EM_ANDAMENTO)
          return false
        }
      }
      this.importacao.dialog = true
      return true
    },

    // a incremental so' vale se, em pessoas e produtos, o servidor ainda conhece a versao local,
    // os diffs somados sao menores que a completa e a ultima completa tem ate' DIAS_INCREMENTAL;
    // senao a janela so' oferece a completa
    verificarIncremental() {
      if (!verificacao) {
        this.importacao.incremental = null
        verificacao = Promise.all(Object.keys(CATALOGO).map((c) => this.incrementalDisponivel(c)))
          .then((disponiveis) => disponiveis.every(Boolean))
          .catch(() => false)
          .then((disponivel) => (this.importacao.incremental = disponivel))
          .finally(() => (verificacao = null))
      }
      return verificacao
    },

    async incrementalDisponivel(chave) {
      const local = this.versao[chave]
      if (!local) {
        return false
      }
      // a completa carimba todos os registros com a versao dela e o diff so' os que mudaram: a
      // menor versao da tabela e' a da ultima completa
      const maisAntigo = await db[chave].orderBy('versao').first()
      const limite = Date.now() - DIAS_INCREMENTAL * 24 * 60 * 60 * 1000
      if (!maisAntigo || paraDate(dataDaVersao(maisAntigo.versao)) < limite) {
        return false
      }
      const { data } = await api.get(`/v1/pdv/catalogo/${CATALOGO[chave].tabela}/versoes`, {
        params: { desde: local },
      })
      const somaDiffs = data.diffs.reduce((soma, d) => soma + d.tamanho, 0)
      return !!data.desde?.conhecida && somaDiffs < data.atual.tamanho
    },

    // Uma sincronizacao por vez, entre todas as janelas do navegador (TASK-197): a trava do
    // Web Locks vale para as abas do mesmo site e solta sozinha se a janela que sincroniza fechar
    // full = botao Sincronizacao completa: pessoas e produtos vem pelo arquivo inteiro; sem ele,
    // so' os diffs (incremental)
    async sincronizar(full = false) {
      if (this.importacao.rodando) {
        return
      }
      // desabilita o botao ja no clique, antes da trava e do dispositivo() responderem
      this.importacao.rodando = true
      this.importacao.erro = false

      // sem Web Locks (navegador antigo ou pagina sem https) sincroniza sem a trava
      if (!navigator.locks) {
        await this.executarSincronizacao(full)
        return
      }
      const rodou = await navigator.locks.request(TRAVA, { ifAvailable: true }, async (trava) => {
        if (!trava) {
          return false
        }
        await this.executarSincronizacao(full)
        return true
      })
      if (!rodou) {
        this.importacao.rodando = false
        this.importacao.dialog = false
        erro(EM_ANDAMENTO)
      }
    },

    async executarSincronizacao(full) {
      // o Cancelar aborta da espera da localizacao ate' os downloads de todas as etapas
      abortador = new AbortController()
      // verifica se PDV pode acessar API (o dispositivo() ja avisou o erro)
      try {
        await this.dispositivo(abortador.signal)
      } catch (error) {
        console.log(error)
        this.importacao.rodando = false
        return
      }
      // cancelada enquanto o backend respondia o dispositivo
      if (!this.importacao.rodando) {
        return
      }
      if (this.pdv.inativo) {
        this.importacao.rodando = false
        erro(DISPOSITIVO_INATIVO)
        return
      }

      // Etapas em paralelo (TASK-197): cada uma e' uma linha do dialog, erro numa nao derruba
      // as outras, e o Cancelar aborta os downloads de todas pelo mesmo controlador
      const inicio = performance.now()
      const cronometro = setInterval(() => {
        this.importacao.tempoTotal = Math.round((performance.now() - inicio) / 1000)
      }, 500)
      const etapas = []
      if (this.sincronizacao.config) {
        etapas.push(this.rodarEtapa('config', () => this.sincronizarConfig()))
      }
      if (this.sincronizacao.pessoa) {
        etapas.push(this.rodarEtapa('pessoa', () => this.sincronizarCatalogo('pessoa', full)))
      }
      if (this.sincronizacao.produto) {
        etapas.push(this.rodarEtapa('produto', () => this.sincronizarCatalogo('produto', full)))
      }
      if (this.sincronizacao.prancheta) {
        etapas.push(this.rodarEtapa('prancheta', () => this.sincronizarPrancheta()))
      }
      if (this.sincronizacao.valeModelo) {
        etapas.push(this.rodarEtapa('valeModelo', () => this.sincronizarValeModelo()))
      }
      await Promise.allSettled(etapas)
      clearInterval(cronometro)

      // esconde janela de progresso; com erro ela fica aberta, junto dos avisos
      // (cancelada pelo usuario, rodando ja e false e a janela segue fechada)
      const manterAberta = this.importacao.erro && this.importacao.rodando
      const finalizada = !this.importacao.erro && this.importacao.rodando
      this.inicializaVars()
      if (manterAberta) {
        this.importacao.dialog = true
      }
      if (finalizada) {
        Notify.create({ message: 'Sincronização finalizada', color: 'green-5', icon: 'done' })
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
          completa: this.ultimaSincronizacao.completa,
        })
      } catch (error) {
        console.log(error)
      }
    },

    async inicializaVars() {
      this.importacao.progresso = 0
      this.importacao.dialog = false
      this.importacao.rodando = false
      // sem pessoas ou produtos (completa nunca feita ou interrompida) o PDV nao esta' sincronizado:
      // o null iria para o fim do sort e valeria a data das outras etapas
      const u = this.ultimaSincronizacao
      if (!u.pessoa || !u.produto) {
        u.completa = null
        return
      }
      u.completa = [
        u.impressora,
        u.formaPagamento,
        u.estoqueLocal,
        u.naturezaOperacao,
        u.valeModelo,
        u.pessoa,
        u.produto,
        u.prancheta,
      ].sort()[0]
    },

    async abortarSincronizacao() {
      this.importacao.rodando = false
      this.importacao.dialog = false
      abortador?.abort()
    },

    // uma etapa = uma linha do dialog; fn devolve false (ou lanca) quando falhou
    async rodarEtapa(chave, fn) {
      const etapa = this.importacao.etapas[chave]
      Object.assign(etapa, etapaVazia(etapa.label), { rodando: true })
      let ok = false
      try {
        ok = (await fn()) !== false
      } catch (error) {
        // cancelada no meio nao e' erro; as funcoes de configuracao avisam sozinhas
        if (!['AbortError', 'CanceledError'].includes(error?.name)) {
          console.log(error)
          erro(`${etapa.label}: ${error?.response?.data?.message ?? error?.message}`)
        }
      }
      etapa.rodando = false
      etapa.concluida = ok
      if (!ok && this.importacao.rodando) {
        etapa.erro = true
        this.importacao.erro = true
      }
      return ok
    },

    // progresso geral (botao e circulo do dialog): gravados sobre esperados, todas as etapas
    atualizarProgresso() {
      let total = 0
      let feito = 0
      for (const e of Object.values(this.importacao.etapas)) {
        total += e.total
        feito += Math.min(e.registros, e.total)
      }
      this.importacao.progresso = total ? Math.round((feito * 100) / total) : 0
    },

    // registro que veio ao vivo (bipe, CNPJ, cadastro) leva a versao local da tabela: sem isso
    // ele nao entraria no indice e sobreviveria a' limpeza da proxima full
    carimbar(chave, registros) {
      const versao = this.versao[chave] ?? '0'
      registros.forEach((r) => (r.versao = versao))
      return registros
    },

    // as quatro de configuracao, em sequencia, numa linha so' do dialog
    async sincronizarConfig() {
      const etapa = this.importacao.etapas.config
      etapa.total = 4
      let ok = true
      for (const fn of [
        this.sincronizarImpressora,
        this.sincronizarFormaPagamento,
        this.sincronizarEstoqueLocal,
        this.sincronizarNaturezaOperacao,
      ]) {
        if ((await fn.call(this)) === false) {
          ok = false
        }
        etapa.registros++
        this.atualizarProgresso()
      }
      return ok
    },

    // ---- Catalogo (TASK-197) --------------------------------------------------------------

    urlCatalogo(tabela, caminho) {
      return api.getUri({
        url: `/v1/pdv/catalogo/${tabela}/${caminho}`,
      })
    },

    // resposta de um arquivo do catalogo; o navegador descomprime o gzip sozinho. Fora do axios:
    // o cabecalho do dispositivo vai a mao
    async baixarCatalogo(tabela, versao, tipo) {
      const resposta = await fetch(this.urlCatalogo(tabela, `${versao}.${tipo}`), {
        headers: { 'X-Pdv': this.pdv.uuid },
        signal: abortador?.signal,
      })
      if (!resposta.ok) {
        const corpo = await resposta.json().catch(() => ({}))
        throw new Error(corpo.message ?? `${resposta.status} ${resposta.statusText}`)
      }
      return resposta
    },

    async sincronizarCatalogo(chave, full) {
      const { tabela, pk } = CATALOGO[chave]
      const etapa = this.importacao.etapas[chave]
      const local = this.versao[chave]
      const { data: versoes } = await api.get(`/v1/pdv/catalogo/${tabela}/versoes`, {
        params: { desde: local ?? undefined },
        signal: abortador?.signal,
      })
      const { atual, diffs } = versoes
      if (full) {
        return await this.aplicarFull(chave, etapa, tabela, atual)
      }
      // a janela so' oferece a incremental se o servidor conhece a versao local; se ela saiu de
      // la' depois disso, recusa em vez de baixar tudo por conta propria
      if (!versoes.desde?.conhecida) {
        throw new Error(
          'a versão deste PDV não está mais no servidor, use a Sincronização completa',
        )
      }
      return await this.aplicarDiffs(chave, pk, etapa, tabela, atual, diffs)
    },

    // baixa ate' 5 diffs por vez e aplica na ordem das versoes: '-' e' chave que saiu, '+' e'
    // registro que entrou ou mudou (o mudado vem nas duas e o bulkPut sobrescreve)
    async aplicarDiffs(chave, pk, etapa, tabela, atual, diffs) {
      etapa.modo = 'diff'
      etapa.total = diffs.length
      const baixar = limitar(5)
      const textos = diffs.map((d) =>
        baixar(async () => (await this.baixarCatalogo(tabela, d.versao, 'diff')).text()),
      )
      // uma falha derruba a etapa no await dela; as outras nao podem virar rejeicao solta
      textos.forEach((t) => t.catch(() => {}))
      for (let i = 0; i < diffs.length; i++) {
        const texto = await textos[i]
        if (!this.importacao.rodando) {
          return false
        }
        const incluir = []
        const saindo = []
        for (const linha of texto.split('\n')) {
          if (linha.startsWith('+{')) {
            const registro = JSON.parse(linha.slice(1))
            registro.versao = diffs[i].versao
            incluir.push(registro)
          } else if (linha.startsWith('-{')) {
            saindo.push(parseInt(linha.slice(linha.indexOf(':') + 1)))
          }
        }
        const voltou = new Set(incluir.map((r) => r[pk]))
        const excluir = saindo.filter((k) => !voltou.has(k))
        await db[chave].bulkDelete(excluir)
        await db[chave].bulkPut(incluir)
        etapa.alterados += incluir.length
        etapa.excluidos += excluir.length
        etapa.registros++
        this.versao[chave] = diffs[i].versao
        this.ultimaSincronizacao[chave] = dataDaVersao(diffs[i].versao)
        this.atualizarProgresso()
      }
      // rodada sem mudanca nao grava diff: depois do ultimo, o local ja e' igual ao json atual
      this.versao[chave] = atual.versao
      this.ultimaSincronizacao[chave] = dataDaVersao(atual.versao)
      return true
    },

    // baixa o json atual em fluxo, um registro por linha, gravando a cada LOTE; no fim o que
    // ficou com versao velha nao existe mais no servidor
    async aplicarFull(chave, etapa, tabela, atual) {
      etapa.modo = 'full'
      etapa.total = atual.registros ?? 0
      // antes de comecar: a completa interrompida (Cancelar, F5, travamento) deixa a tabela pela
      // metade, e ate' ela terminar o PDV fica como nunca sincronizado, so' com a completa
      this.versao[chave] = null
      this.ultimaSincronizacao[chave] = null
      this.ultimaSincronizacao.completa = null
      const resposta = await this.baixarCatalogo(tabela, atual.versao, 'json')
      const leitor = resposta.body.getReader()
      const decodificador = new TextDecoder()
      let resto = ''
      let lote = []
      const gravar = async () => {
        if (!lote.length) {
          return
        }
        await db[chave].bulkPut(lote)
        etapa.registros += lote.length
        lote = []
        this.atualizarProgresso()
      }
      const empilhar = (linha) => {
        if (!linha) {
          return
        }
        const registro = JSON.parse(linha)
        registro.versao = atual.versao
        lote.push(registro)
      }
      for (;;) {
        const { done, value } = await leitor.read()
        if (!this.importacao.rodando) {
          await leitor.cancel()
          return false
        }
        if (done) {
          break
        }
        // a ultima linha do pedaco pode vir cortada: fica para o proximo
        const linhas = (resto + decodificador.decode(value, { stream: true })).split('\n')
        resto = linhas.pop()
        for (const linha of linhas) {
          empilhar(linha)
          if (lote.length >= LOTE) {
            await gravar()
          }
        }
      }
      empilhar(resto + decodificador.decode())
      await gravar()
      await db[chave].where('versao').below(atual.versao).delete()
      this.versao[chave] = atual.versao
      this.ultimaSincronizacao[chave] = dataDaVersao(atual.versao)
      return true
    },

    async sincronizarImpressora() {
      if (!this.importacao.rodando) {
        return
      }

      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/impressora')

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
          message: error?.response?.data?.message ?? error.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async sincronizarFormaPagamento() {
      if (!this.importacao.rodando) {
        return
      }

      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/forma-pagamento')

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
          message: error?.response?.data?.message ?? error.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async sincronizarEstoqueLocal() {
      if (!this.importacao.rodando) {
        return
      }

      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/estoque-local')

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
          message: error?.response?.data?.message ?? error.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async silentSincronizarEstoqueLocal() {
      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/estoque-local')

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

      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/natureza-operacao')

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
          message: error?.response?.data?.message ?? error.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
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

      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/vale-modelo')

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
        return false
      }
    },

    async sincronizarPrancheta(forcar = false) {
      if (!this.importacao.rodando && !forcar) {
        return
      }

      let sincronizado = null

      try {
        // busca registros na ApI
        let { data } = await api.get('/v1/pdv/prancheta')

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
          message: error?.response?.data?.message ?? error.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async putNegocio(negocio) {
      const params = {
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
        const { data } = await api.get('/v1/pdv/negocio/' + codOrUuid)
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
        const { data } = await api.post('/v1/pdv/negocio/' + codnegocio + '/apropriar')
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

    // venda fechada reaberta pelo gerente (TASK-30), com o motivo (TASK-209)
    async reabrirNegocio(codnegocio, justificativa) {
      try {
        const { data } = await api.post('/v1/pdv/negocio/' + codnegocio + '/reabrir', {
          justificativa,
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
        var { data } = await api.post('/v1/pdv/pessoa', pessoa)
        await db.pessoa.bulkPut(this.carimbar('pessoa', data))
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
        var { data } = await api.get('/v1/pdv/pessoa/cnpj/' + cnpj)
        await db.pessoa.bulkPut(this.carimbar('pessoa', data))
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
