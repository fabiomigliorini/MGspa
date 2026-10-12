import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/boot/axios'
import { Notify } from 'quasar'
import { sincronizacaoStore } from 'stores/sincronizacao'

// Store do dominio "dispositivo" (PDV, tblpdv — TASK-46). Serve a lista com filtro na
// drawer e a pagina de cada dispositivo: dados, configuracao, cadastro e ultimos registros.
// Toda chamada leva o uuid deste navegador (`pdv`): o backend reconhece o proprio dispositivo.

const notificar = (type, message) =>
  Notify.create({
    type,
    message,
    timeout: 3000,
    actions: [{ icon: 'close', color: 'white' }],
  })

const mensagemErro = (error, fallback) => {
  const data = error?.response?.data
  const primeiro = data?.errors ? Object.values(data.errors).flat()[0] : null
  return primeiro || data?.message || error?.message || fallback
}

// status: '' = ativos (ativo = autorizado), 'inativo', 'todos'
const filtrosVazios = () => ({
  apelido: null,
  codfilial: null,
  codsetor: null,
  status: '',
  ip: null,
  uuid: null,
})

// os mesmos grupos do PdvService: o cadastro so' Administrador/Gerente altera. A filial nao vai:
// o backend usa a do local de estoque
const CAMPOS_CADASTRO = [
  'apelido',
  'codsetor',
  'codportador',
  'monitoramento',
  'minutosesquecido',
  'observacoes',
]
const CAMPOS_CONFIGURACAO = [
  'codestoquelocal',
  'codnaturezaoperacao',
  'impressora',
  'codmaquineta',
  'codportadorpix',
]

const registrosVazios = () => ({ negocios: [], pagamentos: [], ocorrencias: [], localizacoes: [] })

export const dispositivoStore = defineStore(
  'dispositivo',
  () => {
    const sSinc = sincronizacaoStore()

    const filtros = ref(filtrosVazios())
    const dispositivos = ref([])
    const carregando = ref(false)
    const erroLista = ref(null)

    const dispositivo = ref(null)
    const pode = ref({})
    const erro = ref(null)
    const registros = ref(registrosVazios())
    const salvando = ref(false)

    const filtrosAtivos = computed(
      () => Object.values(filtros.value).filter((v) => v !== null && v !== '').length,
    )

    const proprio = computed(
      () => !!dispositivo.value && dispositivo.value.codpdv === sSinc.pdv.codpdv,
    )

    function limparFiltros() {
      filtros.value = filtrosVazios()
    }

    // ---- lista ----
    async function carregar() {
      carregando.value = true
      erroLista.value = null
      try {
        const params = Object.fromEntries(
          Object.entries(filtros.value).filter(([, v]) => v !== null && v !== ''),
        )
        const { data } = await api.get('/v1/pdv/dispositivo', { params })
        dispositivos.value = data.data
      } catch (error) {
        dispositivos.value = []
        erroLista.value = mensagemErro(error, 'Falha ao carregar os dispositivos')
      } finally {
        carregando.value = false
      }
    }

    // ---- pagina do dispositivo ----
    async function buscar(codpdv) {
      dispositivo.value = null
      pode.value = {}
      erro.value = null
      try {
        const { data } = await api.get(`/v1/pdv/dispositivo/${codpdv}`, {
          params: { pdv: sSinc.pdv.uuid },
        })
        dispositivo.value = data.data
        pode.value = data.pode
        // o proprio: status, cadastro e configuracao locais em dia (o redirecionamento da
        // abertura olha o status local)
        if (data.data.codpdv === sSinc.pdv.codpdv) {
          sSinc.aplicarDispositivo(data.data)
        }
      } catch (error) {
        erro.value = mensagemErro(error, 'Falha ao carregar o dispositivo')
      }
    }

    async function carregarRegistros(codpdv) {
      registros.value = registrosVazios()
      try {
        const { data } = await api.get(`/v1/pdv/dispositivo/${codpdv}/registros`, {
          params: { pdv: sSinc.pdv.uuid },
        })
        registros.value = data
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Falha ao carregar os registros'))
      }
    }

    // a resposta das acoes e' o dispositivo; as permissoes da tela continuam as mesmas
    function atualizar(pdv) {
      dispositivo.value = pdv
      const i = dispositivos.value.findIndex((d) => d.codpdv === pdv.codpdv)
      if (i >= 0) {
        dispositivos.value[i] = pdv
      }
      // o proprio PDV passa a usar na hora o que mudou
      if (pdv.codpdv === sSinc.pdv.codpdv) {
        sSinc.aplicarDispositivo(pdv)
      }
    }

    async function acao(metodo, url, sucesso) {
      if (salvando.value) return false
      salvando.value = true
      try {
        const { data } = await api[metodo](url)
        atualizar(data.data)
        notificar('positive', sucesso)
        return true
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Falha ao alterar o dispositivo'))
        return false
      } finally {
        salvando.value = false
      }
    }

    // ativar e' autorizar: o servidor exige apelido, filial, local de estoque, setor e natureza
    const ativar = (pdv) =>
      acao('delete', `/v1/pdv/dispositivo/${pdv.codpdv}/inativo`, 'Dispositivo ativado!')

    const inativar = (pdv) =>
      acao('post', `/v1/pdv/dispositivo/${pdv.codpdv}/inativo`, 'Dispositivo inativado!')

    // o proprio PDV so' manda a configuracao: o servidor recusa o cadastro de quem nao pode
    async function salvar(model) {
      if (salvando.value) return false
      salvando.value = true
      const campos = pode.value.cadastro
        ? [...CAMPOS_CADASTRO, ...CAMPOS_CONFIGURACAO]
        : CAMPOS_CONFIGURACAO
      try {
        const { data } = await api.put(`/v1/pdv/dispositivo/${model.codpdv}`, {
          pdv: sSinc.pdv.uuid,
          ...Object.fromEntries(campos.map((c) => [c, model[c] ?? null])),
        })
        // salvo pela tela, a configuracao do servidor vale: a 1a sincronizacao nao manda mais o
        // legado do navegador (que preencheria o que o usuario deixou vazio de proposito)
        if (data.data.codpdv === sSinc.pdv.codpdv) {
          sSinc.configuracaoMigrada = true
        }
        atualizar(data.data)
        notificar('positive', 'Dispositivo salvo!')
        return true
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Falha ao salvar o dispositivo'))
        return false
      } finally {
        salvando.value = false
      }
    }

    return {
      filtros,
      dispositivos,
      carregando,
      erroLista,
      dispositivo,
      pode,
      erro,
      registros,
      salvando,
      filtrosAtivos,
      proprio,
      limparFiltros,
      carregar,
      buscar,
      carregarRegistros,
      ativar,
      inativar,
      salvar,
    }
  },
  {
    persist: { pick: ['filtros'] },
  },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(dispositivoStore, import.meta.hot))
}
