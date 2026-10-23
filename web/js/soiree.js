// Page soiree.html : détail d'une soirée, chargé depuis l'API et affiché avec un template Handlebars

// ── helpers Handlebars : mise en forme des données renvoyées par l'API ─

// "2027-07-08" → "jeudi 8 juillet 2027" ; T12:00 évite un décalage de jour dû au fuseau horaire
Handlebars.registerHelper('dateLongue', date => new Date(`${date}T12:00:00`).toLocaleDateString(
    'fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }
));

// 35 → "35,00 €"
Handlebars.registerHelper('prix', prix =>
    new Intl.NumberFormat(
        'fr-FR',
        {
            style: 'currency',
            currency: 'EUR'
        }
    ).format(prix)
);

// "reduit" → "Tarif réduit", "normal" → "Tarif normal"
Handlebars.registerHelper('nomTarif', nom => (nom === 'reduit' ? 'Tarif réduit' : 'Tarif normal'));

// ['John Doe', 'The Roes'] → "John Doe, The Roes"
Handlebars.registerHelper('join', liste => liste.join(', '));

// ── chargement de la soirée ─

const container = document.querySelector('.soiree-container');
// le template est compilé une seule fois, puis appliqué aux données de la soirée
const template = Handlebars.compile(document.getElementById('tpl-soiree').innerHTML);

// message d'erreur affiché dans la page ; textContent insère du texte, jamais du HTML
function afficherErreur(message) {
    container.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'erreur';
    p.textContent = message;
    container.appendChild(p);
}

async function chargerSoiree() {
    // l'identifiant de la soirée arrive dans l'URL : soiree.html?id=…
    const id = new URLSearchParams(location.search).get('id');
    if(!id) {
        afficherErreur('Aucune soirée sélectionnée.');
        return;
    }

    try {
        // encodeURIComponent : l'id vient de l'URL, il ne doit pas pouvoir modifier l'adresse appelée
        const response = await fetch(`${API}/soiree/${encodeURIComponent(id)}`)

        // 404 : soirée inconnue ; 400 : identifiant qui n'est pas un UUID
        if(response.status === 404 || response.status === 400) {
            afficherErreur("Cette soirée n'existe pas.");
            return;
        }
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        // la soirée est dans data.soiree ; {{…}} échappe le HTML, ce qui protège des injections (XSS)
        const data = await response.json();
        document.title = `${data.soiree.nom} - NRV`;
        container.innerHTML = template(data.soiree);
        brancherAjoutPanier(id);
    }
    catch (e) {
        // API arrêtée, réseau coupé ou erreur 500
        console.error(e);
        afficherErreur('Impossible de charger la soirée, réessayez plus tard.');
    }
}

chargerSoiree();


document.addEventListener('DOMContentLoaded', () => {
    // Gestion du menu burger
    const burgerBtn = document.getElementById('burger-btn');
    const mainNav = document.getElementById('main-nav');

    if (burgerBtn && mainNav) {
        burgerBtn.addEventListener('click', () => {
            // Ajoute ou retire la classe "active" sur le bouton et le menu
            burgerBtn.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }
});
function brancherAjoutPanier(idSoiree) {
    const form = document.getElementById('form-ajout');
    if (!form) return; // soirée sans tarif

    const messageAjout = document.getElementById('ajout-message');
    const valeur = document.getElementById('qte-valeur');
    const moins = document.getElementById('qte-moins');
    const plus = document.getElementById('qte-plus');
    const bouton = document.getElementById('btn-ajout');
    let quantite = 1;

    const tarifChoisi = () => form.querySelector('input[name="ajout-tarif"]:checked');

    // Fonction pour mettre à jour l'affichage de la quantité
    function majAffichage() {
        valeur.textContent = quantite;
        moins.disabled = quantite <= 1;
        bouton.textContent = `🛒 Ajouter ${quantite} billet${quantite > 1 ? 's' : ''}`;
    }

    function afficherAjout(texte, erreur) {
        messageAjout.textContent = texte; 
        messageAjout.className = erreur ? 'message message-error' : 'message message-success';
        messageAjout.hidden = false;
    }

    moins.addEventListener('click', () => {
        if (quantite > 1) {
            quantite--;
            majAffichage();
        }
    });

    plus.addEventListener('click', () => {
        quantite++;
        majAffichage();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // il faut être connecté pour ajouter au panier
        const token = localStorage.getItem(TOKEN_KEY);
        if (!token) {
            window.location.href = 'connexion.html';
            return;
        }

        messageAjout.hidden = true;
        bouton.disabled = true; // évite le double clic pendant la requête

        try {
            const response = await fetch(`${API}/panier/lignes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${token}`,
                },
                body: JSON.stringify({ idSoiree, tarif: tarifChoisi().value, quantite }),
            });

            if (response.status === 401) { // token invalide ou expiré
                localStorage.removeItem(TOKEN_KEY);
                window.location.href = 'connexion.html';
                return;
            }

            let data = {};
            try { data = await response.json(); } catch {}

            if (response.ok) {
                const nb = data.panier.nbBillets;
                afficherAjout(`Ajouté. Votre panier contient ${nb} billet${nb > 1 ? 's' : ''}.`, false);
                quantite = 1;
            } else {
                afficherAjout(data.error || data.message || `Erreur ${response.status}`, true);
            }
        } catch (err) {
            console.error(err);
            afficherAjout('Impossible de joindre le serveur API.', true);
        } finally {
            bouton.disabled = false;
            majAffichage();
        }
    });

    majAffichage();
}