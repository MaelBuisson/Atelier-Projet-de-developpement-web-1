document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 1. GESTION DE LA NAVIGATION ET DU HEADER
    // ==========================================
    const burgerBtn = document.getElementById('burger-btn');
    const mainNav = document.getElementById('main-nav');

    // Toggle pour afficher/masquer le menu sur mobile
    if (burgerBtn && mainNav) {
        burgerBtn.addEventListener('click', () => {
            burgerBtn.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }

    // Met à jour l'affichage de la navbar selon l'état de connexion de l'utilisateur
    mettreAJourNavigation();


    // ==========================================
    // 2. GESTION DES SPECTACLES (Page d'accueil)
    // ==========================================
    const conteneurGrille = document.getElementById('spectacles');
    const selectedStyle = document.getElementById('filter-style');
    const selectedDate = document.getElementById('filter-date');

    if (conteneurGrille) {
        // Fonction asynchrone pour interroger l'API en appliquant les filtres
        async function chargerSpectacles() {
            try {
                // URLSearchParams permet de construire proprement la fin de l'URL (?style=Rock&date=...)
                const params = new URLSearchParams();

                if (selectedStyle && selectedStyle.value !== '') {
                    params.append('style', selectedStyle.value);
                }
                if (selectedDate && selectedDate.value !== '') {
                    params.append('date', selectedDate.value);
                }

                const queryString = params.toString();
                const url = queryString ? `${API}/spectacles?${queryString}` : `${API}/spectacles`;

                const reponse = await fetch(url);
                if (!reponse.ok) throw new Error(`Erreur HTTP: ${reponse.status}`);

                const jsonResult = await reponse.json();
                afficherSpectacles(jsonResult.spectacles);

            } catch (erreur) {
                console.error("Erreur lors du chargement des spectacles :", erreur);
                conteneurGrille.innerHTML = `<p style="color: white;">Désolé, impossible de charger le programme pour le moment.</p>`;
            }
        }

        // Génère et injecte le code HTML pour chaque carte de spectacle
        function afficherSpectacles(spectacles) {
            conteneurGrille.innerHTML = ''; // Vide la zone de chargement

            if (spectacles.length === 0) {
                conteneurGrille.innerHTML = `<p style="color: white;">Aucun spectacle ne correspond à vos critères.</p>`;
                return;
            }

            spectacles.forEach(spectacle => {
                // Image par défaut si l'API n'en fournit pas
                const imageUrl = spectacle.image ? spectacle.image : 'https://picsum.photos/seed/nrv-default/400/300';

                const carteHTML = `
                    <article class="spectacle-card" data-id-soiree="${spectacle.idSoiree}">
                        <div class="card-image">
                            <img src="${imageUrl}" alt="${spectacle.titre}" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-content">
                            <h2>${spectacle.titre}</h2>
                            <p>${spectacle.date} à ${spectacle.horaire}</p>
                        </div>
                    </article>
                `;
                conteneurGrille.insertAdjacentHTML('beforeend', carteHTML);
            });

            // Une fois les cartes créées, on leur attache l'événement de clic
            reactiverClicsSurCarte();
        }

        // Rend chaque carte cliquable pour rediriger vers la page de la soirée associée
        function reactiverClicsSurCarte() {
            const cartes = document.querySelectorAll('.spectacle-card');
            cartes.forEach(carte => {
                carte.addEventListener('click', () => {
                    const idSoiree = carte.getAttribute('data-id-soiree');
                    window.location.href = `soiree.html?id=${idSoiree}`;
                });
            });
        }

        // Chargement initial de tous les spectacles au démarrage
        chargerSpectacles();

        // Recharge la grille automatiquement dès qu'un filtre (select) change de valeur
        if (selectedStyle && selectedDate) {
            selectedStyle.addEventListener('change', chargerSpectacles);
            selectedDate.addEventListener('change', chargerSpectacles);
        }
    }
});


// ==========================================
// 3. FONCTION GLOBALE : MISE À JOUR NAVBAR
// ==========================================
async function mettreAJourNavigation() {
    const token = localStorage.getItem(TOKEN_KEY);
    
    const lienPanier = document.querySelector('a[href="panier.html"]');
    const lienConnexion = document.querySelector('a[href="connexion.html"]');
    const lienInscription = document.querySelector('a[href="inscription.html"]');

    // Utilisateur non connecté : état par défaut, pas de mise à jour spécifique
    if (!token) {
        if (lienPanier) lienPanier.textContent = 'Panier (0)';
        return; 
    }

    // Utilisateur connecté : on masque le bouton d'inscription
    if (lienInscription) {
        lienInscription.style.display = 'none'; 
    }

    // Transformation du bouton "Se connecter" en "Se déconnecter"
    if (lienConnexion) {
        lienConnexion.textContent = 'Se déconnecter';
        lienConnexion.href = '#';
        lienConnexion.classList.replace('btn-primary', 'btn-secondary'); 
        
        // Logique de déconnexion
        lienConnexion.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem(TOKEN_KEY); // Détruit le jeton de session
            window.location.href = 'index.html'; // Retour accueil
        });
    }

    // Interrogation de l'API pour récupérer le contenu du panier en temps réel
    try {
        const response = await fetch(`${API}/panier`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        if (response.ok) {
            const data = await response.json();
            // Calcule le nombre total de billets (ex: 2 billets normaux + 1 réduit = 3 articles)
            const nbTotalBillets = data.panier.lignes.reduce((total, ligne) => total + ligne.quantite, 0);
            
            if (lienPanier) {
                lienPanier.textContent = `Panier (${nbTotalBillets})`;
            }
        } else if (response.status === 401) {
            // Sécurité : Si l'API refuse le token (expiré), on déconnecte l'utilisateur
            localStorage.removeItem(TOKEN_KEY);
            window.location.reload(); 
        }
    } catch (erreur) {
        console.error("Erreur lors de la mise à jour du compteur panier :", erreur);
    }
}