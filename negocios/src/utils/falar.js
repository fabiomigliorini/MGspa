const synth = window.speechSynthesis;
const voices = synth.getVoices();
const voice = voices.find((e) => {
  return e.lang == "pt-BR";
});

export const falar = async (mensagem) => {
  const utterThis = new SpeechSynthesisUtterance(mensagem);
  utterThis.pitch = 0.7; // 0 a 1 / 0.1 step
  utterThis.rate = 1.2; // 0.5 a 2 / 0.1 step
  synth.speak(utterThis);
};
