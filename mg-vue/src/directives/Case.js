import Vue from 'vue'

export const trigger = (el, type) => {
  const e = document.createEvent('HTMLEvents')
  e.initEvent(type, true, true)
  el.dispatchEvent(e)
}

function updateValue (el, force = false) {
  let {
    value,
    dataset: {
      previousValue = ''
    }
  } = el

  if (force || (value && value !== previousValue && value.length > previousValue.length)) {
    // REMOVE OS ACENTOS
    value = value.replace(/'/g, ' ')
    value = value.replace(/'/g, ' ')
    value = value.replace(/\s{2,}/g, ' ')
    value = value.replace(/(-)1+/gi, '-')
    value = value.replace(/[á|ã|â|à|ª]/g, 'a')
    value = value.replace(/[Á|Ã|Â|À]/g, 'A')
    value = value.replace(/[é|ẽ|ê|è]/g, 'e')
    value = value.replace(/[É|Ẽ|Ê|È]/g, 'E')
    value = value.replace(/[í|ĩ|î|ì]/g, 'i')
    value = value.replace(/[Í|Ĩ|Î|Ì]/g, 'I')
    value = value.replace(/[ó|õ|ô|ò|º]/g, 'o')
    value = value.replace(/[Ó|Õ|Ô|Ò]/g, 'O')
    value = value.replace(/[ú|ũ|û|ù]/g, 'u')
    value = value.replace(/[Ú|Ũ|Û|Ù]/g, 'U')
    value = value.replace(/[ĉ|ç]/g, 'c')
    value = value.replace(/[Ĉ|Ç]/g, 'C')
    value = value.replace(/[ń|ñ|ǹ]/gi, 'n')
    value = value.replace(/[Ń|Ñ|Ǹ]/gi, 'N')

    var i, lowers, uppers
    value = value.replace(/([^\W_]+[^\s-]*) */g, function (txtVal) {
      return txtVal.charAt(0).toUpperCase() + txtVal.substr(1).toLowerCase()
    })

    // PALAVRAS QUE DEVEM FICAR EM MINÚSCULO
    lowers = ['De', 'Da', 'Do', 'Dos', 'E', 'Em']
    for (i = 0; i < lowers.length; i++) {
      value = value.replace(new RegExp('\\s' + lowers[i] + '\\s', 'g'), function (txt) {
        return txt.toLowerCase()
      })
    }
    // PALAVRAS QUE DEVEM FICAR EM MAIÚSCULO
    uppers = ['Cdce', 'Sa', 'S/a']
    for (i = 0; i < uppers.length; i++) {
      value = value.replace(new RegExp('\\b' + uppers[i] + '\\b', 'g'), uppers[i].toUpperCase())
    }

    el.value = value
    trigger(el, 'input')
  }
  // el.dataset.previousValue = value
}

const Case = {
  twoWay: true,
  bind (el, {
    value
  }) {
    updateValue(el)
  },
  update: function (el) {
    updateValue(el.querySelector('div').querySelector('input'))
  }
}

Vue.directive('case', Case)

export default Case
