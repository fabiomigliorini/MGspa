---
id: TASK-181
title: >-
  Pátio deixa lançar grão no contrato ou talhão errado (milho em contrato de
  soja)
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-28 21:11'
updated_date: '2026-11-05 19:51'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O pátio aceita contrato de outra cultura, talhão de outra safra, contrato de venda como origem, transferência de um silo para ele mesmo e tara maior que o PBT: o servidor só confere que os códigos existem (CargaSincronizarRequest, regras exists). Plano: specs/PLANO-AGRO-ARMAZENAMENTO.md (Fase 4), com o diagnóstico SQL no dev e na PROD antes de ligar as regras. Acréscimos de /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 5): unidade ou contrato inativo recusado como ponto novo; contrato de outra safra da mesma cultura decidido no diagnóstico.

**Estado no código em 22/10/2026 (leitura):** a descrição acima é anterior à TASK-180. Hoje o servidor já recusa tara maior que o PBT, PBT acima de 150 t e leitura fora de 0–100 (CargaSincronizarRequest), então o #4 está pronto (o servidor ainda aceita tara igual ao PBT; a tela exige menor). #2 e #5 existem só na tela: o pátio não oferece talhão de outra safra nem silo/contrato inativo, mas o servidor só confere `exists`. #1 e #3 não existem no servidor (não há `validarPontos` nem `PONTOS_POR_SENTIDO`); o SelectContrato não filtra por cultura nem operação, e a transferência não esconde no destino o silo da origem.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Contrato de outra cultura é recusado, e o select só oferece contratos da cultura da safra
- [x] #2 Talhão de outra safra é recusado como origem
- [x] #3 Origem e destino seguem as combinações permitidas por sentido (D2)
- [x] #4 Tara maior que o PBT é recusada com mensagem clara
- [x] #5 Unidade ou contrato inativo é recusado no servidor (a tela já só oferece silo e contrato ativos)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
05/11/2026 — CargaService::validarPontos em toda gravação: tipo de ponto pela matriz D2 (PONTOS_POR_SENTIDO, espelhado em agro utils/carga.js), talhão e contrato da safra da carga (a safra define a cultura), compra só na origem e venda só no destino, mesmo silo nos dois lados recusado, silo/contrato inativo recusado só como ponto novo. Tela: SelectContaTipo filtra pelo sentido, SelectContrato pela safra e operação, SelectUnidade esconde o silo do outro lado; os três passaram para MgSelect. Testado no dev (rollback): milho em talhão de soja, contrato de milho em carga de soja, silo→mesmo silo, expedição saindo de talhão e contrato de venda como origem → todos 422 com mensagem; milho em talhão de milho aceita. Cargas antigas do dev fora da matriz (não editáveis sem corrigir o ponto): 1, 4 e 21706 (recebimento com origem silo) e 8 (expedição talhão→silo).

05/11/2026 — fechamento. Diagnóstico completo no dev (inclui transferência e mesmo silo nos dois lados): as cargas 1, 4, 6, 8, 21499 e 21706 ficam fora das regras e só voltam a ser salvas com a origem/destino corrigidos (o dev tem um silo só, então toda transferência lá é silo → mesmo silo). Na PROD, rodar à mão para saber quais cargas antigas vão recusar edição:

-- Cargas ativas que as regras da TASK-181 passam a recusar se forem salvas de novo
select c.codcarga, c.sentido, c.etapa, p.papel, p.contatipo, c.codsafra,
       coalesce(pl.codsafra, ct.codsafra) as safra_ponto, ct.operacao
from tblcarga c
join tblcargaponto p using (codcarga)
left join tblplantio pl on pl.codplantio = p.codplantio
left join tblcontrato ct on ct.codcontrato = p.codcontrato
where c.inativo is null and (
     (c.sentido = 'ENTRADA' and p.papel = 'ORIGEM' and p.contatipo not in ('PLANTIO', 'CONTRATO'))
  or (c.sentido = 'ENTRADA' and p.papel = 'DESTINO' and p.contatipo not in ('UNIDADE', 'CONTRATO'))
  or (c.sentido in ('SAIDA', 'TRANSFERENCIA') and p.papel = 'ORIGEM' and p.contatipo <> 'UNIDADE')
  or (c.sentido = 'SAIDA' and p.papel = 'DESTINO' and p.contatipo <> 'CONTRATO')
  or (c.sentido = 'TRANSFERENCIA' and p.papel = 'DESTINO' and p.contatipo <> 'UNIDADE')
  or coalesce(pl.codsafra, ct.codsafra) <> c.codsafra
  or (p.contatipo = 'CONTRATO' and ct.operacao <> case p.papel when 'ORIGEM' then 'COMPRA' else 'VENDA' end)
  or (p.contatipo = 'UNIDADE' and exists (
        select 1 from tblcargaponto o
        where o.codcarga = c.codcarga and o.papel <> p.papel
          and o.contatipo = 'UNIDADE' and o.codunidadearmazenadora = p.codunidadearmazenadora))
)
order by 1, 4;
<!-- SECTION:NOTES:END -->
