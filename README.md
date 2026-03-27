# 🚗 DriverElite - Motorista Particular Premium

Plataforma completa (Landing Page + API) para serviço de motorista particular e transporte executivo. Permite que os clientes calculem a estimativa de valor da viagem com base na distância e escolham o veículo ideal, finalizando o agendamento de forma rápida e direta via WhatsApp.

## 🌟 Funcionalidades

- **Calculadora de Viagens:** Estimativa instantânea de valor baseada no trajeto (origem/destino) e categoria do veículo escolhido.
- **Catálogo de Veículos:** Exibição dinâmica de opções (Sedan Executivo, SUV, Van) consumidas da API.
- **Agendamento Inteligente:** Formulário de reserva que redireciona o cliente para o WhatsApp do motorista com uma mensagem formatada contendo todos os detalhes da corrida.
- **Painel Administrativo / BD:** Estrutura pronta em banco de dados para armazenar histórico de agendamentos e gerenciar o valor por KM rodado de cada veículo.

## 🛠️ Tecnologias Utilizadas

O projeto foi construído utilizando uma arquitetura moderna e separada (Frontend, Backend e Proxy), orquestrada em containers:

**Frontend**
- HTML5 Semântico e CSS3 Moderno (Glassmorphism, Animações)
- JavaScript Vanilla (Consumo de API e Manipulação de DOM)
- Fonts do Google (Inter e Outfit)

**Backend**
- PHP 8.3+ Puro (Estrutura PSR-4, sem frameworks pesados)
- API RESTful para cálculo de rotas e listagem de veículos

**Banco de Dados**
- MariaDB 10.11 (Scripts DDL e DML automatizados)

**Infraestrutura e DevOps**
- Docker e Docker Compose
- NGINX (Proxy reverso servindo o Frontend e roteando a API)

## 🚀 Como Executar Localmente

Certifique-se de ter o Docker e o Docker Compose instalados na sua máquina.

1. Clone o repositório:
   ```bash
   git clone https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git
   ```

2. Configure as variáveis de ambiente baseando-se no arquivo de exemplo:
   ```bash
   cp .env.example .env
   ```
   *(Abra o `.env` e configure as senhas do banco de dados)*

3. Suba os containers com o Docker Compose:
   ```bash
   docker-compose up -d --build
   ```

4. Acesse a aplicação no seu navegador:
   - **Frontend:** http://localhost:8080 (Ou a porta definida em `APP_PORT`)

## 📝 Licença

Este projeto é de uso pessoal e não possui licença de código aberto definida no momento.
