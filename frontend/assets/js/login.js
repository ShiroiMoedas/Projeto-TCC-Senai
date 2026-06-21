/**
 * MAPA - Sistema de Mapeamento de Competências & Aptidões
 * Lógica do Portal de Acesso (Login, Cadastro e Recuperação)
 * 
 * Este arquivo gerencia:
 * - Autenticação de usuários (colaboradores e gestores)
 * - Abertura/fechamento de modais
 * - Cadastro de novas contas
 * - Recuperação de acesso
 * - Validações de formulários
 */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // SELEÇÃO DE ELEMENTOS DO DOM
    // ============================================================
    // Elementos do formulário de login
    const formLogin = document.getElementById('formLogin');
    const btnEsqueceu = document.getElementById('btnEsqueceu');
    
    // Elementos do modal de recuperação
    const modalRecuperacao = document.getElementById('modalRecuperacao');
    const btnFecharModal = document.querySelector('.close-modal');
    const formRecuperacao = document.getElementById('formRecuperacao');
    
    // Elementos do modal de cadastro
    const btnCriarConta = document.getElementById('btnCriarConta');
    const modalCadastro = document.getElementById('modalCadastro');
    const btnFecharCadastro = document.querySelector('.close-modal-cadastro');
    const formCadastro = document.getElementById('formCadastro');
    const btnVoltarLogin = document.getElementById('btnVoltarLogin');

    // ============================================================
    // 1. LÓGICA DE AUTENTICAÇÃO - LOGIN
    // ============================================================
    /**
     * Valida credenciais do usuário e faz login no sistema.
     * Se bem-sucedido: armazena usuário em sessão e redireciona.
     * Se falhar: exibe mensagem de erro.
     */
    formLogin.addEventListener('submit', async (e) => {
        e.preventDefault(); // Previne recarregamento da página

        // Captura os dados do formulário
        const dados = {
            perfil: document.getElementById('perfil').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value
        };

        try {
            // Envia credenciais ao backend para validação
            const response = await fetch('../backend/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'login', ...dados })
            });

            const resultado = await response.json();

            // Se login bem-sucedido
            if (resultado.sucesso) {
                // Armazena dados do usuário na sessão do navegador
                sessionStorage.setItem('mapa_usuario', JSON.stringify(resultado.usuario));

                // Redireciona conforme o perfil do usuário
                window.location.href = dados.perfil === 'gestor'
                    ? 'dashboard/dashboard.html'      // Painel estratégico para gestores
                    : 'quiz/formulario.html';           // Quiz para colaboradores
            } else {
                // Exibe erro de autenticação
                alert('Erro de autenticação: ' + resultado.mensagem);
            }
        } catch (error) {
            console.error('Erro ao conectar com o servidor:', error);
            alert('Não foi possível conectar ao servidor. Verifique se o XAMPP está ativo.');
        }
    });

    // ============================================================
    // 2. GESTÃO DO MODAL DE RECUPERAÇÃO DE ACESSO
    // ============================================================
    /**
     * Abre o modal de recuperação de acesso quando o usuário clica
     * em "Esqueceu-se da senha?".
     */
    btnEsqueceu.addEventListener('click', (e) => {
        e.preventDefault();
        modalRecuperacao.classList.remove('hidden'); // Remove classe 'hidden' para exibir
    });

    // Fecha o modal de recuperação ao clicar no X
    btnFecharModal.addEventListener('click', () => {
        modalRecuperacao.classList.add('hidden');
    });

    // Fecha qualquer modal ao clicar fora da área de conteúdo
    window.addEventListener('click', (e) => {
        // Fecha modal de recuperação se clicar fora
        if (e.target === modalRecuperacao) {
            modalRecuperacao.classList.add('hidden');
        }
        // Fecha modal de cadastro se clicar fora
        if (e.target === modalCadastro) {
            modalCadastro.classList.add('hidden');
        }
    });

    /**
     * Processa o formulário de recuperação de acesso.
     * Valida e-mail e simula envio de instruções de reset de senha.
     */
    formRecuperacao.addEventListener('submit', (e) => {
        e.preventDefault(); // Previne recarregamento

        const email = document.getElementById('emailRecupera').value;

        // TODO: Integrar com sistema de envio de e-mail real
        console.log('Solicitando recuperação para:', email);
        alert('Instruções enviadas para o e-mail: ' + email);
        modalRecuperacao.classList.add('hidden'); // Fecha o modal
    });

    // ============================================================
    // 3. GESTÃO DO MODAL DE CADASTRO
    // ============================================================
    /**
     * Abre o modal de cadastro quando o usuário clica em "Criar nova conta".
     */
    btnCriarConta.addEventListener('click', (e) => {
        e.preventDefault();
        modalCadastro.classList.remove('hidden'); // Exibe o modal
    });

    /**
     * Fecha o modal de cadastro ao clicar no X.
     */
    btnFecharCadastro.addEventListener('click', () => {
        modalCadastro.classList.add('hidden');
    });

    /**
     * Fecha o modal de cadastro e volta ao formulário de login.
     */
    btnVoltarLogin.addEventListener('click', (e) => {
        e.preventDefault();
        modalCadastro.classList.add('hidden');
    });

    // ============================================================
    // 4. LÓGICA DE CADASTRO DE NOVO USUÁRIO
    // ============================================================
    /**
     * Processa o formulário de cadastro.
     * Valida campos localmente e envia dados ao backend para criar novo usuário.
     * Se bem-sucedido: permite fazer login com a nova conta.
     * Se falhar: exibe mensagem de erro específica.
     */
    formCadastro.addEventListener('submit', async (e) => {
        e.preventDefault(); // Previne recarregamento

        // Captura valores dos campos
        const nome = document.getElementById('nomeCompleto').value.trim();
        const email = document.getElementById('emailCadastro').value.trim();
        const perfil = document.getElementById('perfilCadastro').value;
        const senha = document.getElementById('senhaCadastro').value;
        const senhaConfirm = document.getElementById('senhaConfirm').value;

        // ========== VALIDAÇÕES LOCAIS ==========
        // Verifica comprimento mínimo da senha
        if (senha.length < 6) {
            alert('A palavra-passe deve ter no mínimo 6 caracteres.');
            return;
        }

        // Verifica se as senhas coincidem
        if (senha !== senhaConfirm) {
            alert('As palavras-passe não coincidem.');
            return;
        }

        // Verifica se todos os campos foram preenchidos
        if (!nome || !email || !perfil) {
            alert('Preencha todos os campos.');
            return;
        }

        try {
            // ========== ENVIO AO BACKEND ==========
            // Envia solicitação de cadastro ao servidor
            const response = await fetch('../backend/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'cadastro',        // Ação especificada no backend
                    nome,
                    email,
                    perfil,
                    password: senha
                })
            });

            // Aguarda a resposta do backend
            const resultado = await response.json();

            // ========== PROCESSAMENTO DO RESULTADO ==========
            if (resultado.sucesso) {
                // Cadastro bem-sucedido
                alert('Conta criada com sucesso! Agora faça login.');
                
                // Fecha o modal de cadastro
                modalCadastro.classList.add('hidden');
                
                // Limpa o formulário de cadastro
                formCadastro.reset();
                
                // Pré-preenche o formulário de login com e-mail e perfil cadastrados
                // para facilitar o login imediato do novo usuário
                document.getElementById('email').value = email;
                document.getElementById('perfil').value = perfil;
            } else {
                // Cadastro falhou - exibe mensagem de erro do backend
                alert('Erro ao criar conta: ' + resultado.mensagem);
            }
        } catch (error) {
            // Erro de conexão com o servidor
            console.error('Erro ao cadastrar:', error);
            alert('Não foi possível conectar ao servidor. Tente novamente.');
        }
    });
    
});