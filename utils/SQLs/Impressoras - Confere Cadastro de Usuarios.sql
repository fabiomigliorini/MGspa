select impressoramatricial, usuario from tblusuario u where u.impressoramatricial not in (
'epson-cxacen06',
'epson-cxaing02',
'nula'
)

select impressoratelanegocio , usuario from tblusuario u where u.impressoratelanegocio not in (
'nula'
)

select impressoratermica, usuario from tblusuario u where u.impressoratermica not in (
'bematech-cxabot01',
'bematech-cxabot02',
'bematech-cxabot03',
'bematech-cxabot04',
'bematech-cxabot05',
'bematech-cxabot06',
'bematech-cxaing02',
'tmt20-cxacen01',
'tmt20-cxacen02',
'tmt20-cxacen03',
'tmt20-cxacen04',
'tmt20-cxacen05',
'tmt20-cxacen06',
'tmt20-cxaing01',
'tmt20-cxaing03',
'nula'
)


update tblusuario set impressoratermica = 'nula' where impressoratermica ilike 'epson-cxamig02'


select * from tblnegocioprodutobarra t where codnegocioprodutobarra = 7447945


update tblnegocio set codnaturezaoperacao  = 15 where codnegocio  = 2049015




