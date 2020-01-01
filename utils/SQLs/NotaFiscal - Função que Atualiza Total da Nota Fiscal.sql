CREATE OR REPLACE FUNCTION mgsis.fntblnotafiscal_atualiza_valorprodutos(pcodnotafiscal bigint)
 RETURNS boolean
 LANGUAGE plpgsql
AS $function$
DECLARE
	vValorProdutos numeric (14,2);
	vIcmsBase      numeric (14,2);
	vIcmsValor     numeric (14,2);
	vIcmsStBase    numeric (14,2);
	vIcmsStValor   numeric (14,2);
	vIpiBase       numeric (14,2);
	vIpiValor      numeric (14,2);
	vDesconto      numeric (14,2);
	vFrete         numeric (14,2);
	vSeguro        numeric (14,2);
	vOutras        numeric (14,2);
BEGIN
	-- calcula total produtos
	SELECT 
	INTO vValorProdutos
		, vIcmsBase
		, vIcmsValor
		, vIcmsStBase
		, vIcmsStValor
		, vIpiBase
		, vIpiValor
		, vDesconto
		, vFrete
		, vSeguro
		, vOutras
		SUM(COALESCE(tblNotaFiscalProdutoBarra.valortotal, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.IcmsBase, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.IcmsValor, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.IcmsStBase, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.IcmsStValor, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.IpiBase, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.IpiValor, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.valordesconto, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.valorfrete, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.valorseguro, 0))
		, SUM(COALESCE(tblNotaFiscalProdutoBarra.valoroutras, 0))
	FROM tblNotaFiscalProdutoBarra
	WHERE tblNotaFiscalProdutoBarra.codNotaFiscal = pCodNotaFiscal;
	-- Atualiza Tabela com valores calculados
	UPDATE tblNotaFiscal
	SET valorProdutos   = coalesce(vValorProdutos, 0)
		, IcmsBase       = coalesce(vIcmsBase     , 0)
		, IcmsValor      = coalesce(vIcmsValor    , 0)
		, IcmsStBase     = coalesce(vIcmsStBase   , 0)
		, IcmsStValor    = coalesce(vIcmsStValor  , 0)
		, IpiBase        = coalesce(vIpiBase      , 0)
		, IpiValor       = coalesce(vIpiValor     , 0)
		, valordesconto  = coalesce(vDesconto     , 0)
		, valorfrete     = coalesce(vFrete        , 0)
		, valorseguro    = coalesce(vSeguro       , 0)
		, valoroutras    = coalesce(vOutras       , 0)
	WHERE tblNotaFiscal.CodNotaFiscal = pCodNotaFiscal;
	RETURN TRUE;
END;
$function$