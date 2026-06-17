# Documentação — Sistema Biblioteca

## Introdução

O Sistema de Bibliotecas compreende a automatização de uma determinada Biblioteca, onde é possível cadastrar livros, autores, estoque e categorias. O sistema visa aumentar a praticidade desses registros. O objetivo é sistematizar um processo que já existia manualmente, para o digital.

---

## Funcionalidades

O sistema conta com quatro módulos principais, acessíveis pela tela inicial:

- **Cadastrar Categoria** — permite registrar as categorias dos livros
- **Autor** — cadastro e gerenciamento de autores
- **Estoque** — controle da quantidade de livros disponíveis
- **Livro** — cadastro completo dos livros do acervo

---

## Idealização e Explicação do Código

### `Main.java`

É o executável principal do Sistema. Ele cria uma classe `main` do Java, um objeto `tela` através do construtor `Display`, e define a tela como visível.

```java
// Ponto de entrada do sistema
public class Main {
    public static void main(String[] args) {
        Display tela = new Display();
        tela.setVisible(true);
    }
}
```

---

## Tecnologias Utilizadas

- **Linguagem:** Java
- **Interface:** Java Swing (Desktop)
- **Paradigma:** Orientado a Objetos

---

## Requisitos

### Requisitos Funcionais

- [x]  Cadastrar categorias de livros
- [x]  Cadastrar autores
- [x]  Cadastrar livros com vínculo a autor e categoria
- [x]  Controlar estoque de livros

### Requisitos Não Funcionais

- [x]  Interface gráfica intuitiva
- [x]  Sistema desktop executável em Windows

---

## Equipe

> Matheus Gustavo
> 

> Thales Henrique
> 

---

## Observações

> *Atualizações do Sistema em Breve 17/06/2026.*
>