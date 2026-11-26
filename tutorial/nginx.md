# Proxy reverso Nginx

O [Nginx](http://nginx.org/) é um servidor `HTTP` e proxy reverso de alto desempenho, implementado inteiramente em `C`. Graças ao seu alto desempenho e a diversas vantagens, podemos configurá-lo como servidor front-end do `hyperf`, para implementar balanceamento de carga ou como servidor front-end HTTPS, entre outros.

## Configurar o proxy HTTP

```nginx
# É necessário pelo menos um nó Hyperf; para vários, configure várias linhas
upstream hyperf {
    # IP e porta do Hyperf HTTP Server
    server 127.0.0.1:9501;
    server 127.0.0.1:9502;
}

server {
    # Porta de escuta
    listen 80; 
    # Domínio vinculado; preencha com o seu domínio
    server_name proxy.hyperf.io;

    location / {
        # Encaminha as informações de Host e IP do cliente para o nó correspondente  
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        
        # Encaminha o Cookie e define SameSite
        proxy_cookie_path / "/; secure; HttpOnly; SameSite=strict";
        
        # Executa o proxy acessando o servidor real
        proxy_pass http://hyperf;
    }
}
```

## Configurar o proxy WebSocket

```nginx
# É necessário pelo menos um nó Hyperf; para vários, configure várias linhas
upstream hyperf_websocket {
    # Define o modo de balanceamento de carga como o algoritmo IP Hash, assim clientes diferentes sempre interagem com o mesmo nó a cada requisição
    ip_hash;
    # IP e porta do Hyperf WebSocket Server
    server 127.0.0.1:9503;
    server 127.0.0.1:9504;
}

server {
    listen 80;
    server_name websocket.hyperf.io;
    
    location / {
        # WebSocket Header
        proxy_http_version 1.1;
        proxy_set_header Upgrade websocket;
        proxy_set_header Connection "Upgrade";
        
        # Encaminha as informações de Host e IP do cliente para o nó correspondente  
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;
    
        # A conexão é encerrada automaticamente após 60s sem interação entre cliente e servidor; ajuste conforme o cenário real de negócio
        proxy_read_timeout 60s ;
        
        # Executa o proxy acessando o servidor real
        proxy_pass http://hyperf_websocket;
    }
}
```
