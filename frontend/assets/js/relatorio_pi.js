/*
 * Relatório PI - MAPA
 *
 * Este script gera o gráfico de radar com os 4 eixos do Predictive Index:
 * Dominância, Influência, Paciência e Formalidade.
 *
 * Ele também define a lógica de interpretação para destacar pontos fortes
 * e pontos a melhorar, além de disponibilizar exportação em PDF para o
 * relatório e para o certificado.
 */

const PI_LABELS = ['Dominância', 'Influência', 'Paciência', 'Formalidade'];

function initRelatorioPi() {
    const nome = sessionStorage.getItem('mapa_usuario')
        ? JSON.parse(sessionStorage.getItem('mapa_usuario')).nome
        : 'Colaborador PAREX';

    const nomeElement = document.getElementById('nomeColaborador');
    if (nomeElement) {
        nomeElement.textContent = nome;
    }

    const nomeGestor = document.getElementById('nomeColaboradorGestor');
    if (nomeGestor) {
        nomeGestor.textContent = nome;
    }

    const nomeCertificado = document.getElementById('nomeCertificado');
    if (nomeCertificado) {
        nomeCertificado.textContent = nome;
    }

    const resultadoPi = carregarResultadoPi();
    const valoresPi = resultadoPi
        ? [resultadoPi.dominancia, resultadoPi.influencia, resultadoPi.paciencia, resultadoPi.formalidade]
        : [0, 0, 0, 0];

    const perfilPiElement = document.getElementById('perfilPi');
    if (perfilPiElement) {
        perfilPiElement.textContent = definirPerfilPi(valoresPi);
    }

    const ctx = document.getElementById('graficoRadarPi');
    if (ctx) {
        renderizarGraficoRadar(ctx, valoresPi, PI_LABELS);
    }

    const ctxGestor = document.getElementById('graficoRadarGestor');
    if (ctxGestor) {
        renderizarGraficoRadar(ctxGestor, valoresPi, PI_LABELS);
    }

    popularInsights(resultadoPi);
    bindActions();
}

function carregarResultadoPi() {
    const salvo = sessionStorage.getItem('mapa_resultado_pi') || localStorage.getItem('mapa_resultado_pi');
    if (!salvo) {
        return null;
    }

    try {
        return JSON.parse(salvo);
    } catch (erro) {
        console.error('Erro ao ler resultado PI:', erro);
        return null;
    }
}

function definirPerfilPi(valores) {
    const media = valores.reduce((total, valor) => total + valor, 0) / valores.length;

    if (media >= 80) {
        return 'Perfil Forte e Estratégico';
    }

    if (media >= 70) {
        return 'Perfil Equilibrado';
    }

    return 'Perfil em Desenvolvimento';
}

function renderizarGraficoRadar(canvas, valores, labels) {
    const config = {
        type: 'radar',
        data: {
            labels,
            datasets: [{
                label: 'Pontuação PI',
                data: valores,
                fill: true,
                backgroundColor: 'rgba(0, 86, 179, 0.2)',
                borderColor: '#0056b3',
                borderWidth: 2,
                pointBackgroundColor: '#002855',
                pointBorderColor: '#ffffff',
                pointHoverBackgroundColor: '#ffffff',
                pointHoverBorderColor: '#002855',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        color: '#4b5563'
                    },
                    grid: {
                        color: 'rgba(0, 40, 85, 0.18)'
                    },
                    angleLines: {
                        color: 'rgba(0, 40, 85, 0.18)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    };

    new Chart(canvas, config);
}

function popularInsights(resultadoPi) {
    const pontosFortes = document.getElementById('listaPontosFortes');
    const gaps = document.getElementById('listaGaps');
    const interpretacao = document.getElementById('textoInterpretacao');

    if (!resultadoPi) {
        if (pontosFortes) {
            pontosFortes.innerHTML = '<li>Aguardando conclusão do quiz para gerar o relatório.</li>';
        }
        if (gaps) {
            gaps.innerHTML = '<li>Aguardando conclusão do quiz para gerar o relatório.</li>';
        }
        if (interpretacao) {
            interpretacao.textContent = 'Complete o quiz para gerar automaticamente o seu relatório PI.';
        }
        return;
    }

    const eixos = [
        { nome: 'dominancia', label: 'Dominância', textoFortaleza: 'boa capacidade de liderança e tomada de decisão', textoGap: 'maior consistência na condução de processos e decisões formais' },
        { nome: 'influencia', label: 'Influência', textoFortaleza: 'forte capacidade de comunicação e influência de equipe', textoGap: 'mais clareza na influência em momentos de conflito ou pressão' },
        { nome: 'paciencia', label: 'Paciência', textoFortaleza: 'equilíbrio emocional e tolerância em cenários complexos', textoGap: 'maior resiliência e controle frente a mudanças rápidas' },
        { nome: 'formalidade', label: 'Formalidade', textoFortaleza: 'disciplina, organização e adesão a procedimentos', textoGap: 'mais rigor na padronização e execução formal' },
    ];

    const eixoMaisAlto = eixos.reduce((melhor, eixo) => {
        const atual = resultadoPi[eixo.nome] ?? 0;
        return atual > (resultadoPi[melhor.nome] ?? 0) ? eixo : melhor;
    }, eixos[0]);

    const eixoMaisBaixo = eixos.reduce((pior, eixo) => {
        const atual = resultadoPi[eixo.nome] ?? 0;
        return atual < (resultadoPi[pior.nome] ?? 0) ? eixo : pior;
    }, eixos[0]);

    if (pontosFortes) {
        pontosFortes.innerHTML = `
            <li>O eixo ${eixoMaisAlto.label} apresentou a maior pontuação (${resultadoPi[eixoMaisAlto.nome]}%), destacando ${eixoMaisAlto.textoFortaleza}.</li>
            <li>Esse resultado sugere um ponto de força relevante para o perfil comportamental do colaborador.</li>
        `;
    }

    if (gaps) {
        gaps.innerHTML = `
            <li>O eixo ${eixoMaisBaixo.label} ficou abaixo dos demais (${resultadoPi[eixoMaisBaixo.nome]}%), indicando ${eixoMaisBaixo.textoGap}.</li>
            <li>Essa oportunidade de melhoria pode ser trabalhada com foco em treinamento e feedback contínuo.</li>
        `;
    }

    if (interpretacao) {
        interpretacao.textContent = `Com base no Predictive Index, o perfil apresenta ${eixoMaisAlto.label.toLowerCase()} como principal força e ${eixoMaisBaixo.label.toLowerCase()} como maior oportunidade de evolução.`;
    }
}

function bindActions() {
    const btnDownload = document.getElementById('btnDownloadRelatorio');
    if (btnDownload) {
        btnDownload.addEventListener('click', () => exportarSecaoParaPdf('relatorioPiCard', 'relatorio-pi-parex.pdf'));
    }

    document.querySelectorAll('[data-view]').forEach((botao) => {
        botao.addEventListener('click', () => alternarVisualizacao(botao.dataset.view));
    });

    const btnDownloadGestor = document.querySelector('[data-download="true"]');
    if (btnDownloadGestor) {
        btnDownloadGestor.addEventListener('click', () => {
            const painelAtivo = document.querySelector('#viewRelatorio:not(.hidden)') ? 'viewRelatorio' : 'viewCertificado';
            const nomeArquivo = painelAtivo === 'viewRelatorio' ? 'relatorio-pi-parex.pdf' : 'certificado-parex.pdf';
            exportarSecaoParaPdf(painelAtivo, nomeArquivo);
        });
    }
}

function alternarVisualizacao(view) {
    const certificado = document.getElementById('viewCertificado');
    const relatorio = document.getElementById('viewRelatorio');

    if (!certificado || !relatorio) {
        return;
    }

    if (view === 'certificado') {
        certificado.classList.remove('hidden');
        relatorio.classList.add('hidden');
    } else {
        relatorio.classList.remove('hidden');
        certificado.classList.add('hidden');
    }
}

function exportarSecaoParaPdf(elementId, filename) {
    const elemento = document.getElementById(elementId);

    if (!elemento) {
        alert('Elemento para exportação não encontrado.');
        return;
    }

    if (window.html2pdf) {
        const options = {
            margin: [0.2, 0.2, 0.2, 0.2],
            filename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };

        window.html2pdf().set(options).from(elemento).save();
        return;
    }

    window.print();
}

document.addEventListener('DOMContentLoaded', initRelatorioPi);
