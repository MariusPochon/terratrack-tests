// On initialise la carte dans la div "map"
// [46.8, 7.15] = coordonnées de Fribourg | 15 = niveau de zoom
const map = L.map('map').setView([46.8, 7.15], 15);

// On ajoute les tuiles OpenStreetMap
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

// --- GÉOLOCALISATION ---
function geolocalisation() {
    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 14);
            L.marker([lat, lng]).addTo(map).bindPopup("Vous êtes ici !").openPopup();
        },
        function () {
            console.log("Géolocalisation refusée ou indisponible");
        }
    );
}
geolocalisation();

// --- MARQUEURS COLORÉS ---
function createMarker(color) {
    return L.divIcon({
        className: '',
        html: `
            <div style="color: ${color}">
                <svg viewBox="0 0 24 24" width="32" height="32">
                    <path fill="currentColor"
                        d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7z"/>
                </svg>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
    });
}

// --- DESSIN DE ZONES ET MARKERS ---
const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

const drawControl = new L.Control.Draw({
    draw: {
        polygon: true,
        marker: {icon: createMarker('#e74c3c')},
        circle: false,
        rectangle: false,
        polyline: false,
        circlemarker: false
    },
    edit: {
        featureGroup: drawnItems
    }
});
map.addControl(drawControl);

// --- CATÉGORIES ---
// On charge les catégories depuis la DB et on remplit le <select> du modal
fetch('categories.php')
    .then(r => r.json())
    .then(categories => {
        const select = document.getElementById('fk_categorie');
        categories.forEach(c => {
            const option = document.createElement('option');
            option.value = c.pk_categorie;
            option.textContent = c.nom;
            select.appendChild(option);
        });
    })
    .catch(err => console.error("Erreur chargement catégories :", err));

// --- MODAL : références aux éléments HTML ---
const overlay  = document.getElementById('modal-overlay');
const form     = document.getElementById('form-releve');
const statusEl = document.getElementById('modal-status');

// Layer actuellement en cours d'enregistrement
// Permet de le supprimer si l'utilisateur annule
let layerEnCours = null;

// Ouvre le modal et pré-remplit les champs cachés
function ouvrirModal(type, lat, lng, points) {
    // Réinitialise le formulaire
    form.reset();
    statusEl.textContent = '';

    // Date et heure actuelles
    const now = new Date();
    form.date_enregistrement.value = now.toISOString().slice(0, 10);
    form.heure_enregistrement.value = now.toTimeString().slice(0, 8);

    // Coordonnées selon le type de dessin
    form.type.value = type; // 'point' ou 'polygone'

    if (type === 'point') {
        form.latitude.value  = lat;
        form.longitude.value = lng;
    } else {
        // Pour un polygone on sérialise tous les points en JSON
        form.points_json.value = JSON.stringify(points);
    }

    // Affiche le modal
    overlay.classList.add('actif');
}

// Ferme le modal (supprime le layer si on annule)
function fermerModal(annuler = false) {
    if (annuler && layerEnCours) {
        drawnItems.removeLayer(layerEnCours);
    }
    layerEnCours = null;
    overlay.classList.remove('actif');
}

// Bouton Annuler → supprime le layer
document.getElementById('btn-annuler').addEventListener('click', function () {
    fermerModal(true);
});

// Clic sur le fond gris → supprime le layer
overlay.addEventListener('click', function (e) {
    if (e.target === overlay) fermerModal(true);
});

// --- SOUMISSION DU FORMULAIRE ---
form.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    // Si c'est un polygone, on décode le JSON et on ajoute les points un par un
    // (PHP ne sait pas lire du JSON directement, il faut les champs points[i][lat])
    if (form.type.value === 'polygone') {
        const points = JSON.parse(form.points_json.value);
        points.forEach((p, i) => {
            formData.append(`points[${i}][latitude]`,  p.lat);
            formData.append(`points[${i}][longitude]`, p.lng);
        });
        formData.delete('points_json'); // on n'en a plus besoin
    }

    statusEl.textContent = "Envoi en cours...";

    fetch('save.php', {method: 'POST', body: formData})
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = "Enregistré ✅ (id " + data.id + ")";

                // On bind une popup avec les infos sur le layer
                // pour pouvoir les revoir en cliquant dessus
                const nom  = formData.get('nom');
                const desc = formData.get('description') || '';
                layerEnCours.bindPopup(
                    `<strong>${nom}</strong><br>${desc}`
                );

                // Ferme le modal après 1 seconde (sans supprimer le layer)
                setTimeout(() => fermerModal(false), 1000);
            } else {
                statusEl.textContent = "Erreur : " + (data.error || 'inconnue');
            }
        })
        .catch(err => {
            statusEl.textContent = "Erreur réseau";
            console.error(err);
        });
});

// --- ÉVÉNEMENT DÉCLENCHÉ QUAND ON TERMINE DE DESSINER ---
map.on(L.Draw.Event.CREATED, function (e) {
    const layer = e.layer;
    const type  = e.layerType; // "marker" ou "polygon"

    // Style pour les polygones
    if (type === 'polygon') {
        layer.setStyle({
            color: '#2ecc71',
            fillColor: '#2ecc71',
            fillOpacity: 0.3
        });
    }

    drawnItems.addLayer(layer);

    // On mémorise le layer pour pouvoir l'annuler si besoin
    layerEnCours = layer;

    // On ouvre le modal avec les bonnes coordonnées
    if (type === 'marker') {
        const coords = layer.getLatLng();
        console.log("Marqueur posé :", coords);
        ouvrirModal('point', coords.lat, coords.lng, null);

    } else if (type === 'polygon') {
        const points = layer.getLatLngs()[0];
        console.log("Polygone dessiné :", points);
        ouvrirModal('polygone', null, null, points);
    }
});