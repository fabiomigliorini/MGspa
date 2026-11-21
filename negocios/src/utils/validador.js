// CPF e CNPJ vivem em @components/validador (TASK-283).

export const isEmailValido = (email) => {
  return email.match(
    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
  )
}

export const isTelefoneValido = (telefone, tipo) => {
  const num = String(telefone).replace(/\D/g, '')
  switch (tipo) {
    case 1:
      return num.length == 10
    case 2:
      return num.length == 11
    default:
      return true
  }
}
