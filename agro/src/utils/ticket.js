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

export function imprimirTicket(t) {
  const numero = t.numero ? `Nº ${t.numero}` : 'Nº provisório'
  const itens = (t.itens || [])
    .map((p) => {
      const base = p.rotulo || '—'
      if (p.kg !== null && p.kg !== undefined) return `${base} — ${fmt(p.kg)} kg`
      return base
    })
    .join('<br>')

  const temClassificacao = [t.umidade, t.impureza, t.avariados].some(
    (v) => v !== null && v !== undefined,
  )
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
        ${linha('Umidade', fmt(t.umidade, 1) + ' %')}
        ${linha('Impureza', fmt(t.impureza, 1) + ' %')}
        ${linha('Avariados', fmt(t.avariados, 1) + ' %')}
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
