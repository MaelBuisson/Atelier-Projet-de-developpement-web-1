
const API = 'http://localhost:7080';

document.addEventListener('DOMContentLoaded', () => {

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
                if (selectedDate && selectedStyle.value !== '') {
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

                const imageUrl = spectacle.image ? spectacle.image : 'https://picsum.photos/seed/nrv-default/400/300'

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

        chargerSpectacles();

        if (selectedStyle && selectedDate) {
            selectedStyle.addEventListener('change', chargerSpectacles);
            selectedDate.addEventListener('change', chargerSpectacles);
        }
    }
});