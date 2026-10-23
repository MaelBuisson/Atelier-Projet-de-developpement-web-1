// ==========================================
// 1. INITIALISATION & VARIABLES GLOBALES
// ==========================================

const listeBillets = document.getElementById('liste-billets');
const totalPrix = document.getElementById('total-prix');
const message = document.getElementById('message-panier');
const btnValider = document.getElementById('btn-valider');
const btnVider = document.getElementById('btn-vider'); 

let lignesActuelles = []; // Stocke les lignes du panier pour pouvoir les supprimer facilement plus tard

// Utilitaires de formatage
const formatPrix = (prix) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(prix);
const libelleTarif = (nom) => (nom === 'reduit' ? 'Tarif réduit' : 'Tarif normal');

// Gère l'affichage des messages d'erreur ou d'information
function afficherMessage(texte) {
    message.textContent = texte; 
    message.hidden = false;
}

// Déconnecte l'utilisateur en cas de token invalide/expiré
function redirigerVersConnexion() {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = 'connexion.html';
}

// ==========================================
// 2. AFFICHAGE ET GESTION DU PANIER
// ==========================================

// Génère dynamiquement le HTML pour une seule ligne du panier (article)
function creerLigne(ligne) {
    const item = document.createElement('div');
    item.className = 'billet-item';
    
    // Description : Quantité x Nom de la soirée (Lien) + Tarif
    const description = document.createElement('span');
    description.append(`${ligne.quantite}x `);

    const lien = document.createElement('a');
    lien.href = `soiree.html?id=${encodeURIComponent(ligne.idSoiree)}`;
    lien.textContent = ligne.nomSoiree;
    description.append(lien, ` (${libelleTarif(ligne.tarif)})`);
    
    // Conteneur pour le prix et le bouton de suppression
    const prixActionContainer = document.createElement('div');
    prixActionContainer.className = 'prix-action-container';

    const prix = document.createElement('span');
    prix.className = 'prix';
    prix.textContent = formatPrix(ligne.sousTotal);
    
    // Bouton de suppression individuelle (la croix)
    const btnSupprimer = document.createElement('button');
    btnSupprimer.className = 'btn-supprimer-ligne';
    btnSupprimer.innerHTML = '✖'; 
    btnSupprimer.title = 'Supprimer cet article';
    
    // Événement : Suppression d'une ligne spécifique via l'API
    btnSupprimer.addEventListener('click', async () => {
        const token = localStorage.getItem(TOKEN_KEY);
        if (!token) return redirigerVersConnexion();

        btnSupprimer.disabled = true; // Empêche le double-clic

        try {
            const idLigne = ligne.idLigne || ligne.id; 
            const response = await fetch(`${API}/panier/lignes/${idLigne}`, {
                method: 'DELETE',
                headers: { Authorization: `Bearer ${token}` }
            });

            if (response.status === 401) return redirigerVersConnexion();
            if (!response.ok) throw new Error(`Erreur ${response.status}`);
            
            // Si la suppression a réussi sur le serveur, on recharge l'affichage du panier
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

// Met à jour l'interface globale du panier (liste complète + total)
function afficherPanier(panier) {
    listeBillets.replaceChildren(); // Vide la zone de chargement
    lignesActuelles = panier.lignes; // Mise à jour de la variable globale

    // Gestion de l'affichage selon si le panier est vide ou non
    if (panier.lignes.length === 0) {
        const vide = document.createElement('p');
        vide.textContent = 'Votre panier est vide.';
        listeBillets.append(vide);
        btnValider.disabled = true;
        if (btnVider) btnVider.disabled = true;
    } else {
        panier.lignes.forEach(ligne => listeBillets.append(creerLigne(ligne)));
        btnValider.disabled = false;
        if (btnVider) btnVider.disabled = false; 
    }

    totalPrix.textContent = formatPrix(panier.montantTotal);
}

// Appel GET à l'API pour récupérer les données du panier de l'utilisateur
async function chargerPanier() {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) return redirigerVersConnexion();

    try {
        const response = await fetch(`${API}/panier`, {
            headers: { Authorization: `Bearer ${token}` },
        });

        if (response.status === 401) return redirigerVersConnexion();
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        afficherPanier(data.panier);
    } catch (e) {
        console.error(e);
        afficherMessage('Impossible de charger votre panier, réessayez plus tard.');
    }
}

// ==========================================
// 3. ACTIONS GLOBALES (VALIDER & VIDER)
// ==========================================

// Validation : Transforme le panier en Commande via l'API, puis redirige vers le paiement
btnValider.addEventListener('click', async () => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) return redirigerVersConnexion();

    message.hidden = true;
    btnValider.disabled = true; 
    if (btnVider) btnVider.disabled = true; 

    try {
        const response = await fetch(`${API}/panier/validation`, {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}` },
        });

        if (response.status === 401) return redirigerVersConnexion();

        let data = {};
        try { data = await response.json(); } catch { /* Ignore si pas de JSON */ }

        if (!response.ok) {
            afficherMessage(data.error || data.message || `Erreur ${response.status}`);
            btnValider.disabled = false;
            if (btnVider) btnVider.disabled = false;
            return;
        }

        // Récupération souple de l'ID de la commande renvoyé par l'API
        const idCommande = data.idCommande || data.id || (data.commande && (data.commande.idCommande || data.commande.id));
                        
        if (!idCommande) {
            afficherMessage("Erreur interne : l'identifiant de la commande est manquant.");
            btnValider.disabled = false;
            return;
        }

        // Redirection vers la page de paiement avec l'ID en paramètre URL
        window.location.href = `paiement.html?id=${encodeURIComponent(idCommande)}`;
    } catch (e) {
        console.error(e);
        afficherMessage('Impossible de joindre le serveur API.');
        btnValider.disabled = false;
        if (btnVider) btnVider.disabled = false;
    }
});

// Vidage complet : Boucle sur toutes les lignes actuelles pour les supprimer une par une
if (btnVider) {
    btnVider.addEventListener('click', async () => {
        // Sécurité : Demande une confirmation à l'utilisateur avant de tout effacer
        if (!confirm('Êtes-vous sûr de vouloir vider l\'intégralité de votre panier ?')) return;

        const token = localStorage.getItem(TOKEN_KEY);
        if (!token) return redirigerVersConnexion();

        message.hidden = true;
        btnVider.disabled = true;
        btnValider.disabled = true;

        try {
            // Utilisation d'une boucle for...of pour respecter l'asynchrone (attendre chaque suppression)
            for (const ligne of lignesActuelles) {
                const idLigne = ligne.idLigne || ligne.id; 
                await fetch(`${API}/panier/lignes/${idLigne}`, {
                    method: 'DELETE',
                    headers: { Authorization: `Bearer ${token}` }
                });
            }
            
            // Une fois toutes les requêtes terminées, on met à jour l'affichage
            await chargerPanier();
            
        } catch (e) {
            console.error("Erreur lors du vidage :", e);
            afficherMessage('Une erreur est survenue en vidant le panier.');
            btnVider.disabled = false;
            btnValider.disabled = false;
        }
    });
}

// Chargement initial du panier au démarrage du script
chargerPanier();