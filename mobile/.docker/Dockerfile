FROM node:22-alpine

# Instala dependências essenciais e o CLI do Ionic globalmente
# Usar node:22-alpine ajuda a reduzir vulnerabilidades comparado a versões mais antigas
RUN apk update && apk upgrade && \
    apk add --no-cache bash curl git \
    && npm install -g @ionic/cli

WORKDIR /app

# Copia os arquivos de definição de pacotes primeiro para aproveitar o cache do Docker
COPY package*.json ./

# Cria diretórios necessários e garante permissões para o usuário node ANTES do npm install
RUN mkdir -p /app/node_modules /app/.ionic && \
    chown -R node:node /app

USER node

# Instala as dependências do projeto
RUN npm install

# Porta padrão do Ionic
EXPOSE 8100

# Inicia o servidor de desenvolvimento do Ionic na porta 8100
CMD ["ionic", "serve", "--host", "0.0.0.0", "--port", "8100", "--no-open"]
