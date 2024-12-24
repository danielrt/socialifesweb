BEGIN;

CREATE TABLE public.usuario
(
    login text NOT NULL,
    token text NOT NULL,
    nome text NOT NULL,
    data_nascimento date NOT NULL,
    cidade text NOT NULL,
    foto text NOT NULL,
    PRIMARY KEY (login)
);

CREATE TABLE public.post
(
    id serial NOT NULL,
    data_hora timestamp without time zone NOT NULL,
    texto text,
    imagem text,
    usuario_login text NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT check_texto_ou_imagem CHECK (texto IS NOT NULL OR imagem IS NOT NULL),
    CONSTRAINT fk_post_usuario FOREIGN KEY (usuario_login) REFERENCES public.usuario (login) ON DELETE CASCADE
);

CREATE TABLE public.comentario
(
    id serial NOT NULL,
    texto text NOT NULL,
    data_hora timestamp without time zone NOT NULL,
    post_id integer NOT NULL,
    usuario_login text NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_comentario_usuario FOREIGN KEY (usuario_login) REFERENCES public.usuario (login) ON DELETE CASCADE,
    CONSTRAINT fk_comentario_post FOREIGN KEY (post_id) REFERENCES public.post (id) ON DELETE CASCADE
);

CREATE TABLE public.seguindo
(
    id serial NOT NULL,
    usuario_login text NOT NULL,
    usuario_login_seguindo text NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_seguindo_usuario FOREIGN KEY (usuario_login) REFERENCES public.usuario (login) ON DELETE CASCADE,
    CONSTRAINT fk_seguindo_seguindo FOREIGN KEY (usuario_login_seguindo) REFERENCES public.usuario (login) ON DELETE CASCADE
);

CREATE TABLE public.curtida
(
    id serial NOT NULL,
    post_id integer NOT NULL,
    usuario_login text NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT unique_curtida UNIQUE (post_id, usuario_login),
    CONSTRAINT fk_curtida_usuario FOREIGN KEY (usuario_login) REFERENCES public.usuario (login) ON DELETE CASCADE,
    CONSTRAINT fk_curtida_post FOREIGN KEY (post_id) REFERENCES public.post (id) ON DELETE CASCADE
);

CREATE TABLE public.notificacao
(
    id serial NOT NULL,
    nova boolean NOT NULL,
    data_hora timestamp without time zone NOT NULL,
    usuario_login text NOT NULL,
    usuario_login_alvo text NOT NULL,
    acao integer NOT NULL,
    post_id integer,
    PRIMARY KEY (id),
    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (usuario_login) REFERENCES public.usuario (login) ON DELETE CASCADE,
    CONSTRAINT fk_notificacao_usuario_alvo FOREIGN KEY (usuario_login_alvo) REFERENCES public.usuario (login) ON DELETE CASCADE,
    CONSTRAINT fk_notificacao_post FOREIGN KEY (post_id) REFERENCES public.post (id) ON DELETE CASCADE
);

END;
