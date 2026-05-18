// Config local pro shared components, garantindo suporte a ES modules
// quando esses arquivos são lintados por apps que estão fora deste diretório.
module.exports = {
  parserOptions: {
    ecmaVersion: 2021,
    sourceType: 'module',
  },
  env: {
    browser: true,
    es2021: true,
  },
}
