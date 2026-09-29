-- Conferência do agro — SÓ LEITURA. Roda em qualquer banco (dev ou PROD):
--
--   docker exec -i -e PGOPTIONS='-c default_transaction_read_only=on' mgdb-mgdb-1 \
--     psql -U mgsis -d mgsis < api/tests/agro/conferencia.sql
--
-- Cada bloco lista o que VIOLA a regra; bloco vazio = regra cumprida.
-- FALHA = número errado; INFO = medida que não reprova (ex.: silo negativo é
-- permitido pela decisão D1, só avisa).
--
-- A bateria (tests/agro/run.php) lê ESTE arquivo: cada bloco começa numa linha
-- "-- @ID NIVEL título". Os comentários /*ESCOPO:...*/ e /*ESCOPO_CULTURA:...*/
-- viram filtro da massa ZZTESTE quando a bateria confere só o que ela criou;
-- no psql eles são só comentários e a conferência pega o banco inteiro.

\pset pager off
\pset footer off

-- @I1 FALHA Carga finalizada: extrato do papel = líquido (silo de origem: = bruto) e uma linha por ponto
\echo '== I1 [FALHA] Carga finalizada: extrato do papel = líquido (silo de origem: = bruto) e uma linha por ponto'
with c as (
  select c.codcarga, c.bruto, c.liquido
  from tblcarga c
  where c.inativo is null and c.etapa = 'FINALIZADO' and c.liquido > 0 /*ESCOPO:c.codsafra*/
), p as (
  select p.codcarga, p.papel, count(*) as npontos,
         bool_and(p.contatipo = 'UNIDADE' and p.papel = 'ORIGEM') as todos_silo_origem,
         bool_or(p.contatipo = 'UNIDADE' and p.papel = 'ORIGEM') as algum_silo_origem
  from tblcargaponto p join c using (codcarga)
  group by 1, 2
), m as (
  select m.codcarga, m.papel, count(*) as nlinhas, sum(abs(m.liquido)) as extrato
  from tblmovimentograo m join c using (codcarga)
  where not m.manual and m.inativo is null
  group by 1, 2
)
select c.codcarga, p.papel, p.npontos, coalesce(m.nlinhas, 0) as nlinhas,
       case when p.todos_silo_origem then c.bruto else c.liquido end as esperado,
       coalesce(m.extrato, 0) as extrato
from c
join p using (codcarga)
left join m on m.codcarga = p.codcarga and m.papel = p.papel
where p.todos_silo_origem = p.algum_silo_origem -- papel misto é a I14
  and (coalesce(m.nlinhas, 0) <> p.npontos
       or abs(coalesce(m.extrato, 0) - case when p.todos_silo_origem then c.bruto else c.liquido end) > 0.001)
order by 1, 2;

-- @I2 FALHA Nenhum lançamento automático de carga cancelada, não finalizada ou sem líquido
\echo '== I2 [FALHA] Nenhum lançamento automático de carga cancelada, não finalizada ou sem líquido'
select m.codcarga, c.etapa, c.inativo, c.liquido, count(*) as linhas
from tblmovimentograo m
join tblcarga c using (codcarga)
where not m.manual and m.inativo is null
  and (c.inativo is not null or c.etapa <> 'FINALIZADO' or coalesce(c.liquido, 0) <= 0) /*ESCOPO:c.codsafra*/
group by 1, 2, 3, 4
order by 1;

-- @I3 FALHA Transferência: os silos somam −desconto (a quebra)
\echo '== I3 [FALHA] Transferência: os silos somam −desconto (a quebra)'
select c.codcarga, c.desconto, sum(m.liquido) as soma_silos
from tblcarga c
join tblmovimentograo m using (codcarga)
where c.sentido = 'TRANSFERENCIA' and c.etapa = 'FINALIZADO' and c.inativo is null
  and not m.manual and m.inativo is null and m.contatipo = 'UNIDADE' /*ESCOPO:c.codsafra*/
group by 1, 2
having abs(sum(m.liquido) + coalesce(c.desconto, 0)) > 0.001
order by 1;

-- @I4 FALHA Kg do ponto = kg do extrato do ponto (silo de origem: linha sem desconto)
\echo '== I4 [FALHA] Kg do ponto = kg do extrato do ponto (silo de origem: linha sem desconto)'
with p as (
  select p.codcarga, p.papel, p.contatipo,
         coalesce(p.codplantio, p.codunidadearmazenadora, p.codcontrato) as conta,
         sum(p.liquido) as ponto
  from tblcargaponto p
  join tblcarga c using (codcarga)
  where c.inativo is null and c.etapa = 'FINALIZADO' and c.liquido > 0 /*ESCOPO:c.codsafra*/
  group by 1, 2, 3, 4
), m as (
  select m.codcarga, m.papel, m.contatipo,
         coalesce(m.codplantio, m.codunidadearmazenadora, m.codcontrato) as conta,
         sum(abs(m.liquido)) as extrato, sum(abs(m.desconto)) as desconto
  from tblmovimentograo m
  where not m.manual and m.inativo is null and m.codcarga is not null
  group by 1, 2, 3, 4
)
select p.codcarga, p.papel, p.contatipo, p.conta, p.ponto, m.extrato, m.desconto
from p
left join m using (codcarga, papel, contatipo, conta)
where case when p.contatipo = 'UNIDADE' and p.papel = 'ORIGEM'
           then coalesce(m.desconto, 0) > 0.001
           else abs(coalesce(m.extrato, 0) - coalesce(p.ponto, 0)) > 0.001 end
order by 1, 2;

-- @I5 FALHA Ponto com exatamente uma conta, do tipo dele, sem repetir na mesma carga
\echo '== I5 [FALHA] Ponto com exatamente uma conta, do tipo dele, sem repetir na mesma carga'
select p.codcarga, p.codcargaponto, p.papel, p.contatipo, p.codplantio, p.codunidadearmazenadora, p.codcontrato,
       'conta errada' as problema
from tblcargaponto p
join tblcarga c using (codcarga)
where not (   (p.contatipo = 'PLANTIO'  and p.codplantio is not null and p.codunidadearmazenadora is null and p.codcontrato is null)
           or (p.contatipo = 'UNIDADE'  and p.codunidadearmazenadora is not null and p.codplantio is null and p.codcontrato is null)
           or (p.contatipo = 'CONTRATO' and p.codcontrato is not null and p.codplantio is null and p.codunidadearmazenadora is null))
  /*ESCOPO:c.codsafra*/
union all
select p.codcarga, min(p.codcargaponto), p.papel, p.contatipo, p.codplantio, p.codunidadearmazenadora, p.codcontrato,
       count(*) || ' pontos iguais'
from tblcargaponto p
join tblcarga c using (codcarga)
where true /*ESCOPO:c.codsafra*/
group by p.codcarga, p.papel, p.contatipo, p.codplantio, p.codunidadearmazenadora, p.codcontrato
having count(*) > 1
order by 1;

-- @I6 FALHA Talhão da safra da carga; contrato da cultura da safra
\echo '== I6 [FALHA] Talhão da safra da carga; contrato da cultura da safra'
select c.codcarga, 'talhão de outra safra' as problema, p.codplantio as conta, pl.codsafra as da_conta, c.codsafra as da_carga
from tblcargaponto p
join tblcarga c using (codcarga)
join tblplantio pl on pl.codplantio = p.codplantio
where p.contatipo = 'PLANTIO' and pl.codsafra <> c.codsafra and c.inativo is null /*ESCOPO:c.codsafra*/
union all
select c.codcarga, 'contrato de outra cultura', p.codcontrato, k.codcultura, s.codcultura
from tblcargaponto p
join tblcarga c using (codcarga)
join tblsafra s on s.codsafra = c.codsafra
join tblcontrato k on k.codcontrato = p.codcontrato
where p.contatipo = 'CONTRATO' and k.codcultura <> s.codcultura and c.inativo is null /*ESCOPO:c.codsafra*/
order by 1;

-- @I7 FALHA Contrato com teto não fica entregue a mais (1 kg de folga)
\echo '== I7 [FALHA] Contrato com teto não fica entregue a mais (1 kg de folga)'
select k.codcontrato, k.contrato, k.quantidade as sacas, k.quantidade * cu.pesosaca as contratadokg,
       coalesce(sum(m.liquido), 0) as entreguekg
from tblcontrato k
join tblcultura cu using (codcultura)
left join tblmovimentograo m on m.codcontrato = k.codcontrato and m.inativo is null
where k.quantidade is not null and k.inativo is null /*ESCOPO:k.codsafra*/
group by 1, 2, 3, 4
having coalesce(sum(m.liquido), 0) > k.quantidade * cu.pesosaca + 1
order by 1;

-- @I8 FALHA Balanço de massa da safra: silos = colhido + comprado − vendido − quebra (lançamentos das cargas)
\echo '== I8 [FALHA] Balanço de massa da safra: silos = colhido + comprado − vendido − quebra (lançamentos das cargas)'
with m as (
  select m.codsafra,
         sum(case when m.contatipo = 'UNIDADE' then m.liquido else 0 end) as silos,
         sum(case when m.contatipo = 'PLANTIO' then m.liquido else 0 end) as colhido,
         sum(case when m.contatipo = 'CONTRATO' and k.operacao = 'COMPRA' then m.liquido else 0 end) as comprado,
         sum(case when m.contatipo = 'CONTRATO' and k.operacao <> 'COMPRA' then m.liquido else 0 end) as vendido
  from tblmovimentograo m
  left join tblcontrato k on k.codcontrato = m.codcontrato
  where not m.manual and m.inativo is null /*ESCOPO:m.codsafra*/
  group by 1
), q as (
  select c.codsafra, sum(coalesce(c.desconto, 0)) as quebra
  from tblcarga c
  where c.inativo is null and c.etapa = 'FINALIZADO' and c.liquido > 0 /*ESCOPO:c.codsafra*/
    and exists (select 1 from tblcargaponto p
                where p.codcarga = c.codcarga and p.contatipo = 'UNIDADE' and p.papel = 'ORIGEM')
  group by 1
)
select s.codsafra, s.safra, m.silos, m.colhido, m.comprado, m.vendido, coalesce(q.quebra, 0) as quebra,
       m.colhido + m.comprado - m.vendido - coalesce(q.quebra, 0) - m.silos as diferenca
from m
join tblsafra s on s.codsafra = m.codsafra
left join q on q.codsafra = m.codsafra
where abs(m.colhido + m.comprado - m.vendido - coalesce(q.quebra, 0) - m.silos) > 0.01
order by 1;

-- @I9 FALHA Colhido do talhão lançado na safra do próprio talhão
\echo '== I9 [FALHA] Colhido do talhão lançado na safra do próprio talhão'
select m.codmovimentograo, m.codcarga, m.codplantio, pl.codsafra as safra_talhao, m.codsafra as safra_lancamento, m.liquido
from tblmovimentograo m
join tblplantio pl on pl.codplantio = m.codplantio
where m.contatipo = 'PLANTIO' and m.inativo is null and pl.codsafra <> m.codsafra /*ESCOPO:m.codsafra*/
order by 1;

-- @I10 FALHA Pesos da carga: bruto = PBT − tara; desconto = soma dos itens; líquido = bruto − desconto
\echo '== I10 [FALHA] Pesos da carga: bruto = PBT − tara; desconto = soma dos itens; líquido = bruto − desconto'
with i as (
  select codcarga, sum(desconto) as itens from tblcargaclassificacao group by 1
)
select c.codcarga, c.pbt, c.tara, c.bruto, c.desconto, i.itens as soma_itens, c.liquido
from tblcarga c
left join i using (codcarga)
where c.pbt is not null and c.tara is not null /*ESCOPO:c.codsafra*/
  and (   abs(c.bruto - (c.pbt - c.tara)) > 0.001
       or abs(coalesce(c.desconto, 0) - coalesce(i.itens, 0)) > 0.001
       or abs(c.liquido - (c.bruto - coalesce(c.desconto, 0))) > 0.001)
order by 1;

-- @I10b INFO Cargas finalizadas com kg fracionado (a regra de 28/09 é kg inteiro)
\echo '== I10b [INFO] Cargas finalizadas com kg fracionado (a regra de 28/09 é kg inteiro)'
select count(*) as cargas, min(c.codcarga) as primeira, max(c.codcarga) as ultima
from tblcarga c
where c.etapa = 'FINALIZADO' and c.inativo is null /*ESCOPO:c.codsafra*/
  and (c.liquido <> trunc(c.liquido) or coalesce(c.desconto, 0) <> trunc(coalesce(c.desconto, 0)))
having count(*) > 0;

-- @I11 INFO Silo com saldo negativo na safra (D1: o pátio avisa, o servidor não bloqueia)
\echo '== I11 [INFO] Silo com saldo negativo na safra (D1: o pátio avisa, o servidor não bloqueia)'
select u.codunidadearmazenadora, u.unidadearmazenadora, m.codsafra, sum(m.liquido) as saldokg
from tblmovimentograo m
join tblunidadearmazenadora u on u.codunidadearmazenadora = m.codunidadearmazenadora
where m.contatipo = 'UNIDADE' and m.inativo is null /*ESCOPO:m.codsafra*/
group by 1, 2, 3
having sum(m.liquido) < -0.001
order by 1, 3;

-- @I12 FALHA Tabela de classificação: ordem sem repetir e tolerância abaixo de 100
\echo '== I12 [FALHA] Tabela de classificação: ordem sem repetir e tolerância abaixo de 100'
select pc.codcultura, cu.cultura, pc.ordem::text as ordem, string_agg(pc.parametroclassificacao, ', ') as parametros,
       'ordem repetida' as problema
from tblparametroclassificacao pc
join tblcultura cu using (codcultura)
where pc.inativo is null /*ESCOPO_CULTURA:pc.codcultura*/
group by 1, 2, 3
having count(*) > 1
union all
select pc.codcultura, cu.cultura, pc.ordem::text, pc.parametroclassificacao, 'tolerância ' || pc.tolerancia
from tblparametroclassificacao pc
join tblcultura cu using (codcultura)
where pc.inativo is null and pc.tolerancia >= 100 /*ESCOPO_CULTURA:pc.codcultura*/
order by 1, 3;

-- @I12b INFO Tabela de classificação diferente da norma (soja IN MAPA 11/2007, milho IN MAPA 60/2011)
\echo '== I12b [INFO] Tabela de classificação diferente da norma (soja IN MAPA 11/2007, milho IN MAPA 60/2011)'
with norma(cultura, parametro, tolerancia, reduzbase) as (values
  ('soja', 'impureza', 1, true), ('soja', 'umidade', 14, true), ('soja', 'avariados', 8, false),
  ('soja', 'esverdeados', 8, false), ('soja', 'quebrados', 30, false),
  ('milho', 'impureza', 1, true), ('milho', 'umidade', 14, true), ('milho', 'avariados', 6, false))
select cu.codcultura, cu.cultura, pc.parametroclassificacao, pc.metodo, pc.reduzbase, pc.tolerancia, pc.fator, pc.desagio,
       n.tolerancia as tolerancia_norma, n.reduzbase as reduzbase_norma
from tblparametroclassificacao pc
join tblcultura cu using (codcultura)
join norma n on lower(cu.cultura) like '%' || n.cultura || '%' and lower(pc.parametroclassificacao) = n.parametro
where pc.inativo is null /*ESCOPO_CULTURA:pc.codcultura*/
  and (pc.metodo <> 'NORMALIZADO' or pc.tolerancia <> n.tolerancia or pc.reduzbase <> n.reduzbase
       or pc.fator <> 0 or pc.desagio <> 0)
order by 1, 3;

-- @I13 INFO Número de contrato repetido na mesma safra
\echo '== I13 [INFO] Número de contrato repetido na mesma safra'
select k.codsafra, k.contrato, count(*) as vezes, string_agg(k.codcontrato::text, ', ') as contratos
from tblcontrato k
where k.inativo is null /*ESCOPO:k.codsafra*/
group by 1, 2
having count(*) > 1
order by 1, 2;

-- @I14 FALHA Origem e destino nas combinações permitidas por tipo de romaneio (D2), nunca o mesmo silo dos dois lados
\echo '== I14 [FALHA] Origem e destino nas combinações permitidas por tipo de romaneio (D2), nunca o mesmo silo dos dois lados'
with p as (
  select c.codcarga, c.sentido, p.papel, p.contatipo, k.operacao, p.codunidadearmazenadora
  from tblcarga c
  join tblcargaponto p using (codcarga)
  left join tblcontrato k on k.codcontrato = p.codcontrato
  where c.inativo is null /*ESCOPO:c.codsafra*/
)
select codcarga, sentido, papel, contatipo || coalesce(' ' || operacao, '') as conta, 'fora da matriz' as problema
from p
where not (
     (sentido = 'ENTRADA' and papel = 'ORIGEM' and (contatipo = 'PLANTIO' or (contatipo = 'CONTRATO' and operacao = 'COMPRA')))
  or (sentido = 'ENTRADA' and papel = 'DESTINO' and (contatipo = 'UNIDADE' or (contatipo = 'CONTRATO' and operacao = 'VENDA')))
  or (sentido = 'SAIDA' and papel = 'ORIGEM' and contatipo = 'UNIDADE')
  or (sentido = 'SAIDA' and papel = 'DESTINO' and contatipo = 'CONTRATO' and operacao = 'VENDA')
  or (sentido = 'TRANSFERENCIA' and contatipo = 'UNIDADE'))
union all
select o.codcarga, o.sentido, 'ORIGEM/DESTINO', 'UNIDADE ' || o.codunidadearmazenadora, 'mesmo silo dos dois lados'
from p o
join p d on d.codcarga = o.codcarga and d.papel = 'DESTINO' and d.contatipo = 'UNIDADE'
        and d.codunidadearmazenadora = o.codunidadearmazenadora
where o.papel = 'ORIGEM' and o.contatipo = 'UNIDADE'
order by 1;
