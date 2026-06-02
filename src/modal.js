function mostrarModal(modal, mascara) {
  if (modal && modal.style) modal.style.left = '50%';
  if (mascara && mascara.style) mascara.style.visibility = 'visible';
}

function esconderModal(modal, mascara) {
  if (modal && modal.style) modal.style.left = '-30%';
  if (mascara && mascara.style) mascara.style.visibility = 'hidden';
}

module.exports = { mostrarModal, esconderModal };
