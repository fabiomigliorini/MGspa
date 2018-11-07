-- Function: fnatualizaestoqueminimomaximo()

DROP FUNCTION fnatualizaestoqueminimomaximo();

CREATE OR REPLACE FUNCTION fnatualizaestoqueminimomaximo()
  RETURNS TABLE(atualizados bigint) AS
$BODY$
DECLARE
    v_data_atualizacao timestamp := date_trunc('second', now());
    v_corte_ano timestamp := date_trunc('month', now()) - interval '12 months';
    v_corte_semestre timestamp := date_trunc('month', now()) - interval '6 months';
    v_corte_bimestre timestamp := date_trunc('month', now()) - interval '2 month';
    v_registro record;
    v_alterado record;
    v_meses_ignorar int[];
    v_quantidade_ignorar int;
BEGIN  

	raise notice 'Atualização %s, Ano %s, Semestre %s, Bimestre %s', v_data_atualizacao, v_corte_ano, v_corte_semestre, v_corte_bimestre;
	
	-- CRIA COMBINACOES DE ELPV QUE AINDA NAO FORAM CRIADAS
	raise notice 'Criando Combinacoes ELPV';
	insert into tblestoquelocalprodutovariacao (codestoquelocal, codprodutovariacao) 
	select el.codestoquelocal, pv.codprodutovariacao
	from tblproduto p
	inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
	inner join tblestoquelocal el on (el.inativo is null)
	left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = el.codestoquelocal and elpv.codprodutovariacao = pv.codprodutovariacao)
	where p.inativo is null
	and elpv.codestoquelocalprodutovariacao is null;

	-- CALCULA DATA INICIAL DAS VENDAS DE CADA PRODUTO
	raise notice 'Atualizando Data Inicial de Venda dos Produtos';
	create temporary table tmpvendainicio as 
	select pb.codprodutovariacao, min(n.lancamento) as vendainicio
	from tblnegocio n
	inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
	where n.codnegociostatus = 2
	--and pb.codproduto = 21
	group by pb.codprodutovariacao;

	update tmpvendainicio
	set vendainicio = '2012-01-01'::date
	where vendainicio < '2012-01-01'::date;

	update tblprodutovariacao 
	set vendainicio = tmpvendainicio.vendainicio
	from tmpvendainicio
	where tblprodutovariacao.codprodutovariacao = tmpvendainicio.codprodutovariacao
	--and tblprodutovariacao = 21
	;

	update tblprodutovariacao 
	set vendainicio = now()
	where tblprodutovariacao.codprodutovariacao not in (select codprodutovariacao from tmpvendainicio)
	--and tblprodutovariacao = 21
	;

	drop table if exists tmpvendainicio;

	-- SUMARIZA VENDA MENSAL
	raise notice 'Sumarizando Venda Mensal';
	drop table if exists tmpestoquelocalprodutovariacaovenda ;

	create temporary table tmpestoquelocalprodutovariacaovenda as
	select
	    tblprodutobarra.codprodutovariacao
	    , tblnegocio.codestoquelocal
	    , date_trunc('month', tblnegocio.lancamento) as mes
	    , sum(tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as quantidade
	    , sum(tblnegocioprodutobarra.valortotal * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as valor
	    , cast(null as bigint) as codestoquelocalprodutovariacao
	from tblnegocio
	inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
	inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
	inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
	left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
	where tblnegocio.codnegociostatus = 2 --Fechado
	and (tblnaturezaoperacao.venda = true or tblnaturezaoperacao.vendadevolucao = true)
	--and tblprodutobarra.codproduto in (select tblproduto.codproduto from tblproduto where tblproduto.codmarca = 29) -- ACRILEX
	--and tblprodutobarra.codproduto = 555
	group by
	     tblprodutobarra.codprodutovariacao
	    , tblnegocio.codestoquelocal
	    , date_trunc('month', tblnegocio.lancamento);

	-- Atualiza codestoquelocalprodutovariacao
	raise notice 'Gravando Venda Mensal';
	update tmpestoquelocalprodutovariacaovenda
	set codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
	from tblestoquelocalprodutovariacao elpv
	where tmpestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao is null
	and tmpestoquelocalprodutovariacaovenda.codestoquelocal = elpv.codestoquelocal
	and tmpestoquelocalprodutovariacaovenda.codprodutovariacao = elpv.codprodutovariacao;

	insert into tblestoquelocalprodutovariacao (codestoquelocal, codprodutovariacao, criacao, alteracao)
	select distinct tmp.codestoquelocal, tmp.codprodutovariacao, v_data_atualizacao, v_data_atualizacao
	from tmpestoquelocalprodutovariacaovenda tmp
	where tmp.codestoquelocalprodutovariacao is null;

	-- rodar novamente 0 -- Atualiza codestoquelocalprodutovariacao
	-- Atualiza codestoquelocalprodutovariacao
	-- DUPLICADO DE PROPOSITO
	update tmpestoquelocalprodutovariacaovenda
	set codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
	from tblestoquelocalprodutovariacao elpv
	where tmpestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao is null
	and tmpestoquelocalprodutovariacaovenda.codestoquelocal = elpv.codestoquelocal
	and tmpestoquelocalprodutovariacaovenda.codprodutovariacao = elpv.codprodutovariacao;

	update tblestoquelocalprodutovariacaovenda
	set quantidade = tmp.quantidade
	, valor = tmp.valor
	, alteracao = v_data_atualizacao
	from tmpestoquelocalprodutovariacaovenda tmp
	where tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao = tmp.codestoquelocalprodutovariacao
	and tblestoquelocalprodutovariacaovenda.mes = tmp.mes;

	insert into tblestoquelocalprodutovariacaovenda (codestoquelocalprodutovariacao, mes, quantidade, valor, criacao, alteracao)
	select tmp.codestoquelocalprodutovariacao, tmp.mes, tmp.quantidade, tmp.valor, v_data_atualizacao, v_data_atualizacao
	from tmpestoquelocalprodutovariacaovenda tmp
	left join tblestoquelocalprodutovariacaovenda venda on (venda.codestoquelocalprodutovariacao = tmp.codestoquelocalprodutovariacao and venda.mes = tmp.mes)
	where venda.codestoquelocalprodutovariacaovenda is null;

	delete from tblestoquelocalprodutovariacaovenda
	where coalesce(alteracao, '2000-01-01') < v_data_atualizacao;

	drop table tmpestoquelocalprodutovariacaovenda;

	-- SELECIONA PARA CADA ELPV QUAIS MESES VAI CONSIDERAR NO CALCULO DA MEDIA DE VENDAS
	raise notice 'Percorrendo Combinacoes de EstoqueLocalProdutoVariacao Para decidir quais meses ficam no calculo';
	FOR v_registro IN (select codestoquelocalprodutovariacao from tblestoquelocalprodutovariacao) -- where codestoquelocalprodutovariacao < 10000) -- where codestoquelocalprodutovariacao = 71182)
	LOOP

		-- CONSIDERA 12 ULTIMOS MESES
		with alterados as (
		    update tblestoquelocalprodutovariacaovenda
		    set ignorar = false
		    where tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao = v_registro.codestoquelocalprodutovariacao
		    and tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacaovenda in (
			select v.codestoquelocalprodutovariacaovenda
			from tblestoquelocalprodutovariacaovenda v
			where v.codestoquelocalprodutovariacao = tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao
			and v.mes >= v_corte_ano
			order by v.mes desc
			limit 12
		    )
		    RETURNING tblestoquelocalprodutovariacaovenda.mes 
		) select min(mes) as mes, count(*) as quantidade into v_alterado from alterados
		;

		-- DESCONSIDERA PRA TRAS DOS 12 MESES
		update tblestoquelocalprodutovariacaovenda
		set ignorar = true
		where tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao = v_registro.codestoquelocalprodutovariacao
		and ignorar = false
		and mes < coalesce(v_alterado.mes, v_corte_ano::date);

		--RAISE NOTICE '% % %', v_registro.codestoquelocalprodutovariacao, v_alterado.mes, v_alterado.quantidade;

		-- IGNORA MES COM MENOS VENDAS
		IF (v_alterado.quantidade > 5) THEN

		    -- IGNORA DOIS MESES
		    IF (v_alterado.quantidade > 5) THEN
			v_quantidade_ignorar := 2;
		    ELSE
			v_quantidade_ignorar := 1;
		    END IF;

		    UPDATE tblestoquelocalprodutovariacaovenda
		    SET ignorar = TRUE
		    WHERE tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacaovenda IN (
			SELECT v2.codestoquelocalprodutovariacaovenda 
			FROM tblestoquelocalprodutovariacaovenda v2
			WHERE v2.codestoquelocalprodutovariacao = v_registro.codestoquelocalprodutovariacao
			AND coalesce(v2.ignorar, false) = FALSE
			ORDER BY v2.quantidade ASC NULLS FIRST, mes ASC
			LIMIT v_quantidade_ignorar
		    );

		    v_alterado.quantidade := v_alterado.quantidade - v_quantidade_ignorar;

		    --RAISE NOTICE 'Ignorando pouca venda, nova quantidade = %', v_alterado.quantidade;
		    
		END IF;

		-- IGNORA MESES COM MAIS VENDAS
		IF (v_alterado.quantidade > 5) THEN

		    -- IGNORA JANEIRO E FEVEREIRO
		    IF (v_alterado.quantidade > 5) THEN
			v_meses_ignorar := ARRAY [1, 2];
			v_quantidade_ignorar := 2;
		    -- IGNORA JANEIRO 
		    ELSE
			v_meses_ignorar := ARRAY [2];
			v_quantidade_ignorar := 1;
		    END IF;

		    UPDATE tblestoquelocalprodutovariacaovenda
		    SET ignorar = TRUE
		    WHERE tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacaovenda IN (
			SELECT v2.codestoquelocalprodutovariacaovenda 
			FROM tblestoquelocalprodutovariacaovenda v2
			WHERE v2.codestoquelocalprodutovariacao = v_registro.codestoquelocalprodutovariacao
			AND coalesce(v2.ignorar, false) = FALSE
			AND date_part('month', v2.mes) = ANY(v_meses_ignorar)
			ORDER BY v2.quantidade DESC NULLS FIRST, mes ASC
			LIMIT v_quantidade_ignorar
		    );
		    
		    v_alterado.quantidade := v_alterado.quantidade - v_quantidade_ignorar;
		    
		    --RAISE NOTICE 'Ignorando jan/fev (%), nova quantidade = %', v_quantidade_ignorar, v_alterado.quantidade;
		    
		END IF;

		-- IGNORA MES ATUAL
		IF (v_alterado.quantidade > 5) THEN

		    UPDATE tblestoquelocalprodutovariacaovenda
		    SET ignorar = TRUE
		    WHERE tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacaovenda IN (
			SELECT v2.codestoquelocalprodutovariacaovenda 
			FROM tblestoquelocalprodutovariacaovenda v2
			WHERE v2.codestoquelocalprodutovariacao = v_registro.codestoquelocalprodutovariacao
			AND coalesce(v2.ignorar, false) = FALSE
			AND v2.mes = DATE_TRUNC('month', now())
		    );

		END IF;

	END LOOP;

	-- cria tabela temporaria com numero de dias dos meses
	raise notice 'Calculando numero de dias de cada mes';
	drop table if exists tmpdiasmes;

	create temporary table tmpdiasmes as 
	select distinct mes, cast(null as smallint) as dias
	from tblestoquelocalprodutovariacaovenda;

	create index idx_tmpdiasmes_mes on tmpdiasmes (mes);

	update tmpdiasmes
	set dias = DATE_PART('days', DATE_TRUNC('month', mes) + '1 MONTH'::INTERVAL - '1 DAY'::INTERVAL)
	--where mes != date_trunc('month', now())
	;

	--update tmpdiasmes 
	--set dias = DATE_PART('days', now())
	--where mes = date_trunc('month', now());

	-- atualiza quantidade vendida por dia 
	raise notice 'Atualizando media de venda diaria';
	update tblestoquelocalprodutovariacaovenda
	set vendadiaquantidade = coalesce(quantidade, 0) / coalesce(dias, 30)
	from tmpdiasmes
	where tblestoquelocalprodutovariacaovenda.mes = tmpdiasmes.mes
	and coalesce(vendadiaquantidade, 0) != coalesce(quantidade, 0) / coalesce(dias, 30);

	-- Soma vendas do local para a variacao
	raise notice 'Somando Vendas de cada ELPV';
	update tblestoquelocalprodutovariacao
	set vendadiaquantidadeprevisao = iq.vendadiaquantidadeprevisao,
		vendaultimocalculo = v_data_atualizacao,
		vendabimestrequantidade = iq.vendabimestrequantidade,
		vendabimestrevalor = iq.vendabimestrevalor,
		vendasemestrequantidade = iq.vendasemestrequantidade,
		vendasemestrevalor = iq.vendasemestrevalor,
		vendaanoquantidade = iq.vendaanoquantidade,
		vendaanovalor = iq.vendaanovalor
	from (
		select 
			v.codestoquelocalprodutovariacao,
			avg(case when not v.ignorar then vendadiaquantidade else null end) as vendadiaquantidadeprevisao,
			--min(v.alteracao) as vendaultimocalculo,
			sum(case when v.mes >= v_corte_ano then v.quantidade else null end) as vendaanoquantidade,
			sum(case when v.mes >= v_corte_ano then v.valor else null end) as vendaanovalor,
			sum(case when v.mes >= v_corte_semestre then v.quantidade else null end) as vendasemestrequantidade,
			sum(case when v.mes >= v_corte_semestre then v.valor else null end) as vendasemestrevalor,
			sum(case when v.mes >= v_corte_bimestre then v.quantidade else null end) as vendabimestrequantidade,
			sum(case when v.mes >= v_corte_bimestre then v.valor else null end) as vendabimestrevalor
		from tblestoquelocalprodutovariacaovenda v 
		where (coalesce(v.ignorar, false) = false or v.mes >= v_corte_ano)
		group by v.codestoquelocalprodutovariacao
		) iq
	where tblestoquelocalprodutovariacao.codestoquelocalprodutovariacao = iq.codestoquelocalprodutovariacao
	and (
	    coalesce(tblestoquelocalprodutovariacao.vendaanovalor, 0) != coalesce(iq.vendaanovalor, 0)
	    OR coalesce(tblestoquelocalprodutovariacao.vendadiaquantidadeprevisao, 0) != coalesce(iq.vendadiaquantidadeprevisao, 0)
	);

	-- Limpa locais sem vendas no periodo
	update tblestoquelocalprodutovariacao
	set vendadiaquantidadeprevisao = null,
		vendaultimocalculo = v_data_atualizacao,
		vendabimestrequantidade = null,
		vendabimestrevalor = null,
		vendasemestrequantidade = null,
		vendasemestrevalor = null,
		vendaanoquantidade = null,
		vendaanovalor = null
	where coalesce(vendaultimocalculo, '2000-01-01') < v_data_atualizacao;

	-- Calcula Estoque Minimo e Estoque Maximo Filiais
	raise notice 'Calculando Estoque Minimo e Maximo das Filiais';
	update tblestoquelocalprodutovariacao
	set estoqueminimo = ceil(coalesce(tblestoquelocalprodutovariacao.vendadiaquantidadeprevisao, 0) * 20) --coalesce(m.estoqueminimodias, 0))
	, estoquemaximo = ceil(coalesce(tblestoquelocalprodutovariacao.vendadiaquantidadeprevisao, 0) * 40) --coalesce(m.estoquemaximodias, 0))
	from tblestoquelocal el, tblprodutovariacao pv
	inner join tblproduto p on (p.codproduto = pv.codproduto)
	--inner join tblmarca m on (m.codmarca = p.codmarca)
	where tblestoquelocalprodutovariacao.codestoquelocal = el.codestoquelocal
	and coalesce(el.deposito, false) = false
	and tblestoquelocalprodutovariacao.codprodutovariacao = pv.codprodutovariacao
	and coalesce(tblestoquelocalprodutovariacao.estoquemaximo, 0) != ceil(coalesce(tblestoquelocalprodutovariacao.vendadiaquantidadeprevisao, 0) * 40)-- * coalesce(m.estoquemaximodias, 0));
	and tblestoquelocalprodutovariacao.codestoquelocal in (102001, 103001, 104001);

	-- Calcula Estoque Minimo e Maximo Deposito
	raise notice 'Calculando Estoque Minimo e Maximo do Deposito';
	update tblestoquelocalprodutovariacao
	set estoqueminimo = ceil(coalesce(iq.vendadiaquantidadeprevisao, 0) * 20) --coalesce(m.estoqueminimodias, 0))
	, estoquemaximo = ceil(coalesce(iq.vendadiaquantidadeprevisao, 0) * 40) --coalesce(m.estoquemaximodias, 0))
	from (
			select 
				codprodutovariacao,
				sum(vendadiaquantidadeprevisao) as vendadiaquantidadeprevisao
			from tblestoquelocalprodutovariacao elpv
			inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
			where coalesce(el.deposito, false) = false
			and el.inativo is null
			group by codprodutovariacao
		) iq,
		tblestoquelocal el,	
		tblprodutovariacao pv,
		tblproduto p --,
		--tblmarca m
	where tblestoquelocalprodutovariacao.codprodutovariacao = iq.codprodutovariacao
	and tblestoquelocalprodutovariacao.codestoquelocal = el.codestoquelocal
	and coalesce(el.deposito, false) = true
	and tblestoquelocalprodutovariacao.codprodutovariacao = pv.codprodutovariacao
	and pv.codproduto = p.codproduto
	and coalesce(tblestoquelocalprodutovariacao.estoquemaximo, 0) != ceil(coalesce(iq.vendadiaquantidadeprevisao, 0) * 40) --* coalesce(m.estoquemaximodias, 0));
	and tblestoquelocalprodutovariacao.codestoquelocal in (101001);

	-- Coloca minimo como 1 quando for 0
	raise notice 'Usando Minimo de 1 quando for 0';
	update tblestoquelocalprodutovariacao
	set estoqueminimo = 1
	from tblestoquelocal el, tblprodutovariacao pv, tblproduto p
	where coalesce(tblestoquelocalprodutovariacao.estoqueminimo, 0) <= 0
	and tblestoquelocalprodutovariacao.codestoquelocal = el.codestoquelocal
	and el.inativo is null
	and tblestoquelocalprodutovariacao.codprodutovariacao = pv.codprodutovariacao
	and pv.codproduto = p.codproduto
	and p.inativo is null
	and tblestoquelocalprodutovariacao.codestoquelocal in (101001, 102001, 103001, 104001);

	-- Adiciona +1 no maximo quando for igual ou inferior ao minimo
	raise notice 'Quando Maximo for menor que Minimo, Maximo = Minimo';
	update tblestoquelocalprodutovariacao
	set estoquemaximo = tblestoquelocalprodutovariacao.estoqueminimo -- + 1
	from tblestoquelocal el, tblprodutovariacao pv, tblproduto p
	where coalesce(tblestoquelocalprodutovariacao.estoquemaximo, 0) < coalesce(tblestoquelocalprodutovariacao.estoqueminimo, 0)
	and tblestoquelocalprodutovariacao.codestoquelocal = el.codestoquelocal
	and el.inativo is null
	and tblestoquelocalprodutovariacao.codprodutovariacao = pv.codprodutovariacao
	and pv.codproduto = p.codproduto
	and p.inativo is null
	and tblestoquelocalprodutovariacao.codestoquelocal in (101001, 102001, 103001, 104001);

	update tblestoquelocalprodutovariacao
	set estoqueminimo = 0
	, estoquemaximo = 0
	where tblestoquelocalprodutovariacao.codestoquelocal not in (101001, 102001, 103001, 104001);

	drop table if exists tmpdiasmes;
	
	raise notice 'FIM';
	
	RETURN QUERY select count(*) as atualizados from tblestoquelocalprodutovariacaovenda where alteracao = v_data_atualizacao;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION fnatualizaestoqueminimomaximo()
  OWNER TO mgsis;

--select fnatualizaestoqueminimomaximo();

/*
select 
	v.codestoquelocalprodutovariacao, count(*)
from tblestoquelocalprodutovariacaovenda v
where v.ignorar = false
group by v.codestoquelocalprodutovariacao
having count(v.codestoquelocalprodutovariacaovenda) > 4


select 
	* 
from tblestoquelocalprodutovariacaovenda v 
where codestoquelocalprodutovariacao = 3924
and ignorar = false
order by mes desc


select * 
from tblestoquelocalprodutovariacao 
where codprodutovariacao = 4239


select * 
from tblprodutovariacao
where codprodutovariacao = 4239
*/