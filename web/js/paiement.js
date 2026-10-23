// ==========================================
// 1. INITIALISATION & VARIABLES GLOBALES
// ==========================================

// Récupération de l'ID de la commande transmis dans l'URL (ex: paiement.html?id=123)
const idCommande = new URLSearchParams(location.search).get('id');

// Ciblage des éléments du DOM
const listeBillets = document.getElementById('liste-billets');
const totalPrix = document.getElementById('total-prix');
const message = document.getElementById('message-paiement');
const form = document.getElementById('form-paiement');
const btnPayer = document.getElementById('btn-payer');

// Fonctions utilitaires pour le formatage visuel
const formatPrix = (prix) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(prix);
const libelleTarif = (nom) => (nom === 'reduit' ? 'Tarif réduit' : 'Tarif normal');

// Gère l'affichage des messages d'erreur ou de succès
function afficherMessage(texte, erreur = true) {
    message.textContent = texte; 
    message.className = erreur ? 'message message-error' : 'message message-success';
    message.hidden = false;
}

// Déconnecte l'utilisateur et le renvoie vers la page de connexion si son token a expiré
function redirigerVersConnexion() {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = 'connexion.html';
}

// ==========================================
// 2. AFFICHAGE DU RÉCAPITULATIF DE COMMANDE
// ==========================================

// Génère le code HTML pour afficher chaque ligne de la commande
function afficherCommande(commande) {
    listeBillets.replaceChildren(); // Vide la liste de chargement

    commande.lignes.forEach(ligne => {
        const item = document.createElement('div');
        item.className = 'billet-item';

        const description = document.createElement('span');
        description.textContent = `${ligne.quantite}x ${ligne.nomSoiree} (${libelleTarif(ligne.tarif)})`;

        const prix = document.createElement('span');
        prix.textContent = formatPrix(ligne.sousTotal);

        item.append(description, prix);
        listeBillets.append(item);
    });

    // Mise à jour du montant total et du bouton de paiement
    totalPrix.textContent = formatPrix(commande.montantTotal);
    btnPayer.textContent = `Payer ${formatPrix(commande.montantTotal)}`;
    btnPayer.disabled = false; // Active le bouton maintenant que la commande est chargée
}

// Requête GET vers l'API pour récupérer les détails de la commande
async function chargerCommande() {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
        redirigerVersConnexion();
        return;
    }
    if (!idCommande) {
        afficherMessage('Aucune commande à payer.');
        return;
    }

    try {
        const response = await fetch(`${API}/commandes/${encodeURIComponent(idCommande)}`, {
            headers: { Authorization: `Bearer ${token}` },
        });

        // Gestion des erreurs d'authentification ou de ressource introuvable
        if (response.status === 401) {
            redirigerVersConnexion();
            return;
        }
        if (response.status === 404 || response.status === 400) {
            afficherMessage("Cette commande n'existe pas.");
            return;
        }
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        afficherCommande(data.commande); 
    } catch (e) {
        console.error(e);
        afficherMessage('Impossible de charger la commande, réessayez plus tard.');
    }
}

// ==========================================
// 3. TRAITEMENT DU FORMULAIRE DE PAIEMENT
// ==========================================

form.addEventListener('submit', async (e) => {
    e.preventDefault(); // Empêche le rechargement de la page

    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
        redirigerVersConnexion();
        return;
    }

    // Récupération des valeurs saisies
    const numero = document.getElementById('cb-numero').value.trim();
    const dateExpiration = document.getElementById('cb-date').value.trim();
    const cvc = document.getElementById('cb-cvc').value.trim();

    // Validations (Regex) côté Front-end avant d'envoyer au serveur
    const numeroNettoye = numero.replace(/\s/g, ''); // Retire les espaces éventuels
    if (!/^\d{16}$/.test(numeroNettoye)) {
        afficherMessage("Le numéro de carte doit contenir 16 chiffres.");
        return;
    }
    
    if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(dateExpiration)) {
        afficherMessage("La date d'expiration doit être au format MM/AA (ex: 12/25).");
        return;
    }
    
    if (!/^\d{3}$/.test(cvc)) {
        afficherMessage("Le cryptogramme (CVC) doit contenir 3 chiffres.");
        return;
    }

    message.hidden = true;
    btnPayer.disabled = true; // Empêche le double-clic pendant la transaction

    try {
        // Envoi de la requête POST pour valider le paiement
        const response = await fetch(`${API}/commandes/${encodeURIComponent(idCommande)}/paiement`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
            },
            body: JSON.stringify({
                numero: numeroNettoye, 
                dateExpiration: dateExpiration,
                cvc: cvc,
            }),
        });

        if (response.status === 401) {
            redirigerVersConnexion();
            return;
        }

        let data = {};
        try { data = await response.json(); } catch { /* corps vide si pas de JSON */ }

        // Si le paiement échoue (carte refusée, provision insuffisante, etc.)
        if (!response.ok) {
            afficherMessage(data.error || data.message || `Erreur ${response.status}`);
            btnPayer.disabled = false; // Réactive le bouton pour réessayer
            return;
        }

        // Succès du paiement : redirection vers la page de confirmation avec l'ID de la commande
        window.location.href = `confirmation.html?id=${encodeURIComponent(idCommande)}`;
        
    } catch (err) {
        console.error(err);
        afficherMessage('Impossible de joindre le serveur API.');
        btnPayer.disabled = false;
    }
});

// Lancement du chargement de la commande au démarrage du script
chargerCommande();