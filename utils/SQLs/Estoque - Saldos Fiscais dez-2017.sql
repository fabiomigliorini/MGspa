    select
	p.codproduto
	, p.produto
	, p.inativo
	, p.preco
	, fiscal.saldoquantidade as quant
	, fiscal.customedio as custo
	, fiscal.saldovalor as valor
	, p.preco / case when fiscal.customedio != 0 then fiscal.customedio else null end as markup
	--, fisico.saldovalor as fisico_saldovalor
	--, fisico.customedio as fisico_customedio
	--, m.codmarca
	--, m.marca
	--, sp.codsecaoproduto
	--, sp.secaoproduto
	--, fp.codfamiliaproduto
	--, fp.familiaproduto
	--, gp.codgrupoproduto
	--, gp.grupoproduto
	--, sgp.codsubgrupoproduto
	--, sgp.subgrupoproduto
    from tblproduto p
    left join tblmarca m on (m.codmarca = p.codmarca)
    left join tblsubgrupoproduto sgp on (sgp.codsubgrupoproduto = p.codsubgrupoproduto)
    left join tblgrupoproduto gp on (gp.codgrupoproduto = sgp.codgrupoproduto)
    left join tblfamiliaproduto fp on (fp.codfamiliaproduto = gp.codfamiliaproduto)
    left join tblsecaoproduto sp on (sp.codsecaoproduto = fp.codsecaoproduto)
    left join (
	select 
		pv.codproduto
		, sum(em.saldoquantidade) as saldoquantidade
		, sum(em.saldovalor) as saldovalor
		, sum(em.saldovalor) / case when sum(em.saldoquantidade) !=0 then sum(em.saldoquantidade) else null end as customedio
	from tblestoquelocalprodutovariacao elpv
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
	inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
	inner join tblfilial f on (f.codfilial = el.codfilial)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = true)
	inner join tblestoquemes em on (em.codestoquemes = (select em2.codestoquemes from tblestoquemes em2 where em2.codestoquesaldo = es.codestoquesaldo and em2.mes <= '2017-12-31' order by mes desc limit 1))
	where f.codfilial = 101
      group by pv.codproduto
	) fiscal on (fiscal.codproduto = p.codproduto)
    left join tblncm n on (n.codncm = p.codncm)
    where p.codtipoproduto = 0
    --and n.ncm ilike '4901%'
    --and p.produto ilike '%tabua%cozin%'
    --AND p.preco >= 70
    --AND p.preco <= 130
    --and p.inativo is not null
    --AND m.codmarca = {$filtro['codmarca']}
    --AND m.controlada = true
    --AND p.codncm = {$filtro['codncm']}
    --AND p.preco <= {$filtro['preco_ate']}
    --AND p.produto ilike '%{$palavra}%'
    --AND p.codsubgrupoproduto = {$filtro['codsubgrupoproduto']}
    --AND sgp.codgrupoproduto = {$filtro['codgrupoproduto']}
    --AND gp.codfamiliaproduto = {$filtro['codfamiliaproduto']}
    --AND fp.codsecaoproduto = {$filtro['codsecaoproduto']}
    --AND fisico.saldoquantidade < 0
    --AND fisico.saldoquantidade > 0
    --AND fiscal.saldoquantidade < 0
    AND fiscal.saldoquantidade != 0
    --and p.codproduto = 3788
    order by 7 desc, p.produto, p.codproduto
    --limit 200