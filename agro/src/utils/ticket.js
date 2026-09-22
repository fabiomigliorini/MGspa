// Ticket de balança (romaneio) — impressão client-side, funciona OFFLINE: monta
// um HTML autocontido e abre numa janela que imprime sozinha. O número oficial é
// o codcarga (após sync); offline sai "provisório".
//
// Nomenclatura: pbt = caminhão+carga; tara = caminhão vazio; bruto = pbt - tara
// (grão); desconto = classificação; liquido = bruto - desconto.

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

function dataHora(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleString('pt-BR')
}

function linha(rotulo, valor) {
  return `<tr><td class="r">${rotulo}</td><td class="v">${valor}</td></tr>`
}

const TITULO_POR_SENTIDO = {
  SAIDA: 'ROMANEIO DE EXPEDIÇÃO',
  TRANSFERENCIA: 'ROMANEIO DE TRANSFERÊNCIA',
  ENTRADA: 'ROMANEIO DE RECEBIMENTO',
}

const ASSINATURAS_POR_SENTIDO = {
  SAIDA: ['Conferente', 'Motorista', 'Expedidor'],
}
const ASSINATURAS_PADRAO = ['Classificador', 'Motorista', 'Recebedor']

// Monta o objeto do ticket a partir do payload do SERVIDOR (CargaResource com
// CargaService::WITH). O pátio tem a sua própria montagem, no CargaForm, porque
// lá os dados vêm do Dexie e o rótulo/fazenda são resolvidos pelas caches
// locais — aqui tudo já vem mastigado do backend.
//
// As duas montagens precisam gerar o MESMO ticket; se mexer numa, conferir a
// outra lado a lado na mesma carga.
export function ticketDoServidor(c) {
  const pontos = c.CargaPontoS || []
  const itensFonte = pontos.filter((p) => p.papel === (c.sentido === 'SAIDA' ? 'DESTINO' : 'ORIGEM'))

  // CUIDADO com o casing: `Safra`, `Veiculo` e `Plantio` são PascalCase porque
  // os Resources os expõem na mão; as relações DENTRO deles (`cultura`,
  // `fazenda`) são serializadas pelo Eloquent, que usa snake_case.
  const cultura = c.Safra?.cultura || null
  const pesosaca = Number(cultura?.pesosaca) || 60

  // Nome da fazenda sai do primeiro talhão de origem. Sem talhão — expedição,
  // transferência — cai no genérico, igual ao pátio.
  const fazenda =
    pontos.find((p) => p.contatipo === 'PLANTIO' && p.Plantio?.fazenda?.fazenda)?.Plantio?.fazenda
      ?.fazenda || 'MG Agro'

  return {
    titulo: TITULO_POR_SENTIDO[c.sentido] || TITULO_POR_SENTIDO.ENTRADA,
    rotuloItens: c.sentido === 'SAIDA' ? 'Destinos' : 'Origens',
    assinaturas: ASSINATURAS_POR_SENTIDO[c.sentido] || ASSINATURAS_PADRAO,
    numero: c.codcarga,
    data: c.data,
    fazenda,
    cultura: cultura?.cultura,
    safra: c.Safra?.safra,
    placa: c.placa,
    placacarreta: c.placacarreta,
    veiculo: c.Veiculo?.veiculo || null,
    motorista: c.motorista,
    // `liquido` do ponto é o rateio já gravado pelo servidor — no pátio ele é
    // derivado do % na hora, aqui já veio calculado.
    itens: itensFonte.map((p) => ({ rotulo: p.rotulo, kg: p.liquido })),
    pbt: c.pbt,
    tara: c.tara,
    bruto: c.bruto,
    classificacao: (c.classificacao || [])
      .filter((l) => l.leitura !== null && l.leitura !== undefined && l.leitura !== '')
      .map((l) => ({
        nome: l.ParametroClassificacao?.parametroclassificacao || `#${l.codparametroclassificacao}`,
        leitura: l.leitura,
        desconto: l.desconto,
      })),
    desconto: c.desconto,
    liquido: c.liquido,
    sacas: c.liquido != null ? Number(c.liquido) / pesosaca : null,
    pesosaca,
  }
}

export function imprimirTicket(t) {
  const numero = t.numero ? `Nº ${t.numero}` : 'Nº provisório'
  const itens = (t.itens || [])
    .map((p) => {
      const base = p.rotulo || '—'
      if (p.kg !== null && p.kg !== undefined) return `${base} — ${fmt(p.kg)} kg`
      return base
    })
    .join('<br>')

  const temClassificacao = (t.classificacao || []).length > 0
  const assinaturas = t.assinaturas || ['Classificador', 'Motorista', 'Recebedor']

  const corpo = `
    <div class="tk">
      <div class="cab">
        <div class="faz">${t.fazenda || 'MG Agro'}</div>
        <div class="tit">${t.titulo || 'ROMANEIO'}</div>
        <div class="num">${numero} &middot; ${dataHora(t.data)}</div>
      </div>
      <table>
        ${linha('Placa', t.placa || '—')}
        ${t.placacarreta ? linha('Carreta', t.placacarreta) : ''}
        ${t.veiculo ? linha('Caminhão', t.veiculo) : ''}
        ${linha('Motorista', t.motorista || '—')}
        ${t.cultura ? linha('Cultura / Safra', `${t.cultura} — ${t.safra || '—'}`) : ''}
        ${linha(t.rotuloItens || 'Itens', itens || '—')}
      </table>
      <div class="sep"></div>
      <table>
        ${linha('Peso bruto total', fmt(t.pbt) + ' kg')}
        ${linha('Tara', fmt(t.tara) + ' kg')}
        ${linha('Bruto (carga)', fmt(t.bruto) + ' kg')}
      </table>
      ${
        temClassificacao
          ? `<div class="sep"></div>
      <table>
        ${(t.classificacao || []).map((c) => linha(c.nome, fmt(c.leitura, 1) + ' %')).join('')}
        ${linha('Desconto', fmt(t.desconto) + ' kg')}
      </table>`
          : ''
      }
      <div class="sep"></div>
      <table>
        ${linha('<b>LÍQUIDO</b>', `<b>${fmt(t.liquido)} kg</b>`)}
        ${linha('<b>Sacas (' + fmt(t.pesosaca) + 'kg)</b>', `<b>${fmt(t.sacas, 1)} sc</b>`)}
      </table>
      <div class="ass">
        ${assinaturas.map((a) => `<div class="a">${a}</div>`).join('')}
      </div>
    </div>`

  const html = `<!doctype html><html lang="pt-br"><head><meta charset="utf-8">
    <title>Ticket ${numero}</title>
    <style>
      * { box-sizing: border-box; }
      body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; }
      .tk { width: 280px; margin: 8px auto; font-size: 12px; }
      .cab { text-align: center; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 6px; }
      .faz { font-weight: bold; font-size: 14px; }
      .tit { font-size: 12px; letter-spacing: 1px; }
      .num { font-size: 11px; color: #333; }
      table { width: 100%; border-collapse: collapse; }
      td { padding: 2px 0; vertical-align: top; }
      td.r { color: #444; }
      td.v { text-align: right; font-weight: 500; }
      .sep { border-top: 1px dashed #999; margin: 6px 0; }
      .ass { display: flex; justify-content: space-between; margin-top: 28px; gap: 6px; }
      .ass .a { flex: 1; border-top: 1px solid #000; text-align: center; font-size: 10px; padding-top: 3px; }
      @media print { @page { margin: 4mm; } }
    </style></head>
    <body>${corpo}
    <script>window.onload=function(){window.print();window.onafterprint=function(){window.close()}}</script>
    </body></html>`

  const win = window.open('', '_blank', 'width=360,height=640')
  if (!win) return false
  win.document.open()
  win.document.write(html)
  win.document.close()
  return true
}
