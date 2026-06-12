// Ticket de balança (romaneio de recebimento) — impressão client-side, funciona
// OFFLINE: monta um HTML autocontido e abre numa janela que imprime sozinha.
// O número oficial é o codcargacolheita (após sync); offline sai "provisório".

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

function dataHora(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleString('pt-BR')
}

function linha(rotulo, valor) {
  return `<tr><td class="r">${rotulo}</td><td class="v">${valor}</td></tr>`
}

export function imprimirTicket(t) {
  const numero = t.numero ? `Nº ${t.numero}` : 'Nº provisório'
  const itens = (t.talhoes || [])
    .map((p) => {
      const base = p.rotulo || '—'
      if (p.percentual !== null && p.percentual !== undefined) return `${base} (${fmt(p.percentual)}%)`
      if (p.sc !== null && p.sc !== undefined) return `${base} — ${fmt(p.sc)} sc`
      return base
    })
    .join('<br>')

  const corpo = `
    <div class="tk">
      <div class="cab">
        <div class="faz">${t.fazenda || 'MG Agro'}</div>
        <div class="tit">${t.titulo || 'TICKET DE RECEBIMENTO'}</div>
        <div class="num">${numero} &middot; ${dataHora(t.data)}</div>
      </div>
      <table>
        ${linha('Placa', t.placa || '—')}
        ${t.veiculo ? linha('Caminhão', t.veiculo) : ''}
        ${t.renavam ? linha('Renavam', t.renavam) : ''}
        ${linha('Motorista', t.motorista || '—')}
        ${t.cultura ? linha('Cultura / Safra', `${t.cultura} — ${t.safra || '—'}`) : ''}
        ${linha(t.rotuloItens || 'Talhões', itens || '—')}
      </table>
      <div class="sep"></div>
      <table>
        ${linha('Peso bruto', fmt(t.pesobruto) + ' kg')}
        ${linha('Tara', fmt(t.tara) + ' kg')}
        ${linha('Peso líquido', fmt(t.pesoliquido) + ' kg')}
      </table>
      <div class="sep"></div>
      <table>
        ${linha('Umidade', fmt(t.umidade, 1) + ' %')}
        ${linha('Impureza', fmt(t.impureza, 1) + ' %')}
        ${linha('Avariados', fmt(t.avariados, 1) + ' %')}
        ${linha('Desconto umidade', fmt(t.descontoumidade) + ' kg')}
        ${linha('Desconto impureza', fmt(t.descontoimpureza) + ' kg')}
        ${linha('Desconto avariados', fmt(t.descontoavariados) + ' kg')}
      </table>
      <div class="sep"></div>
      <table>
        ${linha('<b>LÍQUIDO SECO</b>', `<b>${fmt(t.pesoliquidoseco)} kg</b>`)}
        ${linha('<b>Sacas (' + fmt(t.pesosaca) + 'kg)</b>', `<b>${fmt(t.sacas, 1)} sc</b>`)}
      </table>
      <div class="ass">
        <div class="a">Classificador</div>
        <div class="a">Motorista</div>
        <div class="a">Recebedor</div>
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
