function requiredFormRule(err = "Campo obrigatório.") {
  return (val) => !!val || err;
}

export { requiredFormRule };
