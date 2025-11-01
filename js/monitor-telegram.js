/**
 * Monitor de Oportunidades do Telegram em Tempo Real
 * Exibe mensagens do Telegram no Bloco 1 de bot_aovivo.php
 */

class MonitorTelegram {
    constructor() {
        this.intervaloSincronizacao = 5000; // 5 segundos
        this.mensagens = [];
        this.ativo = true;
        this.ultimaSincronizacao = 0;
        
        console.log('🤖 Monitor Telegram inicializado');
        this.init();
    }

    /**
     * Inicializa o monitor
     */
    init() {
        this.renderizarContainer();
        this.carregarMensagens();
        this.iniciarSincronizacao();
    }

    /**
     * Renderiza o container das oportunidades
     */
    renderizarContainer() {
        const blocoUm = document.querySelector('.bloco-1');
        
        if (!blocoUm) {
            console.warn('⚠️ Bloco 1 não encontrado');
            return;
        }

        const html = `
            <div class="oportunidades-container">
                <div class="oportunidades-header">
                    <div class="oportunidades-titulo">
                        <i class="fas fa-bell"></i>
                        <span>Oportunidades</span>
                        <span class="contador-oportunidades" id="contador-oportunidades">0</span>
                    </div>
                    <button class="btn-sincronizar" id="btn-sincronizar-telegram" title="Sincronizar com Telegram">
                        <i class="fas fa-sync-alt"></i> Sincronizar
                    </button>
                </div>
                <div class="oportunidades-lista" id="oportunidades-lista">
                    <div class="vazio-mensagem">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhuma oportunidade no momento</p>
                    </div>
                </div>
            </div>
        `;

        blocoUm.innerHTML = html;

        // Adiciona evento do botão de sincronização
        document.getElementById('btn-sincronizar-telegram').addEventListener('click', () => {
            this.sincronizarAgora();
        });
    }

    /**
     * Carrega mensagens do servidor
     */
    async carregarMensagens() {
        try {
            const response = await fetch('telegram-monitor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=obter_mensagens',
            });

            const dados = await response.json();

            if (dados.sucesso && Array.isArray(dados.mensagens)) {
                this.mensagens = dados.mensagens;
                this.renderizarMensagens();
                console.log('✅ Mensagens carregadas:', this.mensagens.length);
            }
        } catch (erro) {
            console.error('❌ Erro ao carregar mensagens:', erro);
        }
    }

    /**
     * Sincroniza com Telegram agora
     */
    async sincronizarAgora() {
        const botao = document.getElementById('btn-sincronizar-telegram');
        
        if (botao.classList.contains('loading')) {
            return;
        }

        botao.classList.add('loading');

        try {
            const response = await fetch('telegram-monitor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=sincronizar',
            });

            const dados = await response.json();

            if (dados.sucesso) {
                this.mensagens = dados.mensagens;
                this.renderizarMensagens();
                console.log('✅ Sincronização concluída:', dados.novas_mensagens, 'novas mensagens');
                
                // Mostrar notificação de sincronização
                if (dados.novas_mensagens > 0) {
                    this.mostrarNotificacao(`✅ ${dados.novas_mensagens} nova(s) oportunidade(s)!`);
                }
            } else {
                console.error('❌ Erro na sincronização:', dados.erro);
                this.mostrarNotificacao('❌ Erro ao sincronizar com Telegram', 'erro');
            }
        } catch (erro) {
            console.error('❌ Erro de conexão:', erro);
            this.mostrarNotificacao('❌ Erro de conexão', 'erro');
        } finally {
            botao.classList.remove('loading');
        }
    }

    /**
     * Inicia sincronização automática
     */
    iniciarSincronizacao() {
        setInterval(async () => {
            if (this.ativo) {
                await this.carregarMensagens();
            }
        }, this.intervaloSincronizacao);

        console.log('🔄 Sincronização automática iniciada a cada 5 segundos');
    }

    /**
     * Renderiza as mensagens na tela
     */
    renderizarMensagens() {
        const lista = document.getElementById('oportunidades-lista');
        const contador = document.getElementById('contador-oportunidades');

        if (!lista) return;

        if (this.mensagens.length === 0) {
            lista.innerHTML = `
                <div class="vazio-mensagem">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma oportunidade no momento</p>
                </div>
            `;
            contador.textContent = '0';
            return;
        }

        contador.textContent = this.mensagens.length;

        let html = '';
        
        for (const msg of this.mensagens) {
            const statusClass = this.obterClasseStatus(msg.resultado);
            const statusTexto = this.obterTextoResultado(msg.resultado);
            
            html += `
                <div class="oportunidade-card status-${statusClass}" data-id="${msg.id}">
                    <div class="oportunidade-titulo">
                        🚨 Oportunidade! 
                    </div>
                    
                    <div class="oportunidade-tipo">
                        📊 🚨 ${msg.tipo || 'N/A'}
                    </div>
                    
                    <div class="oportunidade-jogo">
                        <div class="oportunidade-jogo-nomes">
                            <span class="oportunidade-time">${this.extrairPrimeiroTime(msg.jogo)}</span>
                            <span class="oportunidade-vs">x</span>
                            <span class="oportunidade-time">${this.extrairSegundoTime(msg.jogo)}</span>
                        </div>
                    </div>
                    
                    <div class="oportunidade-info-row">
                        <span class="oportunidade-info-label">⛳️ Escanteios:</span>
                        <span class="oportunidade-info-valor">${msg.escanteis || 'N/A'}</span>
                    </div>
                    
                    <div class="oportunidade-info-row">
                        <span class="oportunidade-info-label">Stake:</span>
                        <span class="oportunidade-info-valor">${msg.stake || 'N/A'}</span>
                    </div>
                    
                    <div class="oportunidade-resultado resultado-${statusClass}">
                        ${statusTexto}
                    </div>
                    
                    <small style="color: #999; font-size: 11px; margin-top: 8px; display: block;">
                        📅 ${this.formatarData(msg.data_chegada)}
                    </small>
                </div>
            `;
        }

        lista.innerHTML = html;
        console.log('🎨 Mensagens renderizadas');
    }

    /**
     * Obtém classe CSS do status
     */
    obterClasseStatus(resultado) {
        switch (resultado.toUpperCase()) {
            case 'GREEN':
                return 'green';
            case 'RED':
                return 'red';
            case 'REEMBOLSO':
                return 'reembolso';
            default:
                return 'pendente';
        }
    }

    /**
     * Obtém texto do resultado
     */
    obterTextoResultado(resultado) {
        switch (resultado.toUpperCase()) {
            case 'GREEN':
                return 'GREEN ✅';
            case 'RED':
                return 'RED ❌';
            case 'REEMBOLSO':
                return 'REEMBOLSO ↩️';
            default:
                return '⏳ PENDENTE';
        }
    }

    /**
     * Extrai primeiro time do jogo
     */
    extrairPrimeiroTime(jogo) {
        if (!jogo) return 'Time A';
        const partes = jogo.split('x');
        return partes[0]?.trim() || 'Time A';
    }

    /**
     * Extrai segundo time do jogo
     */
    extrairSegundoTime(jogo) {
        if (!jogo) return 'Time B';
        const partes = jogo.split('x');
        return partes[1]?.trim() || 'Time B';
    }

    /**
     * Formata data para exibição
     */
    formatarData(data) {
        try {
            const dt = new Date(data);
            return dt.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch (e) {
            return data;
        }
    }

    /**
     * Mostra notificação
     */
    mostrarNotificacao(mensagem, tipo = 'sucesso') {
        // Criar elemento de notificação
        const notif = document.createElement('div');
        notif.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${tipo === 'erro' ? '#e57373' : '#66bb6a'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            animation: slideInNotif 0.3s ease;
            max-width: 300px;
            font-weight: 600;
        `;
        notif.textContent = mensagem;
        document.body.appendChild(notif);

        // Remover após 3 segundos
        setTimeout(() => {
            notif.style.animation = 'slideOutNotif 0.3s ease';
            setTimeout(() => notif.remove(), 300);
        }, 3000);
    }
}

// Estilos para as notificações
const styleNotificacao = document.createElement('style');
styleNotificacao.textContent = `
    @keyframes slideInNotif {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutNotif {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(styleNotificacao);

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    new MonitorTelegram();
});

// Manter ativo mesmo em background
window.addEventListener('beforeunload', function() {
    if (window.monitorTelegram) {
        window.monitorTelegram.ativo = false;
    }
});
