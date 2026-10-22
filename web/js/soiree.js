// Page soiree.html : détail d'une soirée, chargé depuis l'API et affiché avec un template Handlebars

// adresse de l'API : à changer uniquement ici pour docketu
const API = 'http://localhost:7080';

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