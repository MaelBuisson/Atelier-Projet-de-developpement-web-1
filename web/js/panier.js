// Page panier.html : affiche le panier de l'utilisateur connecté (GET /panier)

const listeBillets = document.getElementById('liste-billets');
const totalPrix = document.getElementById('total-prix');
const message = document.getElementById('message-panier');
const btnValider = document.getElementById('btn-valider');
const btnVider = document.getElementById('btn-vider'); // Ajout du bouton vider

let lignesActuelles = []; // Pour mémoriser les lignes à supprimer

const formatPrix = (prix) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(prix);

const libelleTarif = (nom) => (nom === 'reduit' ? 'Tarif réduit' : 'Tarif normal');

function afficherMessage(texte) {
    message.textContent = texte; 
    message.hidden = false;
}

function redirigerVersConnexion() {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = 'connexion.html';
}

// Fonction pour afficher une ligne du panier, avec le nom de la soirée, la quantité, le tarif et le prix total de la ligne
function creerLigne(ligne) {
    const item = document.createElement('div');
    item.className = 'billet-item';

    
    const description = document.createElement('span');
    description.append(`${ligne.quantite}x `);

    const lien = document.createElement('a');
    lien.href = `soiree.html?id=${encodeURIComponent(ligne.idSoiree)}`;
    lien.textContent = ligne.nomSoiree;
    description.append(lien, ` (${libelleTarif(ligne.tarif)})`);

    
    const prixActionContainer = document.createElement('div');
    prixActionContainer.className = 'prix-action-container';

    const prix = document.createElement('span');
    prix.className = 'prix';
    prix.textContent = formatPrix(ligne.sousTotal);

    
    const btnSupprimer = document.createElement('button');
    btnSupprimer.className = 'btn-supprimer-ligne';
    btnSupprimer.innerHTML = '✖'; 
    btnSupprimer.title = 'Supprimer cet article';
    
    btnSupprimer.addEventListener('click', async () => {
        const token = localStorage.getItem(TOKEN_KEY);
        if (!token) return redirigerVersConnexion();

        btnSupprimer.disabled = true; 

        try {
            const idLigne = ligne.idLigne || ligne.id; 
            const response = await fetch(`${API}/panier/lignes/${idLigne}`, {
                method: 'DELETE',
                headers: { Authorization: `Bearer ${token}` }
            });

            if (response.status === 401) {
                return redirigerVersConnexion();
            }

            if (!response.ok) {
                throw new Error(`Erreur ${response.status}`);
            }

            
            chargerPanier();
        } catch (e) {
            console.error(e);
            afficherMessage('Impossible de supprimer cet article.');
            btnSupprimer.disabled = false;
        }
    });

    
    prixActionContainer.append(prix, btnSupprimer);
    item.append(description, prixActionContainer);
    
    return item;
}

function afficherPanier(panier) {
    listeBillets.replaceChildren();
    lignesActuelles = panier.lignes; // On sauvegarde les lignes ici pour pouvoir les supprimer

    if (panier.lignes.length === 0) {
        const vide = document.createElement('p');
        vide.textContent = 'Votre panier est vide.';
        listeBillets.append(vide);
        btnValider.disabled = true;
        if (btnVider) btnVider.disabled = true; // Désactive le bouton vider
    } else {
        panier.lignes.forEach(ligne => listeBillets.append(creerLigne(ligne)));
        btnValider.disabled = false;
        if (btnVider) btnVider.disabled = false; // Active le bouton vider
    }

    totalPrix.textContent = formatPrix(panier.montantTotal);
}

async function chargerPanier() {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
        redirigerVersConnexion();
        return;
    }

    try {
        const response = await fetch(`${API}/panier`, {
            headers: { Authorization: `Bearer ${token}` },
        });

        // Erreur de connexion : le token est invalide ou expiré
        if (response.status === 401) { 
            redirigerVersConnexion();
            return;
        }
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        afficherPanier(data.panier);
    } catch (e) {
        console.error(e);
        afficherMessage('Impossible de charger votre panier, réessayez plus tard.');
    }
}

// Validation : le panier devient une commande, puis on passe au paiement
btnValider.addEventListener('click', async () => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
        redirigerVersConnexion();
        return;
    }

    message.hidden = true;
    btnValider.disabled = true; // évite le double clic
    if (btnVider) btnVider.disabled = true; 

    try {
        const response = await fetch(`${API}/panier/validation`, {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}` },
        });

        if (response.status === 401) {
            redirigerVersConnexion();
            return;
        }

        let data = {};
        try { data = await response.json(); } catch { /* corps vide */ }

        if (!response.ok) {
            afficherMessage(data.error || data.message || `Erreur ${response.status}`);
            btnValider.disabled = false;
            if (btnVider) btnVider.disabled = false;
            return;
        }

        const idCommande = data.commande.idCommande;
        window.location.href = `paiement.html?id=${encodeURIComponent(idCommande)}`;
    } catch (e) {
        console.error(e);
        afficherMessage('Impossible de joindre le serveur API.');
        btnValider.disabled = false;
        if (btnVider) btnVider.disabled = false;
    }
});

// Événement pour vider le panier
if (btnVider) {
    btnVider.addEventListener('click', async () => {
        // Confirmation utilisateur
        if (!confirm('Êtes-vous sûr de vouloir vider l\'intégralité de votre panier ?')) {
            return;
        }

        const token = localStorage.getItem(TOKEN_KEY);
        if (!token) {
            return redirigerVersConnexion();
        }

        message.hidden = true;
        btnVider.disabled = true;
        btnValider.disabled = true;

        try {
            // Suppression de chaque ligne une par une
            for (const ligne of lignesActuelles) {
                const idLigne = ligne.idLigne || ligne.id; // Adapte selon le nom de l'ID retourné par ton API
                
                await fetch(`${API}/panier/lignes/${idLigne}`, {
                    method: 'DELETE',
                    headers: { Authorization: `Bearer ${token}` }
                });
            }
            
            // Recharge le panier une fois toutes les suppressions terminées
            await chargerPanier();
            
        } catch (e) {
            console.error("Erreur lors du vidage :", e);
            afficherMessage('Une erreur est survenue en vidant le panier.');
            btnVider.disabled = false;
            btnValider.disabled = false;
        }
    });
}

chargerPanier();