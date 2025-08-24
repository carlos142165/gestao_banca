document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-confirmacao');
    const btnConfirmar = document.getElementById('btnConfirmar');
    const btnCancelar = document.getElementById('btnCancelar');

    // Função para abrir o modal
    window.abrirModalConfirmacao = function(mensagem = 'Tem certeza que deseja excluir esta entrada?') {
        const modalTexto = modal.querySelector('.modal-texto');
        if (modalTexto) {
            modalTexto.textContent = mensagem;
        }
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    };

    // Função para fechar o modal
    function fecharModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    // Event listeners para os botões
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            // Aqui você pode adicionar a lógica de confirmação
            // Por exemplo, chamar uma função específica para excluir o item
            if (window.confirmarExclusao && typeof window.confirmarExclusao === 'function') {
                window.confirmarExclusao();
            }
            fecharModal();
        });
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', fecharModal);
    }

    // Fechar modal ao clicar fora dele
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            fecharModal();
        }
    });

    // Fechar modal com tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            fecharModal();
        }
    });
});
