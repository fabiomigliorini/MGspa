// Fonte única das telas internas do app (usada no MgAppsMenu do header e no dashboard inicial).
export const menuGroups = [
  {
    label: 'Operações',
    items: [
      {
        label: 'Saldos de Estoque',
        caption: 'Consulta de saldos por produto, local e filial',
        icon: 'inventory',
        color: 'green-7',
        to: { name: 'estoque-saldo' },
      },
      {
        label: 'Conferência de Estoque',
        caption: 'Inventário e conferência de saldos físicos',
        icon: 'fact_check',
        color: 'teal-7',
        to: { name: 'conferencia' },
      },
      {
        label: 'Etiquetas',
        caption: 'Impressão de etiquetas e códigos de barra',
        icon: 'qr_code_2',
        color: 'indigo-7',
        to: { name: 'etiqueta' },
      },
      {
        label: 'Relatórios',
        caption: 'Análise, físico × fiscal, vendas e transferências',
        icon: 'print',
        color: 'red-7',
        to: { name: 'relatorios' },
      },
    ],
  },
  {
    label: 'Cadastros',
    items: [
      {
        label: 'Produtos',
        caption: 'Cadastro de produtos, embalagens e variações',
        icon: 'inventory_2',
        color: 'brown-7',
        to: { name: 'produto' },
      },
      {
        label: 'Marcas',
        caption: 'Cadastro de marcas de produtos',
        icon: 'sell',
        color: 'brown-6',
        to: { name: 'marca' },
      },
      {
        label: 'Hierarquia de Produtos',
        caption: 'Seção, família, grupo e subgrupo',
        icon: 'account_tree',
        color: 'teal-7',
        to: { name: 'secao-produto' },
      },
      {
        label: 'Unidades de Medida',
        caption: 'Cadastro de unidades de medida',
        icon: 'straighten',
        color: 'blue-grey-6',
        to: { name: 'unidade-medida' },
      },
      {
        label: 'Tipos de Produto',
        caption: 'Cadastro de tipos de produto',
        icon: 'category',
        color: 'deep-purple-5',
        to: { name: 'tipo-produto' },
      },
      {
        label: 'NCM',
        caption: 'Classificação fiscal (NCM)',
        icon: 'receipt_long',
        color: 'blue-8',
        to: { name: 'ncm' },
      },
    ],
  },
]
