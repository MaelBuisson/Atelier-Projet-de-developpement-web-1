document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 1. GESTION DE LA NAVIGATION ET DU HEADER
    // ==========================================
    const burgerBtn = document.getElementById('burger-btn');
    const mainNav = document.getElementById('main-nav');

    // Menu burger
    if (burgerBtn && mainNav) {
        burgerBtn.addEventListener('click', () => {
            burgerBtn.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }

    // Mise à jour de la navbar (Panier, Connexion/Inscription)
    mettreAJourNavigation();


    // ==========================================
    // 2. GESTION DES SPECTACLES (Page d'accueil)
    // ==========================================
    const conteneurGrille = document.getElementById('spectacles');
    const selectedStyle = document.getElementById('filter-style');
    const selectedDate = document.getElementById('filter-date');

    if (conteneurGrille) {
        async function chargerSpectacles() {
            try {
                const params = new URLSearchParams();

                if (selectedStyle && selectedStyle.value !== '') {
                    params.append('style', selectedStyle.value);
                }
                // CORRECTION : On vérifie bien selectedDate.value et non selectedStyle.value
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

        function afficherSpectacles(spectacles) {
            conteneurGrille.innerHTML = '';

            if (spectacles.length === 0) {
                conteneurGrille.innerHTML = `<p style="color: white;">Aucun spectacle ne correspond à vos critères.</p>`;
                return;
            }

            spectacles.forEach(spectacle => {
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

            reactiverClicsSurCarte();
        }

        function reactiverClicsSurCarte() {
            const cartes = document.querySelectorAll('.spectacle-card');
            cartes.forEach(carte => {
                carte.addEventListener('click', () => {
                    const idSoiree = carte.getAttribute('data-id-soiree');
                    window.location.href = `soiree.html?id=${idSoiree}`;
                });
            });
        }

        // Chargement initial
        chargerSpectacles();

        // Écoute des changements sur les filtres
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

    if (!token) {
        if (lienPanier) lienPanier.textContent = 'Panier (0)';
        return; 
    }

    if (lienInscription) {
        lienInscription.style.display = 'none'; 
    }

    if (lienConnexion) {
        lienConnexion.textContent = 'Se déconnecter';
        lienConnexion.href = '#';
        lienConnexion.classList.replace('btn-primary', 'btn-secondary'); 
        
        lienConnexion.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem(TOKEN_KEY);
            window.location.href = 'index.html'; 
        });
    }

    try {
        const response = await fetch(`${API}/panier`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        if (response.ok) {
            const data = await response.json();
            const nbTotalBillets = data.panier.lignes.reduce((total, ligne) => total + ligne.quantite, 0);
            
            if (lienPanier) {
                lienPanier.textContent = `Panier (${nbTotalBillets})`;
            }
        } else if (response.status === 401) {
            localStorage.removeItem(TOKEN_KEY);
            window.location.reload(); 
        }
    } catch (erreur) {
        console.error("Erreur lors de la mise à jour du compteur panier :", erreur);
    }
}