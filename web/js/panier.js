// Page panier.html : affiche le panier de l'utilisateur connecté (GET /panier)

const listeBillets = document.getElementById('liste-billets');
const totalPrix = document.getElementById('total-prix');
const message = document.getElementById('message-panier');
const btnValider = document.getElementById('btn-valider');


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

    const prix = document.createElement('span');
    prix.textContent = formatPrix(ligne.sousTotal);

    item.append(description, prix);
    return item;
}

function afficherPanier(panier) {
    listeBillets.replaceChildren();

    if (panier.lignes.length === 0) {
        const vide = document.createElement('p');
        vide.textContent = 'Votre panier est vide.';
        listeBillets.append(vide);
        btnValider.disabled = true;
    } else {
        panier.lignes.forEach(ligne => listeBillets.append(creerLigne(ligne)));
        btnValider.disabled = false;
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
            return;
        }

        const idCommande = data.commande.idCommande;
        window.location.href = `paiement.html?id=${encodeURIComponent(idCommande)}`;
    } catch (e) {
        console.error(e);
        afficherMessage('Impossible de joindre le serveur API.');
        btnValider.disabled = false;
    }
});

chargerPanier();