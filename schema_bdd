-- ==========================================================
-- Base de données application métier ATE (Checking Vol)
-- ==========================================================

CREATE TABLE public.modele (
    id character varying(25) PRIMARY KEY,
    poids integer NOT NULL,
    capacite integer NOT NULL,
    rayon integer NOT NULL
);

CREATE TABLE public.avion (
    matricule SERIAL PRIMARY KEY,
    idmodele character varying(25) NOT NULL REFERENCES public.modele(id)
);

CREATE TABLE public.pilote (
    id SERIAL PRIMARY KEY,
    nom character varying(40) NOT NULL,
    adresse character varying(40) NOT NULL,
    telephone character varying(10) NOT NULL,
    salaire integer NOT NULL,
    visite date NOT NULL
);

CREATE TABLE public.technicien (
    id SERIAL PRIMARY KEY,
    nom character varying(40) NOT NULL,
    adresse character varying(40) NOT NULL,
    telephone character varying(10) NOT NULL,
    salaire integer NOT NULL
);

CREATE TABLE public.test (
    id SERIAL PRIMARY KEY,
    nom character varying(40) NOT NULL,
    seuil integer NOT NULL
);

CREATE TABLE public.vol (
    id SERIAL PRIMARY KEY,
    depart character varying(25) NOT NULL,
    arrivee character varying(25) NOT NULL,
    horaire timestamp without time zone NOT NULL,
    idavion integer NOT NULL REFERENCES public.avion(matricule),
    idpilote integer NOT NULL REFERENCES public.pilote(id)
);

CREATE TABLE public.essai (
    id SERIAL PRIMARY KEY,
    idtest integer REFERENCES public.test(id),
    idavion integer REFERENCES public.avion(matricule),
    idtechnicien integer REFERENCES public.technicien(id),
    dateessai date NOT NULL,
    note integer NOT NULL
);

CREATE TABLE public.expertise (
    idtechnicien integer NOT NULL REFERENCES public.technicien(id),
    idmodele character varying(25) NOT NULL REFERENCES public.modele(id),
    PRIMARY KEY (idtechnicien, idmodele)
);

CREATE TABLE public.aeroports (
    code character varying(3) PRIMARY KEY,
    nom_aeroport character varying(100) NOT NULL,
    latitude numeric(9,6),
    longitude numeric(9,6)
);

-- ==========================================================
-- Tables ajoutées pour l'application ATE (checking vol)
-- ==========================================================

CREATE TABLE public.agent_trafic (
    id SERIAL PRIMARY KEY,
    nom character varying(80) NOT NULL,
    badge character varying(30) UNIQUE NOT NULL,
    telephone character varying(20)
);

INSERT INTO public.agent_trafic (nom, badge, telephone) VALUES
('Amina Diallo',      'ATF-001', '+33 2 12 34 56 01'),
('Yanis Benali',      'ATF-002', '+33 2 12 34 56 02'),
('Clara Moreau',      'ATF-003', '+33 2 12 34 56 03'),
('Moussa Traoré',     'ATF-004', '+33 2 12 34 56 04'),
('Inès Lahcene',      'ATF-005', '+33 2 12 34 56 05'),
('Thomas Nguyen',     'ATF-006', '+33 2 12 34 56 06'),
('Sara El Mansouri',  'ATF-007', '+33 2 12 34 56 07'),
('Lucas Pereira',     'ATF-008', '+33 2 12 34 56 08'),
('Mariam Coulibaly',  'ATF-009', '+33 2 12 34 56 09'),
('Hugo Bernard',      'ATF-010', '+33 2 12 34 56 10');

CREATE TABLE public.check_vol (
    id SERIAL PRIMARY KEY,
    idvol integer NOT NULL REFERENCES public.vol(id) ON DELETE CASCADE,
    idagent integer NOT NULL REFERENCES public.agent_trafic(id) ON DELETE RESTRICT,
    date_check timestamp NOT NULL DEFAULT NOW(),
    statut character varying(15) NOT NULL CHECK (statut IN ('OK', 'KO', 'EN_ATTENTE')),
    commentaire text
);

CREATE INDEX idx_check_vol_idvol ON public.check_vol(idvol);
CREATE INDEX idx_check_vol_date ON public.check_vol(date_check);
