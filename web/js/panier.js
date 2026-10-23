// Page panier.html : affiche le panier de l'utilisateur connecté (GET /panier)

const listeBillets = document.getElementById('liste-billets');
const totalPrix = document.getElementById('total-prix');
const message = document.getElementById('message-panier');
const btnPayer = document.getElementById('btn-payer');

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
        btnPayer.disabled = true;
        btnPayer.textContent = 'Payer';
    } else {
        panier.lignes.forEach(ligne => listeBillets.append(creerLigne(ligne)));
        btnPayer.disabled = false;
        btnPayer.textContent = `Payer ${formatPrix(panier.montantTotal)}`;
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

chargerPanier();