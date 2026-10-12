// Logos de pagamento (bandeiras, bancos, cartões manuais) servidos pelo bundle de cada app.
// Caminho como era em negocios/public: '/bandeiras/1.svg', '/bancos/1.svg', '/logo-cartoes/Stone.jpg'.
const arquivos = import.meta.glob('../assets/pagamento/**/*.{svg,jpg,png}', {
  eager: true,
  query: '?url',
  import: 'default',
})

export const logo = (caminho) =>
  caminho ? (arquivos['../assets/pagamento/' + String(caminho).replace(/^\//, '')] ?? null) : null
