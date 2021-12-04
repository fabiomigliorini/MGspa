
delete from tbljobsspa where tbljobsspa.id not in (select min(id) from tbljobsspa dup group by dup.payload);
delete from tbljobs where tbljobs.id not in (select min(id) from tbljobs dup group by dup.payload);
select 'lara', queue, count(*), min(id), max(id) from tbljobs group by queue union all
select 'spa', queue, count(*), min(id), max(id) from tbljobsspa group by queue order by queue
/*

update tbljobsspa set queue = 'default' where id in (select j.id from tbljobsspa j where queue = 'parado' order by j.id limit 10)


select * from tbljobs

update tbljobsspa set queue = 'parado'

update tbljobsspa set queue = 'default' where payload ilike '%NFePHPResolver%'


select * from tbljobsspa

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