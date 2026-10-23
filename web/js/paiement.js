// Page paiement.html : récapitulatif (GET /commandes/{id}) puis paiement (POST /commandes/{id}/paiement)

const idCommande = new URLSearchParams(location.search).get('id');

const listeBillets = document.getElementById('liste-billets');
const totalPrix = document.getElementById('total-prix');
const message = document.getElementById('message-paiement');
const form = document.getElementById('form-paiement');
const btnPayer = document.getElementById('btn-payer');

const formatPrix = (prix) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(prix);

const libelleTarif = (nom) => (nom === 'reduit' ? 'Tarif réduit' : 'Tarif normal');

function afficherMessage(texte, erreur = true) {
    message.textContent = texte; 
    message.className = erreur ? 'message message-error' : 'message message-success';
    message.hidden = false;
}

// redirige vers la page de connexion si l'utilisateur n'est pas connecté
function redirigerVersConnexion() {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = 'connexion.html';
}

function afficherCommande(commande) {
    listeBillets.replaceChildren();

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

    totalPrix.textContent = formatPrix(commande.montantTotal);
    btnPayer.textContent = `Payer ${formatPrix(commande.montantTotal)}`;
    btnPayer.disabled = false;
}

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

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
        redirigerVersConnexion();
        return;
    }

    message.hidden = true;
    btnPayer.disabled = true; // évite le double paiement

    try {
        const response = await fetch(`${API}/commandes/${encodeURIComponent(idCommande)}/paiement`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
            },
            // ⚠ clés supposées : vérifie PayerCommandeAction / son validator
            body: JSON.stringify({
                numero: document.getElementById('cb-numero').value.trim(),
                dateExpiration: document.getElementById('cb-date').value.trim(),
                cvc: document.getElementById('cb-cvc').value.trim(),
            }),
        });

        if (response.status === 401) {
            redirigerVersConnexion();
            return;
        }

        let data = {};
        try { data = await response.json(); } catch { /* corps vide */ }

        if (!response.ok) {
            afficherMessage(data.error || data.message || `Erreur ${response.status}`);
            btnPayer.disabled = false;
            return;
        }

        form.reset();
        afficherMessage('Paiement accepté, merci !', false);
    } catch (err) {
        console.error(err);
        afficherMessage('Impossible de joindre le serveur API.');
        btnPayer.disabled = false;
    }
});

chargerCommande();