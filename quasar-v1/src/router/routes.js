
export default [
  // Básicos
  { path: '/', component: () => import('components/Index') }, // Default
  { path: '/login', component: () => import('components/Login') }, // Login
  { path: '*', component: () => import('components/Error404') },

  // Etiquetas
  { path: '/etiqueta', component: () => import('components/views/etiqueta/Index') },

  // Boleto
  { path: '/boleto', component: () => import('components/views/boleto/Index') },
  { path: '/boleto/retorno/:codportador/:arquivo/:dataretorno', component: () => import('components/views/boleto/retorno') },
  { path: '/boleto/remessa/:codportador/:remessa', component: () => import('components/views/boleto/remessa') },

  // Marca
  { path: '/marca', component: () => import('components/views/marca/Index') },
  { path: '/marca/create', component: () => import('components/views/marca/Create') },
  { path: '/marca/:id', component: () => import('components/views/marca/View') },
  { path: '/marca/:id/foto', component: () => import('components/views/marca/Photo') },
  { path: '/marca/:id/update', component: () => import('components/views/marca/Update') },

  // Grupo de Usuarios
  { path: '/usuario', component: () => import('components/views/usuario/Index') },
  // { path: '/usuario/create', component: () => import('components/views/usuario/Create') },
  { path: '/usuario/perfil', component: () => import('components/views/usuario/Profile') },
  { path: '/usuario/impressoras', component: () => import('components/views/usuario/Print') },
  { path: '/usuario/foto', component: () => import('components/views/usuario/Photo') },
  { path: '/usuario/senha', component: () => import('components/views/usuario/Password') },
  { path: '/usuario/grupo-usuario/:id', name: 'grupo-usuario', component: () => import('components/views/usuario/Index') },
  { path: '/usuario/:id', component: () => import('components/views/usuario/View') },
  { path: '/usuario/:id/update', component: () => import('components/views/usuario/Update') },
  { path: '/usuario/:id/grupos', component: () => import('components/views/usuario/Grupos') },

  // Permissao
  { path: '/permissao', component: () => import('components/views/permissao/Index') },

  // Filiais
  { path: '/filial', component: () => import('components/views/filial/Index') },

  // Estoque Estatística
  { path: '/estoque-estatistica/:codproduto', component: () => import('components/views/estoque-estatistica/Index') },

  // Estoque Conferência
  { path: '/estoque-saldo-conferencia', component: () => import('components/views/estoque-saldo-conferencia/Index') },
  { path: '/estoque-saldo-conferencia/listagem/:codestoquelocal/:codmarca/:fiscal/:data', component: () => import('components/views/estoque-saldo-conferencia/Listagem/') },

  // NFe
  { path: '/nfe', component: () => import('components/views/nfe/Index') },

  // NotaFiscalTerceiro
  { path: '/nota-fiscal-terceiro', component: () => import('components/views/nota-fiscal-terceiro/Index') },
  // { path: '/notafiscal-terceiro', component: () => import('components/views/notafiscal-tercerio/Index') },
  // { path: '/notafiscal-terceiro/detalhes-nfe/:chave', component: () => import('components/views/notafiscal-tercerio/Detalhes-nfe') },


  { path: '/transferencia/requisicao', component: () => import('components/views/transferencia/requisicao/Index') },
  { path: '/transferencia/requisicao/nova/:codestoquelocal/:codmarca/:fiscal/:data', component: () => import('components/views/transferencia/requisicao/Nova') },

  // Pedidos
  { path: '/pedido/', component: () => import('components/views/pedido/Index') },
  { path: '/pedido/:id', component: () => import('components/views/pedido/View') },

]
