// Funções gerais
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Fechar alertas automaticamente após 5 segundos
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Adicionar classe active ao link da página atual
    var currentPath = window.location.pathname;
    var navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(function(link) {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});

// Função para formatar data
function formatarData(data) {
    const options = { 
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return new Date(data).toLocaleDateString('pt-BR', options);
}

// Função para formatar hora
function formatarHora(hora) {
    return hora.substring(0, 5);
}

// Função para formatar preço
function formatarPreco(preco) {
    return preco.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

// Função para validar email
function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Função para validar senha
function validarSenha(senha) {
    return senha.length >= 6;
}

// Função para mostrar mensagem de erro
function mostrarErro(elemento, mensagem) {
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = mensagem;
    
    elemento.classList.add('is-invalid');
    elemento.parentNode.appendChild(feedback);
}

// Função para remover mensagem de erro
function removerErro(elemento) {
    elemento.classList.remove('is-invalid');
    const feedback = elemento.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.remove();
    }
}

// Função para confirmar ação
function confirmarAcao(mensagem) {
    return confirm(mensagem);
}

// Função para redirecionar após um tempo
function redirecionar(url, tempo) {
    setTimeout(function() {
        window.location.href = url;
    }, tempo);
}

// Função para copiar texto para a área de transferência
function copiarTexto(texto) {
    navigator.clipboard.writeText(texto).then(function() {
        alert('Texto copiado para a área de transferência!');
    }).catch(function(err) {
        console.error('Erro ao copiar texto: ', err);
    });
}

// Função para alternar visibilidade de senha
function alternarVisibilidadeSenha(inputId) {
    const input = document.getElementById(inputId);
    const tipo = input.type === 'password' ? 'text' : 'password';
    input.type = tipo;
}

// Função para verificar força da senha
function verificarForcaSenha(senha) {
    let forca = 0;
    
    if (senha.length >= 8) forca++;
    if (senha.match(/[a-z]/)) forca++;
    if (senha.match(/[A-Z]/)) forca++;
    if (senha.match(/[0-9]/)) forca++;
    if (senha.match(/[^a-zA-Z0-9]/)) forca++;
    
    return forca;
}

// Função para mostrar indicador de força da senha
function mostrarForcaSenha(senha) {
    const forca = verificarForcaSenha(senha);
    const indicador = document.getElementById('indicador-forca-senha');
    
    if (!indicador) return;
    
    let texto = '';
    let classe = '';
    
    switch(forca) {
        case 0:
        case 1:
            texto = 'Muito Fraca';
            classe = 'text-danger';
            break;
        case 2:
            texto = 'Fraca';
            classe = 'text-warning';
            break;
        case 3:
            texto = 'Média';
            classe = 'text-info';
            break;
        case 4:
            texto = 'Forte';
            classe = 'text-primary';
            break;
        case 5:
            texto = 'Muito Forte';
            classe = 'text-success';
            break;
    }
    
    indicador.textContent = texto;
    indicador.className = classe;
}

// Função para formatar o telefone
function formatarTelefone(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length > 11) valor = valor.slice(0, 11);
    
    if (valor.length > 2) {
        valor = '(' + valor.slice(0, 2) + ') ' + valor.slice(2);
    }
    if (valor.length > 10) {
        valor = valor.slice(0, 10) + '-' + valor.slice(10);
    }
    
    input.value = valor;
}


document.addEventListener('DOMContentLoaded', function() {
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function() {
            formatarTelefone(this);
        });
    }
});

function validarAgendamento() {
    const servico = document.getElementById('servico_id').value;
    const data = document.getElementById('data').value;
    const hora = document.getElementById('hora').value;

    if (!servico || !data || !hora) {
        alert('Por favor, preencha todos os campos.');
        return false;
    }

    const dataSelecionada = new Date(data);
    const dataAtual = new Date();
    dataAtual.setHours(0, 0, 0, 0);

    if (dataSelecionada < dataAtual) {
        alert('A data selecionada não pode ser anterior à data atual.');
        return false;
    }

    const diaSemana = dataSelecionada.getDay();
    if (diaSemana === 0) {
        alert('Não é possível agendar aos domingos.');
        return false;
    }

    const dataMaxima = new Date();
    dataMaxima.setDate(dataMaxima.getDate() + 30);
    if (dataSelecionada > dataMaxima) {
        alert('A data selecionada não pode ser posterior a 30 dias.');
        return false;
    }

    return true;
}

const formAgendamento = document.getElementById('formAgendamento');
if (formAgendamento) {
    formAgendamento.addEventListener('submit', function(e) {
        if (!validarAgendamento()) {
            e.preventDefault();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const senhaInputs = document.querySelectorAll('input[type="password"]');
    senhaInputs.forEach(input => {
        const wrapper = input.parentElement;
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        toggleButton.onclick = function() {
            toggleSenha(input.id);
            this.innerHTML = input.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        };
        wrapper.style.position = 'relative';
        wrapper.appendChild(toggleButton);
    });
});

function adicionarFadeIn() {
    const elementos = document.querySelectorAll('.fade-in');
    elementos.forEach(elemento => {
        elemento.style.opacity = '0';
        elemento.style.transform = 'translateY(20px)';
        elemento.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    elementos.forEach(elemento => observer.observe(elemento));
}

document.addEventListener('DOMContentLoaded', adicionarFadeIn); 