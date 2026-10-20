INSERT INTO Tarif (nom, prix) VALUES ('normal', 20.00), ('reduit', 12.00);

-- Données de test NRV

INSERT INTO soiree (id_soiree, nom, thematique, date, horaire, lieu, nb_place) VALUES
('11111111-1111-1111-1111-111111111111', 'Soirée Blues',  'Blues',  '2026-07-10', '20:00', 'Parc de la Pépinière', 500),
('22222222-2222-2222-2222-222222222222', 'Soirée Métal',  'Métal',  '2026-07-12', '19:30', 'Zénith de Nancy',      800),
('33333333-3333-3333-3333-333333333333', 'Soirée Reggae', 'Reggae', '2026-07-15', '20:00', 'Place Stanislas',      600);

INSERT INTO spectacle (id_spectacle, titre, description, horaire, video, style_musique, id_soiree) VALUES
('aaaaaaaa-0000-0000-0000-000000000001', 'Blues Brothers Tribute', 'Hommage aux Blues Brothers', '20:30', 'https://www.youtube.com/watch?v=xxx', 'blues rock', '11111111-1111-1111-1111-111111111111'),
('aaaaaaaa-0000-0000-0000-000000000002', 'Slow Blues Band',        'Blues lent et émouvant',     '22:00', 'https://www.youtube.com/watch?v=yyy', 'blues rock', '11111111-1111-1111-1111-111111111111'),
('aaaaaaaa-0000-0000-0000-000000000003', 'Iron Thunder',           'Métal puissant',             '20:00', 'https://www.youtube.com/watch?v=zzz', 'métal',      '22222222-2222-2222-2222-222222222222'),
('aaaaaaaa-0000-0000-0000-000000000004', 'Dark Riders',            'Métal sombre et rapide',     '21:30', 'https://www.youtube.com/watch?v=www', 'métal',      '22222222-2222-2222-2222-222222222222'),
('aaaaaaaa-0000-0000-0000-000000000005', 'Sunset Vibes',           'Reggae ensoleillé',          '20:30', 'https://www.youtube.com/watch?v=vvv', 'reggae',     '33333333-3333-3333-3333-333333333333');

INSERT INTO image (id_image, url, id_spectacle) VALUES
('bbbbbbbb-0000-0000-0000-000000000001', 'https://picsum.photos/400/300?1', 'aaaaaaaa-0000-0000-0000-000000000001'),
('bbbbbbbb-0000-0000-0000-000000000002', 'https://picsum.photos/400/300?2', 'aaaaaaaa-0000-0000-0000-000000000002'),
('bbbbbbbb-0000-0000-0000-000000000003', 'https://picsum.photos/400/300?3', 'aaaaaaaa-0000-0000-0000-000000000003'),
('bbbbbbbb-0000-0000-0000-000000000004', 'https://picsum.photos/400/300?4', 'aaaaaaaa-0000-0000-0000-000000000004'),
('bbbbbbbb-0000-0000-0000-000000000005', 'https://picsum.photos/400/300?5', 'aaaaaaaa-0000-0000-0000-000000000005'),
('bbbbbbbb-0000-0000-0000-000000000006', 'https://picsum.photos/400/300?6', 'aaaaaaaa-0000-0000-0000-000000000005');

INSERT INTO artiste (id_artiste, nom, prenom) VALUES
('cccccccc-0000-0000-0000-000000000001', 'Dupont', 'Jean'),
('cccccccc-0000-0000-0000-000000000002', 'Martin', 'Sophie'),
('cccccccc-0000-0000-0000-000000000003', 'Moreau', 'Luc');

INSERT INTO jouer_dans_spectacle (id_spectacle, id_artiste) VALUES
('aaaaaaaa-0000-0000-0000-000000000001', 'cccccccc-0000-0000-0000-000000000001'),
('aaaaaaaa-0000-0000-0000-000000000002', 'cccccccc-0000-0000-0000-000000000002'),
('aaaaaaaa-0000-0000-0000-000000000003', 'cccccccc-0000-0000-0000-000000000003'),
('aaaaaaaa-0000-0000-0000-000000000005', 'cccccccc-0000-0000-0000-000000000001'),
('aaaaaaaa-0000-0000-0000-000000000005', 'cccccccc-0000-0000-0000-000000000002');
