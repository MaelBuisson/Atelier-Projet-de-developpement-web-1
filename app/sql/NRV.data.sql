-- Données de test du festival NRV (Nancy Rock Vibration), 3 jours : du 8 au 10 juillet 2027
-- Les identifiants sont écrits à la main pour pouvoir les réutiliser dans les clés étrangères :
--   10000000-…  soirées
--   20000000-…  spectacles
--   30000000-…  artistes
-- Les artistes et groupes sont fictifs. Images : picsum.photos (une image fixe par « seed »).
-- Vidéos : chemins relatifs au client web, fichiers à ajouter dans web/videos/.

-- ============================================================
-- Soirées : 2 par jour, sur 3 lieux
-- ============================================================
INSERT INTO soiree (id_soiree, nom, thematique, date, horaire, lieu, nb_place) VALUES
    ('10000000-0000-4000-8000-000000000001', 'Soirée d''Ouverture : Légende du Rock', 'Rock classique', '2027-07-08', '20:00', 'Parc de la Pépinière', 2000),
    ('10000000-0000-4000-8000-000000000002', 'Blues & Soul Night', 'Blues et soul', '2027-07-08', '19:30', 'Salle Poirel', 600),
    ('10000000-0000-4000-8000-000000000003', 'Nuit Métal', 'Métal et hard rock', '2027-07-09', '20:30', 'L''Autre Canal', 1200),
    ('10000000-0000-4000-8000-000000000004', 'Scène Découverte', 'Jeunes talents du Grand Est', '2027-07-09', '18:00', 'Salle Poirel', 600),
    ('10000000-0000-4000-8000-000000000005', 'Punk Not Dead', 'Punk', '2027-07-10', '20:00', 'L''Autre Canal', 1200),
    ('10000000-0000-4000-8000-000000000006', 'Clôture : Rock Indé & Électro', 'Rock indépendant et électro', '2027-07-10', '21:00', 'Parc de la Pépinière', 2000);

-- ============================================================
-- Tarifs : un tarif normal et un tarif réduit par soirée
-- ============================================================
INSERT INTO tarif (id_soiree, nom, prix) VALUES
    ('10000000-0000-4000-8000-000000000001', 'normal', 35.00),
    ('10000000-0000-4000-8000-000000000001', 'reduit', 25.00),
    ('10000000-0000-4000-8000-000000000002', 'normal', 25.00),
    ('10000000-0000-4000-8000-000000000002', 'reduit', 18.00),
    ('10000000-0000-4000-8000-000000000003', 'normal', 30.00),
    ('10000000-0000-4000-8000-000000000003', 'reduit', 22.00),
    ('10000000-0000-4000-8000-000000000004', 'normal', 15.00),
    ('10000000-0000-4000-8000-000000000004', 'reduit', 10.00),
    ('10000000-0000-4000-8000-000000000005', 'normal', 28.00),
    ('10000000-0000-4000-8000-000000000005', 'reduit', 20.00),
    ('10000000-0000-4000-8000-000000000006', 'normal', 35.00),
    ('10000000-0000-4000-8000-000000000006', 'reduit', 25.00);

-- ============================================================
-- Spectacles : 2 ou 3 par soirée, styles variés pour le filtrage
-- ============================================================
INSERT INTO spectacle (id_spectacle, titre, description, horaire, video, style_musique, id_soiree) VALUES
    -- Soirée d'ouverture
    ('20000000-0000-4000-8000-000000000001', 'Rolling Thunder', 'Un hommage électrique aux grands noms du rock des années 70, guitares saturées et refrains repris par tout le public.', '20:00', 'videos/rolling-thunder.mp4', 'Classic Rock', '10000000-0000-4000-8000-000000000001'),
    ('20000000-0000-4000-8000-000000000002', 'Zeppelin Experience', 'Riffs lourds, solos interminables et une voix qui monte dans les aigus : le hard rock comme à ses débuts.', '21:30', 'videos/zeppelin-experience.mp4', 'Hard Rock', '10000000-0000-4000-8000-000000000001'),
    -- Blues & Soul
    ('20000000-0000-4000-8000-000000000003', 'Delta Road', 'Un trio venu du Mississippi revisité depuis Nancy : slide guitar, harmonica et histoires de route.', '19:30', 'videos/delta-road.mp4', 'Blues', '10000000-0000-4000-8000-000000000002'),
    ('20000000-0000-4000-8000-000000000004', 'Marie Soulier & The Brass', 'Une voix soul puissante accompagnée d''une section de cuivres de huit musiciens.', '21:00', NULL, 'Soul', '10000000-0000-4000-8000-000000000002'),
    -- Nuit métal
    ('20000000-0000-4000-8000-000000000005', 'Iron Lorraine', 'Le groupe de métal le plus bruyant de Lorraine, double pédale et pyrotechnie.', '20:30', 'videos/iron-lorraine.mp4', 'Metal', '10000000-0000-4000-8000-000000000003'),
    ('20000000-0000-4000-8000-000000000006', 'Black Moselle', 'Un doom métal lent et massif, inspiré des vallées industrielles.', '22:00', 'videos/black-moselle.mp4', 'Metal', '10000000-0000-4000-8000-000000000003'),
    ('20000000-0000-4000-8000-000000000007', 'Thunderstruck Nancy', 'Hard rock australien revisité, l''énergie brute des grandes tournées des années 80.', '23:15', NULL, 'Hard Rock', '10000000-0000-4000-8000-000000000003'),
    -- Scène découverte
    ('20000000-0000-4000-8000-000000000008', 'Les Garçons du Faubourg', 'Quatre lycéens de Vandœuvre qui jouent un rock garage sans compromis.', '18:00', 'videos/garcons-du-faubourg.mp4', 'Rock Garage', '10000000-0000-4000-8000-000000000004'),
    ('20000000-0000-4000-8000-000000000009', 'Léa Mirabelle', 'Folk acoustique et textes en français, entre balade et chanson engagée.', '19:15', NULL, 'Folk', '10000000-0000-4000-8000-000000000004'),
    -- Punk
    ('20000000-0000-4000-8000-000000000010', 'Les Chiens Fous', 'Trois accords, deux minutes par morceau et un public qui ne reste jamais immobile.', '20:00', 'videos/chiens-fous.mp4', 'Punk', '10000000-0000-4000-8000-000000000005'),
    ('20000000-0000-4000-8000-000000000011', 'Stan Riot', 'Un punk rock mélodique aux refrains faits pour être chantés à tue-tête.', '21:30', 'videos/stan-riot.mp4', 'Punk Rock', '10000000-0000-4000-8000-000000000005'),
    -- Clôture
    ('20000000-0000-4000-8000-000000000012', 'Neon Kanal', 'Guitares indé et synthétiseurs analogiques pour une fin de festival dansante.', '21:00', 'videos/neon-kanal.mp4', 'Rock Indé', '10000000-0000-4000-8000-000000000006'),
    ('20000000-0000-4000-8000-000000000013', 'Pulse Machine', 'Électro rock massif, lumières stroboscopiques et basses qui font vibrer le parc.', '22:30', 'videos/pulse-machine.mp4', 'Électro Rock', '10000000-0000-4000-8000-000000000006'),
    ('20000000-0000-4000-8000-000000000014', 'Rolling Thunder (rappel)', 'Le groupe d''ouverture revient clôturer le festival avec ses plus grands titres.', '23:45', NULL, 'Classic Rock', '10000000-0000-4000-8000-000000000006');

-- ============================================================
-- Images : une ou deux par spectacle, aucune pour certains (cas « sans image »)
-- ============================================================
INSERT INTO image (url, id_spectacle) VALUES
    ('https://picsum.photos/seed/nrv-rolling-thunder-1/400/300', '20000000-0000-4000-8000-000000000001'),
    ('https://picsum.photos/seed/nrv-rolling-thunder-2/400/300', '20000000-0000-4000-8000-000000000001'),
    ('https://picsum.photos/seed/nrv-zeppelin-experience/400/300', '20000000-0000-4000-8000-000000000002'),
    ('https://picsum.photos/seed/nrv-delta-road/400/300', '20000000-0000-4000-8000-000000000003'),
    ('https://picsum.photos/seed/nrv-marie-soulier/400/300', '20000000-0000-4000-8000-000000000004'),
    ('https://picsum.photos/seed/nrv-iron-lorraine-1/400/300', '20000000-0000-4000-8000-000000000005'),
    ('https://picsum.photos/seed/nrv-iron-lorraine-2/400/300', '20000000-0000-4000-8000-000000000005'),
    ('https://picsum.photos/seed/nrv-black-moselle/400/300', '20000000-0000-4000-8000-000000000006'),
    ('https://picsum.photos/seed/nrv-garcons-du-faubourg/400/300', '20000000-0000-4000-8000-000000000008'),
    ('https://picsum.photos/seed/nrv-lea-mirabelle/400/300', '20000000-0000-4000-8000-000000000009'),
    ('https://picsum.photos/seed/nrv-chiens-fous/400/300', '20000000-0000-4000-8000-000000000010'),
    ('https://picsum.photos/seed/nrv-stan-riot/400/300', '20000000-0000-4000-8000-000000000011'),
    ('https://picsum.photos/seed/nrv-neon-kanal/400/300', '20000000-0000-4000-8000-000000000012'),
    ('https://picsum.photos/seed/nrv-pulse-machine/400/300', '20000000-0000-4000-8000-000000000013');

-- ============================================================
-- Artistes : groupes (prénom NULL) et artistes solo (avec prénom)
-- ============================================================
INSERT INTO artiste (id_artiste, nom, prenom) VALUES
    ('30000000-0000-4000-8000-000000000001', 'Rolling Thunder', NULL),
    ('30000000-0000-4000-8000-000000000002', 'Zeppelin Experience', NULL),
    ('30000000-0000-4000-8000-000000000003', 'Delta Road', NULL),
    ('30000000-0000-4000-8000-000000000004', 'Soulier', 'Marie'),
    ('30000000-0000-4000-8000-000000000005', 'The Brass', NULL),
    ('30000000-0000-4000-8000-000000000006', 'Iron Lorraine', NULL),
    ('30000000-0000-4000-8000-000000000007', 'Black Moselle', NULL),
    ('30000000-0000-4000-8000-000000000008', 'Thunderstruck Nancy', NULL),
    ('30000000-0000-4000-8000-000000000009', 'Les Garçons du Faubourg', NULL),
    ('30000000-0000-4000-8000-000000000010', 'Mirabelle', 'Léa'),
    ('30000000-0000-4000-8000-000000000011', 'Les Chiens Fous', NULL),
    ('30000000-0000-4000-8000-000000000012', 'Stan Riot', NULL),
    ('30000000-0000-4000-8000-000000000013', 'Neon Kanal', NULL),
    ('30000000-0000-4000-8000-000000000014', 'Pulse Machine', NULL),
    ('30000000-0000-4000-8000-000000000015', 'Martin', 'Hugo');

-- ============================================================
-- Qui joue dans quel spectacle
-- Hugo Martin (guitariste invité) joue dans 3 spectacles : un seul objet Artiste partagé
-- Rolling Thunder joue à l'ouverture et à la clôture
-- ============================================================
INSERT INTO jouer_dans_spectacle (id_spectacle, id_artiste) VALUES
    ('20000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001'),
    ('20000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000015'),
    ('20000000-0000-4000-8000-000000000002', '30000000-0000-4000-8000-000000000002'),
    ('20000000-0000-4000-8000-000000000002', '30000000-0000-4000-8000-000000000015'),
    ('20000000-0000-4000-8000-000000000003', '30000000-0000-4000-8000-000000000003'),
    ('20000000-0000-4000-8000-000000000004', '30000000-0000-4000-8000-000000000004'),
    ('20000000-0000-4000-8000-000000000004', '30000000-0000-4000-8000-000000000005'),
    ('20000000-0000-4000-8000-000000000005', '30000000-0000-4000-8000-000000000006'),
    ('20000000-0000-4000-8000-000000000006', '30000000-0000-4000-8000-000000000007'),
    ('20000000-0000-4000-8000-000000000007', '30000000-0000-4000-8000-000000000008'),
    ('20000000-0000-4000-8000-000000000008', '30000000-0000-4000-8000-000000000009'),
    ('20000000-0000-4000-8000-000000000009', '30000000-0000-4000-8000-000000000010'),
    ('20000000-0000-4000-8000-000000000010', '30000000-0000-4000-8000-000000000011'),
    ('20000000-0000-4000-8000-000000000011', '30000000-0000-4000-8000-000000000012'),
    ('20000000-0000-4000-8000-000000000012', '30000000-0000-4000-8000-000000000013'),
    ('20000000-0000-4000-8000-000000000013', '30000000-0000-4000-8000-000000000014'),
    ('20000000-0000-4000-8000-000000000014', '30000000-0000-4000-8000-000000000001'),
    ('20000000-0000-4000-8000-000000000014', '30000000-0000-4000-8000-000000000015');
