// Paleta de cores distintas pros talhões — saturadas, com bom contraste contra
// o verde da vegetação no satélite (evita tons que se confundem com mato).
export const PALETA_TALHAO = [
  '#e53935', // vermelho
  '#fb8c00', // laranja
  '#fdd835', // amarelo
  '#d81b60', // rosa
  '#8e24aa', // roxo
  '#00acc1', // ciano
  '#3949ab', // índigo
  '#f4511e', // laranja-escuro
  '#c0ca33', // lima
  '#5e35b1', // roxo-escuro
  '#00897b', // teal
  '#ec407a', // pink
  '#ffb300', // âmbar
  '#26a69a', // verde-azulado
]

// Cor efetiva do talhão: a salva, ou uma derivada estável do código pros que
// ainda não escolheram cor (assim já aparecem distintos no mapa/lista).
export function corTalhao(t) {
  if (t?.cor) return t.cor
  const id = Number(t?.codtalhao ?? t?.codplantio) || 0
  return PALETA_TALHAO[id % PALETA_TALHAO.length]
}

// Sugere uma cor ainda não usada (aleatória); se todas em uso, qualquer uma.
export function sugerirCor(usadas = []) {
  const livres = PALETA_TALHAO.filter((c) => !usadas.includes(c))
  const pool = livres.length ? livres : PALETA_TALHAO
  return pool[Math.floor(Math.random() * pool.length)]
}
