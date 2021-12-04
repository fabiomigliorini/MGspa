
delete from tbljobsspa where tbljobsspa.id not in (select min(id) from tbljobsspa dup group by dup.payload);
delete from tbljobs where tbljobs.id not in (select min(id) from tbljobs dup group by dup.payload);
select 'lara', queue, count(*), min(id), max(id) from tbljobs group by queue union all
select 'spa', queue, count(*), min(id), max(id) from tbljobsspa group by queue order by queue

/*
 

update tbljobsspa set queue = 'default' where id in (select j.id from tbljobsspa j where queue = 'parado' order by j.id limit 10)

select * from tbljobs where queue = 'urgent'

delete from tbljobs where id = 7882014

update tbljobsspa set queue = 'parado'

update tbljobsspa set queue = 'default' where payload ilike '%NFePHPResolver%'


select * from tbljobsspa

{"job":"Illuminate\\Queue\\CallQueuedHandler@call","data":{"command":"O:36:\"MGLara\\Jobs\\EstoqueCalculaCustoMedio\":5:{s:16:\"\u0000*\u0000codestoquemes\";i:3117007;s:8:\"\u0000*\u0000ciclo\";i:0;s:5:\"queue\";s:6:\"urgent\";s:5:\"delay\";N;s:6:\"\u0000*\u0000job\";N;}"}}

 
select * from tbljobs
delete from tbljobs where id = 6144778;
ALTER SEQUENCE tbljobs_id_seq RESTART;

delete from tbljobsspa;
ALTER SEQUENCE tbljobsspa_id_seq RESTART;

update tbljobs 
set queue = 'parado_cm' 
where queue = 'urgent'

update tbljobs 
set queue = 'urgent' 
where tbljobs.id in (
	select j2.id 
	from tbljobs j2 
	where j2.queue = 'parado_cm' 
	order by j2.payload 
	limit 10
)

**/