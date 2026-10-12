// Apoio ao motorista do pátio (com ou sem cadastro) — chamadas online usadas
// pelo modal de Operação e pelo cadastro de motorista.
import { api } from 'src/services/api'
import { removerAcentos } from '@components/formatters'

// "Nome completo" = ao menos nome e sobrenome.
export function nomeCompleto(nome) {
  return (nome || '').trim().split(/\s+/).filter(Boolean).length >= 2
}

// Pessoa FÍSICA com exatamente este CPF (só dígitos), ou null — inclusive
// INATIVA (vem com `inativo`): o cadastro recusa CPF repetido mesmo de inativo.
// O select de pessoa casa dígitos por substring; aqui só vale o CPF inteiro. O
// cnpj vem numérico do banco (perde zero à esquerda) — por isso o padStart.
export async function pessoaPorCpf(cpf) {
  const { data } = await api.get('v1/select/pessoa', {
    params: { busca: cpf, page: 1, inativos: 1 },
    skipLoading: true,
  })
  const rows = Array.isArray(data) ? data : data?.data || []
  return (
    rows.find(
      (p) => p.fisica && String(Math.round(Number(p.cnpj))).padStart(11, '0') === cpf,
    ) || null
  )
}

// Cadastra o motorista (pessoa física + celular + endereço) a partir dos
// campos *motorista da carga. Devolve { codpessoa, fantasia, pessoa, cnpj }.
export async function cadastrarMotorista(c) {
  const { data } = await api.post('v1/carga/motorista', {
    cpf: c.cpfmotorista,
    nome: c.motorista,
    telefone: c.telefonemotorista,
    cep: c.cepmotorista,
    endereco: c.enderecomotorista,
    bairro: c.bairromotorista,
    codcidade: c.codcidademotorista,
  })
  return data
}

const normaliza = (s) => removerAcentos((s || '').toLowerCase())

// Mesmo cuidado do CardEndereco (pessoas): o param TEM que ser `busca` e o
// resultado casa pelo label "cidade / UF" — sem match exato devolve null
// (campo vazio é melhor que cidade errada no cadastro).
async function codcidadePeloNome(localidade, uf) {
  if (!localidade || !uf) return null
  const { data } = await api.get('v1/select/cidade', {
    params: { busca: `${localidade} ${uf}`, page: 1 },
    skipLoading: true,
  })
  const alvo = normaliza(`${localidade} / ${uf}`)
  return (Array.isArray(data) ? data : []).find((c) => normaliza(c.label) === alvo)?.value ?? null
}

// Endereço pelo CEP (viacep), ou null se não achou. `fetch` puro, não o `api`:
// o interceptor do `api` mandaria o token de login pra um site de terceiro.
export async function enderecoPeloCep(cep) {
  const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`)
  const data = resp.ok ? await resp.json() : null
  if (!data || data.erro) return null
  return {
    endereco: data.logradouro || null,
    bairro: data.bairro || null,
    codcidade: await codcidadePeloNome(data.localidade, data.uf),
  }
}
